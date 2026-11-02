<script setup lang="ts">
import type { Job, JobApplication } from '~/types/api'
import { applicationTone, formatDate, salaryRange } from '~/utils/format'

const route = useRoute()
const api = useApi()
const auth = useAuthStore()
const { public: { siteUrl } } = useRuntimeConfig()
const slug = route.params.slug as string

const { data, error } = await useAsyncData(`job-${slug}`, () => api<{ data: Job }>(`/jobs/${slug}`))
if (error.value || !data.value) throw createError({ statusCode: 404, statusMessage: 'Lowongan tidak ditemukan', fatal: true })
const job = computed(() => data.value!.data)

// Status lamaran milik user (client-only, halaman ini di-cache publik).
const myApplication = ref<JobApplication | null>(null)
onMounted(async () => {
  await auth.fetchUser()
  if (!auth.isLoggedIn) return
  const res = await api<{ data: JobApplication[] }>('/me/applications').catch(() => null)
  myApplication.value = res?.data.find(a => a.job.slug === slug) ?? null
})

const form = reactive({ cover_letter: '', portfolio_url: '' })
const errorMessage = ref<string | null>(null)
const errorCode = ref<string | null>(null)
const submitting = ref(false)

async function apply() {
  errorMessage.value = null
  errorCode.value = null
  if (!auth.isLoggedIn) return navigateTo({ path: '/masuk', query: { redirect: route.fullPath } })
  submitting.value = true
  try {
    myApplication.value = (await api<{ data: JobApplication }>(`/jobs/${slug}/apply`, {
      method: 'POST',
      body: { cover_letter: form.cover_letter || null, portfolio_url: form.portfolio_url || null },
    })).data
  } catch (e) {
    const err = apiError(e)
    errorMessage.value = Object.values(err.errors ?? {})[0]?.[0] ?? err.message
    errorCode.value = err.code ?? null
  } finally {
    submitting.value = false
  }
}

const salary = computed(() => salaryRange(job.value.salary_min, job.value.salary_max))

useSeoMeta({
  title: () => `${job.value.title} – ${job.value.company?.name}`,
  description: () => job.value.description?.slice(0, 160),
})
useHead({
  link: [{ rel: 'canonical', href: `${siteUrl}/lowongan/${slug}` }],
  script: [{
    type: 'application/ld+json',
    innerHTML: () => JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'JobPosting',
      title: job.value.title,
      description: job.value.description,
      datePosted: job.value.published_at,
      validThrough: job.value.closes_at ?? undefined,
      employmentType: { full_time: 'FULL_TIME', contract: 'CONTRACTOR', internship: 'INTERN', part_time: 'PART_TIME' }[job.value.employment_type],
      hiringOrganization: { '@type': 'Organization', name: job.value.company?.name, sameAs: job.value.company?.website ?? undefined },
      ...(job.value.work_mode === 'remote'
        ? { jobLocationType: 'TELECOMMUTE', applicantLocationRequirements: { '@type': 'Country', name: 'Indonesia' } }
        : { jobLocation: { '@type': 'Place', address: { '@type': 'PostalAddress', addressLocality: job.value.location, addressCountry: 'ID' } } }),
      ...(job.value.salary_min ? { baseSalary: { '@type': 'MonetaryAmount', currency: 'IDR', value: { '@type': 'QuantitativeValue', minValue: job.value.salary_min, maxValue: job.value.salary_max ?? undefined, unitText: 'MONTH' } } } : {}),
    }),
  }],
})
</script>

