import React from "react";
import { getLandingPageData } from "@/services/landing-page.service";
import LandingPageClient from "@/components/layout/LandingPageClient";

export default async function HomePage() {
  const initialData = await getLandingPageData();

  return <LandingPageClient initialData={initialData} />;
}
