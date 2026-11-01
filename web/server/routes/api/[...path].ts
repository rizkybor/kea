/**
 * Proxy same-origin ke Laravel supaya cookie sesi Sanctum + CSRF bekerja seperti di produksi
 * (di produksi Nginx yang meneruskan /api dan /sanctum langsung ke PHP-FPM).
 */
export default defineEventHandler((event) => {
  const { apiOrigin } = useRuntimeConfig(event)
  return proxyRequest(event, apiOrigin + event.path)
})
