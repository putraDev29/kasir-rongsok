"use client";

import React from "react";
import { motion } from "framer-motion";
import {
  Scale,
  TrendingUp,
  Banknote,
  Truck,
  Users,
  ShieldCheck,
  Check,
} from "lucide-react";
import { WhyChooseUsItem, LandingSettings } from "@/types/landing-page";

interface WhyChooseUsSectionProps {
  items: WhyChooseUsItem[];
  settings: LandingSettings;
}

const ICON_MAP: Record<string, React.ElementType> = {
  Scale,
  TrendingUp,
  Banknote,
  Truck,
  Users,
  ShieldCheck,
};

export default function WhyChooseUsSection({ items, settings }: WhyChooseUsSectionProps) {
  return (
    <section className="py-20 relative bg-sky-50/30 border-t border-sky-100">
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
            Nilai Keunggulan
          </span>
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 tracking-tight">
            Mengapa Memilih {settings.company_name}?
          </h2>
          <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Kunci utama kesuksesan lelang adalah harga yang sesuai kondisi pasar, kondisi fisik yang obyektif, aspek legalitas yang pasti, dan pemasaran yang efektif.
          </p>
        </motion.div>

        {/* Feature Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {items.map((item, idx) => {
            const IconComponent = ICON_MAP[item.icon] || ShieldCheck;

            return (
              <motion.div
                key={idx}
                initial={false}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.15 }}
                transition={{ duration: 0.4, delay: idx * 0.08 }}
                className="group relative rounded-2xl bg-white border border-slate-200 p-6 sm:p-7 space-y-4 hover:border-sky-400 hover:shadow-xl hover:shadow-sky-100/60 transition-all shadow-sm"
              >
                <div className="flex items-center justify-between">
                  <div className="w-12 h-12 rounded-xl bg-sky-50 text-sky-700 border border-sky-100 flex items-center justify-center group-hover:scale-110 group-hover:bg-gradient-to-r group-hover:from-sky-500 group-hover:to-blue-600 group-hover:text-white transition-all duration-300">
                    <IconComponent className="w-6 h-6" />
                  </div>
                  <span className="w-6 h-6 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-bold">
                    <Check className="w-3.5 h-3.5" />
                  </span>
                </div>

                <h3 className="text-base sm:text-lg font-bold text-slate-900 group-hover:text-sky-600 transition-colors">
                  {item.title}
                </h3>

                <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
                  {item.description}
                </p>
              </motion.div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
