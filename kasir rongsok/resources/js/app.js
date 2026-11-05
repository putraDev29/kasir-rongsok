import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';
import { generatePurchaseReceiptPdf, generateSalesInvoicePdf, generateDebtPaymentPdf } from './receipt-pdf';

window.Alpine = Alpine;
window.createIcons = createIcons;
window.lucideIcons = icons;
window.lucide = {
    createIcons: (opt) => createIcons(opt || { icons })
};

// Initialize Lucide icons on page load and live DOM changes
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
});

window.refreshIcons = () => {
    setTimeout(() => {
        createIcons({ icons });
    }, 50);
};

// Global formatters for Indonesian Rupiah & Weight
window.formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(number || 0);
};

window.formatNumber = (number, decimals = 2) => {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: decimals
    }).format(number || 0);
};

// WhatsApp Gateway Global Alpine Store
Alpine.store('waGateway', {
    isOpen: false,
    receiptModalOpen: false,
    status: 'OFFLINE',
    connected: false,
    phone: '',
    name: '',
    qrCode: null,
    isRefreshing: false,
    isLoggingOut: false,
    isSendingTest: false,
    lastError: null,
    
    // Receipt Modal State
    receipt: {
        type: 'purchase',
        id: null,
        title: '',
        subtitle: '',
        phone: '',
        formattedPhone: '',
        filename: 'Nota.pdf',
        fileDescription: 'Dokumen PDF Resmi Kasir Rongsok (Format A5)',
        pdfUrl: null,
        sendUrl: null,
        sendPayload: null,
        submitButtonText: 'Kirim Nota PDF via WA',
        message: '',
        receiptData: null,
        sendAsPdf: true,
        isLoading: false,
        isSending: false,
        sendSuccess: false,
        sendError: null
    },

    init() {
        const isEnabled = document.querySelector('meta[name="wa-gateway-enabled"]')?.getAttribute('content') === '1';
        if (isEnabled) {
            this.fetchStatus();
            setInterval(() => {
                this.fetchStatus();
            }, 5000);
        }
    },

    async fetchStatus() {
        try {
            const res = await fetch('/whatsapp/status', {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                this.status = data.status || 'OFFLINE';
                this.connected = !!data.connected;
                this.phone = data.phone || '';
                this.name = data.name || '';
                this.lastError = data.lastError || null;

                if (this.status === 'QR_READY') {
                    this.fetchQr();
                } else if (this.status === 'CONNECTED') {
                    this.qrCode = null;
                }
            } else {
                this.status = 'OFFLINE';
                this.connected = false;
            }
        } catch (e) {
            this.status = 'OFFLINE';
            this.connected = false;
        }
    },

    async fetchQr() {
        try {
            const res = await fetch('/whatsapp/qr', {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                if (data.qr) {
                    this.qrCode = data.qr;
                }
            }
        } catch (e) {}
    },

    async reconnect() {
        this.isRefreshing = true;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await fetch('/whatsapp/reconnect', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            setTimeout(() => this.fetchStatus(), 1500);
        } catch (e) {
            alert('Gagal menghubungi gateway server di port 21465.');
        } finally {
            this.isRefreshing = false;
        }
    },

    async logout() {
        if (!confirm('Apakah Anda yakin ingin logout dari akun WhatsApp ini? Koneksi akan diputus dan QR code baru akan disiapkan.')) return;
        this.isLoggingOut = true;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await fetch('/whatsapp/logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            this.connected = false;
            this.status = 'DISCONNECTED';
            this.qrCode = null;
            setTimeout(() => this.fetchStatus(), 1500);
        } catch (e) {
            alert('Gagal logout dari WhatsApp: ' + e.message);
        } finally {
            this.isLoggingOut = false;
        }
    },

    async sendTest(phone, message) {
        if (!phone) {
            alert('Masukkan nomor WhatsApp tujuan!');
            return false;
        }
        this.isSendingTest = true;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/whatsapp/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ phone, message })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                alert('Pesan uji coba berhasil terkirim ke ' + (data.to || phone) + '!');
                return true;
            } else {
                alert(data.message || 'Gagal mengirim pesan uji coba.');
                return false;
            }
        } catch (e) {
            alert('Terjadi kesalahan koneksi ke gateway: ' + e.message);
            return false;
        } finally {
            this.isSendingTest = false;
        }
    },

    async clearChat(phone) {
        if (!phone) {
            alert('Masukkan nomor telepon yang ingin dibersihkan antriannya.');
            return;
        }
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/whatsapp/clear-chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ phone })
            });
            const data = await res.json();
            if (data.success) {
                alert('Berhasil membersihkan antrian pesan nomor ' + phone);
            } else {
                alert(data.message || 'Gagal membersihkan antrian.');
            }
        } catch (e) {
            alert('Gagal menghubungi gateway.');
        }
    },

    // Transaction Receipt Modal Methods
    async openReceipt(type, id, defaultPhone = '') {
        this.receipt.type = type;
        this.receipt.id = id;
        this.receipt.phone = defaultPhone;
        this.receipt.formattedPhone = '';
        this.receipt.message = '';
        this.receipt.receiptData = null;
        this.receipt.sendAsPdf = true; // Format default PDF
        this.receipt.isLoading = true;
        this.receipt.isSending = false;
        this.receipt.sendSuccess = false;
        this.receipt.sendError = null;
        this.receipt.pdfUrl = null;
        this.receipt.sendUrl = null;
        this.receipt.sendPayload = null;
        this.receipt.submitButtonText = 'Kirim Nota PDF via WA';
        this.receiptModalOpen = true;

        let previewUrl = '';
        if (type === 'purchase') {
            previewUrl = `/whatsapp/purchases/${id}/preview`;
            this.receipt.title = 'Kirim Nota Timbang PDF via WhatsApp';
            this.receipt.subtitle = 'Kirim berkas dokumen PDF resmi langsung ke nomor WhatsApp pemasok';
            this.receipt.fileDescription = 'Dokumen PDF Resmi Kasir Rongsok (Format A5)';
            this.receipt.submitButtonText = 'Kirim Nota PDF via WA';
        } else if (type === 'sale') {
            previewUrl = `/whatsapp/sales/${id}/preview`;
            this.receipt.title = 'Kirim Faktur Penjualan Partai PDF';
            this.receipt.subtitle = 'Kirim berkas dokumen PDF faktur penjualan langsung ke WhatsApp pembeli';
            this.receipt.fileDescription = 'Dokumen PDF Resmi Faktur Penjualan Rongsok (A5)';
            this.receipt.submitButtonText = 'Kirim Faktur PDF via WA';
        } else if (type === 'debt_payment') {
            previewUrl = `/whatsapp/debt-payments/${id}/preview`;
            this.receipt.title = 'Kirim Bukti Pembayaran Kasbon PDF';
            this.receipt.subtitle = 'Kirim berkas kuitansi cicilan kasbon langsung ke nomor WhatsApp';
            this.receipt.fileDescription = 'Dokumen PDF Kuitansi Pembayaran Kasbon Resmi';
            this.receipt.submitButtonText = 'Kirim Bukti PDF via WA';
        }

        try {
            const res = await fetch(previewUrl, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                this.receipt.message = data.message || '';
                this.receipt.receiptData = data.receipt_data || null;
                if (data.phone) {
                    this.receipt.phone = data.phone;
                }
                this.receipt.formattedPhone = data.formatted_phone || '';
                if (data.filename) this.receipt.filename = data.filename;
                if (data.pdf_url) this.receipt.pdfUrl = data.pdf_url;
            } else {
                this.receipt.sendError = 'Gagal memuat data pratinjau nota.';
            }
        } catch (e) {
            this.receipt.sendError = 'Gagal memuat data nota: ' + e.message;
        } finally {
            this.receipt.isLoading = false;
            if (window.refreshIcons) window.refreshIcons();
        }
    },

    async openPdfModal(config) {
        this.receipt.type = config.type || 'custom_pdf';
        this.receipt.id = config.id || null;
        this.receipt.title = config.title || 'Kirim Dokumen PDF via WhatsApp';
        this.receipt.subtitle = config.subtitle || 'Kirim berkas dokumen PDF resmi langsung ke nomor WhatsApp';
        this.receipt.phone = config.phone || '';
        this.receipt.formattedPhone = '';
        this.receipt.filename = config.filename || 'Dokumen.pdf';
        this.receipt.fileDescription = config.fileDescription || 'Dokumen PDF Resmi Kasir Rongsok';
        this.receipt.pdfUrl = config.pdfUrl || null;
        this.receipt.sendUrl = config.sendUrl || null;
        this.receipt.sendPayload = config.sendPayload || {};
        this.receipt.submitButtonText = config.submitButtonText || 'Kirim PDF via WA';
        this.receipt.message = config.message || '';
        this.receipt.receiptData = null;
        this.receipt.sendAsPdf = true;
        this.receipt.isLoading = !!config.previewUrl;
        this.receipt.isSending = false;
        this.receipt.sendSuccess = false;
        this.receipt.sendError = null;
        this.receiptModalOpen = true;

        if (config.previewUrl) {
            try {
                const res = await fetch(config.previewUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.message) this.receipt.message = data.message;
                    if (data.phone && !this.receipt.phone) this.receipt.phone = data.phone;
                    if (data.pdf_filename) this.receipt.filename = data.pdf_filename;
                    if (data.pdf_url) this.receipt.pdfUrl = data.pdf_url;
                    if (data.file_description) this.receipt.fileDescription = data.file_description;
                } else {
                    this.receipt.sendError = 'Gagal memuat pratinjau pesan.';
                }
            } catch (e) {
                this.receipt.sendError = 'Gagal memuat data: ' + e.message;
            } finally {
                this.receipt.isLoading = false;
                if (window.refreshIcons) window.refreshIcons();
            }
        }
    },

    async sendReceipt() {
        if (!this.receipt.phone) {
            alert('Nomor WhatsApp tujuan belum diisi!');
            return;
        }
        this.receipt.isSending = true;
        this.receipt.sendSuccess = false;
        this.receipt.sendError = null;

        // Custom sendUrl (used by Global Recaps, Expenses, Agreements, Slips, etc.)
        if (this.receipt.sendUrl) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const payload = {
                    phone: this.receipt.phone,
                    message: this.receipt.message,
                    caption: this.receipt.message,
                    send_pdf: this.receipt.sendAsPdf,
                    filename: this.receipt.filename,
                    ...(this.receipt.sendPayload || {})
                };
                const res = await fetch(this.receipt.sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.receipt.sendSuccess = true;
                    setTimeout(() => {
                        this.receiptModalOpen = false;
                    }, 2200);
                } else {
                    this.receipt.sendError = data.message || 'Gagal mengirim berkas PDF melalui WhatsApp Gateway.';
                }
            } catch (e) {
                this.receipt.sendError = 'Koneksi ke gateway gagal: ' + e.message;
            } finally {
                this.receipt.isSending = false;
                if (window.refreshIcons) window.refreshIcons();
            }
            return;
        }

        let pdfBase64 = null;
        let filename = this.receipt.filename || 'Nota.pdf';

        // 1. Generate crisp PDF document if sendAsPdf is active
        if (this.receipt.sendAsPdf && this.receipt.receiptData) {
            try {
                let doc = null;
                if (this.receipt.type === 'purchase') {
                    doc = generatePurchaseReceiptPdf(this.receipt.receiptData);
                    filename = `Nota_${this.receipt.receiptData.invoiceNumber || 'Pembelian'}.pdf`;
                } else if (this.receipt.type === 'sale') {
                    doc = generateSalesInvoicePdf(this.receipt.receiptData);
                    filename = `Faktur_${this.receipt.receiptData.invoiceNumber || 'Penjualan'}.pdf`;
                } else if (this.receipt.type === 'debt_payment') {
                    doc = generateDebtPaymentPdf(this.receipt.receiptData);
                    filename = `BuktiBayar_${this.receipt.receiptData.paymentNumber || 'Kasbon'}.pdf`;
                }

                if (doc) {
                    pdfBase64 = doc.output('datauristring');
                }
            } catch (err) {
                console.warn('Gagal men-generate PDF di browser:', err);
            }
        }

        let sendUrl = '';
        let payload = {
            phone: this.receipt.phone,
            message: this.receipt.message,
            caption: this.receipt.message
        };

        if (pdfBase64) {
            payload.file = pdfBase64;
            payload.filename = filename;
        }

        if (this.receipt.type === 'purchase') {
            sendUrl = `/whatsapp/purchases/${this.receipt.id}/send`;
        } else if (this.receipt.type === 'sale') {
            sendUrl = `/whatsapp/sales/${this.receipt.id}/send`;
        } else if (this.receipt.type === 'debt_payment') {
            sendUrl = `/whatsapp/debt-payments/${this.receipt.id}/send`;
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.receipt.sendSuccess = true;
                setTimeout(() => {
                    this.receiptModalOpen = false;
                }, 2200);
            } else {
                this.receipt.sendError = data.message || 'Gagal mengirim berkas PDF melalui WhatsApp Gateway.';
            }
        } catch (e) {
            this.receipt.sendError = 'Koneksi ke gateway gagal: ' + e.message;
        } finally {
            this.receipt.isSending = false;
            if (window.refreshIcons) window.refreshIcons();
        }
    },

    downloadPdf() {
        if (this.receipt.pdfUrl) {
            window.open(this.receipt.pdfUrl, '_blank');
            return;
        }

        if (!this.receipt.receiptData) {
            alert('Data nota belum siap diunduh.');
            return;
        }

        let doc = null;
        let filename = this.receipt.filename || 'Nota.pdf';

        try {
            if (this.receipt.type === 'purchase') {
                doc = generatePurchaseReceiptPdf(this.receipt.receiptData);
                filename = `Nota_${this.receipt.receiptData.invoiceNumber || 'Pembelian'}.pdf`;
            } else if (this.receipt.type === 'sale') {
                doc = generateSalesInvoicePdf(this.receipt.receiptData);
                filename = `Faktur_${this.receipt.receiptData.invoiceNumber || 'Penjualan'}.pdf`;
            } else if (this.receipt.type === 'debt_payment') {
                doc = generateDebtPaymentPdf(this.receipt.receiptData);
                filename = `BuktiBayar_${this.receipt.receiptData.paymentNumber || 'Kasbon'}.pdf`;
            }

            if (doc) {
                // Tampilkan preview PDF di tab browser terlebih dahulu
                const blobUrl = doc.output('bloburl');
                window.open(blobUrl, '_blank');
            }
        } catch (e) {
            alert('Gagal mengunduh berkas PDF: ' + e.message);
        }
    },

    openWaMe() {
        const phone = (this.receipt.phone || '').replace(/\D/g, '');
        let formatted = phone;
        if (formatted.startsWith('0')) formatted = '62' + formatted.slice(1);
        else if (!formatted.startsWith('62') && formatted.length > 0) formatted = '62' + formatted;
        
        const url = `https://wa.me/${formatted}?text=${encodeURIComponent(this.receipt.message)}`;
        window.open(url, '_blank');
    }
});

window.openWaGatewayModal = () => {
    Alpine.store('waGateway').isOpen = true;
    Alpine.store('waGateway').fetchStatus();
};

window.openWaReceiptModal = (type, id, phone = '') => {
    Alpine.store('waGateway').openReceipt(type, id, phone);
};

window.openWaPdfModal = (config) => {
    Alpine.store('waGateway').openPdfModal(config);
};

Alpine.start();
