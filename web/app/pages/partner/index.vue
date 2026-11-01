<script setup lang="ts">
import type { Job, PartnerCompany } from '~/types/api'

definePageMeta({ layout: 'partner', middleware: ['auth', 'partner'] })
const api = useApi()
const [{ data: company, error }, { data: jobs }] = await Promise.all([
  useAsyncData('partner-company', () => api<{ data: PartnerCompany }>('/partner/company')),
  useAsyncData('partner-jobs', () => api<{ data: Job[] }>('/partner/jobs')),
])
useSeoMeta({ title: 'Portal Mitra', robots: 'noindex' })
</script>

<template>
  <div class="flex flex-col gap-6">
    <ErrorAlert v-if="error" :message="apiError(error).message" />
    <template v-if="company">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-[28px] font-extrabold">{{ company.data.name }}</h1>
          <p class="text-[15px] text-kea-muted">{{ [company.data.industry, company.data.city].filter(Boolean).join(' · ') }}<template v-if="company.data.is_verified"> · ✓ Mitra terverifikasi</template></p>
        </div>
        <NuxtLink to="/partner/lowongan/baru" class="btn-primary">+ Pasang lowongan</NuxtLink>
      </div>
      <dl class="grid gap-4 sm:grid-cols-3">
        <div v-for="s in [['Lowongan dibuka', company.data.open_jobs], ['Total pelamar', company.data.applications], ['Kandidat diterima', company.data.hired]]" :key="s[0]" class="card p-5">
          <dt class="text-sm font-semibold text-kea-muted">{{ s[0] }}</dt>
          <dd class="text-[28px] font-extrabold">{{ s[1] }}</dd>
        </div>
      </dl>
    </template>

    <section class="flex flex-col gap-4">
      <h2 class="text-xl font-extrabold">Lowongan</h2>
      <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <JobCard v-for="j in jobs?.data ?? []" :key="j.id" :job="j" :to="`/partner/lowongan/${j.id}`" />
      </div>
      <EmptyState v-if="jobs && !jobs.data.length" title="Belum ada lowongan" body="Pasang lowongan pertama untuk menerima lamaran dari alumni KEA.">
        <NuxtLink to="/partner/lowongan/baru" class="btn-primary">Pasang lowongan</NuxtLink>
      </EmptyState>
    </section>

    <section class="flex flex-wrap items-center justify-between gap-4 rounded-kea-lg bg-kea-olive-50 p-6">
      <div class="flex flex-col gap-1">
        <b class="text-lg">Cari talent tervalidasi</b>
        <span class="text-[15px] text-kea-olive-900">Telusuri alumni berdasarkan skill, program yang diselesaikan, dan nilai akhir.</span>
      </div>
      <NuxtLink to="/partner/talent" class="btn-secondary">Buka talent pool</NuxtLink>
    </section>
  </div>
</template>
