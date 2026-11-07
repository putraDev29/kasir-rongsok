"use client";

import React, { useState } from "react";
import { motion } from "framer-motion";
import { Maximize2 } from "lucide-react";
import Image from "next/image";
import { GalleryItem } from "@/types/landing-page";
import LightboxModal from "@/components/ui/LightboxModal";
import { resolveImageUrl } from "@/lib/api";

interface GallerySectionProps {
  gallery: GalleryItem[];
}

export default function GallerySection({ gallery }: GallerySectionProps) {
  const [selectedCategory, setSelectedCategory] = useState<string>("Semua");
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);

  const categories = ["Semua", ...Array.from(new Set(gallery.map((g) => g.category)))];

  const filteredGallery = gallery.filter((item) => {
    if (selectedCategory === "Semua") return true;
    return item.category === selectedCategory;
  });

  const handleOpenLightbox = (index: number) => {
    setCurrentImageIndex(index);
    setLightboxOpen(true);
  };

  const lightboxImages = filteredGallery.map((g) => ({
    url: resolveImageUrl(g.image_url || g.image_path),
    title: g.title,
    category: g.category,
    caption: g.caption,
  }));

  return (
    <section id="gallery" className="py-20 relative bg-sky-50/30 border-t border-sky-100">
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
            Dokumentasi & Fasilitas
          </span>
          <h2 className="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 tracking-tight">
            Galeri Operasional Depo & Gudang
          </h2>
          <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Potret aktivitas penimbangan akurat, armada logistik, penyortiran material daur ulang, dan pengelolaan rongsokan kami.
          </p>
        </motion.div>

        {/* Category Filter Pills */}
        <div className="flex items-center justify-center gap-2 overflow-x-auto pb-4 mb-8">
          {categories.map((cat) => (
            <button
              key={cat}
              onClick={() => setSelectedCategory(cat)}
              className={`px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap ${
                selectedCategory === cat
                  ? "bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md shadow-sky-500/20"
                  : "bg-white text-slate-700 hover:text-sky-700 hover:bg-sky-50/50 border border-slate-200 shadow-sm"
              }`}
            >
              {cat}
            </button>
          ))}
        </div>

        {/* Gallery Image Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredGallery.map((item, idx) => (
            <motion.div
              key={item.id}
              initial={false}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true, amount: 0.15 }}
              transition={{ duration: 0.4, delay: idx * 0.06 }}
              onClick={() => handleOpenLightbox(idx)}
              className="group relative h-64 sm:h-72 rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 cursor-pointer shadow-md hover:border-sky-400 transition-all hover:shadow-xl hover:shadow-sky-100/60"
            >
              <Image
                src={resolveImageUrl(item.image_url || item.image_path)}
                alt={item.title}
                fill
                unoptimized
                className="object-cover group-hover:scale-110 transition-transform duration-500"
                sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent opacity-75 group-hover:opacity-90 transition-opacity" />

              {/* Category Tag */}
              <div className="absolute top-4 left-4">
                <span className="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/95 text-sky-800 border border-sky-200 backdrop-blur-md shadow-sm">
                  {item.category}
                </span>
              </div>

              {/* Expand Icon */}
              <div className="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/90 text-slate-800 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-sm">
                <Maximize2 className="w-4 h-4" />
              </div>

              {/* Title & Caption */}
              <div className="absolute bottom-4 left-4 right-4 space-y-1">
                <h4 className="text-sm font-bold text-white group-hover:text-sky-300 transition-colors">
                  {item.title}
                </h4>
                {item.caption && (
                  <p className="text-xs text-slate-200 line-clamp-1">{item.caption}</p>
                )}
              </div>
            </motion.div>
          ))}
        </div>

        {/* Lightbox Modal */}
        <LightboxModal
          isOpen={lightboxOpen}
          onClose={() => setLightboxOpen(false)}
          images={lightboxImages}
          currentIndex={currentImageIndex}
          onNavigate={(newIndex) => setCurrentImageIndex(newIndex)}
        />
      </div>
    </section>
  );
}
