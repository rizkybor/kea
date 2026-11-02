<script setup lang="ts">
/** Kontrol presentasi (hanya mode demo): ganti peran sekali klik dan reset data ke kondisi awal. */
const auth = useAuthStore()
const open = ref(false)
const busy = ref(false)

const personas = [
  { email: null, label: 'Pengunjung', hint: 'Belum login', to: '/' },
  { email: 'learner@kea.test', label: 'Sari · Learner', hint: 'Bootcamp sedang berjalan', to: '/dashboard' },
  { email: 'alumni@kea.test', label: 'Bima · Alumni', hint: 'Lulus, sedang interview', to: '/dashboard/lamaran' },
  { email: 'mentor@kea.test', label: 'Raka · Mentor', hint: 'Review tugas', to: '/mentor' },
  { email: 'partner@kea.test', label: 'Dewi · Hiring partner', hint: 'Portal mitra', to: '/partner' },
] as const

const current = computed(() => auth.user?.email ?? null)

/** Ganti peran lalu muat ulang aplikasi: cache data halaman lama tidak terbawa ke peran baru. */
async function switchTo(p: (typeof personas)[number]) {
  busy.value = true
  const { demoLoginAs } = await import('~/demo/mock')
  demoLoginAs(p.email)
  reloadAt(p.to)
}

async function reset() {
  if (!confirm('Kembalikan semua data demo ke kondisi awal?')) return
  const { resetDemo } = await import('~/demo/mock')
  resetDemo()
  reloadAt('/')
}

function reloadAt(path: string) {
  location.hash = '#' + path
  location.reload()
}
</script>

<template>
  <div class="fixed right-4 bottom-4 z-50 flex flex-col items-end gap-2 print:hidden">
    <div v-if="open" class="card w-[280px] p-3 shadow-xl" role="dialog" aria-label="Kontrol demo">
      <p class="px-2 pb-2 text-xs font-bold tracking-wide text-kea-moss uppercase">Lihat sebagai</p>
      <button
        v-for="p in personas"
        :key="p.label"
        type="button"
        class="flex w-full flex-col items-start rounded-kea-md px-2 py-2 text-left hover:bg-kea-surface"
        :class="current === p.email ? 'bg-kea-olive-50' : ''"
        :disabled="busy"
        @click="switchTo(p)"
      >
        <b class="text-sm">{{ p.label }}</b>
        <span class="text-xs text-kea-muted">{{ p.hint }}</span>
      </button>
      <button type="button" class="mt-2 w-full border-t border-kea-line px-2 pt-2 text-left text-xs font-semibold text-kea-muted hover:text-kea-ink" @click="reset">↺ Reset data demo</button>
    </div>
    <button type="button" class="flex items-center gap-2 rounded-full bg-kea-ink px-4 py-2.5 text-sm font-bold text-white shadow-lg" :aria-expanded="open" @click="open = !open">
      <span class="size-2 rounded-full bg-kea-ember" aria-hidden="true" />
      Prototype · {{ personas.find(p => p.email === current)?.label ?? (current ? 'Akun baru' : 'Pengunjung') }}
    </button>
  </div>
</template>
