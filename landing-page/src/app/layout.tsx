import type { Metadata } from "next";
import "./globals.css";
import { getLandingPageData } from "@/services/landing-page.service";

export async function generateMetadata(): Promise<Metadata> {
  const data = await getLandingPageData();
  const seo = data.seo;
  const settings = data.settings;

  return {
    title: seo.meta_title || `${settings.company_name} | Jual Beli Rongsok & Besi Tua Terpercaya`,
    description:
      seo.meta_description ||
      "Pengepul rongsok terpercaya terima besi tua, tembaga, kuningan, kardus, plastik, dan aki bekas harga tinggi. Timbangan digital transparan, bayar tunai di tempat.",
    keywords: seo.keywords || "jual beli rongsok, pengepul rongsok, jual besi tua",
    authors: [{ name: settings.company_name }],
    openGraph: {
      title: seo.meta_title,
      description: seo.meta_description,
      url: seo.canonical_url || "http://localhost:3000",
      siteName: settings.company_name,
      images: [
        {
          url: seo.og_image || "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1200",
          width: 1200,
          height: 630,
          alt: settings.company_name,
        },
      ],
      locale: "id_ID",
      type: "website",
    },
    twitter: {
      card: "summary_large_image",
      title: seo.meta_title,
      description: seo.meta_description,
      images: [seo.og_image || "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?q=80&w=1200"],
    },
    icons: {
      icon: [
        { url: "/icon.png", type: "image/png" },
        { url: "/logo.png", type: "image/png" },
      ],
      shortcut: "/icon.png",
      apple: "/icon.png",
    },
    robots: {
      index: true,
      follow: true,
    },
  };
}

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const data = await getLandingPageData();
  const { settings, location } = data;

  // JSON-LD LocalBusiness Schema
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "RecyclingCenter",
    name: settings.company_name,
    description: settings.company_tagline,
    telephone: settings.company_phone,
    address: {
      "@type": "PostalAddress",
      streetAddress: settings.company_address,
      addressCountry: "ID",
    },
    geo: {
      "@type": "GeoCoordinates",
      latitude: location.latitude,
      longitude: location.longitude,
    },
    openingHours: location.operating_hours,
    url: "http://localhost:3000",
    image: settings.logo_url,
    priceRange: "$$",
  };

  return (
    <html lang="id" className="scroll-smooth" suppressHydrationWarning>
      <body className="bg-white text-slate-800 antialiased selection:bg-emerald-500 selection:text-white" suppressHydrationWarning>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
        />
        {children}
      </body>
    </html>
  );
}
