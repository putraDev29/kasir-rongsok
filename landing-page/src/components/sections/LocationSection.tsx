"use client";

import React from "react";
import { motion } from "framer-motion";
import { MapPin, Navigation, ExternalLink, Clock, Phone } from "lucide-react";
import { LocationData, LandingSettings } from "@/types/landing-page";

interface LocationSectionProps {
  location: LocationData;
  settings: LandingSettings;
}

export default function LocationSection({ location, settings }: LocationSectionProps) {
  const directionsUrl =
    location.latitude && location.longitude
      ? `https://www.google.com/maps/dir/?api=1&destination=${location.latitude},${location.longitude}`
      : location.maps_url || `https://maps.google.com/?q=${encodeURIComponent(location.address)}`;

  const openMapUrl = location.maps_url || directionsUrl;

  return (
    <section id="location" className="py-20 relative bg-white border-t border-sky-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <motion.div
          initial={false}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.2 }}
          transition={{ duration: 0.5 }}
          className="text-center max-w-2xl mx-auto space-y-3 mb-14"
        >
          <span className="text-xs font-bold uppercase tracking-widest text-sky-700">
            Lokasi & Akses
          </span>
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 tracking-tight">
            Kunjungi Kantor & Fasilitas Penyimpanan Kami
          </h2>
          <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Lokasi strategis di Jagakarsa Jakarta Selatan dengan akses mudah serta fasilitas penyimpanan aset yang luas, aman, dan memadai.
          </p>
        </motion.div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
          {/* Left Column: Interactive Information Card (5 cols) */}
          <motion.div
            initial={false}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{ duration: 0.5 }}
            className="lg:col-span-5 rounded-3xl bg-sky-50/50 border border-sky-100 p-6 sm:p-8 flex flex-col justify-between space-y-6 shadow-sm"
          >
            <div className="space-y-6">
              <div className="space-y-2 border-b border-slate-200 pb-5">
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-100 text-sky-800 border border-sky-200 text-xs font-bold">
                  <MapPin className="w-3.5 h-3.5 text-sky-600" />
                  Alamat Kantor & Fasilitas Depo
                </span>
                <h3 className="text-lg sm:text-xl font-black text-slate-900">
                  {location.company_name}
                </h3>
                <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
                  {location.address}
                </p>
              </div>

              {/* Operating Hours */}
              <div className="flex items-start gap-3.5">
                <div className="w-10 h-10 rounded-xl bg-white text-sky-700 flex items-center justify-center shrink-0 border border-sky-100 shadow-sm">
                  <Clock className="w-5 h-5 text-sky-600" />
                </div>
                <div>
                  <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">Jam Operasional Kasir</h4>
                  <p className="text-xs text-slate-600 mt-0.5">{location.operating_hours}</p>
                </div>
              </div>

              {/* Phone & Contact */}
              <div className="flex items-start gap-3.5">
                <div className="w-10 h-10 rounded-xl bg-white text-sky-700 flex items-center justify-center shrink-0 border border-sky-100 shadow-sm">
                  <Phone className="w-5 h-5 text-sky-600" />
                </div>
                <div>
                  <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">Telepon & WhatsApp</h4>
                  <p className="text-xs text-slate-600 mt-0.5">{location.phone}</p>
                </div>
              </div>
            </div>

            {/* Navigation Action Buttons */}
            <div className="pt-4 border-t border-slate-200 flex flex-col sm:flex-row gap-3">
              <a
                href={directionsUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-bold text-xs shadow-md shadow-sky-500/25 transition-all hover:scale-105"
              >
                <Navigation className="w-4 h-4 fill-white" />
                <span>Petunjuk Arah (GPS)</span>
              </a>

              <a
                href={openMapUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-white hover:bg-sky-50/50 text-slate-700 hover:text-sky-700 border border-sky-200 font-semibold text-xs shadow-sm transition-colors"
              >
                <ExternalLink className="w-4 h-4" />
                <span>Buka di Google Maps</span>
              </a>
            </div>
          </motion.div>

          {/* Right Column: Google Maps Embed (7 cols) */}
          <motion.div
            initial={false}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{ duration: 0.5 }}
            className="lg:col-span-7 h-[360px] sm:h-[460px] rounded-3xl overflow-hidden border border-sky-100 bg-slate-100 shadow-xl relative"
          >
            {location.maps_embed_url ? (
              <iframe
                src={location.maps_embed_url}
                width="100%"
                height="100%"
                style={{ border: 0 }}
                allowFullScreen={false}
                loading="lazy"
                referrerPolicy="no-referrer-when-downgrade"
                title="Peta Lokasi Usaha"
                className="w-full h-full"
              />
            ) : (
              <div className="w-full h-full flex flex-col items-center justify-center p-6 text-center text-slate-600 space-y-2">
                <MapPin className="w-10 h-10 text-sky-600" />
                <p className="text-xs font-semibold">{location.address}</p>
                <a
                  href={openMapUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-4 py-2 bg-gradient-to-r from-sky-500 to-blue-600 text-white rounded-xl text-xs font-bold shadow-md shadow-sky-500/20"
                >
                  Buka Peta Google Maps
                </a>
              </div>
            )}
          </motion.div>
        </div>
      </div>
    </section>
  );
}
