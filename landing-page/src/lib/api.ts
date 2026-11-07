export function getApiBaseUrl(): string {
  if (typeof window !== "undefined") {
    // In client browser:
    // If NEXT_PUBLIC_API_BASE_URL is explicitly defined, use it.
    // Otherwise fallback to the current website domain origin.
    if (process.env.NEXT_PUBLIC_API_BASE_URL) {
      return process.env.NEXT_PUBLIC_API_BASE_URL.replace(/\/+$/, "");
    }
    return window.location.origin;
  }

  // On Server-side (Node.js SSR):
  return (
    process.env.INTERNAL_API_BASE_URL ||
    process.env.NEXT_PUBLIC_API_BASE_URL ||
    "https://backend-seberayalogam.amiitrans.com"
  ).replace(/\/+$/, "");
}

/**
 * Safely resolves relative image paths (e.g. 'uploads/...') to absolute URLs
 * using the configured backend API host.
 */
export function resolveImageUrl(path?: string | null, fallback = ""): string {
  if (!path || typeof path !== "string" || path.trim() === "") {
    return fallback;
  }
  const cleanPath = path.trim();
  if (cleanPath.startsWith("http://") || cleanPath.startsWith("https://")) {
    return cleanPath;
  }
  if (cleanPath.startsWith("./") || cleanPath.startsWith("/images/")) {
    return cleanPath;
  }
  const baseUrl = getApiBaseUrl();
  const normalized = cleanPath.replace(/^\/+/, "");
  return `${baseUrl}/${normalized}`;
}

/**
 * Normalizes any Indonesian phone number into standard international format (62...)
 * for WhatsApp links (wa.me / api.whatsapp.com), preventing WhatsApp from
 * misinterpreting leading '0' as Japan (+81).
 */
export function formatWhatsAppNumber(phone?: string | null): string {
  if (!phone || typeof phone !== "string") {
    return "6281584234353";
  }
  let clean = phone.replace(/\D/g, "");
  if (!clean) {
    return "6281584234353";
  }
  if (clean.startsWith("0")) {
    clean = "62" + clean.substring(1);
  } else if (!clean.startsWith("62")) {
    clean = "62" + clean;
  }
  return clean;
}

export async function fetchApi<T>(endpoint: string, options?: RequestInit): Promise<T> {
  const baseUrl = getApiBaseUrl();
  const cleanEndpoint = endpoint.replace(/^\/+/, "");
  const url = `${baseUrl}/${cleanEndpoint}`;

  const isClient = typeof window !== "undefined";

  try {
    const res = await fetch(url, {
      ...options,
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        Connection: "close",
        ...(options?.headers || {}),
      },
      // In client browser: no-store ensures fresh data.
      // During build time (server-side export): allow caching so Next.js static generation doesn't bail out.
      ...(isClient ? { cache: "no-store" } : {}),
    });

    if (!res.ok) {
      throw new Error(`API error ${res.status}: ${res.statusText}`);
    }

    const json = await res.json();
    return json;
  } catch (error) {
    if (isClient) {
      console.warn(`[API Client Warning] Failed fetching from ${url}:`, error);
    }
    throw error;
  }
}

