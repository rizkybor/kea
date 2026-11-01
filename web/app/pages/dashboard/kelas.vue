<script setup lang="ts">
import type { Enrollment } from '~/types/api'

definePageMeta({ layout: 'learner', middleware: 'auth' })
const api = useApi()
const { data, status } = await useAsyncData('enrollments', () => api<{ data: Enrollment[] }>('/enrollments'))
useSeoMeta({ title: 'Kelas Saya', robots: 'noindex' })
</script>

<template>
  <div class="flex max-w-[900px] flex-col gap-5">
    <h1 class="text-[28px] font-extrabold">Kelas saya</h1>
    <div v-if="status === 'pending' && !data" class="h-28 animate-pulse rounded-kea-lg bg-kea-line" />
    <EnrollmentCard v-for="e in data?.data ?? []" :key="e.id" :enrollment="e" />
    <EmptyState v-if="data && !data.data.length" title="Belum ada kelas" body="Kelas muncul di sini setelah pembayaran terkonfirmasi.">
      <NuxtLink to="/program" class="btn-primary">Lihat program</NuxtLink>
    </EmptyState>
  </div>
</template>
