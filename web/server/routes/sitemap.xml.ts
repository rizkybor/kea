interface ProgramPage { data: { slug: string }[]; meta: { last_page: number } }

/** sitemap.xml otomatis dari katalog yang tayang (PRD §5 Technical SEO). Cache 1 jam. */
export default defineCachedEventHandler(async (event) => {
  const { apiOrigin, public: { siteUrl } } = useRuntimeConfig(event)
  const slugs: string[] = []
  let page = 1
  let last = 1
  do {
    const res = await $fetch<ProgramPage>(`${apiOrigin}/api/v1/programs`, { query: { per_page: 24, page }, headers: { Accept: 'application/json' } })
    slugs.push(...res.data.map(p => p.slug))
    last = res.meta.last_page
    page++
  } while (page <= last)

  const urls = ['/', '/program', '/verify', ...slugs.map(s => `/program/${s}`)]
  setHeader(event, 'Content-Type', 'application/xml; charset=utf-8')
  return `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls.map(u => `  <url><loc>${siteUrl}${u}</loc></url>`).join('\n')}\n</urlset>\n`
}, { maxAge: 3600 })
