"use client";

import React from "react";
import { motion } from "framer-motion";
import {
  ShieldCheck,
  Coins,
  PackageCheck,
  Recycle,
  BatteryCharging,
  Truck,
  Scale,
  Factory,
  ArrowRight,
  MessageCircle,
} from "lucide-react";
import { ServiceItem, LandingSettings } from "@/types/landing-page";

interface ServicesSectionProps {
  services: ServiceItem[];
  settings: LandingSettings;
}

// Map Lucide icons safely
const ICON_MAP: Record<string, React.ElementType> = {
  ShieldCheck,
  Coins,
  PackageCheck,
  Recycle,
  BatteryCharging,
  Truck,
  Scale,
  Factory,
};

export default function ServicesSection({ services, settings }: ServicesSectionProps) {
  return (
    <section id="services" className="py-20 relative bg-sky-50/40 border-t border-sky-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="text-center max-w-3xl mx-auto space-y-3 mb-14">
          <span className="text-xs font-bold uppercase tracking-widest text-sky-700">
            Layanan Kami
          </span>
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 tracking-tight">
            Layanan Lelang Aset & Pengelolaan Profesional
          </h2>
          <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Menyediakan sistem pelelangan aset barang tak bergerak, survey & appraisal fisik, fasilitas penyimpanan aset yang luas, database pembeli aktif, serta pengelolaan scrap logam dan bongkaran.
          </p>
        </div>

        {/* Services Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {services.map((srv, idx) => {
            const IconComponent = ICON_MAP[srv.icon] || Recycle;

            return (
              <motion.div
                key={srv.id}
                initial={false}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.15 }}
                transition={{ duration: 0.4, delay: idx * 0.08 }}
                className="group relative rounded-2xl bg-white border border-slate-200 p-6 sm:p-7 flex flex-col justify-between hover:border-sky-400 transition-all duration-300 shadow-sm hover:shadow-xl hover:shadow-sky-100/60 hover:-translate-y-1"
              >
                <div className="space-y-4">
                  {/* Icon Box */}
                  <div className="w-12 h-12 rounded-xl bg-sky-50 text-sky-700 border border-sky-100 flex items-center justify-center group-hover:scale-110 group-hover:bg-gradient-to-r group-hover:from-sky-500 group-hover:to-blue-600 group-hover:text-white transition-all duration-300">
                    <IconComponent className="w-6 h-6" />
                  </div>

                  {/* Title */}
                  <h3 className="text-base sm:text-lg font-bold text-slate-900 group-hover:text-sky-600 transition-colors">
                    {srv.title}
                  </h3>

                  {/* Short Description */}
                  <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    {srv.short_description}
                  </p>
                </div>

                {/* Footer Action */}
                <div className="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between">
                  <a
                    href={settings.whatsapp_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1.5 text-xs font-bold text-sky-600 hover:text-sky-700 transition-colors"
                  >
                    <span>Tanya Layanan Ini</span>
                    <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
                  </a>

                  <span className="text-[10px] text-slate-400 font-mono">0{idx + 1}</span>
                </div>
              </motion.div>
            );
          })}
        </div>

        {/* Free Pickup Banner */}
        <div className="mt-12 p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-sky-50 via-blue-50 to-sky-100 border border-sky-200 flex flex-col md:flex-row items-center justify-between gap-6 shadow-md">
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 text-white flex items-center justify-center shadow-lg shadow-sky-500/25 shrink-0">
              <Truck className="w-7 h-7" />
            </div>
            <div>
              <h4 className="text-base sm:text-lg font-bold text-slate-900">Butuh Penjemputan Armada ke Lokasi Anda?</h4>
              <p className="text-xs sm:text-sm text-slate-600 mt-0.5">
                Armada pick-up dan truk kami siap datang menimbang di tempat untuk rongsok partai sedang & pabrik.
              </p>
            </div>
          </div>

          <a
            href={settings.whatsapp_url}
            target="_blank"
            rel="noopener noreferrer"
            className="px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-sky-500/25 transition-all whitespace-nowrap flex items-center gap-2 hover:scale-105"
          >
            <MessageCircle className="w-4 h-4 fill-white" />
            <span>Pesan Armada Penjemputan</span>
          </a>
        </div>
      </div>
    </section>
  );
}
