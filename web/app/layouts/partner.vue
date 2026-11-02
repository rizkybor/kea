<script setup lang="ts">
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const menu = [
  { label: 'Lowongan & pelamar', to: '/partner' },
  { label: 'Pasang lowongan', to: '/partner/lowongan/baru' },
  { label: 'Cari talent', to: '/partner/talent' },
]
const active = (to: string) => (to === '/partner' ? route.path === '/partner' || /^\/partner\/lowongan\/\d+/.test(route.path) : route.path.startsWith(to))

async function logout() {
  await auth.logout()
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-kea-surface">
    <header class="border-b border-kea-line bg-white">
      <div class="container-kea flex flex-wrap items-center gap-x-8 gap-y-3 py-3">
        <KeaLogo :size="32" />
        <span class="badge-olive">Portal Mitra<template v-if="auth.user?.partner_company"> · {{ auth.user.partner_company.name }}</template></span>
        <nav aria-label="Menu mitra" class="flex flex-wrap gap-1">
          <NuxtLink
            v-for="m in menu"
            :key="m.to"
            :to="m.to"
            :aria-current="active(m.to) ? 'page' : undefined"
            class="rounded-kea-md px-3 py-2 text-[15px] font-semibold text-kea-ink no-underline hover:bg-kea-surface hover:text-kea-ink"
            :class="active(m.to) ? 'bg-kea-olive-50' : ''"
          >{{ m.label }}</NuxtLink>
        </nav>
        <button type="button" class="ml-auto text-sm font-semibold text-kea-muted hover:text-kea-ink" @click="logout">Keluar</button>
      </div>
    </header>
    <main class="container-kea py-8">
      <slot />
    </main>
  </div>
</template>
