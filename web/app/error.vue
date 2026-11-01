<script setup lang="ts">
import { clearError, type NuxtError } from '#app'

const props = defineProps<{ error: NuxtError }>()
const notFound = computed(() => props.error.statusCode === 404)
useSeoMeta({ title: () => (notFound.value ? 'Halaman tidak ditemukan' : 'Terjadi kesalahan'), robots: 'noindex' })
</script>

<template>
  <NuxtLayout name="default">
    <main class="container-kea flex flex-col items-start gap-4 py-24">
      <span class="text-6xl font-extrabold text-kea-olive">{{ error.statusCode }}</span>
      <h1 class="text-[32px] font-extrabold">{{ notFound ? 'Halaman tidak ditemukan' : 'Terjadi kesalahan' }}</h1>
      <p class="text-kea-muted">{{ notFound ? 'Halaman yang kamu cari sudah dipindah atau tidak ada.' : 'Coba muat ulang halaman beberapa saat lagi.' }}</p>
      <button type="button" class="btn-primary" @click="clearError({ redirect: '/' })">Kembali ke beranda</button>
    </main>
  </NuxtLayout>
</template>
