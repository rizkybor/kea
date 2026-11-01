export default defineEventHandler((event) => {
  const { public: { siteUrl } } = useRuntimeConfig(event)
  setHeader(event, 'Content-Type', 'text/plain; charset=utf-8')
  return [
    'User-agent: *',
    'Allow: /',
    'Disallow: /dashboard',
    'Disallow: /learn',
    'Disallow: /checkout',
    'Disallow: /mentor',
    'Disallow: /api/',
    'Disallow: /admin',
    `Sitemap: ${siteUrl}/sitemap.xml`,
  ].join('\n')
})
