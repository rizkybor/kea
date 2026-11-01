<script setup lang="ts">
import type { LocationQueryRaw } from 'vue-router'
import type { Paginated, ProgramCard } from '~/types/api'
import { programTypes } from '~/utils/format'

const route = useRoute()
const router = useRouter()
const api = useApi()
const { public: { siteUrl } } = useRuntimeConfig()

const { data: categories } = await useAsyncData('categories', () => api<{ data: { name: string; slug: string }[] }>('/categories'))

/** Filter disimpan di query URL supaya bisa dibagikan & diindeks (CAT-01). */
const filters = computed(() => ({
  type: (route.query.type as string) || '',
  category: ([] as string[]).concat((route.query.category as string | string[] | undefined) ?? []),
  schedule: (route.query.schedule as string) || '',
  q: (route.query.q as string) || '',
  sort: (route.query.sort as string) || 'start',
  page: Number(route.query.page) || 1,
}))

const { data: result, status } = await useAsyncData(
  'catalog',
  () => api<Paginated<ProgramCard>>('/programs', {
    query: {
      type: filters.value.type || undefined,
      'category[]': filters.value.category.length ? filters.value.category : undefined,
      schedule: filters.value.schedule || undefined,
      q: filters.value.q || undefined,
      sort: filters.value.sort,
      page: filters.value.page,
    },
  }),
  { watch: [filters] },
)

function update(patch: Record<string, string | string[] | number | undefined>) {
  const query: LocationQueryRaw = { ...route.query, ...patch }
  if (!('page' in patch)) delete query.page
  for (const key of Object.keys(query)) {
    const v = query[key]
    if (v === '' || v === undefined || (Array.isArray(v) && !v.length) || (key === 'sort' && v === 'start') || (key === 'page' && v === 1)) delete query[key]
  }
  router.replace({ query })
}

const search = ref(filters.value.q)
let timer: ReturnType<typeof setTimeout> | undefined
watch(search, (v) => {
  clearTimeout(timer)
  timer = setTimeout(() => update({ q: v.trim() }), 350)
})

function toggleCategory(slug: string) {
  const current = filters.value.category
  update({ category: current.includes(slug) ? current.filter(c => c !== slug) : [...current, slug] })
}

const typeLabel = computed(() => programTypes.find(t => t.value === filters.value.type)?.label ?? 'Semua')
const heading = computed(() => (filters.value.type ? `Program ${typeLabel.value}` : 'Semua program'))

useSeoMeta({
  title: () => heading.value,
  description: 'Cari bootcamp, short class, video course, dan event KEA sesuai target kariermu. Filter berdasarkan kategori, jadwal, dan harga.',
})
useHead({ link: [{ rel: 'canonical', href: () => siteUrl + '/program' + (filters.value.type ? `?type=${filters.value.type}` : '') }] })
</script>

<template>
  <main class="container-kea flex flex-col gap-6 py-10">
    <AppBreadcrumb :items="[{ label: 'Beranda', to: '/' }, { label: 'Program' }]" />
    <div class="flex flex-col gap-2">
      <h1 class="text-[32px] leading-tight font-extrabold md:text-[40px]">{{ heading }}</h1>
      <p class="text-[17px] text-kea-muted">Cari program sesuai target kariermu.</p>
    </div>

    <div class="flex flex-wrap items-end gap-4">
      <label class="field flex-[1_1_320px]">Cari program
        <input v-model="search" type="search" placeholder="mis. Laravel, UI/UX, SEO" class="input">
      </label>
      <label class="field flex-[0_1_240px]">Urutkan
        <select class="input" :value="filters.sort" @change="update({ sort: ($event.target as HTMLSelectElement).value })">
          <option value="start">Mulai paling dekat</option>
          <option value="price">Harga terendah</option>
          <option value="popular">Paling populer</option>
        </select>
      </label>
    </div>

    <div role="group" aria-label="Filter tipe program" class="flex flex-wrap gap-2.5">
      <button v-for="t in programTypes" :key="t.value" type="button" class="pill" :aria-pressed="filters.type === t.value" @click="update({ type: t.value })">{{ t.label }}</button>
    </div>

    <div class="flex flex-col gap-8 md:flex-row">
      <aside aria-label="Filter" class="flex flex-col gap-3 md:w-[220px] md:flex-none">
        <fieldset class="flex flex-col gap-2">
          <legend class="mb-2 text-sm font-bold">Kategori</legend>
          <label v-for="c in categories?.data ?? []" :key="c.slug" class="flex min-h-9 cursor-pointer items-center gap-2.5 text-[15px]">
            <input type="checkbox" class="size-[18px] accent-kea-forest" :checked="filters.category.includes(c.slug)" @change="toggleCategory(c.slug)">
            {{ c.name }}
          </label>
        </fieldset>
        <fieldset class="mt-4 flex flex-col gap-2">
          <legend class="mb-2 text-sm font-bold">Jadwal</legend>
          <label v-for="s in [{ v: '', l: 'Semua jadwal' }, { v: 'weekday', l: 'Weekday malam' }, { v: 'weekend', l: 'Weekend' }]" :key="s.v" class="flex min-h-9 cursor-pointer items-center gap-2.5 text-[15px]">
            <input type="radio" name="jadwal" class="size-[18px] accent-kea-forest" :checked="filters.schedule === s.v" @change="update({ schedule: s.v })">
            {{ s.l }}
          </label>
        </fieldset>
      </aside>

      <section aria-label="Hasil" class="flex min-w-0 flex-1 flex-col gap-4" :aria-busy="status === 'pending'">
        <span class="text-sm font-semibold text-kea-muted" aria-live="polite">{{ result?.meta.total ?? 0 }} program</span>
        <div v-if="status === 'pending' && !result" class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
          <div v-for="i in 6" :key="i" class="card h-[360px] animate-pulse bg-kea-mist" />
        </div>
        <div v-else-if="result?.data.length" class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3" :class="status === 'pending' ? 'opacity-60' : ''">
          <ProgramCard v-for="p in result.data" :key="p.slug" :program="p" />
        </div>
        <EmptyState v-else title="Program tidak ditemukan" body="Coba ubah kata kunci atau filter. Program baru dibuka setiap bulan.">
          <button type="button" class="btn-outline" @click="router.replace({ query: {} })">Reset filter</button>
        </EmptyState>

        <nav v-if="result && result.meta.last_page > 1" aria-label="Halaman" class="mt-4 flex justify-center gap-2">
          <button
            v-for="n in result.meta.last_page"
            :key="n"
            type="button"
            class="flex size-11 items-center justify-center rounded-kea-md border font-bold"
            :class="n === result.meta.current_page ? 'border-kea-forest bg-kea-forest text-white' : 'border-kea-field bg-white text-kea-ink'"
            :aria-current="n === result.meta.current_page ? 'page' : undefined"
            @click="update({ page: n })"
          >{{ n }}</button>
        </nav>
      </section>
    </div>
  </main>
</template>
