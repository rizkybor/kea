<script setup lang="ts">
import type { Paginated, ProgramCard, Testimonial } from '~/types/api'

const api = useApi()
const { public: { siteUrl, whatsappUrl } } = useRuntimeConfig()

const [{ data: programs }, { data: testimonials }, { data: stats }] = await Promise.all([
  useAsyncData('home-programs', () => api<Paginated<ProgramCard>>('/programs', { query: { type: 'bootcamp', sort: 'start', per_page: 3 } })),
  useAsyncData('home-testimonials', () => api<{ data: Testimonial[] }>('/testimonials')),
  useAsyncData('home-stats', () => api<{ data: { alumni: number; learners: number; programs: number; rating: number } }>('/stats')),
])

const formats = [
  { title: 'Bootcamp', body: 'Kelas live per cohort, tugas mingguan, final project, dan penyaluran kerja.', cta: 'Lihat bootcamp', type: 'bootcamp', icon: 'M3 7l9-4 9 4-9 4-9-4z M7 9v5c0 1.7 2.2 3 5 3s5-1.3 5-3V9' },
  { title: 'Short Class', body: 'Kelas live intensif 1–4 sesi untuk satu skill spesifik.', cta: 'Lihat short class', type: 'short_class', icon: 'M12 3a9 9 0 100 18 9 9 0 000-18z M12 7v5l3 2' },
  { title: 'Video Course', body: 'Belajar mandiri kapan saja, akses selamanya, kuis tiap bab.', cta: 'Lihat video course', type: 'video_course', icon: 'M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z M10 9l5 3-5 3V9z' },
  { title: 'Event & Webinar', body: 'Sesi sharing bareng praktisi, gratis maupun berbayar.', cta: 'Lihat jadwal', type: 'event', icon: 'M5 4h14a2 2 0 012 2v13a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z M3 9h18 M8 2v4 M16 2v4' },
]

const steps = [
  { title: 'Pilih jalur', body: 'Kuesioner minat & target karier merekomendasikan program.' },
  { title: 'Belajar dari praktisi', body: 'Sesi live, video, dan review tugas oleh mentor.' },
  { title: 'Bangun portofolio', body: 'Final project berbasis studi kasus industri.' },
  { title: 'Disalurkan ke mitra', body: 'Profil terverifikasi masuk talent pool mitra hiring.' },
]

const faqs = [
  { q: 'Apakah ada cicilan?', a: 'Ada. Saat checkout pilih Kartu Kredit / Cicilan (cicilan 0% di bank mitra) atau paylater yang tersedia di gateway pembayaran.' },
  { q: 'Bagaimana kalau saya ketinggalan sesi live?', a: 'Rekaman setiap sesi tersedia di Ruang Kelas setelah sesi selesai. Untuk syarat lulus, kehadiran live minimal 80%.' },
  { q: 'Apa syarat garansi kerja?', a: 'Kehadiran live minimal 80%, semua tugas wajib bernilai minimal 70, final project lulus review, dan aktif melamar lewat job connector selama 6 bulan setelah lulus. Detail per program ada di halaman program.' },
  { q: 'Apakah sertifikatnya bisa diverifikasi?', a: 'Ya, setiap sertifikat punya kode & QR yang bisa dicek publik di halaman Verifikasi Sertifikat.' },
]

const statItems = computed(() => [
  { value: stats.value ? `${stats.value.data.learners.toLocaleString('id-ID')}` : '-', label: 'Learner' },
  { value: stats.value ? String(stats.value.data.programs) : '-', label: 'Program aktif' },
  { value: stats.value?.data.rating ? `${stats.value.data.rating.toLocaleString('id-ID')}/5` : '-', label: 'Rating kelas' },
])

