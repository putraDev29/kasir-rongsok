"use client";

import React from "react";
import { motion } from "framer-motion";
import { CheckCircle2, Target, Compass, Award, MessageCircle } from "lucide-react";
import Image from "next/image";
import { AboutData, LandingSettings } from "@/types/landing-page";
import { resolveImageUrl } from "@/lib/api";

interface AboutSectionProps {
  about: AboutData;
  settings: LandingSettings;
}

export default function AboutSection({ about, settings }: AboutSectionProps) {
  // Parse mission lines
  const missionItems = (about.mission || settings.about_mission)
    .split("\n")
    .map((line) => line.replace(/^[0-9]+\.\s*/, "").trim())
    .filter((line) => line.length > 0);

  const rawAboutImage =
    about.image ||
    settings.about_image ||
    "./images/about_depo_seberaya.jpg";
  const aboutImage = resolveImageUrl(rawAboutImage, "./images/about_depo_seberaya.jpg");

  return (
    <section id="about" className="py-20 relative bg-white border-t border-sky-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
          {/* Left Column: Image with Experience Badge */}
          <motion.div
            initial={false}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{ duration: 0.6 }}
            className="lg:col-span-5 relative"
          >
            <div className="relative h-[380px] sm:h-[480px] rounded-3xl overflow-hidden border border-sky-100 shadow-xl bg-slate-100 group">
              <Image
                src={aboutImage}
                alt="Operasional Depo Pengepul Rongsok PT. Seberaya Akses Multi"
                fill
                unoptimized
                className="object-cover group-hover:scale-105 transition-transform duration-500"
                sizes="(max-width: 1024px) 100vw, 500px"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-slate-900/50 via-transparent to-transparent" />

              {/* Floating Experience Badge */}
              <div className="absolute bottom-6 left-6 right-6 p-4 rounded-2xl bg-white/95 border border-sky-200 backdrop-blur-md shadow-xl flex items-center gap-4">
                <div className="w-12 h-12 rounded-xl bg-sky-100 text-sky-700 border border-sky-200 flex items-center justify-center shrink-0">
                  <Award className="w-6 h-6" />
                </div>
                <div>
                  <div className="text-xl font-black text-slate-900 font-mono">
                    {about.years_experience || settings.about_years_experience || "3+"} Tahun
                  </div>
                  <p className="text-xs text-slate-600 font-medium">Pengalaman Lelang & Pengelolaan Aset</p>
                </div>
              </div>
            </div>
          </motion.div>

          {/* Right Column: Text Story, Vision, Mission, CTA */}
          <motion.div
            initial={false}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{ duration: 0.6 }}
            className="lg:col-span-7 space-y-6"
          >
            <div>
              <span className="text-xs font-bold uppercase tracking-widest text-sky-700">
                Tentang Usaha Kami
              </span>
              <h2 className="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 tracking-tight mt-1">
                {about.title || settings.about_title}
              </h2>
            </div>

            <p className="text-sm sm:text-base text-slate-600 leading-relaxed font-normal">
              {about.description || settings.about_description}
            </p>

            {/* Vision and Mission Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
              {/* Vision */}
              <div className="p-4 rounded-2xl bg-sky-50/60 border border-sky-100 space-y-2">
                <div className="flex items-center gap-2 text-sky-700">
                  <Target className="w-4 h-4" />
                  <h4 className="text-xs font-bold uppercase tracking-wider text-slate-900">Visi Bisnis</h4>
                </div>
                <p className="text-xs text-slate-600 leading-relaxed">
                  {about.vision || settings.about_vision}
                </p>
              </div>

              {/* Mission */}
              <div className="p-4 rounded-2xl bg-sky-50/60 border border-sky-100 space-y-2">
                <div className="flex items-center gap-2 text-blue-700">
                  <Compass className="w-4 h-4" />
                  <h4 className="text-xs font-bold uppercase tracking-wider text-slate-900">Misi Kami</h4>
                </div>
                <ul className="space-y-1.5 text-xs text-slate-600">
                  {missionItems.map((item, idx) => (
                    <li key={idx} className="flex items-start gap-1.5">
                      <CheckCircle2 className="w-3.5 h-3.5 text-sky-600 shrink-0 mt-0.5" />
                      <span className="leading-snug">{item}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>

            {/* Call To Action */}
            <div className="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
              <a
                href={settings.whatsapp_url}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs shadow-md shadow-sky-500/20 transition-all hover:scale-105"
              >
                <MessageCircle className="w-4 h-4 fill-white" />
                <span>Konsultasikan Kebutuhan Aset Anda</span>
              </a>

              <a
                href="#location"
                className="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-sky-200 text-slate-700 hover:text-sky-700 hover:bg-sky-50/50 text-xs font-semibold transition"
              >
                <span>Lihat Alamat Kantor & Lokasi Depo</span>
              </a>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
