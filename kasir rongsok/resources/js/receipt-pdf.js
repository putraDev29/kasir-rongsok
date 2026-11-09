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

    const cleanInv = (data.invoiceNumber || 'Pembelian').replace(/[\/\\]/g, '-');
    const cleanSup = (data.supplierName || 'Umum').replace(/[^a-zA-Z0-9_\-]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
    const docTitle = data.filename ? data.filename.replace(/\.pdf$/i, '') : `Nota_${cleanInv}_${cleanSup}`;
    doc.setProperties({
        title: docTitle,
        subject: `Nota Timbang Pembelian ${cleanInv}`,
        author: data.companyName || 'UD. BERKAH RONGSOK MAKMUR'
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
        no: 6,
        desc: 36,
        bruto: 14,
        tara: 13,
        netto: 15,
        price: 22,
        total: 22
    };
    const thY = curY;
    const thH = 6;

    doc.setFillColor(15, 23, 42); // slate-900
    doc.rect(margin, thY, contentWidth, thH, 'F');

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7);
    doc.setTextColor(255, 255, 255);

    let curX = margin;
    doc.text('No', curX + 1.5, thY + 4.2);
    curX += colW.no;
    doc.text('Jenis Barang Rongsok', curX + 1.5, thY + 4.2);
    curX += colW.desc;
    doc.text('Kotor', curX + colW.bruto - 1.5, thY + 4.2, { align: 'right' });
    curX += colW.bruto;
    doc.text('Pot', curX + colW.tara - 1.5, thY + 4.2, { align: 'right' });
    curX += colW.tara;
    doc.text('Netto', curX + colW.netto - 1.5, thY + 4.2, { align: 'right' });
    curX += colW.netto;
    doc.text('Harga Beli', curX + colW.price - 1.5, thY + 4.2, { align: 'right' });
    curX += colW.price;
    doc.text('Subtotal', curX + colW.total - 1.5, thY + 4.2, { align: 'right' });

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
        doc.text(String(idx + 1), x + 1.5, curY + 4.2);
        x += colW.no;

        const descText = doc.splitTextToSize(it.name || it.item_name_snapshot || '-', colW.desc - 3);
        doc.text(descText[0] || it.name, x + 1.5, curY + 4.2);
        x += colW.desc;

        doc.text(String(it.bruto || it.gross_weight || '0'), x + colW.bruto - 1.5, curY + 4.2, { align: 'right' });
        x += colW.bruto;

        doc.text(String(it.tara || it.tare_weight || '0'), x + colW.tara - 1.5, curY + 4.2, { align: 'right' });
        x += colW.tara;

        doc.setFont('helvetica', 'bold');
        doc.text(String(it.netto || it.net_weight || '0'), x + colW.netto - 1.5, curY + 4.2, { align: 'right' });
        x += colW.netto;

        doc.setFont('helvetica', 'normal');
        const priceStr = it.priceFormatted || (it.price ? ('Rp ' + it.price) : ('Rp ' + (it.price_per_kg ? Number(it.price_per_kg).toLocaleString('id-ID') : '0')));
        doc.text(priceStr, x + colW.price - 1.5, curY + 4.2, { align: 'right' });
        x += colW.price;

        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(it.subtotalFormatted || ('Rp ' + (it.subtotal || 0)), x + colW.total - 1.5, curY + 4.2, { align: 'right' });
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

    const cleanInv = (data.invoiceNumber || 'Penjualan').replace(/[\/\\]/g, '-');
    const cleanCust = (data.customerName || 'Pembeli').replace(/[^a-zA-Z0-9_\-]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
    const docTitle = data.filename ? data.filename.replace(/\.pdf$/i, '') : `Faktur_${cleanInv}_${cleanCust}`;
    doc.setProperties({
        title: docTitle,
        subject: `Faktur Penjualan Partai ${cleanInv}`,
        author: data.companyName || 'UD. BERKAH RONGSOK MAKMUR'
    });

    const pageWidth = 148;
    const margin = 10;
    const contentWidth = pageWidth - margin * 2;

    const companyName = data.companyName || 'UD. BERKAH RONGSOK MAKMUR';
    const companyAddress = data.companyAddress || 'Jl. Industri Daur Ulang No. 88, Sentra Rongsok';
    const companyPhone = data.companyPhone || '0812-3456-7890';

    // Header (Matching Image 2 / sales.print)
    let curY = 14;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(15, 23, 42);
    doc.text(companyName, margin, curY);

    // Right Header: INVOICE & Meta
    doc.setFontSize(12);
    doc.text('INVOICE', pageWidth - margin, curY, { align: 'right' });

    curY += 5;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(100, 116, 139);
    doc.text(companyAddress, margin, curY);

    doc.setFont('courier', 'bold');
    doc.setFontSize(10.5);
    doc.setTextColor(30, 41, 59);
    doc.text(data.invoiceNumber || '-', pageWidth - margin, curY, { align: 'right' });

    curY += 4.5;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(100, 116, 139);
    doc.text('Telp: ' + companyPhone, margin, curY);
    doc.text(data.transactionDate || '-', pageWidth - margin, curY, { align: 'right' });

    curY += 4.5;
    const isPickup = data.isPickup ?? true;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    if (isPickup) {
        doc.setTextColor(4, 120, 87);
        doc.text('Diambil Langsung Pembeli', pageWidth - margin, curY, { align: 'right' });
    } else {
        doc.setTextColor(29, 78, 216);
        doc.text('Pengiriman Armada Gudang', pageWidth - margin, curY, { align: 'right' });
    }

    curY += 3;
    doc.setDrawColor(191, 219, 254); // blue-200
    doc.setLineWidth(0.6);
    doc.line(margin, curY, pageWidth - margin, curY);

    // Two Columns Info Section
    curY += 5;
    const colLeftX = margin;
    const colRightX = margin + (contentWidth / 2) + 2;

    // Left Column: Pembeli
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.setTextColor(148, 163, 184); // slate-400
    doc.text(isPickup ? 'PEMBELI (DIAMBIL LANGSUNG):' : 'TUJUAN PENGIRIMAN (PEMBELI):', colLeftX, curY);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10.5);
    doc.setTextColor(15, 23, 42);
    doc.text(data.customerName || 'Pembeli', colLeftX, curY + 5);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(71, 85, 105);
    doc.text(data.customerAddress || '-', colLeftX, curY + 9);
    doc.setFont('courier', 'normal');
    doc.text('Telp: ' + (data.customerPhone || '-'), colLeftX, curY + 13);

    // Right Column: Metode Pembayaran & Catatan
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.setTextColor(148, 163, 184);
    doc.text('METODE PEMBAYARAN:', colRightX, curY);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);
    doc.setTextColor(15, 23, 42);
    doc.text(data.paymentMethod || 'Tunai', colRightX, curY + 4.5);

    let rightY = curY + 8;
    if (data.isBankPayment && data.bankAccountNumber) {
        doc.setFont('courier', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(71, 85, 105);
        doc.text(`No. Rek: ${data.bankAccountNumber}${data.bankAccountName ? ' (a/n ' + data.bankAccountName + ')' : ''}`, colRightX, rightY);
        rightY += 4.5;
    }

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.setTextColor(148, 163, 184);
    doc.text(isPickup ? 'METODE PENGAMBILAN:' : 'CATATAN TRANSPORTASI:', colRightX, rightY);
    rightY += 3.5;

    // Transport / Notes Box
    const noteText = data.notes || (isPickup ? 'Diambil langsung oleh pembeli di gudang/depo (Tanpa kendaraan perusahaan)' : 'Pengiriman armada darat');
    doc.setFillColor(248, 250, 252);
    doc.setDrawColor(226, 232, 240);
    doc.setLineWidth(0.3);
    doc.roundedRect(colRightX, rightY, (contentWidth / 2) - 2, 6, 1, 1, 'FD');
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(51, 65, 85);
    doc.text(noteText, colRightX + 2, rightY + 4, { maxWidth: (contentWidth / 2) - 6 });

    rightY += 8;

    if (!isPickup && data.driverName) {
        doc.setFillColor(239, 246, 255);
        doc.setDrawColor(191, 219, 254);
        doc.roundedRect(colRightX, rightY, (contentWidth / 2) - 2, 9, 1, 1, 'FD');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(30, 58, 138);
        doc.text(`Supir / Armada: ${data.driverName} (${data.vehicleNumber || '-'})`, colRightX + 2, rightY + 3.8);
        doc.setFont('courier', 'normal');
        doc.setFontSize(6.8);
        doc.setTextColor(30, 64, 175);
        const parseCost = (v) => parseFloat(String(v || 0).replace(/[^0-9.-]+/g, '')) || 0;
        const sCost = parseCost(data.shippingCost);
        const lCost = parseCost(data.loadingCost);
        const sCostStr = sCost > 0 ? (typeof data.shippingCost === 'number' ? 'Rp ' + data.shippingCost.toLocaleString('id-ID') : (String(data.shippingCost).startsWith('Rp') ? data.shippingCost : 'Rp ' + data.shippingCost)) : '-';
        const lCostStr = lCost > 0 ? (typeof data.loadingCost === 'number' ? 'Rp ' + data.loadingCost.toLocaleString('id-ID') : (String(data.loadingCost).startsWith('Rp') ? data.loadingCost : 'Rp ' + data.loadingCost)) : '-';
        doc.text(`Ongkos Armada: ${sCostStr} | Bongkar: ${lCostStr}`, colRightX + 2, rightY + 7.2);
        rightY += 11;
    }

    curY = Math.max(curY + 17, rightY + 2);
    doc.setDrawColor(226, 232, 240);
    doc.setLineWidth(0.4);
    doc.line(margin, curY, pageWidth - margin, curY);

    // Items Table Header (Matching Image 2)
    curY += 4;
    const colW = { no: 7, desc: 55, weight: 20, price: 23, total: 23 };
    doc.setDrawColor(203, 213, 225);
    doc.setLineWidth(0.5);
    doc.line(margin, curY + 5.5, pageWidth - margin, curY + 5.5);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7.5);
    doc.setTextColor(71, 85, 105);

    let x = margin;
    doc.text('#', x + 1, curY + 4);
    x += colW.no;
    doc.text('NAMA MATERIAL SCRAP & DAUR ULANG', x + 1, curY + 4);
    x += colW.desc;
    doc.text('BERAT (KG)', x + colW.weight - 1, curY + 4, { align: 'right' });
    x += colW.weight;
    doc.text('HARGA SATUAN/KG', x + colW.price - 1, curY + 4, { align: 'right' });
    x += colW.price;
    doc.text('TOTAL NILAI', x + colW.total - 1, curY + 4, { align: 'right' });

    curY += 7;

    const items = data.items || [];
    items.forEach((it, idx) => {
        doc.setDrawColor(241, 245, 249);
        doc.setLineWidth(0.2);
        doc.line(margin, curY + 5, pageWidth - margin, curY + 5);

        let rowX = margin;
        doc.setFont('courier', 'normal');
        doc.setFontSize(7.8);
        doc.setTextColor(100, 116, 139);
        doc.text(String(idx + 1), rowX + 1, curY + 3.8);

        rowX += colW.no;
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(it.name || '-', rowX + 1, curY + 3.8);

        const wNum = parseFloat(String(it.weight || 0).replace(/[^0-9.-]+/g, '')) || 0;
        const wStr = wNum > 0 ? (String(it.weight).includes('kg') ? it.weight : (it.weight + ' kg')) : '-';

        const pNum = parseFloat(String(it.sellPrice || 0).replace(/[^0-9.-]+/g, '')) || 0;
        const pStr = pNum > 0 ? (String(it.sellPrice).startsWith('Rp') ? it.sellPrice : ('Rp ' + it.sellPrice)) : '-';

        const subNum = parseFloat(String(it.subtotal || 0).replace(/[^0-9.-]+/g, '')) || 0;
        const subStr = subNum > 0 ? (it.subtotalFormatted || ('Rp ' + it.subtotal)) : '-';

        rowX += colW.desc;
        doc.setFont('courier', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(wStr, rowX + colW.weight - 1, curY + 3.8, { align: 'right' });

        rowX += colW.weight;
        doc.setFont('courier', 'normal');
        doc.setTextColor(51, 65, 85);
        doc.text(pStr, rowX + colW.price - 1, curY + 3.8, { align: 'right' });

        rowX += colW.price;
        doc.setFont('courier', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(subStr, rowX + colW.total - 1, curY + 3.8, { align: 'right' });

        curY += 5.5;
    });

    // Summary Section (Matching Image 2: right-aligned)
    curY += 3;
    doc.setDrawColor(203, 213, 225);
    doc.setLineWidth(0.4);
    doc.line(pageWidth - margin - 55, curY, pageWidth - margin, curY);

    curY += 3.5;
    const sumXLabel = pageWidth - margin - 55;
    const sumXVal = pageWidth - margin;

    doc.setFont('courier', 'normal');
    doc.setFontSize(7.8);
    doc.setTextColor(71, 85, 105);
    doc.text('Total Berat:', sumXLabel, curY);
    doc.setFont('courier', 'bold');
    doc.setTextColor(15, 23, 42);
    const totWNum = parseFloat(String(data.totalWeight || 0).replace(/[^0-9.-]+/g, '')) || 0;
    const totWStr = totWNum > 0 ? (String(data.totalWeight).includes('kg') ? data.totalWeight : (data.totalWeight + ' kg')) : '-';
    doc.text(totWStr, sumXVal, curY, { align: 'right' });

    curY += 4.5;
    doc.setFont('courier', 'normal');
    doc.setTextColor(71, 85, 105);
    doc.text('Subtotal:', sumXLabel, curY);
    doc.setFont('courier', 'normal');
    doc.setTextColor(15, 23, 42);
    const subValNum = parseFloat(String(data.subtotalAmount || data.finalAmount || 0).replace(/[^0-9.-]+/g, '')) || 0;
    const subValStr = subValNum > 0 ? (data.subtotalAmount || data.finalAmount) : '-';
    doc.text(subValStr, sumXVal, curY, { align: 'right' });

    if (data.discountAmount) {
        curY += 4.5;
        doc.setTextColor(220, 38, 38);
        doc.text('Diskon:', sumXLabel, curY);
        doc.text(String(data.discountAmount), sumXVal, curY, { align: 'right' });
    }

    curY += 2;
    doc.setDrawColor(15, 23, 42);
    doc.setLineWidth(0.8);
    doc.line(sumXLabel, curY, sumXVal, curY);

    curY += 4.5;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(15, 23, 42);
    doc.text('TOTAL TAGIHAN:', sumXLabel, curY);
    doc.setFont('courier', 'bold');
    doc.setFontSize(10.5);
    const finValNum = parseFloat(String(data.finalAmount || 0).replace(/[^0-9.-]+/g, '')) || 0;
    const finValStr = finValNum > 0 ? (String(data.finalAmount).startsWith('Rp') ? data.finalAmount : ('Rp ' + data.finalAmount)) : '-';
    doc.text(finValStr, sumXVal, curY, { align: 'right' });

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

    const cleanPay = (data.paymentNumber || 'Kasbon').replace(/[\/\\]/g, '-');
    const cleanBorrower = (data.borrowerName || 'Peminjam').replace(/[^a-zA-Z0-9_\-]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
    const docTitle = data.filename ? data.filename.replace(/\.pdf$/i, '') : `BuktiBayar_${cleanPay}_${cleanBorrower}`;
    doc.setProperties({
        title: docTitle,
        subject: `Bukti Pembayaran Kasbon ${cleanPay}`,
        author: data.companyName || 'UD. BERKAH RONGSOK MAKMUR'
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
