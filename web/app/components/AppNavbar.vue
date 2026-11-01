<script setup lang="ts">
const auth = useAuthStore()
const open = ref(false)
const route = useRoute()
watch(() => route.fullPath, () => { open.value = false })

const links = [
  { label: 'Bootcamp', to: '/program?type=bootcamp' },
  { label: 'Video Course', to: '/program?type=video_course' },
  { label: 'Short Class', to: '/program?type=short_class' },
  { label: 'Event', to: '/program?type=event' },
  { label: 'Karier', to: '/#karier' },
  { label: 'Verifikasi Sertifikat', to: '/verify' },
]
</script>

<template>
  <header class="border-b border-kea-line bg-white">
    <nav aria-label="Navigasi utama" class="container-kea flex items-center gap-6 py-4">
      <KeaLogo />
      <div class="hidden flex-1 flex-wrap gap-5 text-[15px] font-medium lg:flex">
        <NuxtLink v-for="link in links" :key="link.to" :to="link.to" class="text-kea-ink no-underline hover:text-kea-moss">{{ link.label }}</NuxtLink>
      </div>
      <div class="ml-auto hidden items-center gap-3 lg:flex">
        <template v-if="auth.isLoggedIn">
          <NuxtLink v-if="auth.isMentor" to="/mentor" class="btn-ghost">Review tugas</NuxtLink>
          <NuxtLink to="/dashboard" class="btn-secondary">Dashboard</NuxtLink>
        </template>
        <template v-else>
          <NuxtLink to="/masuk" class="btn-ghost">Masuk</NuxtLink>
          <NuxtLink to="/daftar" class="btn-primary">Daftar</NuxtLink>
        </template>
      </div>
      <button type="button" class="btn-ghost ml-auto px-3 lg:hidden" :aria-expanded="open" aria-controls="mobile-menu" aria-label="Buka menu" @click="open = !open">
        <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path v-if="!open" d="M4 6h16M4 12h16M4 18h16" /><path v-else d="M6 6l12 12M18 6L6 18" /></svg>
      </button>
    </nav>
    <div v-show="open" id="mobile-menu" class="border-t border-kea-line lg:hidden">
      <div class="container-kea flex flex-col gap-1 py-4">
        <NuxtLink v-for="link in links" :key="link.to" :to="link.to" class="rounded-kea-md px-2 py-3 font-medium text-kea-ink no-underline hover:bg-kea-surface">{{ link.label }}</NuxtLink>
        <div class="mt-3 flex gap-3">
          <NuxtLink v-if="auth.isLoggedIn" to="/dashboard" class="btn-secondary flex-1">Dashboard</NuxtLink>
          <template v-else>
            <NuxtLink to="/masuk" class="btn-outline flex-1">Masuk</NuxtLink>
            <NuxtLink to="/daftar" class="btn-primary flex-1">Daftar</NuxtLink>
          </template>
        </div>
      </div>
    </div>
  </header>
</template>
