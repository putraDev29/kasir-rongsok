"use client";

import React, { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Star, ChevronLeft, ChevronRight, Quote, PlusCircle, CheckCircle2, X, Loader2 } from "lucide-react";
import Image from "next/image";
import { TestimonialItem } from "@/types/landing-page";
import { submitCustomerTestimonial } from "@/services/landing-page.service";

interface TestimonialsSectionProps {
  testimonials: TestimonialItem[];
}

export default function TestimonialsSection({ testimonials: initialTestimonials }: TestimonialsSectionProps) {
  const [testimonials, setTestimonials] = useState<TestimonialItem[]>(initialTestimonials || []);
  const [currentIndex, setCurrentIndex] = useState(0);

  // Modal & Form State
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [formData, setFormData] = useState({
    client_name: "",
    client_role: "",
    rating: 5,
    content: "",
  });
  const [hoverRating, setHoverRating] = useState(0);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [statusMessage, setStatusMessage] = useState<{ type: "success" | "error"; text: string } | null>(null);

  const nextSlide = () => {
    if (testimonials.length === 0) return;
    setCurrentIndex((prev) => (prev + 1) % testimonials.length);
  };

  const prevSlide = () => {
    if (testimonials.length === 0) return;
    setCurrentIndex((prev) => (prev - 1 + testimonials.length) % testimonials.length);
  };

  const handleSubmitReview = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.client_name.trim() || !formData.content.trim()) {
      setStatusMessage({ type: "error", text: "Mohon lengkapi nama dan ulasan Anda." });
      return;
    }

    setIsSubmitting(true);
    setStatusMessage(null);

    try {
      const res = await submitCustomerTestimonial({
        client_name: formData.client_name.trim(),
        client_role: formData.client_role.trim() || "Pelanggan Setia",
        rating: formData.rating,
        content: formData.content.trim(),
      });

      if (res.success) {
        const newReview: TestimonialItem = res.data || {
          id: Date.now(),
          client_name: formData.client_name.trim(),
          client_role: formData.client_role.trim() || "Pelanggan Setia",
          rating: formData.rating,
          content: formData.content.trim(),
          order: 99,
        };

        // Add to beginning of testimonials
        setTestimonials((prev) => [newReview, ...prev]);
        setCurrentIndex(0);
        setStatusMessage({
          type: "success",
          text: "Terima kasih banyak! Ulasan Anda berhasil dikirim dan kini tampil di website.",
        });

        // Reset form after delay
        setTimeout(() => {
          setFormData({ client_name: "", client_role: "", rating: 5, content: "" });
          setIsModalOpen(false);
          setStatusMessage(null);
        }, 2000);
      } else {
        setStatusMessage({
          type: "error",
          text: res.message || "Gagal mengirim ulasan. Silakan coba kembali.",
        });
      }
    } catch (err) {
      console.error("Submit testimonial error:", err);
      setStatusMessage({
        type: "error",
        text: "Terjadi gangguan jaringan. Silakan coba kembali sesaat lagi.",
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <section id="testimonials" className="py-20 relative bg-sky-50/40 border-t border-sky-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <motion.div
          initial={false}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.2 }}
          transition={{ duration: 0.5 }}
          className="text-center max-w-2xl mx-auto space-y-3 mb-12"
        >
          <span className="text-xs font-bold uppercase tracking-widest text-sky-700">
            Ulasan Kepuasan Pelanggan
          </span>
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 tracking-tight">
            Apa Kata Mitra & Pelanggan Depo Kami?
          </h2>
          <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Kepercayaan pabrik, bengkel, toko logistik, dan masyarakat dibangun atas dasar timbangan akurat presisi dan pembayaran kontan tepat janji.
          </p>

          {/* Trigger Button: Tulis Ulasan */}
          <div className="pt-2">
            <button
              onClick={() => setIsModalOpen(true)}
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs shadow-md shadow-sky-500/20 hover:scale-105 transition-all"
            >
              <PlusCircle className="w-4 h-4" />
              <span>Tulis Ulasan Anda</span>
            </button>
          </div>
        </motion.div>

        {/* Carousel / Interactive Card View */}
        {testimonials.length > 0 && (
          <div className="max-w-4xl mx-auto relative">
            <AnimatePresence mode="wait">
              <motion.div
                key={currentIndex}
                initial={false}
                animate={{ opacity: 1, scale: 1 }}
                exit={{ opacity: 0, scale: 0.96 }}
                transition={{ duration: 0.3 }}
                className="rounded-3xl bg-white border border-sky-100 p-8 sm:p-12 relative shadow-xl shadow-sky-100/50 space-y-6"
              >
                <Quote className="w-12 h-12 text-sky-100 absolute top-6 right-6 pointer-events-none" />

                {/* Star Rating */}
                <div className="flex items-center gap-1 text-amber-400">
                  {Array.from({ length: testimonials[currentIndex]?.rating || 5 }).map((_, i) => (
                    <Star key={i} className="w-5 h-5 fill-amber-400 text-amber-400" />
                  ))}
                </div>

                {/* Testimonial Quote */}
                <p className="text-base sm:text-lg md:text-xl text-slate-700 leading-relaxed italic font-normal">
                  "{testimonials[currentIndex]?.content}"
                </p>

                {/* Client Info */}
                <div className="flex items-center gap-4 pt-4 border-t border-slate-100">
                  <div className="relative w-12 h-12 rounded-full overflow-hidden bg-sky-100 border-2 border-sky-500 shrink-0">
                    {testimonials[currentIndex]?.avatar_url ? (
                      <Image
                        src={testimonials[currentIndex].avatar_url!}
                        alt={testimonials[currentIndex].client_name}
                        fill
                        unoptimized
                        className="object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center font-bold text-sky-700">
                        {testimonials[currentIndex]?.client_name.charAt(0)}
                      </div>
                    )}
                  </div>

                  <div>
                    <h4 className="text-sm sm:text-base font-bold text-slate-900">
                      {testimonials[currentIndex]?.client_name}
                    </h4>
                    <p className="text-xs text-sky-700 font-semibold">
                      {testimonials[currentIndex]?.client_role}
                    </p>
                  </div>
                </div>
              </motion.div>
            </AnimatePresence>

            {/* Carousel Arrows & Dots */}
            <div className="flex items-center justify-between mt-6 px-2">
              <div className="flex items-center gap-1.5 flex-wrap">
                {testimonials.map((_, idx) => (
                  <button
                    key={idx}
                    onClick={() => setCurrentIndex(idx)}
                    className={`h-2 rounded-full transition-all ${
                      currentIndex === idx ? "w-8 bg-sky-600" : "w-2 bg-slate-300 hover:bg-slate-400"
                    }`}
                    aria-label={`Lihat ulasan ${idx + 1}`}
                  />
                ))}
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={prevSlide}
                  className="p-2.5 rounded-full bg-white hover:bg-sky-50 text-slate-700 border border-slate-300 shadow-sm transition-colors"
                  aria-label="Sebelumnya"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <button
                  onClick={nextSlide}
                  className="p-2.5 rounded-full bg-white hover:bg-sky-50 text-slate-700 border border-slate-300 shadow-sm transition-colors"
                  aria-label="Berikutnya"
                >
                  <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Modal: Tulis Ulasan / Customer Review Dialog */}
        <AnimatePresence>
          {isModalOpen && (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
              {/* Backdrop */}
              <motion.div
                initial={false}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                onClick={() => !isSubmitting && setIsModalOpen(false)}
                className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
              />

              {/* Modal Box */}
              <motion.div
                initial={false}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 15 }}
                transition={{ duration: 0.2 }}
                className="relative w-full max-w-lg rounded-3xl bg-white border border-sky-100 p-6 sm:p-8 shadow-2xl z-10 space-y-5"
              >
                {/* Header */}
                <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                  <div>
                    <h3 className="text-lg font-black text-slate-900">Tulis Ulasan Pelanggan</h3>
                    <p className="text-xs text-slate-500 mt-0.5">
                      Bagikan pengalaman Anda bertransaksi dengan depo kami.
                    </p>
                  </div>
                  <button
                    onClick={() => !isSubmitting && setIsModalOpen(false)}
                    className="p-2 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 transition-colors"
                    disabled={isSubmitting}
                  >
                    <X className="w-5 h-5" />
                  </button>
                </div>

                {/* Status Alert */}
                {statusMessage && (
                  <div
                    className={`p-3.5 rounded-xl text-xs flex items-center gap-2 ${
                      statusMessage.type === "success"
                        ? "bg-sky-50 border border-sky-200 text-sky-800"
                        : "bg-rose-50 border border-rose-200 text-rose-800"
                    }`}
                  >
                    {statusMessage.type === "success" && <CheckCircle2 className="w-4 h-4 shrink-0 text-sky-600" />}
                    <span>{statusMessage.text}</span>
                  </div>
                )}

                {/* Form */}
                <form onSubmit={handleSubmitReview} className="space-y-4">
                  {/* Rating Stars Picker */}
                  <div className="space-y-1.5">
                    <label className="text-xs font-bold text-slate-700 block">Rating Kepuasan</label>
                    <div className="flex items-center gap-1.5">
                      {[1, 2, 3, 4, 5].map((star) => (
                        <button
                          type="button"
                          key={star}
                          onClick={() => setFormData({ ...formData, rating: star })}
                          onMouseEnter={() => setHoverRating(star)}
                          onMouseLeave={() => setHoverRating(0)}
                          className="p-1 hover:scale-125 transition-transform"
                        >
                          <Star
                            className={`w-6 h-6 transition-colors ${
                              (hoverRating || formData.rating) >= star
                                ? "text-amber-400 fill-amber-400"
                                : "text-slate-200"
                            }`}
                          />
                        </button>
                      ))}
                      <span className="text-xs font-bold text-slate-600 ml-2">
                        {hoverRating || formData.rating} / 5 Bintang
                      </span>
                    </div>
                  </div>

                  {/* Name */}
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-700 block">
                      Nama Lengkap / Nama Bisnis <span className="text-rose-500">*</span>
                    </label>
                    <input
                      type="text"
                      required
                      placeholder="Contoh: Bpk. Wahyu / Bengkel Mulia"
                      value={formData.client_name}
                      onChange={(e) => setFormData({ ...formData, client_name: e.target.value })}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors"
                    />
                  </div>

                  {/* Role / Entity */}
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-700 block">
                      Peran / Usaha (Opsional)
                    </label>
                    <input
                      type="text"
                      placeholder="Contoh: Pemilik Bengkel Bubut / Mitra Pengepul"
                      value={formData.client_role}
                      onChange={(e) => setFormData({ ...formData, client_role: e.target.value })}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors"
                    />
                  </div>

                  {/* Review Text */}
                  <div className="space-y-1">
                    <label className="text-xs font-bold text-slate-700 block">
                      Isi Ulasan / Pengalaman <span className="text-rose-500">*</span>
                    </label>
                    <textarea
                      required
                      rows={4}
                      placeholder="Ceritakan pengalaman Anda mengenai transparansi timbangan, ketepatan pembayaran, atau layanan penjemputan armada..."
                      value={formData.content}
                      onChange={(e) => setFormData({ ...formData, content: e.target.value })}
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors resize-none"
                    />
                  </div>

                  {/* Submit Button */}
                  <div className="pt-2 flex items-center justify-end gap-3">
                    <button
                      type="button"
                      onClick={() => setIsModalOpen(false)}
                      disabled={isSubmitting}
                      className="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
                    >
                      Batal
                    </button>

                    <button
                      type="submit"
                      disabled={isSubmitting}
                      className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs shadow-md shadow-sky-500/25 transition-all disabled:opacity-50"
                    >
                      {isSubmitting ? (
                        <>
                          <Loader2 className="w-4 h-4 animate-spin" />
                          <span>Menyimpan...</span>
                        </>
                      ) : (
                        <span>Kirim Ulasan Sekarang</span>
                      )}
                    </button>
                  </div>
                </form>
              </motion.div>
            </div>
          )}
        </AnimatePresence>
      </div>
    </section>
  );
}