useSeoMeta({
  title: 'Belajar skill siap kerja bareng praktisi industri',
  description: 'KEA — ekosistem pembelajaran digital terpadu: bootcamp live, short class, dan video course bersama praktisi industri, lengkap dengan portofolio, sertifikat terverifikasi, dan penyaluran kerja.',
  ogTitle: 'KEA — Knowledge Ecosystem for Advancement',
  ogDescription: 'Transforming Knowledge into Sustainable Competence.',
  ogImage: `${siteUrl}/icon-512.png`,
  twitterCard: 'summary',
})
useHead({
  link: [{ rel: 'canonical', href: siteUrl + '/' }],
  script: [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'EducationalOrganization',
      name: 'KEA',
      alternateName: 'Knowledge Ecosystem for Advancement',
      slogan: 'Transforming Knowledge into Sustainable Competence.',
      parentOrganization: { '@type': 'Organization', name: 'Jendela Cakra Digital' },
      url: siteUrl,
      logo: `${siteUrl}/icon-512.png`,
    }),
  }],
})
</script>

<template>
  <main>
    <section class="bg-kea-forest text-white">
      <div class="container-kea flex flex-wrap items-center gap-12 py-16 md:py-20">
        <div class="flex flex-[1_1_480px] flex-col gap-6">
          <span class="self-start rounded-full bg-kea-olive px-3 py-1.5 text-sm font-semibold text-kea-ink">Transforming Knowledge into Sustainable Competence</span>
          <h1 class="text-[38px] leading-[1.1] font-extrabold md:text-display">Kuasai keahlian praktis bareng praktisi industri.</h1>
          <p class="text-lg leading-relaxed text-kea-on-forest">Ekosistem pembelajaran digital terpadu untuk penguasaan keahlian praktis, pengayaan portofolio, dan pengembangan kapasitas diri.</p>
          <div class="flex flex-wrap gap-3">
            <NuxtLink to="/program" class="btn-primary px-6 py-4">Lihat Program</NuxtLink>
            <a :href="whatsappUrl" target="_blank" rel="noopener" class="btn border-[1.5px] border-white px-6 py-4 font-semibold text-white hover:bg-white/10 hover:text-white">Konsultasi Gratis</a>
          </div>
          <dl class="flex flex-wrap gap-8 pt-2">
            <div v-for="s in statItems" :key="s.label">
              <dt class="sr-only">{{ s.label }}</dt>
              <dd class="text-[28px] font-extrabold">{{ s.value }}</dd>
              <span aria-hidden="true" class="text-sm text-kea-on-forest">{{ s.label }}</span>
            </div>
          </dl>
        </div>
        <div class="relative flex h-[320px] flex-[1_1_420px] overflow-hidden rounded-[20px] bg-[#0a2c27] md:h-[400px]" aria-hidden="true">
          <div class="absolute inset-0 grid grid-cols-2 gap-3 p-6">
            <div class="flex flex-col justify-between rounded-2xl bg-kea-olive-50 p-5 text-kea-ink">
              <span class="badge self-start bg-kea-forest text-white">Live</span>
              <span class="text-lg leading-snug font-extrabold">REST API dengan Laravel</span>
              <span class="text-sm text-kea-muted">Sesi 7 · 19.00 WIB</span>
            </div>
            <div class="flex flex-col justify-between rounded-2xl bg-kea-ember p-5 text-kea-ink">
              <span class="text-sm font-bold">Final project</span>
              <span class="text-4xl font-extrabold">3</span>
              <span class="text-sm font-semibold">proyek portofolio</span>
            </div>
            <div class="col-span-2 flex items-center gap-4 rounded-2xl bg-white p-5 text-kea-ink">
              <img src="/kea-icon.svg" alt="" width="56" height="56">
              <div class="flex flex-1 flex-col gap-2">
                <span class="text-sm font-bold">Progres belajar</span>
                <ProgressBar :value="72" label="Contoh progres" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section aria-label="Mitra hiring" class="border-b border-kea-line">
      <div class="container-kea flex flex-wrap items-center justify-between gap-6 py-8">
        <span class="text-sm font-semibold text-kea-muted">Alumni kami bekerja di</span>
        <span v-for="p in ['Startup Fintech', 'E-commerce', 'Agensi Kreatif', 'Konsultan IT', 'BUMN Digital']" :key="p" class="flex h-10 w-[140px] items-center justify-center rounded-kea-sm bg-kea-mist text-[13px] font-semibold text-kea-muted">{{ p }}</span>
      </div>
    </section>

    <section class="container-kea flex flex-col gap-8 py-[72px]">
      <div class="flex flex-col gap-2">
        <h2 class="h-section">Pilih cara belajarmu</h2>
        <p class="text-[17px] text-kea-muted">Empat format, satu ekosistem yang sama.</p>
      </div>
      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <NuxtLink v-for="f in formats" :key="f.type" :to="`/program?type=${f.type}`" class="card flex flex-col gap-3 p-6 text-kea-ink no-underline hover:border-kea-forest hover:text-kea-ink">
          <svg aria-hidden="true" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0E3B34" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path :d="f.icon" /></svg>
          <span class="text-xl font-bold">{{ f.title }}</span>
          <span class="text-[15px] leading-normal text-kea-muted">{{ f.body }}</span>
          <span class="mt-auto text-sm font-bold text-kea-forest">{{ f.cta }} →</span>
        </NuxtLink>
      </div>
    </section>

    <section class="bg-kea-surface">
      <div class="container-kea flex flex-col gap-8 py-[72px]">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div class="flex flex-col gap-2">
            <h2 class="h-section">Bootcamp segera dimulai</h2>
            <p class="text-[17px] text-kea-muted">Kuota tiap cohort terbatas.</p>
          </div>
          <NuxtLink to="/program?type=bootcamp" class="py-3 font-bold">Semua program →</NuxtLink>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <ProgramCard v-for="p in programs?.data ?? []" :key="p.slug" :program="p" />
        </div>
      </div>
    </section>

    <section id="karier" class="container-kea flex scroll-mt-4 flex-col gap-8 py-[72px]">
      <h2 class="h-section">Dari belajar sampai kerja</h2>
      <ol class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <li v-for="(s, i) in steps" :key="s.title" class="flex flex-col gap-2.5">
          <span class="flex size-11 items-center justify-center rounded-xl font-extrabold" :class="i === 3 ? 'bg-kea-ember text-kea-ink' : 'bg-kea-forest text-white'">{{ i + 1 }}</span>
          <span class="text-lg font-bold">{{ s.title }}</span>
          <span class="text-[15px] leading-normal text-kea-muted">{{ s.body }}</span>
        </li>
      </ol>
      <div class="flex flex-wrap items-center justify-between gap-4 rounded-kea-lg bg-kea-olive-50 p-6">
        <div class="flex flex-col gap-1">
          <span class="text-lg font-extrabold">Garansi kerja</span>
          <span class="text-[15px] text-kea-olive-900">Belum dapat kerja dalam 6 bulan setelah lulus dan memenuhi syarat? Biaya program dikembalikan sesuai ketentuan program.</span>
        </div>
        <NuxtLink to="/#faq" class="py-3 font-bold">Baca syarat →</NuxtLink>
      </div>
      <div class="flex flex-wrap gap-3">
        <NuxtLink to="/jalur-karier" class="btn-primary">Mulai kuesioner jalur karier</NuxtLink>
        <NuxtLink to="/lowongan" class="btn-outline">Lihat lowongan mitra</NuxtLink>
      </div>
    </section>

    <section v-if="testimonials?.data.length" class="bg-kea-surface">
      <div class="container-kea flex flex-col gap-8 py-[72px]">
        <h2 class="h-section">Cerita alumni</h2>
        <div class="grid gap-5 md:grid-cols-3">
          <figure v-for="t in testimonials.data" :key="t.name" class="card flex flex-col gap-4 p-6">
            <blockquote class="leading-relaxed">“{{ t.quote }}”</blockquote>
            <figcaption class="mt-auto flex items-center gap-3">
              <span class="flex size-11 items-center justify-center rounded-full bg-kea-olive-50 font-bold text-kea-olive-900" aria-hidden="true">{{ t.name.charAt(0) }}</span>
              <span class="flex flex-col"><b>{{ t.name }}</b><span class="text-sm text-kea-muted">{{ t.role }} di {{ t.company }}</span></span>
            </figcaption>
          </figure>
        </div>
      </div>
    </section>

    <section id="faq" class="mx-auto flex max-w-[860px] scroll-mt-4 flex-col gap-4 px-6 py-[72px]">
      <h2 class="h-section mb-4">Pertanyaan umum</h2>
      <FaqList :items="faqs" />
    </section>
  </main>
</template>
