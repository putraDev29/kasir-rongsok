"use client";

import React from "react";
import { motion } from "framer-motion";
import {
  PhoneCall,
  MessageSquareQuote,
  Truck,
  Scale,
  Wallet,
  ArrowRight,
} from "lucide-react";
import { ProcessStep, LandingSettings } from "@/types/landing-page";

interface ProcessSectionProps {
  steps: ProcessStep[];
  settings: LandingSettings;
}

const ICON_MAP: Record<string, React.ElementType> = {
  PhoneCall,
  MessageSquareQuote,
  Truck,
  Scale,
  Wallet,
};

export default function ProcessSection({ steps, settings }: ProcessSectionProps) {
  return (
    <section id="process" className="py-20 relative bg-white border-t border-sky-100">
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
            Alur Transaksi & Pelelangan
          </span>
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 tracking-tight">
            5 Tahapan Pelelangan & Penjualan Aset
          </h2>
          <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Sistem penjualan yang cepat, efisien, terbuka, dan obyektif dari survey awal, appraisal fisik, hingga terbentuk harga optimum dan aspek legalitas pasti.
          </p>
        </motion.div>

        {/* Step Flow Cards */}
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4 relative">
          {steps.map((step, idx) => {
            const IconComponent = ICON_MAP[step.icon] || Scale;

            return (
              <motion.div
                key={step.step}
                initial={false}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.15 }}
                transition={{ duration: 0.4, delay: idx * 0.1 }}
                className="relative rounded-2xl bg-sky-50/40 border border-sky-100 p-5 flex flex-col justify-between space-y-4 hover:border-sky-400 hover:bg-white hover:shadow-lg transition-all group"
              >
                <div className="space-y-3">
                  {/* Step Number & Icon */}
                  <div className="flex items-center justify-between">
                    <span className="w-8 h-8 rounded-full bg-sky-100 text-sky-800 border border-sky-200 flex items-center justify-center font-mono font-bold text-xs">
                      0{step.step}
                    </span>
                    <div className="w-9 h-9 rounded-xl bg-white text-slate-600 group-hover:text-sky-600 group-hover:scale-110 flex items-center justify-center border border-slate-200 shadow-sm transition-all">
                      <IconComponent className="w-4 h-4" />
                    </div>
                  </div>

                  {/* Title */}
                  <h3 className="text-sm font-bold text-slate-900 group-hover:text-sky-600 transition-colors">
                    {step.title}
                  </h3>

                  {/* Description */}
                  <p className="text-xs text-slate-600 leading-relaxed">
                    {step.description}
                  </p>
                </div>

                {/* Sub Arrow for Steps */}
                {idx < steps.length - 1 && (
                  <div className="hidden md:flex justify-end text-sky-200">
                    <ArrowRight className="w-4 h-4" />
                  </div>
                )}
              </motion.div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
