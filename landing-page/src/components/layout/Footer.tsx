"use client";

import React, { useState } from "react";
import { Recycle, MapPin, Phone, Mail, Clock, MessageCircle } from "lucide-react";
import Image from "next/image";
import { LandingSettings, CategoryItem } from "@/types/landing-page";
import { resolveImageUrl } from "@/lib/api";

interface FooterProps {
  settings: LandingSettings;
  categories: CategoryItem[];
}

export default function Footer({ settings, categories }: FooterProps) {
  const currentYear = new Date().getFullYear();
  const [imgError, setImgError] = useState(false);

  const rawLogo = settings.logo_url && !settings.logo_url.includes("icon-default.svg") ? settings.logo_url : "./logo.png";
  const logoSrc = imgError ? "./logo.png" : resolveImageUrl(rawLogo, "./logo.png");

  return (
    <footer className="relative bg-slate-50 border-t border-sky-100 text-slate-600 pt-16 pb-12 overflow-hidden">
      {/* Background glow accent */}
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-3/4 h-32 bg-sky-400/10 blur-[120px] pointer-events-none" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 lg:gap-8 pb-12 border-b border-slate-200">
          {/* Column 1: Brand & Bio (Span 2) */}
          <div className="lg:col-span-2 space-y-4">
            <div className="flex items-center gap-3">
              <div className="w-12 h-10 rounded-xl bg-white flex items-center justify-center shadow-sm border border-sky-200 p-0.5 overflow-hidden">
                <Image
                  src={logoSrc}
                  alt={settings.company_name}
                  width={48}
                  height={40}
                  unoptimized
                  onError={() => setImgError(true)}
                  className="object-contain w-full h-full"
                />
              </div>
              <div>
                <span className="font-black text-base tracking-tight text-slate-900 uppercase block">
                  {settings.company_name}
                </span>
                <span className="text-[10px] text-sky-700 font-bold uppercase tracking-wider">
                  Lelang Aset Barang Tak Bergerak & Pengelolaan Aset
                </span>
              </div>
            </div>

            <p className="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-md">
              {settings.company_tagline}. Perusahaan yang bergerak di bidang lelang aset berupa barang tak bergerak, survey & appraisal fisik, fasilitas tempat penyimpanan aset yang luas, penjualan cepat, serta pengelolaan scrap logam dan bongkaran.
            </p>

            {/* Social Media Links */}
            <div className="flex items-center gap-3 pt-2">
              {settings.social_instagram && (
                <a
                  href={settings.social_instagram}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:border-sky-400 hover:text-sky-600 text-slate-600 flex items-center justify-center transition-colors shadow-sm"
                  aria-label="Instagram"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                  </svg>
                </a>
              )}
              {settings.social_facebook && (
                <a
                  href={settings.social_facebook}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:border-sky-400 hover:text-sky-600 text-slate-600 flex items-center justify-center transition-colors shadow-sm"
                  aria-label="Facebook"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/>
                  </svg>
                </a>
              )}
              {settings.social_tiktok && (
                <a
                  href={settings.social_tiktok}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:border-sky-400 hover:text-sky-600 text-slate-600 flex items-center justify-center transition-colors shadow-sm"
                  aria-label="TikTok"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.97v7.54c-.03 2.21-.84 4.41-2.37 6.03-1.7 1.81-4.14 2.89-6.66 2.83-2.62-.05-5.1-1.3-6.68-3.37-1.59-2.07-2.12-4.83-1.46-7.36.66-2.52 2.48-4.59 4.9-5.51 1.25-.47 2.6-.64 3.93-.52v4.18c-.89-.13-1.83.05-2.58.55-.77.51-1.28 1.34-1.39 2.26-.14 1.14.36 2.3 1.26 2.97.91.68 2.14.77 3.14.23.63-.34 1.05-.96 1.18-1.67.07-.38.08-.77.08-1.16V.02h.65z"/>
                  </svg>
                </a>
              )}
              {settings.social_youtube && (
                <a
                  href={settings.social_youtube}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:border-sky-400 hover:text-sky-600 text-slate-600 flex items-center justify-center transition-colors shadow-sm"
                  aria-label="YouTube"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                  </svg>
                </a>
              )}
            </div>
          </div>

          {/* Column 2: Quick Links */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-900">Navigasi Cepat</h4>
            <ul className="space-y-2 text-xs">
              <li>
                <a href="#hero" className="hover:text-sky-600 transition-colors">
                  Beranda Depo
                </a>
              </li>
              <li>
                <a href="#about" className="hover:text-sky-600 transition-colors">
                  Tentang Perusahaan
                </a>
              </li>
              <li>
                <a href="#services" className="hover:text-sky-600 transition-colors">
                  Layanan & Jasa Jemput
                </a>
              </li>
              <li>
                <a href="#categories" className="hover:text-sky-600 transition-colors">
                  Jenis & Harga Rongsok
                </a>
              </li>
              <li>
                <a href="#process" className="hover:text-sky-600 transition-colors">
                  Alur Penimbangan
                </a>
              </li>
              <li>
                <a href="#gallery" className="hover:text-sky-600 transition-colors">
                  Dokumentasi Gudang
                </a>
              </li>
              <li>
                <a href="#faq" className="hover:text-sky-600 transition-colors">
                  Tanya Jawab (FAQ)
                </a>
              </li>
            </ul>
          </div>

          {/* Column 3: Material Categories */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-900">Material Daur Ulang</h4>
            <ul className="space-y-2 text-xs">
              {categories.slice(0, 6).map((cat) => (
                <li key={cat.id}>
                  <a href="#categories" className="hover:text-sky-600 transition-colors flex items-center gap-1.5">
                    <span className="w-1.5 h-1.5 rounded-full bg-sky-500" />
                    <span>{cat.name}</span>
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 4: Contact & Office */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-900">Kontak & Operasional</h4>
            <ul className="space-y-2.5 text-xs">
              <li className="flex items-start gap-2.5">
                <MapPin className="w-4 h-4 text-sky-600 shrink-0 mt-0.5" />
                <span className="leading-snug text-slate-600">{settings.company_address}</span>
              </li>
              <li className="flex items-center gap-2.5">
                <Phone className="w-4 h-4 text-sky-600 shrink-0" />
                <a href={`tel:${settings.company_phone.replace(/[^0-9]/g, "")}`} className="hover:text-slate-900 transition-colors">
                  {settings.company_phone}
                </a>
              </li>
              <li className="flex items-center gap-2.5">
                <MessageCircle className="w-4 h-4 text-sky-600 shrink-0" />
                <a href={settings.whatsapp_url} target="_blank" rel="noopener noreferrer" className="hover:text-slate-900 transition-colors">
                  WhatsApp: {settings.whatsapp_raw}
                </a>
              </li>
              <li className="flex items-center gap-2.5">
                <Mail className="w-4 h-4 text-sky-600 shrink-0" />
                <span className="text-slate-600">{settings.email}</span>
              </li>
              <li className="flex items-start gap-2.5 pt-1">
                <Clock className="w-4 h-4 text-sky-600 shrink-0 mt-0.5" />
                <span className="text-slate-600 leading-tight">{settings.operating_hours}</span>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom Bar: Copyright & Admin Portal Link */}
        <div className="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
          <p>© {currentYear} {settings.company_name}. Hak Cipta Dilindungi Undang-Undang.</p>
          <div className="flex items-center gap-4">
            <span className="text-sky-700 font-semibold">Sistem POS & Kasir Timbangan Terintegrasi</span>
            <span className="text-slate-300">|</span>
            <a
              href="http://localhost:8000/login"
              target="_blank"
              rel="noopener noreferrer"
              className="text-slate-600 hover:text-sky-700 transition-colors flex items-center gap-1 font-semibold"
            >
              <span>Portal Admin Kasir</span>
              <span>&rarr;</span>
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
}
