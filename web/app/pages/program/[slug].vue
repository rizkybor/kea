<script setup lang="ts">
import type { Order, ProgramDetail } from '~/types/api'
import { rupiah } from '~/utils/format'

const route = useRoute()
const router = useRouter()
const api = useApi()
const auth = useAuthStore()
const { public: { siteUrl, whatsappUrl } } = useRuntimeConfig()
const slug = route.params.slug as string

const { data, error, refresh } = await useAsyncData(`program-${slug}`, () => api<{ data: ProgramDetail }>(`/programs/${slug}`))
if (error.value || !data.value) {
  throw createError({ statusCode: 404, statusMessage: 'Program tidak ditemukan', fatal: true })
}
const program = computed(() => data.value!.data)

const needsCohort = computed(() => program.value.type !== 'video_course')
const upcoming = computed(() => program.value.cohorts.filter(c => new Date(c.starts_at) > new Date()))
const selectedId = ref<number | null>(Number(route.query.cohort) || upcoming.value.find(c => c.is_available)?.id || null)
const selected = computed(() => upcoming.value.find(c => c.id === selectedId.value) ?? null)

const ctaLabel = computed(() => {
  if (!needsCohort.value) return program.value.selling_price === 0 ? 'Ambil gratis' : 'Beli sekarang'
  if (!selected.value) return 'Pilih cohort dulu'
  return `Daftar ${selected.value.name.split(' · ')[0]}`
})

const submitting = ref(false)
const errorMessage = ref<string | null>(null)
const errorCode = ref<string | null>(null)

async function startCheckout() {
  errorMessage.value = null
  errorCode.value = null
  if (needsCohort.value && !selected.value) return

  await auth.fetchUser()
  if (!auth.isLoggedIn) {
    const back = `/program/${slug}?${new URLSearchParams({ ...(selected.value ? { cohort: String(selected.value.id) } : {}), checkout: '1' })}`
    return navigateTo({ path: '/daftar', query: { redirect: back } })
  }

  submitting.value = true
  try {
    const res = await api<{ data: Order }>('/orders', { method: 'POST', body: { program: slug, cohort_id: selected.value?.id ?? null } })
    await navigateTo(`/checkout/${res.data.uuid}`)
  } catch (e) {
    const err = apiError(e)
    errorMessage.value = err.message
    errorCode.value = err.code ?? null
    if (err.code === 'cohort_full') refresh()
  } finally {
    submitting.value = false
  }
}

const resent = ref(false)
async function resendVerification() {
  await api('/auth/email/resend', { method: 'POST' }).catch(() => {})
  resent.value = true
}

onMounted(() => {
  if (route.query.checkout === '1') {
    router.replace({ query: { ...route.query, checkout: undefined } })
    startCheckout()
  }
})

const lessonCount = computed(() => program.value.sections.reduce((n, s) => n + s.lessons.length, 0))
const liveCount = computed(() => program.value.sections.reduce((n, s) => n + s.lessons.filter(l => l.type === 'live').length, 0))
const typeQuery = computed(() => `/program?type=${program.value.type}`)

useSeoMeta({
  title: () => program.value.title,
  description: () => program.value.summary,
  ogTitle: () => `${program.value.title} – KEA`,
  ogDescription: () => program.value.summary,
  ogImage: () => program.value.thumbnail_url ?? `${siteUrl}/icon-512.png`,
  twitterCard: 'summary_large_image',
})
useHead({
  link: [{ rel: 'canonical', href: `${siteUrl}/program/${slug}` }],
  script: [{
    type: 'application/ld+json',
    innerHTML: () => JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'Course',
      name: program.value.title,
      description: program.value.summary,
      provider: { '@type': 'Organization', name: 'KEA', sameAs: siteUrl },
      offers: { '@type': 'Offer', price: program.value.selling_price, priceCurrency: 'IDR', category: program.value.selling_price ? 'Paid' : 'Free' },
      ...(program.value.rating ? { aggregateRating: { '@type': 'AggregateRating', ratingValue: program.value.rating, ratingCount: program.value.rating_count, bestRating: 5 } } : {}),
      hasCourseInstance: (needsCohort.value ? upcoming.value : [null]).map(c => ({
        '@type': 'CourseInstance',
        courseMode: needsCohort.value ? 'Online' : 'Online',
        courseWorkload: program.value.duration_label ?? undefined,
        ...(c ? { name: c.name, startDate: c.starts_at, endDate: c.ends_at ?? undefined } : {}),
      })),
    }),
  }],
})
</script>

