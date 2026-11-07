import { jsPDF } from 'jspdf';

/**
 * Generate PDF Nota Timbangan Pembelian Rongsok (A5 Portrait)
 */
export function generatePurchaseReceiptPdf(data) {
    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a5',
    });

    const pageWidth = 148;
    const margin = 10;
    const contentWidth = pageWidth - margin * 2; // 128 mm

    const companyName = data.companyName || 'UD. BERKAH RONGSOK MAKMUR';
    const companyAddress = data.companyAddress || 'Sentra Daur Ulang Logam & Rongsok';
    const companyPhone = data.companyPhone || '0812-3456-7890';
    const disclaimer = data.disclaimer || 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI';
    const footerText = data.footerText || 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali.';

    // 1. Header Banner
    doc.setFillColor(240, 253, 244); // emerald-50
    doc.rect(margin, 8, contentWidth, 23, 'F');

    // Accent line
    doc.setDrawColor(16, 185, 129); // emerald-500
    doc.setLineWidth(1.0);
    doc.line(margin, 31, pageWidth - margin, 31);

    // Title & Contact
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(15, 23, 42); // slate-900
    doc.text(companyName, margin + 4, 15.5);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(71, 85, 105); // slate-600
    doc.text(companyAddress, margin + 4, 21);
    doc.text('Telp/WA: ' + companyPhone, margin + 4, 26);

    // Status Badge on top-right (LUNAS / SELESAI)
    doc.setFillColor(236, 253, 245);
    doc.setDrawColor(16, 185, 129);
    doc.setLineWidth(0.4);
    doc.roundedRect(pageWidth - margin - 32, 12, 28, 8, 2, 2, 'FD');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(5, 150, 105);
    doc.text('LUNAS / PAID', pageWidth - margin - 18, 17.2, { align: 'center' });

    // 2. Document Title
    let curY = 37.5;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.setTextColor(15, 23, 42);
    doc.text('BUKTI PENERIMAAN / NOTA TIMBANG', margin, curY);

    curY += 5;

    const hasBank = !!(data.isBankPayment && (data.bankAccountNumber || data.bankAccountName || data.bankInfo));
    let bankLines = 0;
    if (hasBank) {
        if (data.bankAccountNumber) bankLines++;
        if (data.bankAccountName) bankLines++;
        if (!data.bankAccountNumber && !data.bankAccountName && data.bankInfo) bankLines++;
    }

    const boxHeight = 22 + (bankLines * 5.5);

    // Metadata 2-Column Box
    doc.setFillColor(248, 250, 252); // slate-50
    doc.setDrawColor(226, 232, 240); // slate-200
    doc.setLineWidth(0.3);
    doc.roundedRect(margin, curY, contentWidth, boxHeight, 2, 2, 'FD');

    const col1X = margin + 3.5;
    const col2X = margin + contentWidth / 2 + 3.5;
    const metaY = curY + 5;

    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);

    // Left Column
    doc.text('No. Nota:', col1X, metaY);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(5, 150, 105);
    doc.text(data.invoiceNumber || '-', col1X + 22, metaY);

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);
    doc.text('Waktu / Tanggal:', col1X, metaY + 5.5);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(15, 23, 42);
    doc.text(data.transactionDate || '-', col1X + 22, metaY + 5.5);

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);
    doc.text('Kasir / Timbang:', col1X, metaY + 11);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(15, 23, 42);
    doc.text(data.cashierName || '-', col1X + 22, metaY + 11);

    // Right Column
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);
    doc.text('Nama Pemasok:', col2X, metaY);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(15, 23, 42);
    doc.text(data.supplierName || 'Pemasok Umum', col2X + 22, metaY);

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);
    doc.text('HP Pemasok:', col2X, metaY + 5.5);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(15, 23, 42);
    doc.text(data.supplierPhone || '-', col2X + 22, metaY + 5.5);

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);
    doc.text('Metode Bayar:', col2X, metaY + 11);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(13, 148, 136);
    doc.text(data.paymentMethod || 'Tunai', col2X + 22, metaY + 11);

    if (hasBank) {
        let bankY = metaY + 16.5;
        if (data.bankAccountNumber) {
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(100, 116, 139);
            doc.text('No. Rekening:', col2X, bankY);
            doc.setFont('courier', 'bold');
            doc.setTextColor(15, 23, 42);
            doc.text(String(data.bankAccountNumber), col2X + 22, bankY);
            bankY += 5.5;
        }
        if (data.bankAccountName) {
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(100, 116, 139);
            doc.text('Nama Rekening:', col2X, bankY);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(15, 23, 42);
            doc.text(String(data.bankAccountName), col2X + 22, bankY);
            bankY += 5.5;
        }
        if (!data.bankAccountNumber && !data.bankAccountName && data.bankInfo) {
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(100, 116, 139);
            doc.text('Data Rekening:', col2X, bankY);
            doc.setFont('courier', 'bold');
            doc.setTextColor(15, 23, 42);
            doc.text(String(data.bankInfo), col2X + 22, bankY);
            bankY += 5.5;
        }
    }

    curY += boxHeight + 4;

    // 3. Table Header
    const colW = {
        no: 7,
        desc: 51,
        bruto: 16,
        tara: 16,
        netto: 18,
        total: 20
    };
    const thY = curY;
    const thH = 6;

    doc.setFillColor(15, 23, 42); // slate-900
    doc.rect(margin, thY, contentWidth, thH, 'F');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7);
    doc.setTextColor(255, 255, 255);

    let curX = margin;
    doc.text('No', curX + 2, thY + 4.2);
    curX += colW.no;
    doc.text('Jenis Barang Rongsok', curX + 2, thY + 4.2);
    curX += colW.desc;
    doc.text('Kotor (kg)', curX + colW.bruto - 2, thY + 4.2, { align: 'right' });
    curX += colW.bruto;
    doc.text('Pot (kg)', curX + colW.tara - 2, thY + 4.2, { align: 'right' });
    curX += colW.tara;
    doc.text('Netto (kg)', curX + colW.netto - 2, thY + 4.2, { align: 'right' });
    curX += colW.netto;
    doc.text('Subtotal', curX + colW.total - 2, thY + 4.2, { align: 'right' });

    curY += thH;

    // 4. Table Rows
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7);

    const items = data.items || [];
    items.forEach((it, idx) => {
        const isEven = idx % 2 === 0;
        const rowH = 6;

        if (isEven) {
            doc.setFillColor(255, 255, 255);
        } else {
            doc.setFillColor(248, 250, 252);
        }
        doc.rect(margin, curY, contentWidth, rowH, 'F');

        doc.setDrawColor(241, 245, 249);
        doc.setLineWidth(0.2);
        doc.line(margin, curY + rowH, margin + contentWidth, curY + rowH);

        doc.setTextColor(51, 65, 85);
        let x = margin;
        doc.text(String(idx + 1), x + 2, curY + 4.2);
        x += colW.no;

        const descText = doc.splitTextToSize(it.name || it.item_name_snapshot || '-', colW.desc - 4);
        doc.text(descText[0] || it.name, x + 2, curY + 4.2);
        x += colW.desc;

        doc.text(String(it.bruto || it.gross_weight || '0'), x + colW.bruto - 2, curY + 4.2, { align: 'right' });
        x += colW.bruto;

        doc.text(String(it.tara || it.tare_weight || '0'), x + colW.tara - 2, curY + 4.2, { align: 'right' });
        x += colW.tara;

        doc.setFont('helvetica', 'bold');
        doc.text(String(it.netto || it.net_weight || '0'), x + colW.netto - 2, curY + 4.2, { align: 'right' });
        x += colW.netto;

        doc.setTextColor(15, 23, 42);
        doc.text(it.subtotalFormatted || ('Rp ' + (it.subtotal || 0)), x + colW.total - 2, curY + 4.2, { align: 'right' });
        doc.setFont('helvetica', 'normal');

        curY += rowH;
    });

    // 5. Financial & Weight Summary Box
    curY += 3;
    const summaryW = 68;
    const summaryX = margin + contentWidth - summaryW;

    const addSummaryRow = (label, value, isBold = false, color = [15, 23, 42]) => {
        doc.setFont('helvetica', isBold ? 'bold' : 'normal');
        doc.setFontSize(isBold ? 8 : 7);
        doc.setTextColor(color[0], color[1], color[2]);
        doc.text(label, summaryX, curY);
        doc.text(value, margin + contentWidth - 2, curY, { align: 'right' });
        curY += isBold ? 5 : 4.2;
    };

    addSummaryRow('Total Berat Bersih (Netto):', (data.totalNetWeight || '0') + ' kg', false, [5, 150, 105]);
    addSummaryRow('Nilai Total Pembelian:', data.subtotalAmount || ('Rp ' + (data.finalAmount || 0)));

    if (data.debtDeductionAmount && parseFloat(data.debtDeductionAmount) > 0) {
        addSummaryRow('Potongan Kasbon/Hutang:', `- Rp ${data.debtDeductionAmount}`, false, [225, 29, 72]);
    }

    // Divider line
    doc.setDrawColor(203, 213, 225);
    doc.setLineWidth(0.4);
    doc.line(summaryX, curY - 1, margin + contentWidth, curY - 1);

    addSummaryRow(data.isBankPayment ? 'TRANSFER DIBAYARKAN:' : 'TUNAI DIBAYARKAN:', data.netCashPaid || data.finalAmountFormatted || data.subtotalAmount, true, [16, 185, 129]);

    if (data.remainingDebtAfter && parseFloat(data.remainingDebtAfter) > 0) {
        addSummaryRow('Sisa Kasbon Pemasok:', `Rp ${data.remainingDebtAfter}`, false, [180, 83, 9]);
    }

    // 6. Disclaimer Legal Notice
    curY += 4;
    doc.setFillColor(254, 242, 242); // red-50
    doc.setDrawColor(254, 202, 202); // red-200
    doc.setLineWidth(0.4);
    doc.roundedRect(margin, curY, contentWidth, 14, 1.5, 1.5, 'FD');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7);
    doc.setTextColor(185, 28, 28); // red-700
    doc.text('PERINGATAN HUKUM: ' + disclaimer, margin + 3, curY + 4.5);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(6.5);
    doc.setTextColor(100, 116, 139);
    doc.text(footerText, margin + 3, curY + 9);

    // Footer signature notice
    curY += 18;
    doc.setFontSize(6.5);
    doc.setTextColor(148, 163, 184);
    doc.text(`Dicetak otomatis oleh Sistem Kasir Rongsok pada ${new Date().toLocaleString('id-ID')}`, margin, curY);

    return doc;
}

