"use client";

import React, { useState, useMemo } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  Tag,
  Search,
  MessageCircle,
  ChevronDown,
  Layers,
  Sparkles,
  Flame,
  FileText,
  Recycle,
  Zap,
  ArrowRight,
  SlidersHorizontal,
} from "lucide-react";
import { CategoryItem, LandingSettings, ScrapPriceItem } from "@/types/landing-page";
import { formatRupiah } from "@/lib/utils";
import { formatWhatsAppNumber } from "@/lib/api";

interface CategoriesSectionProps {
  categories: CategoryItem[];
  settings: LandingSettings;
}

export default function CategoriesSection({ categories, settings }: CategoriesSectionProps) {
  const [selectedCategoryId, setSelectedCategoryId] = useState<number | "all">("all");
  const [searchQuery, setSearchQuery] = useState("");
  // Track open/collapsed state of category accordions
  const [expandedCategories, setExpandedCategories] = useState<Record<number, boolean>>({});

  // Dynamic icon selector based on category name
  const getCategoryIcon = (name: string) => {
    const lower = name.toLowerCase();
    if (lower.includes("besi") || lower.includes("baja")) return Layers;
    if (lower.includes("tembaga") || lower.includes("kuningan") || lower.includes("logam") || lower.includes("scrap")) return Sparkles;
    if (lower.includes("gas") || lower.includes("tabung")) return Flame;
    if (lower.includes("kardus") || lower.includes("kertas")) return FileText;
    if (lower.includes("plastik")) return Recycle;
    if (lower.includes("aki") || lower.includes("baterai")) return Zap;
    return Tag;
  };

  // Toggle single category open/close
  const toggleCategory = (catId: number) => {
    setExpandedCategories((prev) => ({
      ...prev,
      [catId]: !prev[catId],
    }));
  };

  // Toggle all categories open or closed
  const isAllOpen = useMemo(() => {
    if (categories.length === 0) return false;
    return categories.every((cat) => expandedCategories[cat.id]);
  }, [categories, expandedCategories]);

  const handleToggleAll = () => {
    const nextState = !isAllOpen;
    const updated: Record<number, boolean> = {};
    categories.forEach((cat) => {
      updated[cat.id] = nextState;
    });
    setExpandedCategories(updated);
  };

  // Handle pill click: filter and auto-expand that category
  const handlePillClick = (catId: number | "all") => {
    setSelectedCategoryId(catId);
    if (catId !== "all") {
      setExpandedCategories((prev) => ({
        ...prev,
        [catId]: true,
      }));
    }
  };

  // Filtered categories
  const filteredCategories = categories.filter((cat) => {
    if (selectedCategoryId !== "all" && cat.id !== selectedCategoryId) {
      return false;
    }
    return true;
  });

  // Calculate price summary helper
  const getPriceRange = (items: ScrapPriceItem[]) => {
    const prices = items.map((i) => i.buy_price_per_kg).filter((p) => p > 0);
    if (prices.length === 0) return "Nego Terbaik";
    const min = Math.min(...prices);
    const max = Math.max(...prices);
    if (min === max) return `${formatRupiah(min)} /kg`;
    return `${formatRupiah(min)} - ${formatRupiah(max)}`;
  };

  return (
    <section id="categories" className="py-16 sm:py-20 relative bg-white border-t border-sky-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <motion.div
          initial={false}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.2 }}
          transition={{ duration: 0.5 }}
          className="flex flex-col md:flex-row md:items-end justify-between gap-5 mb-8 sm:mb-10"
        >
          <div className="space-y-1.5">
            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-100 text-sky-800 text-xs font-bold uppercase tracking-wider">
              <Sparkles className="w-3.5 h-3.5 text-sky-600" />
              Katalog & Estimasi Harga
            </span>
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 tracking-tight">
              Daftar Barang & Harga Beli
            </h2>
            <p className="text-xs sm:text-sm text-slate-600 max-w-xl">
              Klik kategori di bawah untuk membuka daftar rincian barang dan estimasi harga beli terkini.
            </p>
          </div>

          {/* Search Box & Toggle All */}
          <div className="flex items-center gap-2.5 w-full md:w-auto">
            <div className="relative flex-1 md:w-72">
              <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Cari jenis barang..."
                className="w-full pl-10 pr-4 py-2 bg-sky-50/60 border border-sky-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:bg-white transition-colors"
              />
            </div>

            <button
              onClick={handleToggleAll}
              className="shrink-0 inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-sky-700 text-xs font-semibold border border-slate-200 transition-colors"
              title={isAllOpen ? "Tutup Semua Kategori" : "Buka Semua Kategori"}
            >
              <SlidersHorizontal className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">{isAllOpen ? "Tutup Semua" : "Buka Semua"}</span>
            </button>
          </div>
        </motion.div>

        {/* Category Filter Pills Navigation */}
        <div className="flex items-center gap-2 overflow-x-auto pb-3 scrollbar-none mb-6">
          <button
            onClick={() => handlePillClick("all")}
            className={`px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all ${
              selectedCategoryId === "all"
                ? "bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-sm shadow-sky-500/25"
                : "bg-slate-100 text-slate-700 hover:text-sky-700 hover:bg-sky-50 border border-slate-200"
            }`}
          >
            Semua ({categories.length})
          </button>

          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => handlePillClick(cat.id)}
              className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
                selectedCategoryId === cat.id
                  ? "bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-sm shadow-sky-500/25"
                  : "bg-slate-100 text-slate-700 hover:text-sky-700 hover:bg-sky-50 border border-slate-200"
              }`}
            >
              {cat.name} ({cat.items.length})
            </button>
          ))}
        </div>

        {/* Accordion / Dropdown Cards for Categories */}
        <div className="space-y-3.5">
          {filteredCategories.map((category) => {
            const Icon = getCategoryIcon(category.name);

            // Filter items by search query
            const visibleItems = category.items.filter((item) => {
              if (!searchQuery) return true;
              return (
                item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                category.name.toLowerCase().includes(searchQuery.toLowerCase())
              );
            });

            // If user searched and category has no match, hide it
            if (searchQuery && visibleItems.length === 0) return null;

            // Auto-expand if search query matches items in this category
            const isOpen = searchQuery ? true : !!expandedCategories[category.id];

            return (
              <div
                key={category.id}
                className={`rounded-2xl border transition-all duration-300 overflow-hidden ${
                  isOpen
                    ? "bg-white border-sky-300 shadow-md ring-2 ring-sky-100"
                    : "bg-slate-50/80 hover:bg-white border-slate-200/90 hover:border-sky-200 shadow-xs"
                }`}
              >
                {/* 1. Clickable Category Trigger Header */}
                <button
                  type="button"
                  onClick={() => toggleCategory(category.id)}
                  className="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left transition-colors cursor-pointer"
                  aria-expanded={isOpen}
                >
                  <div className="flex items-center gap-3.5 min-w-0">
                    <div
                      className={`w-10 h-10 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center shrink-0 transition-colors ${
                        isOpen
                          ? "bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-sm"
                          : "bg-sky-100/80 text-sky-700"
                      }`}
                    >
                      <Icon className="w-5 h-5" />
                    </div>

                    <div className="min-w-0">
                      <div className="flex items-center gap-2 flex-wrap">
                        <h3 className="text-sm sm:text-base font-bold text-slate-900 truncate">
                          {category.name}
                        </h3>
                        <span className="text-[10px] sm:text-[11px] font-bold font-mono px-2 py-0.5 rounded-full bg-sky-100 text-sky-800 border border-sky-200">
                          {visibleItems.length} Jenis
                        </span>
                      </div>
                      <p className="text-[11px] sm:text-xs text-slate-500 line-clamp-1 mt-0.5">
                        {category.description || `Kisaran harga: ${getPriceRange(category.items)}`}
                      </p>
                    </div>
                  </div>

                  {/* Right Action: Pill & Animated Chevron */}
                  <div className="flex items-center gap-2 shrink-0">
                    <span className="hidden md:inline-flex text-[11px] font-semibold text-sky-700 bg-sky-50 px-2.5 py-1 rounded-lg border border-sky-100">
                      {isOpen ? "Tutup Rincian" : "Lihat Daftar & Harga"}
                    </span>
                    <div
                      className={`w-8 h-8 rounded-full flex items-center justify-center transition-transform duration-300 ${
                        isOpen ? "bg-sky-500 text-white rotate-180" : "bg-slate-200/70 text-slate-600"
                      }`}
                    >
                      <ChevronDown className="w-4 h-4" />
                    </div>
                  </div>
                </button>

                {/* 2. Dropdown Accordion Content (Items & Prices) */}
                <AnimatePresence initial={false}>
                  {isOpen && (
                    <motion.div
                      initial={{ height: 0, opacity: 0 }}
                      animate={{ height: "auto", opacity: 1 }}
                      exit={{ height: 0, opacity: 0 }}
                      transition={{ duration: 0.3, ease: "easeInOut" }}
                    >
                      <div className="px-4 pb-5 sm:px-6 sm:pb-6 pt-1 border-t border-sky-100 bg-sky-50/30">
                        {/* Items Grid */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-3">
                          {visibleItems.map((item) => (
                            <div
                              key={item.id}
                              className="group p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 hover:border-sky-300 transition-all flex flex-col justify-between space-y-2.5 shadow-2xs hover:shadow-sm"
                            >
                              <div className="space-y-1">
                                <div className="flex items-start justify-between gap-2">
                                  <h4 className="text-xs sm:text-sm font-bold text-slate-800 group-hover:text-sky-600 transition-colors">
                                    {item.name}
                                  </h4>
                                  <span className="text-[10px] text-slate-500 font-mono uppercase bg-slate-100 px-1.5 py-0.5 rounded shrink-0">
                                    /{item.unit || "kg"}
                                  </span>
                                </div>
                                {item.description && (
                                  <p className="text-[11px] text-slate-500 line-clamp-2 leading-relaxed">
                                    {item.description}
                                  </p>
                                )}
                              </div>

                              {/* Price Tag & WhatsApp Button */}
                              <div className="pt-2 border-t border-slate-100 flex items-center justify-between">
                                <div>
                                  <span className="text-[9px] text-slate-400 block uppercase font-semibold">
                                    Estimasi Beli
                                  </span>
                                  <div className="text-xs sm:text-sm font-black text-sky-700 font-mono">
                                    {item.buy_price_per_kg > 0
                                      ? `${formatRupiah(item.buy_price_per_kg)}`
                                      : "Nego Terbaik"}
                                  </div>
                                </div>

                                <a
                                  href={`https://wa.me/${formatWhatsAppNumber(
                                    settings.whatsapp_number || settings.whatsapp_raw
                                  )}?text=${encodeURIComponent(
                                    `Halo Admin PT. Seberaya Akses Multi, saya ingin menjual/tanya harga material: ${item.name} (${category.name}). Mohon info penimbangannya.`
                                  )}`}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-sky-50 hover:bg-gradient-to-r hover:from-sky-500 hover:to-blue-600 text-sky-700 hover:text-white border border-sky-200 hover:border-transparent text-[11px] font-semibold transition-all shadow-2xs"
                                  title="Chat WhatsApp untuk barang ini"
                                >
                                  <MessageCircle className="w-3.5 h-3.5" />
                                  <span>Jual Ini</span>
                                </a>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>
            );
          })}
        </div>

        {/* Note on Large Volume Deals */}
        <div className="mt-8 text-center p-3.5 sm:p-4 rounded-2xl bg-sky-50/80 border border-sky-200 text-xs text-slate-700">
          💡 <span className="font-semibold text-sky-900">Punya rongsok tonase besar / bongkaran gedung?</span> Harga beli untuk rekanan industri & borongan dapat dinegosiasikan khusus. Silakan hubungi admin via WhatsApp.
        </div>
      </div>
    </section>
  );
}
