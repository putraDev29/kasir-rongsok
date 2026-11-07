"use client";

import React, { useState } from "react";
import { motion } from "framer-motion";
import {
  MessageCircle,
  Phone,
  Mail,
  MapPin,
  Clock,
  Send,
  CheckCircle,
  AlertCircle,
  Loader2,
} from "lucide-react";
import { LandingSettings } from "@/types/landing-page";
import { submitContactInquiry } from "@/services/landing-page.service";

interface ContactSectionProps {
  settings: LandingSettings;
}

export default function ContactSection({ settings }: ContactSectionProps) {
  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    material_type: "",
    estimated_weight: "",
    address: "",
    message: "",
  });

  const [loading, setLoading] = useState(false);
  const [successResponse, setSuccessResponse] = useState<{
    message: string;
    waUrl?: string;
  } | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setErrorMessage(null);

    try {
      const res = await submitContactInquiry(formData);
      if (res.success) {
        setSuccessResponse({
          message: res.message || "Permintaan penjemputan Anda berhasil kami terima!",
          waUrl: res.data?.direct_whatsapp_url || settings.whatsapp_url,
        });
        setFormData({
          name: "",
          phone: "",
          material_type: "",
          estimated_weight: "",
          address: "",
          message: "",
        });
      } else {
        setErrorMessage("Gagal mengirim data. Silakan coba lagi atau hubungi via WhatsApp langsung.");
      }
    } catch (err) {
      setErrorMessage("Koneksi gagal. Silakan hubungi langsung via WhatsApp.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <section id="contact" className="py-20 relative bg-sky-50/30 border-t border-sky-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <motion.div
          initial={false}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.2 }}
          transition={{ duration: 0.5 }}
          className="text-center max-w-2xl mx-auto space-y-3 mb-16"
        >
          <span className="text-xs font-bold uppercase tracking-widest text-sky-700">
            Hubungi Kami
          </span>
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 tracking-tight">
            Konsultasikan Kebutuhan Lelang & Penjualan Aset
          </h2>
          <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Besar harapan kami untuk diberikan kesempatan kerjasama dengan Anda untuk membantu penjualan aset perusahaan atau pribadi melalui sistem lelang terpercaya.
          </p>
        </motion.div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
          {/* Left Column: Direct Contact Info (5 cols) */}
          <motion.div
            initial={false}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{ duration: 0.5 }}
            className="lg:col-span-5 space-y-6"
          >
            <div className="rounded-3xl bg-white border border-sky-100 p-6 sm:p-8 space-y-6 shadow-xl shadow-sky-100/50">
              <h3 className="text-lg font-bold text-slate-900">Layanan Kontak Resmi</h3>

              <div className="space-y-4 text-xs sm:text-sm">
                {/* WhatsApp */}
                <a
                  href={settings.whatsapp_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-3.5 p-3 rounded-2xl bg-slate-50 hover:bg-sky-50 border border-slate-200 hover:border-sky-300 transition-all group"
                >
                  <div className="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                    <MessageCircle className="w-5 h-5 fill-sky-600 text-sky-600" />
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-500 font-bold uppercase">WhatsApp Langsung</span>
                    <p className="font-bold text-slate-900 group-hover:text-sky-700 transition-colors">
                      {settings.whatsapp_raw}
                    </p>
                  </div>
                </a>

                {/* Telephone */}
                <a
                  href={`tel:${settings.company_phone.replace(/[^0-9]/g, "")}`}
                  className="flex items-center gap-3.5 p-3 rounded-2xl bg-slate-50 hover:bg-sky-50 border border-slate-200 hover:border-sky-300 transition-all group"
                >
                  <div className="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <Phone className="w-5 h-5 text-blue-600" />
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-500 font-bold uppercase">Telepon Kantor</span>
                    <p className="font-bold text-slate-900 group-hover:text-sky-700 transition-colors">
                      {settings.company_phone}
                    </p>
                  </div>
                </a>

                {/* Email */}
                <div className="flex items-center gap-3.5 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                  <div className="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                    <Mail className="w-5 h-5 text-sky-600" />
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-500 font-bold uppercase">Email Kantor</span>
                    <p className="font-bold text-slate-900">{settings.email}</p>
                  </div>
                </div>

                {/* Address */}
                <div className="flex items-start gap-3.5 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                  <div className="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0 mt-0.5">
                    <MapPin className="w-5 h-5 text-sky-600" />
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-500 font-bold uppercase">Alamat Kantor & Depo</span>
                    <p className="font-bold text-slate-900 leading-snug">{settings.company_address}</p>
                  </div>
                </div>

                {/* Hours */}
                <div className="flex items-start gap-3.5 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                  <div className="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                    <Clock className="w-5 h-5 text-amber-600" />
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-500 font-bold uppercase">Jam Kerja</span>
                    <p className="font-bold text-slate-900 leading-snug">{settings.operating_hours}</p>
                  </div>
                </div>
              </div>
            </div>
          </motion.div>

          {/* Right Column: Inquiry Form (7 cols) */}
          <motion.div
            initial={false}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{ duration: 0.5 }}
            className="lg:col-span-7 rounded-3xl bg-white border border-sky-100 p-6 sm:p-8 shadow-xl shadow-sky-100/50"
          >
            <h3 className="text-lg font-bold text-slate-900 mb-2">Formulir Permintaan Penawaran & Penjemputan</h3>
            <p className="text-xs text-slate-600 mb-6">
              Isi data di bawah ini, tim kasir & logistik kami akan segera merespons estimasi harga dan jadwal armada.
            </p>

            {successResponse ? (
              <div className="p-6 rounded-2xl bg-sky-50 border border-sky-300 text-center space-y-4">
                <CheckCircle className="w-12 h-12 text-sky-600 mx-auto" />
                <h4 className="text-base font-bold text-slate-900">{successResponse.message}</h4>
                <p className="text-xs text-slate-600">
                  Untuk percepatan konfirmasi langsung ke kasir WhatsApp kami, silakan klik tombol di bawah:
                </p>
                {successResponse.waUrl && (
                  <a
                    href={successResponse.waUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs shadow-md shadow-sky-500/20 transition"
                  >
                    <MessageCircle className="w-4 h-4 fill-white" />
                    <span>Lanjutkan Konfirmasi ke WhatsApp</span>
                  </a>
                )}
                <div className="pt-2">
                  <button
                    type="button"
                    onClick={() => setSuccessResponse(null)}
                    className="text-xs text-slate-500 hover:text-sky-700 underline"
                  >
                    Kirim formulir baru
                  </button>
                </div>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-4">
                {errorMessage && (
                  <div className="p-3 rounded-xl bg-rose-50 border border-rose-300 text-rose-800 text-xs flex items-center gap-2">
                    <AlertCircle className="w-4 h-4 shrink-0" />
                    <span>{errorMessage}</span>
                  </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      Nama Lengkap / Perusahaan *
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      placeholder="Contoh: Bpk. Budi Santoso"
                      className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      Nomor WhatsApp / HP Aktif *
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      placeholder="Contoh: 081234567890"
                      className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      Jenis Material Rongsokan
                    </label>
                    <input
                      type="text"
                      value={formData.material_type}
                      onChange={(e) => setFormData({ ...formData, material_type: e.target.value })}
                      placeholder="Besi Tua, Kardus, Tembaga, dll"
                      className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      Estimasi Berat / Kuantitas
                    </label>
                    <input
                      type="text"
                      value={formData.estimated_weight}
                      onChange={(e) => setFormData({ ...formData, estimated_weight: e.target.value })}
                      placeholder="Contoh: 500 kg / 3 Ton"
                      className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Alamat Penjemputan / Lokasi Gudang
                  </label>
                  <input
                    type="text"
                    value={formData.address}
                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                    placeholder="Nama jalan, kecamatan, kawasan industri..."
                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Catatan Tambahan (Kondisi Barang, Jadwal, dsb)
                  </label>
                  <textarea
                    rows={3}
                    value={formData.message}
                    onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                    placeholder="Tuliskan catatan khusus bila ada..."
                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors"
                  />
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-sky-500/25 transition-all flex items-center justify-center gap-2 hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50"
                >
                  {loading ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      <span>Sedang Mengirim...</span>
                    </>
                  ) : (
                    <>
                      <Send className="w-4 h-4" />
                      <span>Kirim Permintaan Penawaran</span>
                    </>
                  )}
                </button>
              </form>
            )}
          </motion.div>
        </div>
      </div>
    </section>
  );
}
