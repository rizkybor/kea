<script setup lang="ts">
const route = useRoute()
const auth = useAuthStore()
const state = ref<'loading' | 'ok' | 'error'>('loading')

onMounted(async () => {
  const url = String(route.query.url ?? '')
  // Hanya terima path endpoint verifikasi API kita sendiri.
  if (!url.startsWith('/api/v1/auth/email/verify/')) {
    state.value = 'error'
    return
  }
  try {
    await $fetch(url, { headers: { Accept: 'application/json' }, credentials: 'include' })
    state.value = 'ok'
    auth.fetchUser(true)
  } catch {
    state.value = 'error'
  }
})

useSeoMeta({ title: 'Verifikasi email', robots: 'noindex' })
</script>

<template>
  <AuthCard v-if="state === 'loading'" title="Memverifikasi email…" />
  <AuthCard v-else-if="state === 'ok'" title="Email terverifikasi" subtitle="Akunmu sudah aktif penuh. Sekarang kamu bisa checkout program.">
    <NuxtLink to="/program" class="btn-primary w-full">Lihat program</NuxtLink>
  </AuthCard>
  <AuthCard v-else title="Link tidak valid" subtitle="Link verifikasi sudah kedaluwarsa atau tidak valid. Masuk lalu kirim ulang email verifikasi dari halaman program.">
    <NuxtLink to="/masuk" class="btn-primary w-full">Masuk</NuxtLink>
  </AuthCard>
</template>
