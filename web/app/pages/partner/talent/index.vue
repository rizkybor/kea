<script setup lang="ts">
import type { Paginated, ProgramCard, Talent } from '~/types/api'

definePageMeta({ layout: 'partner', middleware: ['auth', 'partner'] })
const api = useApi()
const filters = reactive({ q: '', skill: '', program: '', min_score: '' as number | '', open_to_work: false, page: 1 })
const applied = ref({ ...filters })

const { data: programs } = await useAsyncData('talent-programs', () => api<Paginated<ProgramCard>>('/programs', { query: { per_page: 24, sort: 'popular' } }))
const { data, status } = await useAsyncData('talents', () => api<Paginated<Talent>>('/partner/talents', {
  query: {
    q: applied.value.q || undefined,
    skill: applied.value.skill || undefined,
    program: applied.value.program || undefined,
    min_score: applied.value.min_score === '' ? undefined : applied.value.min_score,
    open_to_work: applied.value.open_to_work ? 1 : undefined,
    page: applied.value.page,
  },
}), { watch: [applied], deep: true })

function search(page = 1) {
  filters.page = page
  applied.value = { ...filters }
}

useSeoMeta({ title: 'Talent pool', robots: 'noindex' })
</script>

<template>
  <div class="flex flex-col gap-6">
    <div>
      <h1 class="text-[28px] font-extrabold">Talent pool</h1>
      <p class="text-[15px] text-kea-muted">Alumni KEA yang sudah lulus dan setuju profilnya dibagikan ke mitra hiring.</p>
    </div>
    <form class="card grid gap-4 p-5 md:grid-cols-2 lg:grid-cols-5" @submit.prevent="search()">
      <label class="field">Nama / headline<input v-model="filters.q" class="input" placeholder="mis. Backend"></label>
      <label class="field">Skill<input v-model="filters.skill" class="input" placeholder="mis. Laravel"></label>
      <label class="field">Program lulusan
        <select v-model="filters.program" class="input">
          <option value="">Semua program</option>
          <option v-for="p in (programs?.data ?? []).filter(p => p.type !== 'event')" :key="p.slug" :value="p.slug">{{ p.title }}</option>
        </select>
      </label>
      <label class="field">Nilai akhir minimal<input v-model.number="filters.min_score" type="number" min="0" max="100" class="input" placeholder="mis. 80"></label>
      <div class="flex flex-col justify-end gap-2">
        <label class="flex items-center gap-2 text-sm font-semibold"><input v-model="filters.open_to_work" type="checkbox" class="size-4 accent-kea-forest"> Hanya open to work</label>
        <button type="submit" class="btn-secondary">Cari</button>
      </div>
    </form>

    <span class="text-sm font-semibold text-kea-muted" aria-live="polite">{{ data?.meta.total ?? 0 }} talent</span>
    <div v-if="data?.data.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3" :class="status === 'pending' ? 'opacity-60' : ''">
      <TalentCard v-for="t in data.data" :key="t.id" :talent="t" />
    </div>
    <EmptyState v-else-if="status !== 'pending'" title="Tidak ada talent yang cocok" body="Longgarkan filter skill atau nilai minimal." />

    <nav v-if="data && data.meta.last_page > 1" aria-label="Halaman" class="flex justify-center gap-2">
      <button v-for="n in data.meta.last_page" :key="n" type="button" class="flex size-11 items-center justify-center rounded-kea-md border font-bold" :class="n === data.meta.current_page ? 'border-kea-forest bg-kea-forest text-white' : 'border-kea-field bg-white'" @click="search(n)">{{ n }}</button>
    </nav>
  </div>
</template>
