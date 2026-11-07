"use client";

import React from "react";
import { motion } from "framer-motion";
import { MessageCircle, ArrowRight, ShieldCheck, Scale, Truck, MapPin, ChevronDown } from "lucide-react";
import Image from "next/image";
import { HeroData, LandingSettings, StatisticItem, StoryItem } from "@/types/landing-page";
import { HeroStoryViewer } from "@/components/story/HeroStoryViewer";
import { resolveImageUrl } from "@/lib/api";

interface HeroSectionProps {
  hero: HeroData;
  settings: LandingSettings;
  statistics: StatisticItem[];
  stories?: StoryItem[];
}

export default function HeroSection({ hero, settings, stories }: HeroSectionProps) {
  const handleScrollToCategories = (e: React.MouseEvent) => {
    e.preventDefault();
    const el = document.querySelector("#categories");
    if (el) el.scrollIntoView({ behavior: "smooth" });
  };

  const handleScrollToAbout = (e: React.MouseEvent) => {
    e.preventDefault();
    const el = document.querySelector("#about");
    if (el) el.scrollIntoView({ behavior: "smooth" });
  };

  // Dynamic Bento Visuals configured from Landing Page Management
  const mainImage = resolveImageUrl(
    hero.main_image || hero.background_image,
    "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1200&auto=format&fit=crop"
  );
  const mainTitle = hero.main_title || "Penimbangan Digital & Penampungan Logam Scrap";
  const mainSubtitle = hero.main_subtitle || "Depo Srengseng Sawah • Jagakarsa";

  const sub1Image = resolveImageUrl(
    hero.sub1_image,
    "https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?q=80&w=800&auto=format&fit=crop"
  );
  const sub1Title = hero.sub1_title || "Tembaga, Kuningan & Nikel";
  const sub1Tag = hero.sub1_tag || "Logam Presisi";

  const sub2Image = resolveImageUrl(
    hero.sub2_image,
    "https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?q=80&w=800&auto=format&fit=crop"
  );
  const sub2Title = hero.sub2_title || "Truk & Pick-Up Siap 24 Jam";
  const sub2Tag = hero.sub2_tag || "Armada Jemput";

  const sub3Image = resolveImageUrl(
    hero.sub3_image,
    "https://images.unsplash.com/photo-1526951521990-620dc14c214b?q=80&w=800&auto=format&fit=crop"
  );
  const sub3Title = hero.sub3_title || "Bongkar Gedung & Tabung Gas";
  const sub3Tag = hero.sub3_tag || "Bongkar & Gas";

  return (
    <section
      id="hero"
      className="relative w-full min-h-[calc(100vh-4rem)] flex flex-col justify-center pt-20 sm:pt-24 lg:pt-28 pb-10 sm:pb-14 bg-gradient-to-b from-sky-50/70 via-white to-slate-50 overflow-visible"
    >
      {/* Subtle ambient lighting */}
      <div className="absolute top-10 left-1/3 w-96 h-96 bg-sky-200/30 rounded-full blur-[140px] pointer-events-none" />
      <div className="absolute top-20 right-10 w-80 h-80 bg-blue-200/25 rounded-full blur-[130px] pointer-events-none" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex flex-col gap-5 sm:gap-6 lg:gap-8 relative z-10">
        {/* 1. Header Bar: Headline, Tagline & CTAs */}
        <motion.div
          initial={false}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
          className="flex flex-col lg:flex-row lg:items-center justify-between gap-4"
        >
          {/* Headline & Concise Subtitle */}
          <div className="space-y-1.5 sm:space-y-2 max-w-3xl">

            <h1 className="text-xl sm:text-2xl md:text-3xl lg:text-[34px] font-extrabold text-slate-900 tracking-tight leading-snug">
              {hero.headline || settings.hero_headline || "Lelang Aset Barang Tak Bergerak & Pengelolaan Aset Terpercaya"}
            </h1>

            <p className="text-xs sm:text-sm md:text-base text-slate-600 font-normal leading-relaxed line-clamp-3">
              {hero.subheadline || settings.hero_subheadline || "PT. Seberaya Akses Multi melayani lelang aset barang tak bergerak, survey & appraisal obyektif, penyimpanan aset luas, dan penjualan cepat dengan kepastian legalitas."}
            </p>
          </div>

          {/* Action Buttons */}
          <div className="flex items-center gap-2.5 shrink-0">
            <a
              href={settings.whatsapp_url}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-sky-500/25 transition-all hover:scale-105 active:scale-95"
            >
              <MessageCircle className="w-4 h-4 fill-white" />
              <span>Chat WhatsApp</span>
            </a>

            <button
              onClick={handleScrollToCategories}
              className="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl bg-white hover:bg-sky-50 text-slate-700 hover:text-sky-700 font-semibold text-xs sm:text-sm border border-slate-200 shadow-xs transition-all"
            >
              <span>Cek Harga</span>
              <ArrowRight className="w-4 h-4 text-sky-600" />
            </button>
          </div>
        </motion.div>

        {/* 2. POWERFUL VISUAL SHOWCASE GRID */}
        <motion.div
          initial={false}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.6, delay: 0.1 }}
          className="w-full grid grid-cols-1 lg:grid-cols-12 gap-3.5 sm:gap-4 lg:gap-5"
        >
          {/* Main Hero Photo: Interactive Story Reel (Unlimited photos, latest first, likes & auto-translated comments) */}
          <div className="lg:col-span-7 h-[360px] sm:h-[420px] md:h-[460px] lg:h-[480px] xl:h-[500px] w-full relative">
            <HeroStoryViewer
              initialStories={stories}
              settings={settings}
              fallbackImage={mainImage}
              fallbackTitle={mainTitle}
              fallbackSubtitle={mainSubtitle}
            />
          </div>

          {/* Secondary Visual Cards: 3 cards */}
          <div className="lg:col-span-5 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-1 gap-3 sm:gap-3.5 h-auto lg:h-[480px] xl:h-[500px]">
            {/* Card 1: Scrap Tembaga & Kuningan */}
            <div className="relative h-[120px] sm:h-[140px] lg:h-auto lg:flex-1 rounded-2xl overflow-hidden shadow-xs border border-sky-100 group">
              <Image
                src={sub1Image}
                alt={sub1Title}
                fill
                unoptimized
                className="object-cover group-hover:scale-105 transition-transform duration-500"
                sizes="(max-width: 640px) 100vw, (max-width: 1024px) 33vw, 40vw"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent" />
              <div className="absolute bottom-2.5 left-3 right-3 text-white">
                <span className="text-[9px] sm:text-[10px] text-amber-300 font-bold uppercase tracking-wider block">{sub1Tag}</span>
                <h4 className="text-xs sm:text-sm font-bold text-white leading-tight line-clamp-1">{sub1Title}</h4>
              </div>
            </div>

            {/* Card 2: Armada Penjemputan Truk / Pick-Up */}
            <div className="relative h-[120px] sm:h-[140px] lg:h-auto lg:flex-1 rounded-2xl overflow-hidden shadow-xs border border-sky-100 group">
              <Image
                src={sub2Image}
                alt={sub2Title}
                fill
                unoptimized
                className="object-cover group-hover:scale-105 transition-transform duration-500"
                sizes="(max-width: 640px) 100vw, (max-width: 1024px) 33vw, 40vw"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent" />
              <div className="absolute bottom-2.5 left-3 right-3 text-white">
                <span className="text-[9px] sm:text-[10px] text-sky-300 font-bold uppercase tracking-wider block">{sub2Tag}</span>
                <h4 className="text-xs sm:text-sm font-bold text-white leading-tight line-clamp-1">{sub2Title}</h4>
              </div>
            </div>

            {/* Card 3: Bongkaran Bangunan & Tabung Gas */}
            <div className="relative h-[120px] sm:h-[140px] lg:h-auto lg:flex-1 rounded-2xl overflow-hidden shadow-xs border border-sky-100 group">
              <Image
                src={sub3Image}
                alt={sub3Title}
                fill
                unoptimized
                className="object-cover group-hover:scale-105 transition-transform duration-500"
                sizes="(max-width: 640px) 100vw, (max-width: 1024px) 33vw, 40vw"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent" />
              <div className="absolute bottom-2.5 left-3 right-3 text-white">
                <span className="text-[9px] sm:text-[10px] text-teal-300 font-bold uppercase tracking-wider block">{sub3Tag}</span>
                <h4 className="text-xs sm:text-sm font-bold text-white leading-tight line-clamp-1">{sub3Title}</h4>
              </div>
            </div>
          </div>
        </motion.div>

        {/* 3. Bottom Row: Quick Trust Badges & Subtle Scroll Cue */}
        <motion.div
          initial={false}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.15 }}
          className="space-y-3 pt-2 border-t border-sky-100/80"
        >
          <div className="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-3 text-xs">
            <div className="flex items-center gap-2 bg-white/90 backdrop-blur-sm border border-sky-100 px-3 py-2 rounded-xl shadow-xs">
              <Scale className="w-4 h-4 text-sky-600 shrink-0" />
              <span className="font-semibold text-slate-800 text-[11px] sm:text-xs truncate">Timbangan Digital Presisi</span>
            </div>
            <div className="flex items-center gap-2 bg-white/90 backdrop-blur-sm border border-sky-100 px-3 py-2 rounded-xl shadow-xs">
              <Truck className="w-4 h-4 text-sky-600 shrink-0" />
              <span className="font-semibold text-slate-800 text-[11px] sm:text-xs truncate">Armada Jemput 24 Jam</span>
            </div>
            <div className="flex items-center gap-2 bg-white/90 backdrop-blur-sm border border-sky-100 px-3 py-2 rounded-xl shadow-xs">
              <ShieldCheck className="w-4 h-4 text-sky-600 shrink-0" />
              <span className="font-semibold text-slate-800 text-[11px] sm:text-xs truncate">Bayar Kontan di Tempat</span>
            </div>
            <div className="flex items-center gap-2 bg-white/90 backdrop-blur-sm border border-sky-100 px-3 py-2 rounded-xl shadow-xs">
              <MapPin className="w-4 h-4 text-sky-600 shrink-0" />
              <span className="font-semibold text-slate-800 text-[11px] sm:text-xs truncate">Jagakarsa, Jakarta Selatan</span>
            </div>
          </div>

          {/* Centered Scroll Indicator */}
          <div className="flex justify-center items-center pt-0.5">
            <button
              onClick={handleScrollToAbout}
              className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-sky-600 transition-colors group cursor-pointer"
            >
              <span>Scroll untuk daftar harga & profil</span>
              <ChevronDown className="w-3.5 h-3.5 text-sky-500 animate-bounce group-hover:translate-y-0.5 transition-transform" />
            </button>
          </div>
        </motion.div>
      </div>
    </section>
  );
}
