"use client";

import React, { useState, useEffect, useRef, useCallback } from "react";
import Image from "next/image";
import { motion, AnimatePresence } from "framer-motion";
import {
  Heart,
  MessageCircle,
  Share2,
  ChevronLeft,
  ChevronRight,
  Pause,
  Play,
  Send,
  X,
  Languages,
  CheckCircle2,
  Sparkles,
  ArrowUpRight,
  Clock,
  User,
} from "lucide-react";
import { StoryItem, StoryComment, LandingSettings } from "@/types/landing-page";
import { getApiBaseUrl, resolveImageUrl, formatWhatsAppNumber } from "@/lib/api";

interface HeroStoryViewerProps {
  initialStories?: StoryItem[];
  settings: LandingSettings;
  fallbackImage: string;
  fallbackTitle: string;
  fallbackSubtitle: string;
}

// Human-friendly Indonesian language names mapper
const getLanguageName = (code?: string): string => {
  if (!code) return "Bahasa Asing";
  const map: Record<string, string> = {
    en: "Bahasa Inggris",
    jw: "Bahasa Jawa",
    jv: "Bahasa Jawa",
    su: "Bahasa Sunda",
    zh: "Bahasa Mandarin",
    "zh-cn": "Bahasa Mandarin",
    "zh-tw": "Bahasa Mandarin",
    ja: "Bahasa Jepang",
    ko: "Bahasa Korea",
    ar: "Bahasa Arab",
    ms: "Bahasa Melayu",
    es: "Bahasa Spanyol",
    fr: "Bahasa Prancis",
    de: "Bahasa Jerman",
    ru: "Bahasa Rusia",
    hi: "Bahasa Hindi",
    id: "Bahasa Indonesia",
  };
  return map[code.toLowerCase()] || `Bahasa (${code.toUpperCase()})`;
};

