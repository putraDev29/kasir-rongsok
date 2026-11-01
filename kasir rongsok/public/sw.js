// Service Worker for Kasir Rongsok PWA
const CACHE_NAME = 'kasir-rongsok-pwa-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Only intercept GET requests for POS safety
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(() => {
            return new Response(
                '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Offline</title><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family:sans-serif;text-align:center;padding:40px 20px;background:#0f172a;color:#f8fafc;"><h2>Koneksi Terputus</h2><p>Tidak dapat terhubung ke PC Server Kasir Rongsok.<br>Pastikan tablet masih terhubung ke Wi-Fi yang sama dengan PC server.</p><button onclick="location.reload()" style="background:#059669;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:15px;">Coba Hubungkan Ulang</button></body></html>',
                { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            );
        })
    );
});

