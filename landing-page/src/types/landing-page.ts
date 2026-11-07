export interface LandingSettings {
  app_name: string;
  company_name: string;
  company_tagline: string;
  company_phone: string;
  company_address: string;
  logo_url: string;
  whatsapp_number: string;
  whatsapp_raw: string;
  whatsapp_message: string;
  whatsapp_url: string;
  email: string;
  operating_hours: string;
  maps_embed_url: string;
  maps_url: string;
  latitude: string;
  longitude: string;
  hero_badge: string;
  hero_headline: string;
  hero_subheadline: string;
  hero_cta_primary: string;
  hero_cta_secondary: string;
  about_title: string;
  about_description: string;
  about_vision: string;
  about_mission: string;
  about_years_experience: string;
  about_image?: string;
  social_facebook?: string;
  social_instagram?: string;
  social_tiktok?: string;
  social_youtube?: string;
  seo_meta_title: string;
  seo_meta_description: string;
  seo_keywords: string;
}

export interface HeroData {
  badge: string;
  headline: string;
  subheadline: string;
  cta_primary: string;
  cta_secondary: string;
  background_image?: string;
  main_image?: string;
  main_title?: string;
  main_subtitle?: string;
  sub1_image?: string;
  sub1_title?: string;
  sub1_tag?: string;
  sub2_image?: string;
  sub2_title?: string;
  sub2_tag?: string;
  sub3_image?: string;
  sub3_title?: string;
  sub3_tag?: string;
}

export interface AboutData {
  title: string;
  description: string;
  vision: string;
  mission: string;
  years_experience: string;
  image: string;
}

export interface ServiceItem {
  id: number;
  title: string;
  slug: string;
  icon: string;
  short_description: string;
  full_description?: string;
  image_url?: string;
  order: number;
  is_active: boolean;
}

export interface ScrapPriceItem {
  id: number;
  code: string;
  name: string;
  unit: string;
  buy_price_per_kg: number;
  description?: string;
}

export interface CategoryItem {
  id: number;
  name: string;
  slug: string;
  description?: string;
  items_count: number;
  items: ScrapPriceItem[];
}

export interface WhyChooseUsItem {
  icon: string;
  title: string;
  description: string;
}

export interface ProcessStep {
  step: number;
  title: string;
  description: string;
  icon: string;
}

export interface GalleryItem {
  id: number;
  title: string;
  category: string;
  image_path: string;
  image_url: string;
  caption?: string;
  order: number;
}

export interface StatisticItem {
  id: number;
  label: string;
  value: string;
  prefix?: string;
  suffix?: string;
  icon?: string;
  order: number;
}

export interface LocationData {
  company_name: string;
  address: string;
  phone: string;
  whatsapp: string;
  whatsapp_url: string;
  operating_hours: string;
  maps_url: string;
  maps_embed_url: string;
  latitude: string;
  longitude: string;
}

export interface TestimonialItem {
  id: number;
  client_name: string;
  client_role: string;
  rating: number;
  avatar_url?: string;
  content: string;
  order: number;
}

export interface FaqItem {
  id: number;
  question: string;
  answer: string;
  category?: string;
  order: number;
}

export interface SeoData {
  meta_title: string;
  meta_description: string;
  keywords: string;
  company_name: string;
  canonical_url: string;
  og_image: string;
}

export interface StoryComment {
  id: number;
  story_id: number;
  user_name: string;
  comment: string;
  original_comment?: string;
  detected_language?: string;
  is_translated: boolean;
  likes_count: number;
  created_at: string;
}

export interface StoryItem {
  id: number;
  title: string;
  subtitle?: string;
  tag?: string;
  image_path: string;
  image_url: string;
  caption?: string;
  likes_count: number;
  comments_count: number;
  created_at?: string;
  created_at_human?: string;
  comments: StoryComment[];
}

export interface LandingPageData {
  settings: LandingSettings;
  hero: HeroData;
  about: AboutData;
  services: ServiceItem[];
  categories: CategoryItem[];
  why_choose_us: WhyChooseUsItem[];
  process: ProcessStep[];
  gallery: GalleryItem[];
  stories?: StoryItem[];
  statistics: StatisticItem[];
  location: LocationData;
  testimonials: TestimonialItem[];
  faqs: FaqItem[];
  seo: SeoData;
}

export interface ContactSubmissionPayload {
  name: string;
  phone: string;
  material_type?: string;
  estimated_weight?: string;
  address?: string;
  message?: string;
}

export interface ContactSubmissionResponse {
  success: boolean;
  message: string;
  data?: {
    id: number;
    direct_whatsapp_url?: string;
  };
  errors?: Record<string, string[]>;
}

export interface TestimonialSubmissionPayload {
  client_name: string;
  client_role?: string;
  rating: number;
  content: string;
}

export interface TestimonialSubmissionResponse {
  success: boolean;
  message: string;
  data?: TestimonialItem;
  errors?: Record<string, string[]>;
}


