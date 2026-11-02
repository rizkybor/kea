import tailwindcss from '@tailwindcss/vite'

const noindex = { 'X-Robots-Tag': 'noindex, nofollow' }

/**
 * KEA_DEMO=1 → prototype statis untuk pitch: tanpa backend (API di-mock di browser, lihat app/demo/mock.ts),
 * SPA dengan hash routing + path relatif supaya bisa di-host di folder/hosting statis mana pun.
 */
const demo = process.env.KEA_DEMO === '1'

export default defineNuxtConfig({
  compatibilityDate: '2026-11-01',
  devtools: { enabled: false },
  modules: ['@pinia/nuxt'],
  css: ['~/assets/css/main.css'],
  vite: { plugins: [tailwindcss()] },

  ssr: !demo,
  ...(demo ? { router: { options: { hashMode: true } } } : {}),

  runtimeConfig: {
    // Origin Laravel untuk proxy server-side (/api, /sanctum). Prod: http://127.0.0.1 via Nginx.
    apiOrigin: 'http://127.0.0.1:8000',
    public: {
      siteUrl: 'http://localhost:3000',
      whatsappUrl: 'https://wa.me/6281200000000',
      demo,
    },
  },

  app: {
    // Aset JS/CSS dimuat relatif terhadap index.html (hosting di subfolder). Routing hash: app/router.options.ts.
    ...(demo ? { cdnURL: './', buildAssetsDir: '/assets/' } : {}),
    head: {
      htmlAttrs: { lang: 'id' },
      titleTemplate: '%s | KEA – Jendela Cakra Digital',
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'theme-color', content: '#0E3B34' },
      ],
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: (demo ? '.' : '') + '/kea-icon.svg' },
        { rel: 'icon', type: 'image/png', sizes: '32x32', href: (demo ? '.' : '') + '/favicon-32.png' },
        { rel: 'apple-touch-icon', href: (demo ? '.' : '') + '/apple-touch-icon.png' },
        { rel: 'manifest', href: (demo ? '.' : '') + '/site.webmanifest' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap' },
      ],
    },
  },

  // Publik: SSR + cache ISR 10 menit. Learner/checkout: SPA, noindex (PRD §5 Technical SEO).
  routeRules: demo ? {} : {
    '/': { isr: 600 },
    '/program': { isr: 600 },
    '/program/**': { isr: 600 },
    '/dashboard/**': { ssr: false, headers: noindex },
    '/learn/**': { ssr: false, headers: noindex },
    '/checkout/**': { ssr: false, headers: noindex },
    '/mentor/**': { ssr: false, headers: noindex },
    '/partner/**': { ssr: false, headers: noindex },
    '/lowongan': { isr: 600 },
    '/lowongan/**': { isr: 600 },
    '/masuk': { headers: noindex },
    '/daftar': { headers: noindex },
    '/lupa-password': { headers: noindex },
    '/reset-password': { ssr: false, headers: noindex },
    '/verifikasi-email': { ssr: false, headers: noindex },
  },

  typescript: { strict: true },
})
