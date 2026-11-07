/**
 * Direct ESC/POS Thermal Printing via Web Bluetooth API
 * Bypasses 3rd party apps (like RawBT) and eliminates watermarks entirely.
 */
export class ThermalBluetooth {
    static isSupported() {
        return typeof navigator !== 'undefined' && !!navigator.bluetooth;
    }

    static padRow(left, right, width = 32) {
        left = String(left || '');
        right = String(right || '');
        const spaces = Math.max(1, width - left.length - right.length);
        return left + ' '.repeat(spaces) + right + '\n';
    }

    static hr(char = '-', width = 32) {
        return char.repeat(width) + '\n';
    }

    static buildPurchaseEscPos(data, charWidth = 32) {
        const encoder = new TextEncoder();
        let bytes = [];

        const append = (str) => {
            const encoded = encoder.encode(str);
            for (let i = 0; i < encoded.length; i++) bytes.push(encoded[i]);
        };

        // 1. Initialize
        bytes.push(0x1B, 0x40); // ESC @ (Reset)

        // 2. Header (Center align)
        bytes.push(0x1B, 0x61, 0x01); // Center
        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append((data.companyName || 'UD. BERKAH RONGSOK MAKMUR') + '\n');
        bytes.push(0x1B, 0x45, 0x00); // Bold off

        if (data.companyAddress) append(data.companyAddress + '\n');
        if (data.companyPhone) append('Telp: ' + data.companyPhone + '\n');

        // 3. Divider
        bytes.push(0x1B, 0x61, 0x00); // Left align
        append(this.hr('-', charWidth));

        // 4. Metadata
        append(this.padRow('No. Nota', data.invoiceNumber, charWidth));
        append(this.padRow('Tanggal', data.transactionDate, charWidth));
        append(this.padRow('Pemasok', data.supplierName, charWidth));
        if (data.supplierPhone) append(this.padRow('HP Pemasok', data.supplierPhone, charWidth));
        append(this.padRow('Kasir', data.cashierName, charWidth));
        append(this.padRow('Metode', data.paymentMethod, charWidth));

        if (data.isBankPayment) {
            if (data.bankAccountNumber) append(this.padRow('No. Rek', data.bankAccountNumber, charWidth));
            if (data.bankAccountName) append(this.padRow('a/n', data.bankAccountName, charWidth));
            if (!data.bankAccountNumber && !data.bankAccountName && data.bankInfo) {
                append(this.padRow('Bank Info', data.bankInfo, charWidth));
            }
        }

        append(this.hr('-', charWidth));

        // 5. Items breakdown
        const items = data.items || [];
        items.forEach((it, idx) => {
            bytes.push(0x1B, 0x45, 0x01); // Bold on
            append(`${idx + 1}. ${it.name}\n`);
            bytes.push(0x1B, 0x45, 0x00); // Bold off

            const qtyPrice = `   ${it.net} ${it.unit} x ${it.price}`;
            append(this.padRow(qtyPrice, it.subtotal, charWidth));
        });

        append(this.hr('-', charWidth));

        // 6. Totals
        append(this.padRow('Total Bersih', `${data.activeNet} kg`, charWidth));
        append(this.padRow('Subtotal', data.activeSubtotal, charWidth));
        if (data.discount) append(this.padRow('Potongan', '- ' + data.discount, charWidth));
        if (data.rounding) append(this.padRow('Pembulatan', data.rounding, charWidth));

        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append(this.padRow('TOTAL HARGA', data.activeFinal, charWidth));
        bytes.push(0x1B, 0x45, 0x00); // Bold off

        if (data.debtDeduction) {
            append(this.padRow('Potong Hutang', '- ' + data.debtDeduction, charWidth));
        }
        if (data.remainingDebt) {
            append(this.padRow('Sisa Hutang', data.remainingDebt, charWidth));
        }

        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append(this.padRow('UANG DITERIMA', data.activeNetCash, charWidth));
        bytes.push(0x1B, 0x45, 0x00); // Bold off

        append(this.hr('=', charWidth));

        // 7. Footer & Disclaimer
        bytes.push(0x1B, 0x61, 0x01); // Center
        append('*** ' + (data.disclaimer || 'TIDAK MENERIMA BARANG ILEGAL') + ' ***\n');
        append((data.footer || 'Barang dibeli tidak dapat ditarik kembali.') + '\n');
        append('*** TERIMA KASIH ***\n');

        // 8. Feed 4 lines & cut
        append('\n\n\n\n');
        bytes.push(0x1D, 0x56, 0x41, 0x03); // GS V A 3 (feed paper & cut)

        return new Uint8Array(bytes);
    }

    static async printBluetooth(data, charWidth = 32, onProgress = null) {
        if (!this.isSupported()) {
            throw new Error('Web Bluetooth tidak didukung pada browser ini. Pastikan menggunakan browser Google Chrome di Android atau PC dengan koneksi Bluetooth aktif.');
        }

        if (onProgress) onProgress('Mencari printer Bluetooth...');

        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: [
                '000018f0-0000-1000-8000-00805f9b34fb',
                'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
                '49535343-fe7d-4ae5-8fa9-9fafd205e455',
                '0000ffe0-0000-1000-8000-00805f9b34fb',
                '0000fff0-0000-1000-8000-00805f9b34fb',
                '00001800-0000-1000-8000-00805f9b34fb',
                '00001801-0000-1000-8000-00805f9b34fb',
                '0000af30-0000-1000-8000-00805f9b34fb'
            ]
        });

        if (onProgress) onProgress('Menghubungkan ke ' + (device.name || 'Printer') + '...');

        const server = await device.gatt.connect();
        const services = await server.getPrimaryServices();
        let writableChar = null;

        for (const service of services) {
            try {
                const chars = await service.getCharacteristics();
                for (const char of chars) {
                    if (char.properties.write || char.properties.writeWithoutResponse) {
                        writableChar = char;
                        break;
                    }
                }
            } catch (e) {
                // Ignore service read failures
            }
            if (writableChar) break;
        }

        if (!writableChar) {
            throw new Error('Tidak dapat menemukan port tulis (writable characteristic) pada printer ini.');
        }

        if (onProgress) onProgress('Mengirim data cetak...');

        const rawBytes = this.buildPurchaseEscPos(data, charWidth);

        // Chunking in 100-byte packets to prevent GATT buffer overflow
        const chunkSize = 100;
        for (let i = 0; i < rawBytes.length; i += chunkSize) {
            const chunk = rawBytes.slice(i, i + chunkSize);
            if (writableChar.properties.writeWithoutResponse) {
                await writableChar.writeValueWithoutResponse(chunk);
            } else {
                await writableChar.writeValue(chunk);
            }
            // Small delay to prevent printer buffer overruns
            await new Promise(r => setTimeout(r, 15));
        }

        if (onProgress) onProgress('Selesai dicetak!');
    }

    static printQuickPrinter(data, charWidth = 32) {
        const rawBytes = this.buildPurchaseEscPos(data, charWidth);
        let binary = '';
        const len = rawBytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(rawBytes[i]);
        }
        const b64 = window.btoa(binary);
        window.location.href = `intent:base64,${b64}#Intent;scheme=quickprinter;package=pe.diegoveloper.printerserverapp;end;`;
    }
}

if (typeof window !== 'undefined') {
    window.ThermalBluetooth = ThermalBluetooth;
}
