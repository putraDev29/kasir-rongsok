"use client";

import React, { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { MessageCircle, X } from "lucide-react";
import { LandingSettings } from "@/types/landing-page";

interface WhatsAppButtonProps {
  settings: LandingSettings;
}

export default function WhatsAppButton({ settings }: WhatsAppButtonProps) {
  const [showTooltip, setShowTooltip] = useState(true);

  return (
    <div className="fixed bottom-6 right-6 z-40 flex items-center gap-3">
      {/* Dynamic Floating Tooltip */}
      <AnimatePresence>
        {showTooltip && (
          <motion.div
            initial={{ opacity: 0, x: 20, scale: 0.9 }}
            animate={{ opacity: 1, x: 0, scale: 1 }}
            exit={{ opacity: 0, x: 20, scale: 0.9 }}
            className="hidden sm:flex items-center gap-2 bg-white text-slate-800 border border-sky-200 px-3.5 py-2 rounded-2xl shadow-xl backdrop-blur-md text-xs font-semibold"
          >
            <span className="relative flex h-2 w-2">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2 w-2 bg-sky-500"></span>
            </span>
            <a
              href={settings.whatsapp_url}
              target="_blank"
              rel="noopener noreferrer"
              className="hover:text-sky-600 transition-colors"
            >
              Tanya Harga / Jemput Rongsok?
            </a>
            <button
              onClick={() => setShowTooltip(false)}
              className="text-slate-400 hover:text-slate-700 p-0.5 ml-1"
              aria-label="Tutup saran"
            >
              <X className="w-3.5 h-3.5" />
            </button>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Main WhatsApp Floating Action Button */}
      <motion.a
        href={settings.whatsapp_url}
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Hubungi kami melalui WhatsApp"
        whileHover={{ scale: 1.1 }}
        whileTap={{ scale: 0.95 }}
        className="relative group w-14 h-14 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white flex items-center justify-center shadow-xl shadow-emerald-950/25 transition-all border-2 border-white"
      >
        {/* Subtle Pulse Ring */}
        <span className="absolute -inset-1 rounded-full bg-emerald-500/30 animate-ping opacity-75 pointer-events-none" />

        <MessageCircle className="w-7 h-7 fill-white relative z-10" />

        {/* Floating Mini Badge */}
        <span className="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-black rounded-full h-5 w-5 flex items-center justify-center shadow-md border-2 border-white">
          1
        </span>
      </motion.a>
    </div>
  );
}
