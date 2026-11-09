/**
 * Direct ESC/POS Thermal Printing via Web Bluetooth API
 * Bypasses 3rd party apps (like RawBT) and eliminates watermarks entirely.
 * Supports: Purchases, Sales, Debt Payments, Delivery Orders
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

        bytes.push(0x1B, 0x45, 0x01);
        append('BUKTI PENERIMAAN / NOTA TIMBANG\n');
        bytes.push(0x1B, 0x45, 0x00);

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

    static buildSaleEscPos(data, charWidth = 32) {
        const encoder = new TextEncoder();
        let bytes = [];

        const append = (str) => {
            const encoded = encoder.encode(str);
            for (let i = 0; i < encoded.length; i++) bytes.push(encoded[i]);
        };

        // 1. Initialize
        bytes.push(0x1B, 0x40);

        // 2. Header
        bytes.push(0x1B, 0x61, 0x01); // Center
        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append((data.companyName || 'UD. BERKAH RONGSOK MAKMUR') + '\n');
        bytes.push(0x1B, 0x45, 0x00);

        if (data.companyAddress) append(data.companyAddress + '\n');
        if (data.companyPhone) append('Telp: ' + data.companyPhone + '\n');

        bytes.push(0x1B, 0x45, 0x01);
        append('FAKTUR PENJUALAN PARTAI\n');
        bytes.push(0x1B, 0x45, 0x00);

        // 3. Divider
        bytes.push(0x1B, 0x61, 0x00);
        append(this.hr('-', charWidth));

        // 4. Metadata
        append(this.padRow('No. Faktur', data.invoiceNumber, charWidth));
        append(this.padRow('Tanggal', data.transactionDate, charWidth));
        append(this.padRow('Pembeli', data.buyerName || data.customerName, charWidth));
        if (data.buyerPhone) append(this.padRow('No. Telp', data.buyerPhone, charWidth));
        append(this.padRow('Kasir', data.cashierName, charWidth));
        append(this.padRow('Metode', data.paymentMethod, charWidth));
        if (data.deliveryType) append(this.padRow('Pengiriman', data.deliveryType, charWidth));

        if (data.isBankPayment) {
            if (data.bankAccountNumber) append(this.padRow('No. Rek', data.bankAccountNumber, charWidth));
            if (data.bankAccountName) append(this.padRow('a/n', data.bankAccountName, charWidth));
            if (!data.bankAccountNumber && !data.bankAccountName && data.bankInfo) {
                append(this.padRow('Bank Info', data.bankInfo, charWidth));
            }
        }

        append(this.hr('-', charWidth));

        // 5. Items
        const items = data.items || [];
        items.forEach((it, idx) => {
            bytes.push(0x1B, 0x45, 0x01);
            append(`${idx + 1}. ${it.name}\n`);
            bytes.push(0x1B, 0x45, 0x00);

            const qtyPrice = `   ${it.weight} kg x ${it.price}`;
            append(this.padRow(qtyPrice, it.subtotal, charWidth));
        });

        append(this.hr('-', charWidth));

        // 6. Totals
        append(this.padRow('Total Berat', `${data.totalWeight} kg`, charWidth));
        append(this.padRow('Subtotal', data.subtotalAmount, charWidth));
        if (data.discountAmount) append(this.padRow('Diskon', '- ' + data.discountAmount, charWidth));

        bytes.push(0x1B, 0x45, 0x01);
        append(this.padRow('TOTAL AKHIR', data.finalAmount, charWidth));
        bytes.push(0x1B, 0x45, 0x00);

        append(this.hr('=', charWidth));

        // 7. Footer
        bytes.push(0x1B, 0x61, 0x01);
        append('*** BUKTI PENJUALAN RESMI ***\n');
        append((data.footer || 'Terima kasih atas kerja samanya.') + '\n');

        // 8. Feed & Cut
        append('\n\n\n\n');
        bytes.push(0x1D, 0x56, 0x41, 0x03);

        return new Uint8Array(bytes);
    }

    static buildDebtPaymentEscPos(data, charWidth = 32) {
        const encoder = new TextEncoder();
        let bytes = [];

        const append = (str) => {
            const encoded = encoder.encode(str);
            for (let i = 0; i < encoded.length; i++) bytes.push(encoded[i]);
        };

        // 1. Initialize
        bytes.push(0x1B, 0x40);

        // 2. Header
        bytes.push(0x1B, 0x61, 0x01);
        bytes.push(0x1B, 0x45, 0x01);
        append((data.companyName || 'UD. BERKAH RONGSOK MAKMUR') + '\n');
        bytes.push(0x1B, 0x45, 0x00);

        if (data.companyAddress) append(data.companyAddress + '\n');
        if (data.companyPhone) append('Telp: ' + data.companyPhone + '\n');

        bytes.push(0x1B, 0x45, 0x01);
        append('KUITANSI PEMBAYARAN KASBON\n');
        bytes.push(0x1B, 0x45, 0x00);

        // 3. Divider
        bytes.push(0x1B, 0x61, 0x00);
        append(this.hr('-', charWidth));

        // 4. Metadata
        append(this.padRow('No. Kuitansi', data.paymentNumber, charWidth));
        append(this.padRow('Tanggal', data.paymentDate, charWidth));
        append(this.padRow('Peminjam', data.borrowerName, charWidth));
        append(this.padRow('Kasir', data.cashierName, charWidth));
        append(this.padRow('Metode', data.paymentMethod, charWidth));
        if (data.debtNumber) append(this.padRow('No. Kasbon', data.debtNumber, charWidth));

        append(this.hr('-', charWidth));

        // 5. Amount breakdown
        if (data.remainingBefore) append(this.padRow('Hutang Awal', data.remainingBefore, charWidth));
        bytes.push(0x1B, 0x45, 0x01);
        append(this.padRow('JUMLAH BAYAR', data.amountPaid, charWidth));
        bytes.push(0x1B, 0x45, 0x00);
        append(this.padRow('Sisa Hutang', data.remainingAfter, charWidth));
        if (data.status) append(this.padRow('Status', data.status, charWidth));

        if (data.notes) {
            append(this.hr('-', charWidth));
            append(`Ket: ${data.notes}\n`);
        }

        append(this.hr('=', charWidth));

        // 6. Footer
        bytes.push(0x1B, 0x61, 0x01);
        append('*** BUKTI PEMBAYARAN SAH ***\n');
        append('Simpan kuitansi ini sebagai bukti pelunasan.\n');

        // 7. Feed & Cut
        append('\n\n\n\n');
        bytes.push(0x1D, 0x56, 0x41, 0x03);

        return new Uint8Array(bytes);
    }

    static buildDeliveryOrderEscPos(data, charWidth = 32) {
        const encoder = new TextEncoder();
        let bytes = [];

        const append = (str) => {
            const encoded = encoder.encode(str);
            for (let i = 0; i < encoded.length; i++) bytes.push(encoded[i]);
        };

        // 1. Initialize
        bytes.push(0x1B, 0x40);

        // 2. Header
        bytes.push(0x1B, 0x61, 0x01);
        bytes.push(0x1B, 0x45, 0x01);
        append((data.companyName || 'UD. BERKAH RONGSOK MAKMUR') + '\n');
        bytes.push(0x1B, 0x45, 0x00);

        if (data.companyAddress) append(data.companyAddress + '\n');
        if (data.companyPhone) append('Telp: ' + data.companyPhone + '\n');

        bytes.push(0x1B, 0x45, 0x01);
        append('SURAT JALAN PENGIRIMAN\n');
        bytes.push(0x1B, 0x45, 0x00);

        // 3. Divider
        bytes.push(0x1B, 0x61, 0x00);
        append(this.hr('-', charWidth));

        // 4. Metadata
        append(this.padRow('No. SJ', data.deliveryNumber, charWidth));
        append(this.padRow('Tanggal', data.deliveryDate, charWidth));
        append(this.padRow('Tujuan', data.recipientName, charWidth));
        append(this.padRow('Supir', data.driverName, charWidth));
        if (data.driverPhone) append(this.padRow('HP Supir', data.driverPhone, charWidth));
        append(this.padRow('No. Polisi', data.vehicleNumber, charWidth));

        append(this.hr('-', charWidth));

        // 5. Items
        const items = data.items || [];
        items.forEach((it, idx) => {
            bytes.push(0x1B, 0x45, 0x01);
            append(`${idx + 1}. ${it.name}\n`);
            bytes.push(0x1B, 0x45, 0x00);
            append(this.padRow(`   Berat:`, `${it.weight} kg`, charWidth));
        });

        append(this.hr('-', charWidth));
        bytes.push(0x1B, 0x45, 0x01);
        append(this.padRow('TOTAL BERAT', `${data.totalWeight} kg`, charWidth));
        bytes.push(0x1B, 0x45, 0x00);

        append(this.hr('=', charWidth));

        // 6. Signatures / Footer
        bytes.push(0x1B, 0x61, 0x01);
        append('Tanda Tangan Pengirim & Penerima\n');
        append('\n\n');
        append('Gudang         Supir         Penerima\n');

        // 7. Feed & Cut
        append('\n\n\n\n');
        bytes.push(0x1D, 0x56, 0x41, 0x03);

        return new Uint8Array(bytes);
    }

    static getBytes(data, charWidth = 32, type = 'purchase') {
        const docType = type || data.type || 'purchase';
        if (docType === 'sale') {
            return this.buildSaleEscPos(data, charWidth);
        } else if (docType === 'debt_payment') {
            return this.buildDebtPaymentEscPos(data, charWidth);
        } else if (docType === 'delivery_order') {
            return this.buildDeliveryOrderEscPos(data, charWidth);
        }
        return this.buildPurchaseEscPos(data, charWidth);
    }

    static async printBluetooth(data, charWidth = 32, onProgress = null, type = 'purchase') {
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

        const rawBytes = this.getBytes(data, charWidth, type);

        // Chunking in 100-byte packets to prevent GATT buffer overflow
        const chunkSize = 100;
        for (let i = 0; i < rawBytes.length; i += chunkSize) {
            const chunk = rawBytes.slice(i, i + chunkSize);
            if (writableChar.properties.writeWithoutResponse) {
                await writableChar.writeValueWithoutResponse(chunk);
            } else {
                await writableChar.writeValue(chunk);
            }
            await new Promise(r => setTimeout(r, 15));
        }

        if (onProgress) onProgress('Selesai dicetak!');
    }

    static printQuickPrinter(data, charWidth = 32, type = 'purchase') {
        const rawBytes = this.getBytes(data, charWidth, type);
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
