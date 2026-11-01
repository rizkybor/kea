<script setup lang="ts">
import type { JobApplication } from '~/types/api'
import { applicationTone, formatDate } from '~/utils/format'

definePageMeta({ layout: 'learner', middleware: 'auth' })
const api = useApi()
const { data, refresh } = await useAsyncData('my-applications', () => api<{ data: JobApplication[] }>('/me/applications'))
const busy = ref<number | null>(null)
const finalStatuses = ['hired', 'rejected', 'withdrawn']

async function withdraw(a: JobApplication) {
  if (!confirm(`Batalkan lamaran ${a.job.title}?`)) return
  busy.value = a.id
  await api(`/me/applications/${a.id}/withdraw`, { method: 'POST' }).catch(() => {})
  busy.value = null
  refresh()
}

useSeoMeta({ title: 'Lamaran Saya', robots: 'noindex' })
</script>

<template>
  <div class="flex max-w-[900px] flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-[28px] font-extrabold">Lamaran saya</h1>
        <p class="text-[15px] text-kea-muted">Pantau status lamaran ke mitra hiring. Riwayat ini juga jadi dasar klaim garansi kerja.</p>
      </div>
      <NuxtLink to="/lowongan" class="btn-secondary">Cari lowongan</NuxtLink>
    </div>
    <div v-for="a in data?.data ?? []" :key="a.id" class="card flex flex-col gap-4 p-5 md:flex-row">
      <div class="flex min-w-0 flex-1 flex-col gap-2">
        <NuxtLink :to="`/lowongan/${a.job.slug}`" class="text-lg font-bold">{{ a.job.title }}</NuxtLink>
        <span class="text-sm text-kea-muted">{{ a.job.company?.name }} · {{ a.job.employment_type_label }} · dilamar {{ formatDate(a.applied_at) }}</span>
        <span :class="`badge-${applicationTone[a.status]} self-start`">{{ a.status_label }}</span>
        <button v-if="!finalStatuses.includes(a.status)" type="button" class="mt-1 self-start text-sm font-semibold text-kea-muted underline hover:text-kea-ink" :disabled="busy === a.id" @click="withdraw(a)">Batalkan lamaran</button>
      </div>
      <div class="md:w-[300px] md:border-l md:border-kea-line md:pl-5">
        <ApplicationTimeline :events="a.events" />
      </div>
    </div>
    <EmptyState v-if="data && !data.data.length" title="Belum ada lamaran" body="Lowongan khusus alumni terbuka setelah kamu lulus program. Lengkapi CV & skill di Profil Karier dulu.">
      <NuxtLink to="/lowongan" class="btn-primary">Lihat lowongan</NuxtLink>
    </EmptyState>
  </div>
</template>