/**
 * Generate PDF Faktur Penjualan Partai Pabrik (A5 Portrait)
 */
export function generateSalesInvoicePdf(data) {
    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a5',
    });

    const pageWidth = 148;
    const margin = 10;
    const contentWidth = pageWidth - margin * 2;

    const companyName = data.companyName || 'UD. BERKAH RONGSOK MAKMUR';
    const companyAddress = data.companyAddress || 'Sentra Daur Ulang Logam & Rongsok';
    const companyPhone = data.companyPhone || '0812-3456-7890';

    // Header
    doc.setFillColor(239, 246, 255); // blue-50
    doc.rect(margin, 8, contentWidth, 23, 'F');
    doc.setDrawColor(37, 99, 235); // blue-600
    doc.setLineWidth(1.0);
    doc.line(margin, 31, pageWidth - margin, 31);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(15, 23, 42);
    doc.text(companyName, margin + 4, 15.5);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(71, 85, 105);
    doc.text(companyAddress, margin + 4, 21);
    doc.text('Telp/WA: ' + companyPhone, margin + 4, 26);

    // Document Title
    let curY = 38;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.setTextColor(15, 23, 42);
    doc.text('FAKTUR PENJUALAN PARTAI (GROSIR)', margin, curY);

    curY += 5;
    doc.setFillColor(248, 250, 252);
    doc.setDrawColor(226, 232, 240);
    doc.setLineWidth(0.3);
    doc.roundedRect(margin, curY, contentWidth, 18, 2, 2, 'FD');

    const col1X = margin + 3.5;
    const col2X = margin + contentWidth / 2 + 3.5;
    const metaY = curY + 5;

    doc.setFontSize(7.5);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);
    doc.text('No. Faktur:', col1X, metaY);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(37, 99, 235);
    doc.text(data.invoiceNumber || '-', col1X + 22, metaY);

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);
    doc.text('Tanggal:', col1X, metaY + 6);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(15, 23, 42);
    doc.text(data.transactionDate || '-', col1X + 22, metaY + 6);

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);
    doc.text('Pembeli/Pabrik:', col2X, metaY);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(15, 23, 42);
    doc.text(data.customerName || 'Pembeli', col2X + 22, metaY);

    doc.setFont('helvetica', 'normal');
    doc.setTextColor(100, 116, 139);
    doc.text('HP Pembeli:', col2X, metaY + 6);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(15, 23, 42);
    doc.text(data.customerPhone || '-', col2X + 22, metaY + 6);

    curY += 23;

    // Items Table
    const colW = { no: 8, desc: 62, weight: 24, price: 24, total: 24 };
    doc.setFillColor(15, 23, 42);
    doc.rect(margin, curY, contentWidth, 6, 'F');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7);
    doc.setTextColor(255, 255, 255);

    let x = margin;
    doc.text('No', x + 2, curY + 4.2);
    x += colW.no;
    doc.text('Nama Barang', x + 2, curY + 4.2);
    x += colW.desc;
    doc.text('Berat (kg)', x + colW.weight - 2, curY + 4.2, { align: 'right' });
    x += colW.weight;
    doc.text('Harga/kg', x + colW.price - 2, curY + 4.2, { align: 'right' });
    x += colW.price;
    doc.text('Total', x + colW.total - 2, curY + 4.2, { align: 'right' });

    curY += 6;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7);

    const items = data.items || [];
    items.forEach((it, idx) => {
        doc.setFillColor(idx % 2 === 0 ? 255 : 248, idx % 2 === 0 ? 255 : 250, idx % 2 === 0 ? 255 : 252);
        doc.rect(margin, curY, contentWidth, 6, 'F');
        doc.setTextColor(51, 65, 85);

        let rowX = margin;
        doc.text(String(idx + 1), rowX + 2, curY + 4.2);
        rowX += colW.no;
        doc.text(it.name || '-', rowX + 2, curY + 4.2);
        rowX += colW.desc;
        doc.text(String(it.weight || 0), rowX + colW.weight - 2, curY + 4.2, { align: 'right' });
        rowX += colW.weight;
        doc.text('Rp ' + (it.sellPrice || 0), rowX + colW.price - 2, curY + 4.2, { align: 'right' });
        rowX += colW.price;
        doc.setFont('helvetica', 'bold');
        doc.text(it.subtotalFormatted || ('Rp ' + (it.subtotal || 0)), rowX + colW.total - 2, curY + 4.2, { align: 'right' });
        doc.setFont('helvetica', 'normal');

        curY += 6;
    });

    curY += 4;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(37, 99, 235);
    doc.text('TOTAL MUATAN: ' + (data.totalWeight || '0') + ' kg', margin, curY);
    doc.text('TOTAL TAGIHAN: ' + (data.finalAmount || 'Rp 0'), margin + contentWidth, curY, { align: 'right' });

    return doc;
}

