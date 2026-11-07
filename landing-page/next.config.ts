import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Default to export for standard cPanel public_html deployment
  output: process.env.NEXT_OUTPUT_MODE === "standalone" ? "standalone" : "export",
  images: {
    // Enable unoptimized for static export on cPanel
    unoptimized: true,
    remotePatterns: [
      { protocol: "https", hostname: "**" },
      { protocol: "http", hostname: "**" },
    ],
  },
  turbopack: {
    rules: {
      "*.css": {
        loaders: ["@tailwindcss/turbopack"],
        as: "*.css",
      },
    },
  },
};

export default nextConfig;