<template>
  <div>
    <section class="bg-kea-forest text-white">
      <div class="container-kea flex flex-col gap-4 py-10 md:py-14">
        <AppBreadcrumb light :items="[{ label: 'Beranda', to: '/' }, { label: program.type_label, to: typeQuery }, { label: program.title }]" />
        <div class="flex flex-wrap gap-2">
          <span class="badge bg-kea-olive text-kea-ink">{{ program.type_label }}</span>
          <span v-if="program.has_job_guarantee" class="badge bg-kea-ember text-kea-ink">Garansi kerja</span>
        </div>
        <h1 class="max-w-[820px] text-[32px] leading-[1.15] font-extrabold md:text-[46px]">{{ program.title }}<template v-if="program.type === 'bootcamp'"> Bootcamp</template></h1>
        <p class="max-w-[760px] text-[17px] leading-relaxed text-kea-on-forest">{{ program.summary }}</p>
        <div class="flex flex-wrap gap-x-6 gap-y-2 text-[15px] text-kea-on-forest">
          <span v-if="program.duration_label">{{ program.duration_label }}</span>
          <span v-if="program.format_label">{{ program.format_label }}</span>
          <span>Level: {{ program.level }}</span>
          <span v-if="program.rating">★ {{ program.rating.toLocaleString('id-ID') }} dari {{ program.rating_count }} alumni</span>
        </div>
      </div>
    </section>

    <nav aria-label="Bagian halaman" class="sticky top-0 z-10 border-b border-kea-line bg-white">
      <div class="container-kea flex gap-1 overflow-x-auto">
        <a v-for="t in [['ringkasan', 'Ringkasan'], ['silabus', 'Silabus'], ['mentor', 'Mentor'], ['jadwal', needsCohort ? 'Jadwal & Harga' : 'Harga'], ['faq', 'FAQ']]" :key="t[0]" :href="`#${t[0]}`" class="shrink-0 px-3 py-4 text-[15px] font-semibold whitespace-nowrap text-kea-ink no-underline hover:text-kea-moss">{{ t[1] }}</a>
      </div>
    </nav>

    <div class="container-kea flex flex-col gap-10 py-10 lg:flex-row lg:items-start">
      <main class="flex min-w-0 flex-1 flex-col gap-14">
        <section id="ringkasan" class="flex scroll-mt-20 flex-col gap-5">
          <h2 class="text-[28px] font-extrabold">Yang kamu dapat</h2>
          <ul class="grid gap-4 sm:grid-cols-2">
            <li v-for="b in program.benefits.length ? program.benefits : [
              { title: `${lessonCount} materi`, body: liveCount ? `${liveCount} sesi live + rekaman.` : 'Akses selamanya, belajar kapan saja.' },
              { title: 'Sertifikat terverifikasi', body: 'Kode & QR bisa dicek recruiter.' },
            ]" :key="b.title" class="card flex flex-col gap-1 p-5">
              <b>{{ b.title }}</b><span class="text-[15px] text-kea-muted">{{ b.body }}</span>
            </li>
          </ul>
          <p v-if="program.description && program.description !== program.summary" class="leading-relaxed text-kea-muted">{{ program.description }}</p>
        </section>

        <section id="silabus" class="flex scroll-mt-20 flex-col gap-3">
          <h2 class="mb-2 text-[28px] font-extrabold">Silabus</h2>
          <details v-for="(s, i) in program.sections" :key="s.title" :open="i === 0" class="rounded-xl border border-kea-line px-5 py-4">
            <summary class="font-bold">{{ s.title }} <span class="font-medium text-kea-muted">· {{ s.lessons.length }} materi</span></summary>
            <ul class="mt-3 flex flex-col gap-2 text-[15px]">
              <li v-for="l in s.lessons" :key="l.title" class="flex flex-wrap justify-between gap-2 border-t border-kea-line pt-2">
                <span>{{ l.title }} <span v-if="l.is_preview" class="badge-olive ml-1">Preview</span></span>
                <span class="text-sm text-kea-muted">{{ l.kind_label }}</span>
              </li>
            </ul>
          </details>
        </section>

        <section v-if="program.mentors.length" id="mentor" class="flex scroll-mt-20 flex-col gap-5">
          <h2 class="text-[28px] font-extrabold">Mentor</h2>
          <div class="grid gap-4 sm:grid-cols-2">
            <div v-for="m in program.mentors" :key="m.name" class="card flex items-center gap-4 p-5">
              <span class="flex size-14 flex-none items-center justify-center rounded-full bg-kea-olive-50 text-lg font-extrabold text-kea-olive-900" aria-hidden="true">{{ m.name.charAt(0) }}</span>
              <span class="flex flex-col"><b>{{ m.name }}</b><span class="text-sm text-kea-muted">{{ [m.headline, m.company].filter(Boolean).join(' · ') }}</span></span>
            </div>
          </div>
        </section>
        <section v-else id="mentor" class="scroll-mt-20" />

        <section v-if="program.testimonials.length" class="flex flex-col gap-5">
          <h2 class="text-[28px] font-extrabold">Kata alumni</h2>
          <figure v-for="t in program.testimonials" :key="t.name" class="card flex flex-col gap-3 p-6">
            <blockquote class="leading-relaxed">“{{ t.quote }}”</blockquote>
            <figcaption class="text-sm text-kea-muted"><b class="text-kea-ink">{{ t.name }}</b> · {{ t.role }} di {{ t.company }}</figcaption>
          </figure>
        </section>

        <section id="faq" class="flex scroll-mt-20 flex-col gap-4">
          <h2 class="text-[28px] font-extrabold">FAQ</h2>
          <FaqList :items="program.faqs" />
        </section>
      </main>

      <aside id="jadwal" aria-label="Pilih jadwal dan daftar" class="card flex scroll-mt-20 flex-col gap-5 p-6 lg:sticky lg:top-20 lg:w-[400px] lg:flex-none">
        <div class="aspect-video overflow-hidden rounded-xl">
          <iframe v-if="program.preview_video_url" :src="program.preview_video_url" title="Video preview program" class="h-full w-full" loading="lazy" allowfullscreen />
          <ProgramThumb v-else :src="program.thumbnail_url" :title="program.title" :type="program.type" height="h-full" />
        </div>

        <fieldset v-if="needsCohort" class="flex flex-col gap-2.5">
          <legend class="mb-2.5 font-bold">Pilih cohort</legend>
          <CohortOption v-for="c in upcoming" :key="c.id" :cohort="c" :selected="c.id === selectedId" @select="selectedId = c.id" />
          <p v-if="!upcoming.length" class="text-sm text-kea-muted">Belum ada jadwal cohort baru. Hubungi kami untuk info batch berikutnya.</p>
        </fieldset>

        <div class="flex flex-col gap-1">
          <s v-if="program.promo_price" class="text-sm text-kea-muted">{{ rupiah(program.price) }}</s>
          <span class="text-[28px] font-extrabold">{{ rupiah(program.selling_price) }}</span>
          <span v-if="program.installment_from" class="text-sm text-kea-muted">Cicilan mulai {{ rupiah(program.installment_from) }}/bulan</span>
        </div>

        <ErrorAlert :message="errorMessage" />
        <div v-if="errorCode === 'email_unverified'" class="text-sm">
          <button v-if="!resent" type="button" class="font-bold text-kea-forest underline" @click="resendVerification">Kirim ulang email verifikasi</button>
          <span v-else class="text-kea-muted">Email verifikasi sudah dikirim. Cek inbox kamu.</span>
        </div>
        <NuxtLink v-if="errorCode === 'already_enrolled'" to="/dashboard" class="text-sm font-bold">Buka dashboard →</NuxtLink>

        <button type="button" class="btn-primary w-full py-4 text-base" :disabled="submitting || (needsCohort && !selected)" @click="startCheckout">
          {{ submitting ? 'Memproses…' : ctaLabel }}
        </button>
        <a :href="whatsappUrl" target="_blank" rel="noopener" class="text-center text-sm font-bold">Konsultasi dulu via WhatsApp</a>
      </aside>
    </div>
  </div>
</template>
