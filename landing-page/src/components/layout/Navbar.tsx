"use client";

import React, { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Menu, X, PhoneCall, Recycle, MessageCircle } from "lucide-react";
import Image from "next/image";
import { LandingSettings } from "@/types/landing-page";
import { resolveImageUrl } from "@/lib/api";

interface NavbarProps {
  settings: LandingSettings;
}

const NAV_LINKS = [
  { label: "Home", href: "#hero" },
  { label: "Tentang Kami", href: "#about" },
  { label: "Layanan", href: "#services" },
  { label: "Jenis Rongsok", href: "#categories" },
  { label: "Alur Transaksi", href: "#process" },
  { label: "Galeri", href: "#gallery" },
  { label: "Lokasi", href: "#location" },
  { label: "FAQ", href: "#faq" },
  { label: "Kontak", href: "#contact" },
];

export default function Navbar({ settings }: NavbarProps) {
  const [isScrolled, setIsScrolled] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [imgError, setImgError] = useState(false);

  const rawLogo = settings.logo_url && !settings.logo_url.includes("icon-default.svg") ? settings.logo_url : "./logo.png";
  const logoSrc = imgError ? "./logo.png" : resolveImageUrl(rawLogo, "./logo.png");

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  const handleLinkClick = (e: React.MouseEvent<HTMLAnchorElement>, href: string) => {
    if (href.startsWith("#")) {
      e.preventDefault();
      setMobileMenuOpen(false);
      const el = document.querySelector(href);
      if (el) {
        el.scrollIntoView({ behavior: "smooth" });
      }
    }
  };

  return (
    <>
      <header
        className={`fixed top-0 left-0 right-0 z-40 transition-all duration-300 ${
          isScrolled
            ? "bg-white/95 backdrop-blur-md border-b border-slate-200/90 shadow-sm py-2 sm:py-3"
            : "bg-white/85 backdrop-blur-sm py-2.5 sm:py-3.5 border-b border-slate-100"
        }`}
      >
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between">
            {/* Logo & Brand Name */}
            <a
              href="#hero"
              onClick={(e) => handleLinkClick(e, "#hero")}
              className="flex items-center gap-2.5 sm:gap-3 group min-w-0"
            >
              <div className="relative w-10 h-8 sm:w-12 sm:h-10 rounded-xl bg-white flex items-center justify-center shadow-xs border border-sky-200 group-hover:scale-105 transition-transform overflow-hidden p-0.5 shrink-0">
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
              <div className="flex flex-col min-w-0 max-w-[180px] xs:max-w-[220px] sm:max-w-xs md:max-w-sm lg:max-w-md">
                <span className="font-black text-xs sm:text-base tracking-tight text-slate-900 uppercase group-hover:text-sky-600 transition-colors truncate">
                  {settings.company_name}
                </span>
              </div>
            </a>

            {/* Desktop Navigation Menu */}
            <nav className="hidden lg:flex items-center gap-1 xl:gap-2 bg-slate-100/90 border border-slate-200/80 px-4 py-1.5 rounded-full backdrop-blur-sm shadow-inner">
              {NAV_LINKS.map((link) => (
                <a
                  key={link.label}
                  href={link.href}
                  onClick={(e) => handleLinkClick(e, link.href)}
                  className="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-sky-600 hover:bg-white rounded-full transition-all"
                >
                  {link.label}
                </a>
              ))}
            </nav>

            {/* Right CTAs */}
            <div className="hidden sm:flex items-center gap-3">
              <a
                href={settings.whatsapp_url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white text-xs font-bold shadow-md shadow-sky-500/25 transition-all hover:scale-105"
              >
                <MessageCircle className="w-4 h-4 fill-white" />
                <span>Hubungi Kami</span>
              </a>
            </div>

            {/* Mobile Hamburger Button */}
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="lg:hidden p-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-100 transition-colors"
              aria-label="Buka Menu"
            >
              {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </header>

      {/* Mobile Menu Drawer */}
      <AnimatePresence>
        {mobileMenuOpen && (
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-x-0 top-[68px] z-30 lg:hidden bg-white/98 border-b border-sky-100 backdrop-blur-xl px-6 py-6 shadow-2xl space-y-4"
          >
            <div className="flex flex-col space-y-2">
              {NAV_LINKS.map((link) => (
                <a
                  key={link.label}
                  href={link.href}
                  onClick={(e) => handleLinkClick(e, link.href)}
                  className="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:text-sky-600 hover:bg-sky-50 transition-colors"
                >
                  {link.label}
                </a>
              ))}
            </div>

            <div className="pt-4 border-t border-slate-200 flex flex-col gap-2.5">
              <a
                href={settings.whatsapp_url}
                target="_blank"
                rel="noopener noreferrer"
                onClick={() => setMobileMenuOpen(false)}
                className="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 text-white font-bold text-xs shadow-md shadow-sky-500/20 transition"
              >
                <MessageCircle className="w-4 h-4 fill-white" />
                <span>Chat WhatsApp ({settings.whatsapp_raw})</span>
              </a>

              <a
                href={`tel:${settings.company_phone.replace(/[^0-9]/g, "")}`}
                onClick={() => setMobileMenuOpen(false)}
                className="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:text-slate-900 text-xs font-semibold hover:bg-slate-50 transition"
              >
                <PhoneCall className="w-3.5 h-3.5 text-emerald-600" />
                <span>Telepon Kantor</span>
              </a>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}
