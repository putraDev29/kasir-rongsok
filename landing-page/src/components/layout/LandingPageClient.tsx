"use client";

import React, { useEffect, useState, useCallback } from "react";
import { LandingPageData } from "@/types/landing-page";
import { getLandingPageData } from "@/services/landing-page.service";
import { getApiBaseUrl } from "@/lib/api";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import WhatsAppButton from "@/components/layout/WhatsAppButton";
import HeroSection from "@/components/sections/HeroSection";
import AboutSection from "@/components/sections/AboutSection";
import ServicesSection from "@/components/sections/ServicesSection";
import CategoriesSection from "@/components/sections/CategoriesSection";
import WhyChooseUsSection from "@/components/sections/WhyChooseUsSection";
import ProcessSection from "@/components/sections/ProcessSection";
import GallerySection from "@/components/sections/GallerySection";
import StatisticsSection from "@/components/sections/StatisticsSection";
import LocationSection from "@/components/sections/LocationSection";
import TestimonialsSection from "@/components/sections/TestimonialsSection";
import FAQSection from "@/components/sections/FAQSection";
import ContactSection from "@/components/sections/ContactSection";

interface LandingPageClientProps {
  initialData: LandingPageData;
}

export default function LandingPageClient({ initialData }: LandingPageClientProps) {
  const [data, setData] = useState<LandingPageData>(initialData);

  // Live client-side data synchronization from API
  const refreshData = useCallback(async () => {
    try {
      console.log("[Seberaya Live Sync] Fetching fresh data from API backend...");
      const fresh = await getLandingPageData();
      if (fresh && fresh.settings && fresh.settings.company_name) {
        console.log(
          `[Seberaya Live Sync] Synchronized successfully! Categories: ${fresh.categories?.length}, Stories: ${fresh.stories?.length}`
        );
        setData(fresh);
      }
    } catch (err) {
      console.warn("[Live Sync Warning] Keeping existing data due to connection:", err);
    }
  }, []);

  useEffect(() => {
    console.log("[Seberaya Live Sync] Initialized. API target URL:", getApiBaseUrl());
    // 1. Fetch immediately on client mount
    refreshData();

    // 2. Refresh when window regains focus (e.g. user returns to the tab)
    const handleFocus = () => refreshData();
    window.addEventListener("focus", handleFocus);

    // 3. Periodic polling every 30 seconds for live scrap price changes
    const interval = setInterval(refreshData, 30000);

    return () => {
      window.removeEventListener("focus", handleFocus);
      clearInterval(interval);
    };
  }, [refreshData]);

  return (
    <div className="min-h-screen bg-white text-slate-800 flex flex-col overflow-x-hidden selection:bg-sky-500 selection:text-white">
      {/* Dynamic Sticky Header */}
      <Navbar settings={data.settings} />

      {/* Main Content Sections */}
      <main className="flex-1">
        {/* A. Hero Section (With Interactive Story Viewer) */}
        <HeroSection
          hero={data.hero}
          settings={data.settings}
          statistics={data.statistics}
          stories={data.stories}
        />

        {/* B. About Us */}
        <AboutSection
          about={data.about}
          settings={data.settings}
        />

        {/* C. Services */}
        <ServicesSection
          services={data.services}
          settings={data.settings}
        />

        {/* D. Scrap Categories & Live Prices */}
        <CategoriesSection
          categories={data.categories}
          settings={data.settings}
        />

        {/* E. Why Choose Us */}
        <WhyChooseUsSection
          items={data.why_choose_us}
          settings={data.settings}
        />

        {/* F. Business Process / Workflow */}
        <ProcessSection
          steps={data.process}
          settings={data.settings}
        />

        {/* G. Operations Gallery with Lightbox */}
        <GallerySection
          gallery={data.gallery}
        />

        {/* H. Animated Counter Statistics */}
        <StatisticsSection
          statistics={data.statistics}
        />

        {/* I. Google Maps Location */}
        <LocationSection
          location={data.location}
          settings={data.settings}
        />

        {/* J. Testimonials */}
        <TestimonialsSection
          testimonials={data.testimonials}
        />

        {/* K. FAQ Accordion */}
        <FAQSection
          faqs={data.faqs}
          settings={data.settings}
        />

        {/* L. Contact & Inquiry Form */}
        <ContactSection
          settings={data.settings}
        />
      </main>

      {/* Modern Dynamic Footer */}
      <Footer
        settings={data.settings}
        categories={data.categories}
      />

      {/* WhatsApp Floating Action Button */}
      <WhatsAppButton
        settings={data.settings}
      />
    </div>
  );
}