export function HeroStoryViewer({
  initialStories = [],
  settings,
  fallbackImage,
  fallbackTitle,
  fallbackSubtitle,
}: HeroStoryViewerProps) {
  // Ensure we always have at least 1 story
  const stories: StoryItem[] =
    initialStories && initialStories.length > 0
      ? initialStories
      : [
          {
            id: 999,
            title: fallbackTitle || "Penimbangan Digital & Penampungan Logam Scrap",
            subtitle: fallbackSubtitle || "Depo Srengseng Sawah • Jagakarsa",
            tag: "Cerita Depo",
            image_path: fallbackImage,
            image_url: fallbackImage,
            caption:
              "Fasilitas timbangan digital akurat di Jakarta Selatan, siap tampung partai kecil & besar dengan harga tertinggi.",
            likes_count: 128,
            comments_count: 2,
            comments: [
              {
                id: 1,
                story_id: 999,
                user_name: "Bpk. Hendra Wijaya",
                comment: "Pelayanan sangat cepat, penimbangan terbuka dan uang langsung cair di tempat.",
                original_comment: "Very fast service and transparent weighing!",
                detected_language: "en",
                is_translated: true,
                likes_count: 6,
                created_at: "1 jam lalu",
              },
              {
                id: 2,
                story_id: 999,
                user_name: "CV. Logam Berkah",
                comment: "Timbangan terkalibrasi resmi, harga tembaga dan besi selalu update.",
                original_comment: "Timbangan terkalibrasi resmi, harga tembaga dan besi selalu update.",
                detected_language: "id",
                is_translated: false,
                likes_count: 9,
                created_at: "3 jam lalu",
              },
            ],
          },
        ];

  // The latest photo is always at index 0 (descending order)
  const [currentIndex, setCurrentIndex] = useState(0);
  const [progress, setProgress] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const [direction, setDirection] = useState(1); // 1 = next, -1 = prev

  // Local likes tracking
  const [storyLikes, setStoryLikes] = useState<Record<number, number>>(() => {
    const init: Record<number, number> = {};
    stories.forEach((s) => {
      init[s.id] = s.likes_count;
    });
    return init;
  });
  const [hasLiked, setHasLiked] = useState<Record<number, boolean>>({});
  const [flyingHearts, setFlyingHearts] = useState<{ id: number; x: number; y: number }[]>([]);

  // Local comments tracking per story
  const [storyComments, setStoryComments] = useState<Record<number, StoryComment[]>>(() => {
    const init: Record<number, StoryComment[]> = {};
    stories.forEach((s) => {
      init[s.id] = s.comments || [];
    });
    return init;
  });

  // Comment Sheet Drawer State
  const [isCommentOpen, setIsCommentOpen] = useState(false);
  const [commentName, setCommentName] = useState("");
  const [commentText, setCommentText] = useState("");
  const [sendToWhatsApp, setSendToWhatsApp] = useState(true);
  const [isSubmittingComment, setIsSubmittingComment] = useState(false);
  const [submissionSuccess, setSubmissionSuccess] = useState(false);

  // Swipe gesture tracking
  const touchStartX = useRef<number | null>(null);
  const touchStartY = useRef<number | null>(null);

  const currentStory = stories[currentIndex] || stories[0];
  const currentLikes = storyLikes[currentStory.id] ?? currentStory.likes_count;
  const currentCommentsList = storyComments[currentStory.id] ?? currentStory.comments;

  const STORY_DURATION = 6500; // 6.5s per slide

  // Navigation handlers
  const goToNext = useCallback(() => {
    setDirection(1);
    setProgress(0);
    setCurrentIndex((prev) => (prev + 1) % stories.length);
  }, [stories.length]);

  const goToPrev = useCallback(() => {
    setDirection(-1);
    setProgress(0);
    setCurrentIndex((prev) => (prev - 1 + stories.length) % stories.length);
  }, [stories.length]);

  const jumpToStory = (index: number) => {
    setDirection(index > currentIndex ? 1 : -1);
    setProgress(0);
    setCurrentIndex(index);
  };

  // Timer auto-advance effect
  useEffect(() => {
    if (isPaused || isCommentOpen) return;

    const intervalStep = 50;
    const increment = (intervalStep / STORY_DURATION) * 100;

    const timer = setInterval(() => {
      setProgress((prev) => {
        if (prev >= 100) {
          goToNext();
          return 0;
        }
        return prev + increment;
      });
    }, intervalStep);

    return () => clearInterval(timer);
  }, [isPaused, isCommentOpen, goToNext]);

  // Keyboard navigation
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (isCommentOpen) return;
      if (e.key === "ArrowRight") goToNext();
      if (e.key === "ArrowLeft") goToPrev();
      if (e.key === " ") setIsPaused((p) => !p);
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isCommentOpen, goToNext, goToPrev]);

  // Touch Swipe handlers
  const handleTouchStart = (e: React.TouchEvent) => {
    touchStartX.current = e.touches[0].clientX;
    touchStartY.current = e.touches[0].clientY;
  };

  const handleTouchEnd = (e: React.TouchEvent) => {
    if (touchStartX.current === null || touchStartY.current === null) return;
    const diffX = touchStartX.current - e.changedTouches[0].clientX;
    const diffY = touchStartY.current - e.changedTouches[0].clientY;

    if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 40) {
      if (diffX > 0) {
        goToNext();
      } else {
        goToPrev();
      }
    }
    touchStartX.current = null;
    touchStartY.current = null;
  };

  // Heart Like Trigger
  const handleLikeClick = async (e?: React.MouseEvent) => {
    const storyId = currentStory.id;
    const alreadyLiked = hasLiked[storyId];

    // Optimistic UI
    setStoryLikes((prev) => ({
      ...prev,
      [storyId]: (prev[storyId] || currentStory.likes_count) + (alreadyLiked ? -1 : 1),
    }));
    setHasLiked((prev) => ({ ...prev, [storyId]: !alreadyLiked }));

    // Trigger floating burst hearts
    const newHearts = Array.from({ length: 4 }).map((_, i) => ({
      id: Date.now() + i,
      x: (Math.random() - 0.5) * 60,
      y: -Math.random() * 80 - 40,
    }));
    setFlyingHearts((prev) => [...prev, ...newHearts]);

    setTimeout(() => {
      setFlyingHearts((prev) => prev.filter((h) => !newHearts.some((nh) => nh.id === h.id)));
    }, 1200);

    // Call API (fire-and-forget / non-blocking)
    try {
      const apiBase = getApiBaseUrl();
      await fetch(`${apiBase}/api/landing-page/stories/${storyId}/like`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
      });
    } catch (err) {
      console.warn("Failed to sync like with backend:", err);
    }
  };

  // WhatsApp Question Link Generator
  const generateWhatsAppUrl = (customText?: string) => {
    const cleanNumber = formatWhatsAppNumber(settings.whatsapp_number || settings.whatsapp_raw);
    const title = currentStory.title || "Foto Story Depo";
    const textMsg = customText
      ? `Halo Admin PT. Seberaya Akses Multi,\n\nSaya ingin bertanya mengenai foto story: *"${title}"*.\n\n${customText}\n\nTerima kasih!`
      : `Halo Admin PT. Seberaya Akses Multi, saya melihat foto story: *"${title}"* di website dan ingin bertanya mengenai harga serta layanan jemput rongsok.`;

    return `https://wa.me/${cleanNumber}?text=${encodeURIComponent(textMsg)}`;
  };

  // Comment Submission Handler
  const handleCommentSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!commentText.trim() || !commentName.trim() || isSubmittingComment) return;

    setIsSubmittingComment(true);
    const storyId = currentStory.id;
    const authorName = commentName.trim();
    const rawComment = commentText.trim();

    try {
      const apiBase = getApiBaseUrl();
      const res = await fetch(`${apiBase}/api/landing-page/stories/${storyId}/comments`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          user_name: authorName,
          comment: rawComment,
        }),
      });

      let newComment: StoryComment;

      if (res.ok) {
        const json = await res.json();
        newComment = json.data;
      } else {
        // Fallback optimistic comment if backend is temporarily unreachable
        newComment = {
          id: Date.now(),
          story_id: storyId,
          user_name: authorName,
          comment: rawComment,
          original_comment: rawComment,
          detected_language: "id",
          is_translated: false,
          likes_count: 0,
          created_at: "Baru saja",
        };
      }

      // Prepend to current comments
      setStoryComments((prev) => ({
        ...prev,
        [storyId]: [newComment, ...(prev[storyId] || [])],
      }));

      setCommentText("");
      setSubmissionSuccess(true);
      setTimeout(() => setSubmissionSuccess(false), 3000);

      // If user selected "Kirim juga via WhatsApp", open WhatsApp in new tab
      if (sendToWhatsApp) {
        let waMsg = `Nama Pengirim: *${authorName}*\n\nPesan / Pertanyaan (Bahasa Indonesia):\n*"${newComment.comment}"*`;

        if (newComment.is_translated && newComment.original_comment) {
          const langName = getLanguageName(newComment.detected_language);
          waMsg += `\n\n🌐 *Keterangan:* Pesan ini otomatis diterjemahkan dari ${langName} (Teks Asli: "${newComment.original_comment}")`;
        }

        const waUrl = generateWhatsAppUrl(waMsg);
        window.open(waUrl, "_blank", "noopener,noreferrer");
      }
    } catch (err) {
      console.error("Comment submit error:", err);
      // Still append optimistically so the visitor sees their comment
      const fallbackComment: StoryComment = {
        id: Date.now(),
        story_id: storyId,
        user_name: authorName,
        comment: rawComment,
        original_comment: rawComment,
        detected_language: "id",
        is_translated: false,
        likes_count: 0,
        created_at: "Baru saja",
      };
      setStoryComments((prev) => ({
        ...prev,
        [storyId]: [fallbackComment, ...(prev[storyId] || [])],
      }));
      setCommentText("");
    } finally {
      setIsSubmittingComment(false);
    }
  };

  const rawImage =
    currentStory.image_url ||
    currentStory.image_path ||
    fallbackImage ||
    "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1200&auto=format&fit=crop";
  const imageSrc = resolveImageUrl(rawImage, "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1200&auto=format&fit=crop");

  return (
    <div
      className="relative w-full h-full min-h-[360px] sm:min-h-[420px] md:h-full rounded-2xl md:rounded-3xl overflow-hidden shadow-xl border border-sky-100 bg-slate-950 select-none group"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
    >
      {/* 1. SEGMENTED STORY PROGRESS BARS (Like Instagram / TikTok) */}
      <div className="absolute top-2.5 sm:top-3.5 left-3 right-3 sm:left-4 sm:right-4 z-30 flex items-center gap-1.5 pointer-events-auto">
        {stories.map((story, idx) => (
          <button
            key={story.id || idx}
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              jumpToStory(idx);
            }}
            className="flex-1 h-1 sm:h-1.5 rounded-full overflow-hidden bg-white/25 backdrop-blur-xs transition hover:h-2"
            title={`Lihat story: ${story.title}`}
          >
            <div
              className={`h-full transition-all duration-75 ${
                idx < currentIndex
                  ? "w-full bg-white shadow-xs"
                  : idx === currentIndex
                  ? "bg-gradient-to-r from-amber-400 via-emerald-400 to-sky-400 shadow-md"
                  : "w-0"
              }`}
              style={idx === currentIndex ? { width: `${progress}%` } : undefined}
            />
          </button>
        ))}
      </div>

      {/* 2. TOP STORY HEADER BADGE & CONTROLS */}
      <div className="absolute top-6 sm:top-8 left-3 right-3 sm:left-4 sm:right-4 z-30 flex items-center justify-between pointer-events-auto">
        <div className="flex items-center gap-2">
          {/* Live Story Pulse Badge */}
          <span className="px-2.5 py-1 rounded-full bg-slate-900/80 backdrop-blur-md border border-white/20 text-white text-[10px] sm:text-[11px] font-bold flex items-center gap-1.5 shadow-md">
            <span className="w-2 h-2 rounded-full bg-rose-500 animate-ping" />
            <span className="w-1.5 h-1.5 rounded-full bg-rose-500 -ml-3.5" />
            <span className="hidden xs:inline">Cerita Depo</span>
            <span className="text-amber-300 font-mono text-[9px] sm:text-[10px]">
              {currentIndex + 1}/{stories.length}
            </span>
          </span>

          {/* Current Tag Badge */}
          {currentStory.tag && (
            <span className="px-2.5 py-1 rounded-full bg-white/95 text-sky-950 text-[10px] sm:text-[11px] font-extrabold shadow-md backdrop-blur-md border border-sky-200 hidden sm:inline-flex items-center gap-1">
              <Sparkles className="w-3 h-3 text-amber-500" />
              {currentStory.tag}
            </span>
          )}
        </div>

        {/* Play/Pause & Direct WhatsApp Button */}
        <div className="flex items-center gap-1.5">
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              setIsPaused((p) => !p);
            }}
            className="p-1.5 sm:p-2 rounded-full bg-slate-900/70 hover:bg-slate-900 text-white/90 hover:text-white backdrop-blur-md border border-white/20 transition shadow-md"
            title={isPaused ? "Lanjutkan Story" : "Jeda Story"}
          >
            {isPaused ? <Play className="w-3.5 h-3.5" /> : <Pause className="w-3.5 h-3.5" />}
          </button>

          <a
            href={generateWhatsAppUrl()}
            target="_blank"
            rel="noopener noreferrer"
            onClick={(e) => e.stopPropagation()}
            className="px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-[10px] sm:text-[11px] font-black transition flex items-center gap-1 shadow-lg hover:scale-105 active:scale-95"
            title="Tanya Admin Langsung via WhatsApp"
          >
            <span>Tanya WA</span>
            <ArrowUpRight className="w-3 h-3" />
          </a>
        </div>
      </div>

      {/* 3. STORY IMAGE WITH ANIMATED TRANSITIONS */}
      <AnimatePresence initial={false} custom={direction}>
        <motion.div
          key={currentStory.id || currentIndex}
          custom={direction}
          initial={false}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0, scale: 0.98 }}
          transition={{ duration: 0.35, ease: "easeInOut" }}
          className="absolute inset-0 w-full h-full"
        >
          <Image
            src={imageSrc}
            alt={currentStory.title}
            fill
            priority
            unoptimized
            className="object-cover"
            sizes="(max-width: 768px) 100vw, 60vw"
          />

          {/* Gradients for text legibility */}
          <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-slate-950/40" />
          <div className="absolute inset-0 bg-radial from-transparent to-black/30" />
        </motion.div>
      </AnimatePresence>

      {/* 4. TAP NAVIGATION HIT AREAS (Left 30% = prev, Right 30% = next) */}
      <div
        onClick={goToPrev}
        className="absolute inset-y-16 left-0 w-[30%] z-20 cursor-pointer flex items-center pl-2 group/nav opacity-0 hover:opacity-100 transition"
        title="Slide Sebelumnya"
      >
        <button
          type="button"
          className="w-8 h-8 rounded-full bg-slate-900/60 text-white backdrop-blur-md flex items-center justify-center border border-white/20 shadow-lg group-hover/nav:scale-110 transition"
        >
          <ChevronLeft className="w-5 h-5" />
        </button>
      </div>

      <div
        onClick={goToNext}
        className="absolute inset-y-16 right-0 w-[30%] z-20 cursor-pointer flex items-center justify-end pr-2 group/nav opacity-0 hover:opacity-100 transition"
        title="Slide Berikutnya"
      >
        <button
          type="button"
          className="w-8 h-8 rounded-full bg-slate-900/60 text-white backdrop-blur-md flex items-center justify-center border border-white/20 shadow-lg group-hover/nav:scale-110 transition"
        >
          <ChevronRight className="w-5 h-5" />
        </button>
      </div>

      {/* 5. FLOATING BURST HEARTS (When user likes) */}
      <div className="absolute bottom-16 right-4 sm:right-6 z-40 pointer-events-none">
        <AnimatePresence>
          {flyingHearts.map((heart) => (
            <motion.div
              key={heart.id}
              initial={{ opacity: 1, scale: 0.6, x: 0, y: 0 }}
              animate={{ opacity: 0, scale: 1.6, x: heart.x, y: heart.y }}
              exit={{ opacity: 0 }}
              transition={{ duration: 1, ease: "easeOut" }}
              className="absolute text-rose-500 fill-rose-500 pointer-events-none"
            >
              <Heart className="w-7 h-7 fill-rose-500 text-rose-500 drop-shadow-lg" />
            </motion.div>
          ))}
        </AnimatePresence>
      </div>

      {/* 6. BOTTOM CONTENT & ACTION BAR OVERLAY */}
      <div className="absolute bottom-0 inset-x-0 z-30 p-3 sm:p-5 text-white flex flex-col justify-end bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent pt-12">
        {/* Story Title & Subtitle */}
        <div className="space-y-1 pr-14 sm:pr-16">
          <div className="flex items-center gap-2">
            <span className="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-300 drop-shadow-sm">
              {currentStory.subtitle || "PT. Seberaya Akses Multi"}
            </span>
            {currentStory.created_at_human && (
              <span className="text-[9px] sm:text-[10px] text-slate-300 font-medium">
                • {currentStory.created_at_human}
              </span>
            )}
          </div>

          <h3 className="text-sm sm:text-lg md:text-xl font-black text-white leading-snug drop-shadow-md">
            {currentStory.title}
          </h3>

          {currentStory.caption && (
            <p className="text-[11px] sm:text-xs text-slate-200 line-clamp-2 max-w-xl leading-relaxed drop-shadow-xs">
              {currentStory.caption}
            </p>
          )}
        </div>

        {/* Action Controls: Heart Like, Comment Sheet Trigger, and WhatsApp Inquiry */}
        <div className="mt-3 pt-2.5 border-t border-white/15 flex items-center justify-between gap-2">
          {/* Left Buttons: Like & Comment */}
          <div className="flex items-center gap-2 sm:gap-3">
            {/* Like Button */}
            <button
              type="button"
              onClick={handleLikeClick}
              className={`px-3 py-1.5 rounded-full flex items-center gap-1.5 text-xs font-bold transition shadow-lg backdrop-blur-md border ${
                hasLiked[currentStory.id]
                  ? "bg-rose-500 text-white border-rose-400 scale-105"
                  : "bg-slate-900/80 hover:bg-slate-800 text-white border-white/20 hover:scale-105 active:scale-95"
              }`}
            >
              <Heart
                className={`w-4 h-4 transition ${
                  hasLiked[currentStory.id] ? "fill-white text-white" : "text-rose-400"
                }`}
              />
              <span>{currentLikes}</span>
            </button>

            {/* Comment Drawer Trigger */}
            <button
              type="button"
              onClick={() => {
                setIsPaused(true);
                setIsCommentOpen(true);
              }}
              className="px-3 py-1.5 rounded-full bg-slate-900/80 hover:bg-slate-800 text-white border border-white/20 backdrop-blur-md flex items-center gap-1.5 text-xs font-bold transition shadow-lg hover:scale-105 active:scale-95"
            >
              <MessageCircle className="w-4 h-4 text-sky-400" />
              <span>{currentCommentsList.length}</span>
              <span className="hidden xs:inline text-[11px] font-normal text-slate-300">Komen</span>
            </button>
          </div>

          {/* Right Action: Direct WhatsApp Chat with Photo Context */}
          <a
            href={generateWhatsAppUrl()}
            target="_blank"
            rel="noopener noreferrer"
            className="px-3 py-1.5 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-lg flex items-center gap-1.5 hover:scale-105 active:scale-95"
          >
            <span>Tanya Foto Ini</span>
            <ArrowUpRight className="w-3.5 h-3.5" />
          </a>
        </div>
      </div>

      {/* 7. INTERACTIVE COMMENT DRAWER OVERLAY (With Auto-Translate to Indonesian) */}
      <AnimatePresence>
        {isCommentOpen && (
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 30 }}
            transition={{ duration: 0.25, ease: "easeOut" }}
            className="absolute inset-0 z-50 bg-slate-950/95 backdrop-blur-md flex flex-col text-slate-100 p-4 sm:p-5"
          >
            {/* Drawer Header */}
            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
              <div className="flex items-center gap-2">
                <span className="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center">
                  <MessageCircle className="w-4 h-4" />
                </span>
                <div>
                  <h4 className="text-xs sm:text-sm font-bold text-white flex items-center gap-1.5">
                    <span>Komentar & Diskusi Foto</span>
                    <span className="px-2 py-0.2 rounded-full text-[10px] font-bold bg-sky-500/30 text-sky-300">
                      {currentCommentsList.length}
                    </span>
                  </h4>
                  <p className="text-[10px] sm:text-[11px] text-emerald-400 flex items-center gap-1 mt-0.5">
                    <Languages className="w-3 h-3" />
                    <span>Auto-Translate: Semua bahasa ditampilkan dalam Bahasa Indonesia</span>
                  </p>
                </div>
              </div>

              <button
                type="button"
                onClick={() => {
                  setIsCommentOpen(false);
                  setIsPaused(false);
                }}
                className="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Success alert */}
            {submissionSuccess && (
              <motion.div
                initial={{ opacity: 0, height: 0 }}
                animate={{ opacity: 1, height: "auto" }}
                className="my-2 p-2.5 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 text-xs flex items-center gap-2"
              >
                <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                <span>Komentar berhasil dikirim dan otomatis diterjemahkan!</span>
              </motion.div>
            )}

            {/* Comment List */}
            <div className="flex-1 overflow-y-auto my-3 space-y-2.5 pr-1">
              {currentCommentsList.length === 0 ? (
                <div className="py-8 text-center text-slate-400 space-y-1">
                  <MessageCircle className="w-8 h-8 mx-auto text-slate-600 mb-1" />
                  <p className="text-xs font-semibold">Belum ada komentar untuk foto ini.</p>
                  <p className="text-[11px] text-slate-500">Jadilah yang pertama memberikan pertanyaan atau tanggapan!</p>
                </div>
              ) : (
                currentCommentsList.map((cmt) => (
                  <div
                    key={cmt.id}
                    className="p-2.5 sm:p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-xs space-y-1.5 shadow-xs"
                  >
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <div className="w-6 h-6 rounded-full bg-gradient-to-tr from-sky-500 to-emerald-500 text-white flex items-center justify-center text-[10px] font-black uppercase shadow-xs">
                          {cmt.user_name.charAt(0)}
                        </div>
                        <span className="font-bold text-slate-200 text-xs">{cmt.user_name}</span>
                      </div>
                      <span className="text-[10px] text-slate-400 flex items-center gap-1">
                        <Clock className="w-2.5 h-2.5" />
                        {cmt.created_at || "Baru saja"}
                      </span>
                    </div>

                    {/* Indonesian translated comment */}
                    <p className="text-slate-100 font-normal leading-relaxed pl-8">
                      {cmt.comment}
                    </p>

                    {/* Translation badge if original language was foreign */}
                    {cmt.is_translated && cmt.original_comment && (
                      <div className="ml-8 text-[10px] text-emerald-400/90 bg-emerald-950/40 border border-emerald-800/40 px-2 py-0.5 rounded flex items-center gap-1.5">
                        <Languages className="w-3 h-3 text-emerald-400 shrink-0" />
                        <span className="truncate">
                          Diterjemahkan dari {getLanguageName(cmt.detected_language)} (Asli: &ldquo;{cmt.original_comment}&rdquo;)
                        </span>
                      </div>
                    )}
                  </div>
                ))
              )}
            </div>

            {/* Comment Form */}
            <form onSubmit={handleCommentSubmit} className="pt-2 border-t border-slate-800 space-y-2">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <input
                  type="text"
                  required
                  value={commentName}
                  onChange={(e) => setCommentName(e.target.value)}
                  placeholder="Nama Anda / Usaha *"
                  className="w-full px-3 py-1.5 text-xs rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500"
                />

                {/* Option: Send to WhatsApp */}
                <label className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 text-[11px] cursor-pointer hover:border-emerald-500 transition">
                  <input
                    type="checkbox"
                    checked={sendToWhatsApp}
                    onChange={(e) => setSendToWhatsApp(e.target.checked)}
                    className="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 accent-emerald-500"
                  />
                  <span className="font-semibold text-emerald-400">Kirim juga via WA (+62 815...)</span>
                </label>
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="text"
                  required
                  value={commentText}
                  onChange={(e) => setCommentText(e.target.value)}
                  placeholder="Tulis komentar/pertanyaan (Bahasa apa saja)..."
                  className="flex-1 px-3 py-2 text-xs rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500"
                />

                <button
                  type="submit"
                  disabled={isSubmittingComment || !commentText.trim() || !commentName.trim()}
                  className="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-sky-500 hover:from-emerald-400 hover:to-sky-400 disabled:opacity-50 text-slate-950 font-black text-xs flex items-center gap-1.5 transition shadow-lg shrink-0"
                >
                  {isSubmittingComment ? (
                    <span className="animate-spin w-3.5 h-3.5 border-2 border-slate-950 border-t-transparent rounded-full" />
                  ) : (
                    <>
                      <span>Kirim</span>
                      <Send className="w-3.5 h-3.5" />
                    </>
                  )}
                </button>
              </div>
            </form>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

