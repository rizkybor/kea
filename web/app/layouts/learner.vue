<script setup lang="ts">
const auth = useAuthStore()
const router = useRouter()
const open = ref(false)
const route = useRoute()
watch(() => route.fullPath, () => { open.value = false })

const menu = computed(() => [
  { label: 'Beranda', to: '/dashboard' },
  { label: 'Kelas Saya', to: '/dashboard/kelas' },
  { label: 'Jadwal Live', to: '/dashboard#jadwal' },
  { label: 'Tugas', to: '/dashboard#tugas' },
  { label: 'Sertifikat', to: '/dashboard/sertifikat' },
  { label: 'Pesanan', to: '/dashboard/pesanan' },
  { label: 'Profil Karier', to: '/dashboard/profil' },
  ...(auth.isMentor ? [{ label: 'Review Tugas (Mentor)', to: '/mentor' }] : []),
])

async function logout() {
  await auth.logout()
  router.push('/')
}
</script>

<template>
  <div class="flex min-h-screen flex-col bg-kea-surface lg:flex-row">
    <div class="flex items-center justify-between border-b border-kea-line bg-white px-4 py-3 lg:hidden">
      <KeaLogo :size="32" />
      <button type="button" class="btn-ghost px-3" :aria-expanded="open" aria-controls="learner-menu" aria-label="Menu learner" @click="open = !open">
        <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
      </button>
    </div>
    <nav
      id="learner-menu"
      aria-label="Menu learner"
      class="flex-col gap-1 border-kea-line bg-white p-6 lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-[248px] lg:flex-none lg:border-r"
      :class="open ? 'flex border-b' : 'hidden'"
    >
      <div class="mb-6 hidden lg:block"><KeaLogo /></div>
      <NuxtLink
        v-for="item in menu"
        :key="item.to"
        :to="item.to"
        :aria-current="route.path === item.to ? 'page' : undefined"
        class="rounded-kea-md px-3 py-3 text-[15px] font-semibold text-kea-ink no-underline hover:bg-kea-surface hover:text-kea-ink"
        :class="route.path === item.to && !item.to.includes('#') ? 'bg-kea-olive-50 hover:bg-kea-olive-50' : ''"
      >{{ item.label }}</NuxtLink>
      <div class="mt-auto flex flex-col gap-2 border-t border-kea-line pt-4">
        <NuxtLink to="/program" class="px-3 py-2 text-sm font-semibold">Jelajahi program</NuxtLink>
        <button type="button" class="px-3 py-2 text-left text-sm font-semibold text-kea-muted hover:text-kea-ink" @click="logout">Keluar</button>
      </div>
    </nav>
    <main class="min-w-0 flex-1 p-4 sm:p-8 lg:p-10">
      <slot />
    </main>
  </div>
</template>
