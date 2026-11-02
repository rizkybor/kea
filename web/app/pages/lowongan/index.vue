<script setup lang="ts">
import type { LocationQueryRaw } from 'vue-router'
import type { Job, Paginated } from '~/types/api'
import { employmentTypes, workModes } from '~/utils/format'

const route = useRoute()
const router = useRouter()
const api = useApi()
const { public: { siteUrl } } = useRuntimeConfig()

const { data: categories } = await useAsyncData('categories', () => api<{ data: { name: string; slug: string }[] }>('/categories'))

const filters = computed(() => ({
  q: (route.query.q as string) || '',
  category: (route.query.category as string) || '',
  type: (route.query.type as string) || '',
  mode: (route.query.mode as string) || '',
  page: Number(route.query.page) || 1,
}))

const { data: result, status } = await useAsyncData('jobs', () => api<Paginated<Job>>('/jobs', {
  query: {
    q: filters.value.q || undefined,
    category: filters.value.category || undefined,
    type: filters.value.type || undefined,
    mode: filters.value.mode || undefined,
    page: filters.value.page,
  },
}), { watch: [filters] })

function update(patch: Record<string, string | number>) {
  const query: LocationQueryRaw = { ...route.query, ...patch }
  if (!('page' in patch)) delete query.page
  for (const k of Object.keys(query)) if (query[k] === '' || query[k] === 1) delete query[k]
  router.replace({ query })
}

const search = ref(filters.value.q)
let timer: ReturnType<typeof setTimeout> | undefined
watch(search, (v) => {
  clearTimeout(timer)
  timer = setTimeout(() => update({ q: v.trim() }), 350)
})

useSeoMeta({
  title: 'Lowongan kerja dari mitra hiring',
  description: 'Job board KEA: lowongan dari perusahaan mitra untuk alumni bootcamp dan talent terverifikasi.',
})
useHead({ link: [{ rel: 'canonical', href: `${siteUrl}/lowongan` }] })
</script>

<template>
  <main>
    <section class="bg-kea-forest text-white">
      <div class="container-kea flex flex-col gap-4 py-12">
        <AppBreadcrumb light :items="[{ label: 'Beranda', to: '/' }, { label: 'Lowongan' }]" />
        <h1 class="text-[32px] leading-tight font-extrabold md:text-[42px]">Lowongan dari mitra hiring</h1>
        <p class="max-w-[680px] text-[17px] text-kea-on-forest">Lamar pakai profil KEA + CV. Lowongan bertanda <b>Khusus alumni</b> hanya bisa dilamar learner yang sudah lulus program.</p>
        <div class="flex flex-wrap gap-3">
          <NuxtLink to="/jalur-karier" class="btn-primary">Belum siap? Temukan jalur belajarmu</NuxtLink>
          <NuxtLink to="/dashboard/lamaran" class="btn border-[1.5px] border-white text-white hover:text-white">Lamaran saya</NuxtLink>
        </div>
      </div>
    </section>

    <div class="container-kea flex flex-col gap-6 py-10">
      <div class="flex flex-wrap items-end gap-4">
        <label class="field flex-[1_1_280px]">Cari posisi, perusahaan, atau kota
          <input v-model="search" type="search" placeholder="mis. Backend, Bandung" class="input">
        </label>
        <label class="field flex-[0_1_200px]">Bidang
          <select class="input" :value="filters.category" @change="update({ category: ($event.target as HTMLSelectElement).value })">
            <option value="">Semua bidang</option>
            <option v-for="c in categories?.data ?? []" :key="c.slug" :value="c.slug">{{ c.name }}</option>
          </select>
        </label>
        <label class="field flex-[0_1_180px]">Tipe
          <select class="input" :value="filters.type" @change="update({ type: ($event.target as HTMLSelectElement).value })">
            <option value="">Semua tipe</option>
            <option v-for="t in employmentTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </label>
      </div>
      <div role="group" aria-label="Filter mode kerja" class="flex flex-wrap gap-2.5">
        <button type="button" class="pill" :aria-pressed="!filters.mode" @click="update({ mode: '' })">Semua</button>
        <button v-for="m in workModes" :key="m.value" type="button" class="pill" :aria-pressed="filters.mode === m.value" @click="update({ mode: m.value })">{{ m.label }}</button>
      </div>

      <span class="text-sm font-semibold text-kea-muted" aria-live="polite">{{ result?.meta.total ?? 0 }} lowongan</span>
      <div v-if="result?.data.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3" :class="status === 'pending' ? 'opacity-60' : ''">
        <JobCard v-for="j in result.data" :key="j.id" :job="j" />
      </div>
      <EmptyState v-else-if="status !== 'pending'" title="Belum ada lowongan yang cocok" body="Coba ubah filter, atau lengkapi profil karier supaya mitra bisa menemukanmu di talent pool.">
        <NuxtLink to="/dashboard/profil" class="btn-outline">Lengkapi profil karier</NuxtLink>
      </EmptyState>

      <nav v-if="result && result.meta.last_page > 1" aria-label="Halaman" class="flex justify-center gap-2">
        <button v-for="n in result.meta.last_page" :key="n" type="button" class="flex size-11 items-center justify-center rounded-kea-md border font-bold" :class="n === result.meta.current_page ? 'border-kea-forest bg-kea-forest text-white' : 'border-kea-field bg-white'" :aria-current="n === result.meta.current_page ? 'page' : undefined" @click="update({ page: n })">{{ n }}</button>
      </nav>
    </div>
  </main>
</template>