<template>
  <div>
    <section class="bg-kea-forest text-white">
      <div class="container-kea flex flex-col gap-4 py-10">
        <AppBreadcrumb light :items="[{ label: 'Beranda', to: '/' }, { label: 'Lowongan', to: '/lowongan' }, { label: job.title }]" />
        <div class="flex flex-wrap gap-2">
          <span class="badge bg-kea-olive text-kea-ink">{{ job.employment_type_label }}</span>
          <span class="badge bg-white/15 text-white">{{ job.work_mode_label }}</span>
          <span v-if="job.alumni_only" class="badge bg-kea-ember text-kea-ink">Khusus alumni KEA</span>
        </div>
        <h1 class="text-[30px] leading-tight font-extrabold md:text-[42px]">{{ job.title }}</h1>
        <p class="text-[17px] text-kea-on-forest">{{ job.company?.name }}<template v-if="job.location"> · {{ job.location }}</template><template v-if="salary"> · {{ salary }}/bulan</template></p>
      </div>
    </section>

    <div class="container-kea flex flex-col gap-10 py-10 lg:flex-row lg:items-start">
      <main class="flex min-w-0 flex-1 flex-col gap-8">
        <section class="flex flex-col gap-3">
          <h2 class="text-2xl font-extrabold">Tentang posisi ini</h2>
          <p class="leading-relaxed whitespace-pre-line text-kea-muted">{{ job.description }}</p>
        </section>
        <section v-if="job.requirements?.length" class="flex flex-col gap-3">
          <h2 class="text-2xl font-extrabold">Kualifikasi</h2>
          <ul class="flex list-disc flex-col gap-1.5 pl-5 leading-relaxed">
            <li v-for="r in job.requirements" :key="r">{{ r }}</li>
          </ul>
        </section>
        <section v-if="job.skills.length" class="flex flex-col gap-3">
          <h2 class="text-2xl font-extrabold">Skill yang dicari</h2>
          <div class="flex flex-wrap gap-2"><span v-for="s in job.skills" :key="s" class="badge-olive px-3 py-1.5 text-sm">{{ s }}</span></div>
        </section>
        <section v-if="job.company" class="card flex flex-col gap-2 p-6">
          <h2 class="text-lg font-bold">Tentang {{ job.company.name }} <span v-if="job.company.is_verified" class="badge-olive ml-1">✓ Terverifikasi</span></h2>
          <p class="text-[15px] text-kea-muted">{{ [job.company.industry, job.company.city].filter(Boolean).join(' · ') }}</p>
          <p v-if="job.company.description" class="text-[15px]">{{ job.company.description }}</p>
          <a v-if="job.company.website" :href="job.company.website" target="_blank" rel="noopener" class="text-sm font-bold">Situs perusahaan →</a>
        </section>
      </main>

      <aside aria-label="Lamar lowongan" class="card flex flex-col gap-4 p-6 lg:sticky lg:top-6 lg:w-[380px] lg:flex-none">
        <template v-if="myApplication">
          <span class="text-sm font-semibold text-kea-muted">Status lamaranmu</span>
          <span :class="`badge-${applicationTone[myApplication.status]} self-start px-3 py-1.5 text-sm`">{{ myApplication.status_label }}</span>
          <ApplicationTimeline :events="myApplication.events" />
          <NuxtLink to="/dashboard/lamaran" class="btn-outline">Lihat semua lamaran</NuxtLink>
        </template>
        <template v-else-if="job.is_open">
          <h2 class="text-lg font-bold">Lamar posisi ini</h2>
          <p class="text-sm text-kea-muted">Lamaran memakai profil KEA, sertifikat, dan CV terbarumu. Perusahaan akan melihat email & nomor WhatsApp-mu.</p>
          <label class="field">Pesan untuk perusahaan <span class="font-normal text-kea-muted">(opsional)</span>
            <textarea v-model="form.cover_letter" rows="4" maxlength="3000" class="input" placeholder="Ceritakan proyek atau pengalaman yang relevan" />
          </label>
          <label class="field">Link portofolio <span class="font-normal text-kea-muted">(opsional)</span>
            <input v-model="form.portfolio_url" type="url" placeholder="https://" class="input">
          </label>
          <ErrorAlert :message="errorMessage" />
          <NuxtLink v-if="errorCode === 'cv_required'" to="/dashboard/profil" class="text-sm font-bold">Unggah CV di Profil Karier →</NuxtLink>
          <NuxtLink v-if="errorCode === 'alumni_only'" to="/jalur-karier" class="text-sm font-bold">Cari program yang menyiapkanmu untuk posisi ini →</NuxtLink>
          <button type="button" class="btn-primary w-full" :disabled="submitting" @click="apply">{{ submitting ? 'Mengirim…' : auth.isLoggedIn ? 'Kirim lamaran' : 'Masuk untuk melamar' }}</button>
          <span v-if="job.closes_at" class="text-center text-xs text-kea-muted">Lamaran ditutup {{ formatDate(job.closes_at) }}</span>
        </template>
        <p v-else class="text-sm text-kea-muted">Lowongan ini sudah ditutup.</p>
      </aside>
    </div>
  </div>
</template>
