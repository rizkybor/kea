<script setup lang="ts">
import type { Certificate } from '~/types/api'
import { formatDate } from '~/utils/format'

definePageMeta({ layout: 'learner', middleware: 'auth' })
const api = useApi()
const { data } = await useAsyncData('my-certificates', () => api<{ data: Certificate[] }>('/certificates'))
useSeoMeta({ title: 'Sertifikat Saya', robots: 'noindex' })
</script>

<template>
  <div class="flex max-w-[900px] flex-col gap-5">
    <h1 class="text-[28px] font-extrabold">Sertifikat saya</h1>
    <div v-for="c in data?.data ?? []" :key="c.code" class="card flex flex-wrap items-center gap-5 p-5">
      <QrCode :value="c.verify_url" :size="88" :label="`QR verifikasi ${c.code}`" />
      <div class="flex min-w-0 flex-1 flex-col gap-1">
        <b class="text-lg">{{ c.program_title }}</b>
        <span class="text-sm text-kea-muted">{{ c.cohort_name }} · Diterbitkan {{ formatDate(c.issued_at) }}</span>
        <span class="font-mono text-sm">{{ c.code }}</span>
      </div>
      <div class="flex flex-wrap gap-2">
        <NuxtLink :to="`/verify/${c.code}`" class="btn-ghost">Lihat</NuxtLink>
        <a :href="c.pdf_url" class="btn-outline">Unduh PDF</a>
        <a :href="c.linkedin_url" target="_blank" rel="noopener" class="btn-secondary">Tambah ke LinkedIn</a>
      </div>
    </div>
    <EmptyState v-if="data && !data.data.length" title="Belum ada sertifikat" body="Sertifikat terbit otomatis saat semua syarat lulus terpenuhi: progres 100%, kehadiran live ≥ 80%, dan tugas wajib bernilai ≥ 70." />
  </div>
</template>
