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

    static wrapText(text, width = 32) {
        if (!text) return [];
        const words = String(text).replace(/\r\n/g, '\n').split(/\s+/);
        const lines = [];
        let currentLine = '';

        for (const word of words) {
            if (!word) continue;
            if (!currentLine) {
                if (word.length <= width) {
                    currentLine = word;
                } else {
                    for (let i = 0; i < word.length; i += width) {
                        lines.push(word.slice(i, i + width));
                    }
                }
            } else if ((currentLine + ' ' + word).length <= width) {
                currentLine += ' ' + word;
            } else {
                lines.push(currentLine);
                if (word.length <= width) {
                    currentLine = word;
                } else {
                    for (let i = 0; i < word.length; i += width) {
                        lines.push(word.slice(i, i + width));
                    }
                    currentLine = '';
                }
            }
        }
        if (currentLine) {
            lines.push(currentLine);
        }
        return lines;
    }

    static padColonRow(label, value, colonPos = 13, width = 32) {
        label = String(label || '');
        value = String(value || '-');
        const paddedLabel = label.padEnd(colonPos, ' ');
        const prefix = paddedLabel + ': ';
        const maxValWidth = width - prefix.length;

        if (value.length <= maxValWidth) {
            return prefix + value + '\n';
        }

        const lines = this.wrapText(value, maxValWidth);
        let result = prefix + lines[0] + '\n';
        const indent = ' '.repeat(prefix.length);
        for (let i = 1; i < lines.length; i++) {
            result += indent + lines[i] + '\n';
        }
        return result;
    }

    static padTotalRow(label, value, colonPos = 13, width = 32) {
        label = String(label || '');
        value = String(value || '');
        const paddedLabel = label.padEnd(colonPos, ' ');
        const prefix = paddedLabel + ': ';
        const spaces = Math.max(1, width - prefix.length - value.length);
        return prefix + ' '.repeat(spaces) + value + '\n';
    }

    static async imageToEscPos(dataUrl, maxDots = 144) {
        if (!dataUrl) return null;
        try {
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.src = dataUrl;
            await new Promise((resolve, reject) => {
                if (img.complete && img.naturalWidth) return resolve();
                img.onload = () => resolve();
                img.onerror = () => reject(new Error('Gagal memuat logo'));
                setTimeout(() => reject(new Error('Timeout memuat logo')), 3000);
            });

            if (!img.naturalWidth || !img.naturalHeight) return null;

            // Target width in dots, must be multiple of 8 (144 dots ≈ 18mm pada 203 DPI)
            let targetWidth = Math.min(maxDots, img.naturalWidth || maxDots);
            targetWidth = Math.max(32, Math.round(targetWidth / 8) * 8);
            const scale = targetWidth / img.naturalWidth;
            const targetHeight = Math.max(8, Math.round(img.naturalHeight * scale));

            const canvas = document.createElement('canvas');
            canvas.width = targetWidth;
            canvas.height = targetHeight;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, targetWidth, targetHeight);
            ctx.drawImage(img, 0, 0, targetWidth, targetHeight);

            const imgData = ctx.getImageData(0, 0, targetWidth, targetHeight).data;
            const widthBytes = targetWidth / 8;

            const bytes = [
                0x1B, 0x61, 0x01, // Center align
                0x1D, 0x76, 0x30, 0x00, // GS v 0 0 (Raster bit image normal mode)
                widthBytes & 0xFF,
                (widthBytes >> 8) & 0xFF,
                targetHeight & 0xFF,
                (targetHeight >> 8) & 0xFF
            ];

            for (let y = 0; y < targetHeight; y++) {
                for (let x = 0; x < targetWidth; x += 8) {
                    let byte = 0;
                    for (let b = 0; b < 8; b++) {
                        const px = x + b;
                        const idx = (y * targetWidth + px) * 4;
                        const r = imgData[idx];
                        const g = imgData[idx + 1];
                        const b_col = imgData[idx + 2];
                        const a = imgData[idx + 3];
                        // High-contrast monochrome: pixel hitam jika tidak transparan dan gelap
                        const isBlack = (a > 50 && (r * 0.299 + g * 0.587 + b_col * 0.114) < 180);
                        if (isBlack) {
                            byte |= (1 << (7 - b));
                        }
                    }
                    bytes.push(byte);
                }
            }
            bytes.push(0x0A); // Line feed setelah logo
            return bytes;
        } catch (e) {
            console.warn('Gagal memproses logo ke ESC/POS:', e);
            return null;
        }
    }

    static async buildPurchaseEscPos(data, charWidth = 32) {
        const encoder = new TextEncoder();
        let bytes = [];

        const append = (str) => {
            const encoded = encoder.encode(str);
            for (let i = 0; i < encoded.length; i++) bytes.push(encoded[i]);
        };

        // 1. Initialize
        bytes.push(0x1B, 0x40); // ESC @ (Reset)

        // 2. Logo (Center align)
        if (data.logoBase64 || data.logoUrl) {
            const logoBytes = await this.imageToEscPos(data.logoBase64 || data.logoUrl, 144);
            if (logoBytes && logoBytes.length > 0) {
                for (let i = 0; i < logoBytes.length; i++) bytes.push(logoBytes[i]);
            }
        }

        // 3. Header (Center align)
        bytes.push(0x1B, 0x61, 0x01); // Center
        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append((data.companyName || 'UD. BERKAH RONGSOK MAKMUR').toUpperCase() + '\n');
        bytes.push(0x1B, 0x45, 0x00); // Bold off

        if (data.companyAddress) {
            this.wrapText(data.companyAddress, charWidth).forEach(l => append(l + '\n'));
        }
        if (data.companyPhone) {
            this.wrapText('Telp: ' + data.companyPhone, charWidth).forEach(l => append(l + '\n'));
        }

        // 3. Divider
        bytes.push(0x1B, 0x61, 0x00); // Left align
        append(this.hr('-', charWidth));

        // 4. Metadata (Aligned Colons matching preview)
        append(this.padColonRow('No. Nota', data.invoiceNumber, 13, charWidth));
        append(this.padColonRow('Tanggal', data.transactionDate, 13, charWidth));
        append(this.padColonRow('Pemasok', data.supplierName || '-', 13, charWidth));
        if (data.supplierPhone) {
            append(this.padColonRow('HP Pemasok', data.supplierPhone, 13, charWidth));
        }
        append(this.padColonRow('Kasir', data.cashierName || '-', 13, charWidth));
        append(this.padColonRow('Metode Bayar', data.paymentMethod || 'Tunai', 13, charWidth));

        if (data.isBankPayment) {
            if (data.bankAccountNumber) {
                append(this.padColonRow('No. Rekening', data.bankAccountNumber, 13, charWidth));
            }
            if (data.bankAccountName) {
                append(this.padColonRow('Nama Rekening', data.bankAccountName, 13, charWidth));
            }
            if (!data.bankAccountNumber && !data.bankAccountName && data.bankInfo) {
                append(this.padColonRow('Data Rekening', data.bankInfo, 13, charWidth));
            }
        }

        append(this.hr('-', charWidth));

        // 5. Items breakdown (Matching preview layout: Name -> Weight -> Price/Subtotal)
        const items = data.items || [];
        items.forEach((it, idx) => {
            bytes.push(0x1B, 0x45, 0x01); // Bold on
            let title = String(it.name || '').toUpperCase();
            if (it.subName) title += ` (${it.subName})`;
            const wrappedTitle = this.wrapText(title, charWidth);
            wrappedTitle.forEach(l => append(l + '\n'));
            bytes.push(0x1B, 0x45, 0x00); // Bold off

            const unit = it.unit || 'kg';
            const leftW = `${it.gross} - ${it.tare} ${unit}`;
            const rightW = `${it.net} ${unit}`;
            append(this.padRow(leftW, rightW, charWidth));

            const leftPrice = `@ ${it.price}`;
            const rightSub = it.subtotal;
            bytes.push(0x1B, 0x45, 0x01); // Bold on for subtotal
            append(this.padRow(leftPrice, rightSub, charWidth));
            bytes.push(0x1B, 0x45, 0x00); // Bold off

            if (idx < items.length - 1) {
                append(this.hr('.', charWidth));
            }
        });

        append(this.hr('-', charWidth));

        // 6. Totals / Calculation (Colons aligned at 13, values right-aligned)
        append(this.padTotalRow('Berat Kotor', `${data.activeGross} kg`, 13, charWidth));
        append(this.padTotalRow('Pot./Tara', `${data.activeTare} kg`, 13, charWidth));
        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append(this.padTotalRow('BERAT BERSIH', `${data.activeNet} kg`, 13, charWidth));
        bytes.push(0x1B, 0x45, 0x00); // Bold off

        append(this.padTotalRow('Subtotal', data.activeSubtotal, 13, charWidth));
        if (data.discount) {
            append(this.padTotalRow('Potongan', '- ' + data.discount, 13, charWidth));
        }
        if (data.rounding) {
            append(this.padTotalRow('Pembulatan', data.rounding, 13, charWidth));
        }

        // Solid divider
        append(this.hr('-', charWidth));

        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append(this.padTotalRow('TOTAL HARGA', data.activeFinal, 13, charWidth));
        bytes.push(0x1B, 0x45, 0x00); // Bold off

        if (data.hasDebt || data.debtBefore || data.debtDeduction || data.remainingDebt) {
            append(this.hr('-', charWidth));
            bytes.push(0x1B, 0x61, 0x01); // Center
            bytes.push(0x1B, 0x45, 0x01); // Bold on
            append('-- RINCIAN HUTANG --\n');
            bytes.push(0x1B, 0x45, 0x00); // Bold off
            bytes.push(0x1B, 0x61, 0x00); // Left align

            if (data.debtBefore) {
                append(this.padTotalRow('Hutang Awal', data.debtBefore, 13, charWidth));
            }
            if (data.debtDeduction) {
                const dedVal = String(data.debtDeduction).startsWith('-') || String(data.debtDeduction) === 'Rp 0' || String(data.debtDeduction) === '0' || String(data.debtDeduction) === '-'
                    ? String(data.debtDeduction)
                    : '- ' + data.debtDeduction;
                append(this.padTotalRow('Potong Hutang', dedVal, 13, charWidth));
            }
            if (data.remainingDebt) {
                bytes.push(0x1B, 0x45, 0x01); // Bold on
                append(this.padTotalRow('SISA HUTANG', data.remainingDebt, 13, charWidth));
                bytes.push(0x1B, 0x45, 0x00); // Bold off
            }
        }

        // Solid divider
        append(this.hr('-', charWidth));

        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append(this.padTotalRow('UANG DITERIMA', data.activeNetCash, 13, charWidth));
        bytes.push(0x1B, 0x45, 0x00); // Bold off

        // Dashed divider before disclaimer
        append(this.hr('-', charWidth));

        // 7. Footer & Disclaimer (Centered, full words wrapped without splitting)
        bytes.push(0x1B, 0x61, 0x01); // Center

        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append('[ PERHATIAN / DISCLAIMER ]\n');

        const discTitle = '*** ' + (data.disclaimer || 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI') + ' ***';
        this.wrapText(discTitle, charWidth).forEach(l => append(l + '\n'));
        bytes.push(0x1B, 0x45, 0x00); // Bold off

        const legal = data.legalNotice || 'Barang yang dijual wajib milik sah pribadi penjual. Depo tidak melayani barang curian / ilegal.';
        this.wrapText(legal, charWidth).forEach(l => append(l + '\n'));

        if (data.terms) {
            this.wrapText(data.terms, charWidth).forEach(l => append(l + '\n'));
        }

        const footer = data.footer || 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali. Terima kasih atas kerja samanya.';
        this.wrapText(footer, charWidth).forEach(l => append(l + '\n'));

        // 8. Feed 4 lines & cut
        append('\n\n\n\n');
        bytes.push(0x1D, 0x56, 0x41, 0x03); // GS V A 3 (feed paper & cut)

        return new Uint8Array(bytes);
    }

    static async buildSaleEscPos(data, charWidth = 32) {
        const encoder = new TextEncoder();
        let bytes = [];

        const append = (str) => {
            const encoded = encoder.encode(str);
            for (let i = 0; i < encoded.length; i++) bytes.push(encoded[i]);
        };

        // 1. Initialize
        bytes.push(0x1B, 0x40);

        // 2. Logo (Center align)
        if (data.logoBase64 || data.logoUrl) {
            const logoBytes = await this.imageToEscPos(data.logoBase64 || data.logoUrl, 144);
            if (logoBytes && logoBytes.length > 0) {
                for (let i = 0; i < logoBytes.length; i++) bytes.push(logoBytes[i]);
            }
        }

        // 3. Header
        bytes.push(0x1B, 0x61, 0x01); // Center
        bytes.push(0x1B, 0x45, 0x01); // Bold on
        append((data.companyName || 'UD. BERKAH RONGSOK MAKMUR').toUpperCase() + '\n');
        bytes.push(0x1B, 0x45, 0x00);

        if (data.companyAddress) {
            this.wrapText(data.companyAddress, charWidth).forEach(l => append(l + '\n'));
        }
        if (data.companyPhone) {
            this.wrapText('Telp: ' + data.companyPhone, charWidth).forEach(l => append(l + '\n'));
        }

        // 3. Divider
        bytes.push(0x1B, 0x61, 0x00);
        append(this.hr('-', charWidth));

        // 4. Metadata
        append(this.padColonRow('No. Faktur', data.invoiceNumber, 13, charWidth));
        append(this.padColonRow('Tanggal', data.transactionDate, 13, charWidth));
        append(this.padColonRow('Pembeli', data.buyerName || data.customerName || '-', 13, charWidth));
        if (data.buyerPhone) append(this.padColonRow('No. Telp', data.buyerPhone, 13, charWidth));
        append(this.padColonRow('Kasir', data.cashierName || '-', 13, charWidth));
        append(this.padColonRow('Metode Bayar', data.paymentMethod || 'Tunai', 13, charWidth));
        if (data.deliveryType) append(this.padColonRow('Pengiriman', data.deliveryType, 13, charWidth));

        if (data.isBankPayment) {
            if (data.bankAccountNumber) append(this.padColonRow('No. Rekening', data.bankAccountNumber, 13, charWidth));
            if (data.bankAccountName) append(this.padColonRow('Nama Rekening', data.bankAccountName, 13, charWidth));
            if (!data.bankAccountNumber && !data.bankAccountName && data.bankInfo) {
                append(this.padColonRow('Data Rekening', data.bankInfo, 13, charWidth));
            }
        }

        append(this.hr('-', charWidth));

        // 5. Items
        const items = data.items || [];
        items.forEach((it, idx) => {
            bytes.push(0x1B, 0x45, 0x01);
            const wrappedTitle = this.wrapText(String(it.name || '').toUpperCase(), charWidth);
            wrappedTitle.forEach(l => append(l + '\n'));
            bytes.push(0x1B, 0x45, 0x00);

            const qtyStr = `${it.weight || 0} kg`;
            const priceStr = `@ ${it.price || 0}`;
            append(this.padRow(qtyStr, priceStr, charWidth));

            bytes.push(0x1B, 0x45, 0x01);
            append(this.padRow('Total Nilai', it.subtotal || '0', charWidth));
            bytes.push(0x1B, 0x45, 0x00);

            if (idx < items.length - 1) {
                append(this.hr('.', charWidth));
            }
        });

        append(this.hr('-', charWidth));

        // 6. Totals
        append(this.padTotalRow('Total Berat', `${data.totalWeight || 0} kg`, 13, charWidth));
        append(this.padTotalRow('Subtotal', data.subtotalAmount || '0', 13, charWidth));
        if (data.discountAmount) append(this.padTotalRow('Diskon', '- ' + data.discountAmount, 13, charWidth));

        append(this.hr('-', charWidth));

        bytes.push(0x1B, 0x45, 0x01);
        append(this.padTotalRow('TOTAL TAGIHAN', data.finalAmount || '0', 13, charWidth));
        bytes.push(0x1B, 0x45, 0x00);

        append(this.hr('-', charWidth));

        // 7. Footer
        bytes.push(0x1B, 0x61, 0x01);
        append('*** BUKTI PENJUALAN RESMI ***\n');
        const footer = data.footer || 'Terima kasih atas kerja samanya.';
        this.wrapText(footer, charWidth).forEach(l => append(l + '\n'));

        // 8. Feed & Cut
        append('\n\n\n\n');
        bytes.push(0x1D, 0x56, 0x41, 0x03);

        return new Uint8Array(bytes);
    }

    static async buildDebtPaymentEscPos(data, charWidth = 32) {
        const encoder = new TextEncoder();
        let bytes = [];

        const append = (str) => {
            const encoded = encoder.encode(str);
            for (let i = 0; i < encoded.length; i++) bytes.push(encoded[i]);
        };

        // 1. Initialize
        bytes.push(0x1B, 0x40);

        // 2. Logo (Center align)
        if (data.logoBase64 || data.logoUrl) {
            const logoBytes = await this.imageToEscPos(data.logoBase64 || data.logoUrl, 144);
            if (logoBytes && logoBytes.length > 0) {
                for (let i = 0; i < logoBytes.length; i++) bytes.push(logoBytes[i]);
            }
        }

        // 3. Header
        bytes.push(0x1B, 0x61, 0x01);
        bytes.push(0x1B, 0x45, 0x01);
        append((data.companyName || 'UD. BERKAH RONGSOK MAKMUR').toUpperCase() + '\n');
        bytes.push(0x1B, 0x45, 0x00);

        if (data.companyAddress) {
            this.wrapText(data.companyAddress, charWidth).forEach(l => append(l + '\n'));
        }
        if (data.companyPhone) {
            this.wrapText('Telp: ' + data.companyPhone, charWidth).forEach(l => append(l + '\n'));
        }

        bytes.push(0x1B, 0x45, 0x01);
        append('KUITANSI PEMBAYARAN KASBON\n');
        bytes.push(0x1B, 0x45, 0x00);

        // 3. Divider
        bytes.push(0x1B, 0x61, 0x00);
        append(this.hr('-', charWidth));

        // 4. Metadata
        append(this.padColonRow('No. Kuitansi', data.paymentNumber, 13, charWidth));
        append(this.padColonRow('Tanggal', data.paymentDate, 13, charWidth));
        append(this.padColonRow('Peminjam', data.borrowerName || '-', 13, charWidth));
        append(this.padColonRow('Kasir', data.cashierName || '-', 13, charWidth));
        append(this.padColonRow('Metode Bayar', data.paymentMethod || 'Tunai', 13, charWidth));
        if (data.debtNumber) append(this.padColonRow('No. Kasbon', data.debtNumber, 13, charWidth));

        append(this.hr('-', charWidth));

        // 5. Amount breakdown
        if (data.remainingBefore) append(this.padTotalRow('Hutang Awal', data.remainingBefore, 13, charWidth));
        bytes.push(0x1B, 0x45, 0x01);
        append(this.padTotalRow('JUMLAH BAYAR', data.amountPaid, 13, charWidth));
        bytes.push(0x1B, 0x45, 0x00);
        append(this.padTotalRow('Sisa Hutang', data.remainingAfter, 13, charWidth));
        if (data.status) append(this.padColonRow('Status', data.status, 13, charWidth));

        if (data.notes) {
            append(this.hr('-', charWidth));
            this.wrapText(`Ket: ${data.notes}`, charWidth).forEach(l => append(l + '\n'));
        }

        append(this.hr('-', charWidth));

        // 6. Footer
        bytes.push(0x1B, 0x61, 0x01);
        append('*** BUKTI PEMBAYARAN SAH ***\n');
        append('Simpan kuitansi ini sebagai bukti pelunasan.\n');

        // 7. Feed & Cut
        append('\n\n\n\n');
        bytes.push(0x1D, 0x56, 0x41, 0x03);

        return new Uint8Array(bytes);
    }

    static async buildDeliveryOrderEscPos(data, charWidth = 32) {
        const encoder = new TextEncoder();
        let bytes = [];

        const append = (str) => {
            const encoded = encoder.encode(str);
            for (let i = 0; i < encoded.length; i++) bytes.push(encoded[i]);
        };

        // 1. Initialize
        bytes.push(0x1B, 0x40);

        // 2. Logo (Center align)
        if (data.logoBase64 || data.logoUrl) {
            const logoBytes = await this.imageToEscPos(data.logoBase64 || data.logoUrl, 144);
            if (logoBytes && logoBytes.length > 0) {
                for (let i = 0; i < logoBytes.length; i++) bytes.push(logoBytes[i]);
            }
        }

        // 3. Header
        bytes.push(0x1B, 0x61, 0x01);
        bytes.push(0x1B, 0x45, 0x01);
        append((data.companyName || 'UD. BERKAH RONGSOK MAKMUR').toUpperCase() + '\n');
        bytes.push(0x1B, 0x45, 0x00);

        if (data.companyAddress) {
            this.wrapText(data.companyAddress, charWidth).forEach(l => append(l + '\n'));
        }
        if (data.companyPhone) {
            this.wrapText('Telp: ' + data.companyPhone, charWidth).forEach(l => append(l + '\n'));
        }

        bytes.push(0x1B, 0x45, 0x01);
        append('SURAT JALAN PENGIRIMAN\n');
        bytes.push(0x1B, 0x45, 0x00);

        // 3. Divider
        bytes.push(0x1B, 0x61, 0x00);
        append(this.hr('-', charWidth));

        // 4. Metadata
        append(this.padColonRow('No. SJ', data.deliveryNumber, 13, charWidth));
        append(this.padColonRow('Tanggal', data.deliveryDate, 13, charWidth));
        append(this.padColonRow('Tujuan', data.recipientName || '-', 13, charWidth));
        append(this.padColonRow('Supir', data.driverName || '-', 13, charWidth));
        if (data.driverPhone) append(this.padColonRow('HP Supir', data.driverPhone, 13, charWidth));
        append(this.padColonRow('No. Polisi', data.vehicleNumber || '-', 13, charWidth));

        append(this.hr('-', charWidth));

        // 5. Items
        const items = data.items || [];
        items.forEach((it, idx) => {
            bytes.push(0x1B, 0x45, 0x01);
            const wrappedTitle = this.wrapText(String(it.name || '').toUpperCase(), charWidth);
            wrappedTitle.forEach(l => append(l + '\n'));
            bytes.push(0x1B, 0x45, 0x00);

            append(this.padRow(`   Berat:`, `${it.weight || 0} kg`, charWidth));
            if (idx < items.length - 1) {
                append(this.hr('.', charWidth));
            }
        });

        append(this.hr('-', charWidth));
        bytes.push(0x1B, 0x45, 0x01);
        append(this.padTotalRow('TOTAL BERAT', `${data.totalWeight || 0} kg`, 13, charWidth));
        bytes.push(0x1B, 0x45, 0x00);

        append(this.hr('-', charWidth));

        // 6. Signatures / Footer
        bytes.push(0x1B, 0x61, 0x01);
        append('Tanda Tangan Pengirim & Penerima\n\n\n');
        append('Gudang         Supir         Penerima\n');

        // 7. Feed & Cut
        append('\n\n\n\n');
        bytes.push(0x1D, 0x56, 0x41, 0x03);

        return new Uint8Array(bytes);
    }

    static async getBytes(data, charWidth = 32, type = 'purchase') {
        const docType = type || data.type || 'purchase';
        if (docType === 'sale') {
            return await this.buildSaleEscPos(data, charWidth);
        } else if (docType === 'debt_payment') {
            return await this.buildDebtPaymentEscPos(data, charWidth);
        } else if (docType === 'delivery_order') {
            return await this.buildDeliveryOrderEscPos(data, charWidth);
        }
        return await this.buildPurchaseEscPos(data, charWidth);
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

        const rawBytes = await this.getBytes(data, charWidth, type);

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

    static async printQuickPrinter(data, charWidth = 32, type = 'purchase') {
        const rawBytes = await this.getBytes(data, charWidth, type);
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