/**
 * Generate PDF Bukti Pembayaran Kasbon (A5 Portrait)
 */
export function generateDebtPaymentPdf(data) {
    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a5',
    });

    const pageWidth = 148;
    const margin = 10;
    const contentWidth = pageWidth - margin * 2;

    const companyName = data.companyName || 'UD. BERKAH RONGSOK MAKMUR';
    const companyPhone = data.companyPhone || '0812-3456-7890';

    doc.setFillColor(254, 243, 199); // amber-100
    doc.rect(margin, 8, contentWidth, 23, 'F');
    doc.setDrawColor(217, 119, 6); // amber-600
    doc.setLineWidth(1.0);
    doc.line(margin, 31, pageWidth - margin, 31);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(15, 23, 42);
    doc.text(companyName, margin + 4, 15.5);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(71, 85, 105);
    doc.text('Sentra Daur Ulang Logam & Rongsok | Telp: ' + companyPhone, margin + 4, 22);

    let curY = 38;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.setTextColor(15, 23, 42);
    doc.text('BUKTI PEMBAYARAN KASBON / MODAL', margin, curY);

    curY += 6;
    doc.setFillColor(248, 250, 252);
    doc.setDrawColor(226, 232, 240);
    doc.setLineWidth(0.3);
    doc.roundedRect(margin, curY, contentWidth, 36, 2, 2, 'FD');

    const startX = margin + 4;
    let textY = curY + 6;

    const addRow = (label, val, isBold = false) => {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(100, 116, 139);
        doc.text(label, startX, textY);
        doc.setFont('helvetica', isBold ? 'bold' : 'normal');
        doc.setTextColor(15, 23, 42);
        doc.text(String(val || '-'), startX + 45, textY);
        textY += 6;
    };

    addRow('No. Bukti Bayar:', data.paymentNumber, true);
    addRow('Waktu Transaksi:', data.paymentDate);
    addRow('Nama Pemasok:', data.supplierName, true);
    addRow('Metode Pembayaran:', data.paymentMethod);
    addRow('Jumlah Dibayarkan:', data.amount, true);
    addRow('Sisa Kasbon Terakhir:', data.remainingAfter, true);

    return doc;
}
