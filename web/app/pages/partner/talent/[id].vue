<script setup lang="ts">
import type { Talent } from '~/types/api'
import { formatDate } from '~/utils/format'

definePageMeta({ layout: 'partner', middleware: ['auth', 'partner'] })
const route = useRoute()
const api = useApi()
const id = route.params.id as string
const { data, error } = await useAsyncData(`talent-${id}`, () => api<{ data: Talent }>(`/partner/talents/${id}`))
const t = computed(() => data.value?.data ?? null)
useSeoMeta({ title: () => t.value?.name ?? 'Talent', robots: 'noindex' })
</script>

<template>
  <div class="flex max-w-[900px] flex-col gap-6">
    <NuxtLink to="/partner/talent" class="text-sm font-semibold">← Talent pool</NuxtLink>
    <EmptyState v-if="error || !t" title="Talent tidak ditemukan" body="Profil ini tidak tersedia atau persetujuan talent pool sudah dicabut." />
    <template v-else>
      <section class="card flex flex-wrap items-start gap-5 p-6">
        <span class="flex size-16 items-center justify-center rounded-full bg-kea-forest text-2xl font-bold text-white" aria-hidden="true">{{ t.name.charAt(0) }}</span>
        <div class="flex min-w-0 flex-1 flex-col gap-1">
          <h1 class="text-[26px] font-extrabold">{{ t.name }}</h1>
          <span class="text-[15px] text-kea-muted">{{ [t.headline, t.city].filter(Boolean).join(' · ') }}</span>
          <span v-if="t.open_to_work" class="badge-olive mt-1 self-start">Open to work</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <a v-if="t.has_cv" :href="`/api/v1/partner/talents/${t.id}/cv`" class="btn-secondary">Unduh CV</a>
          <a v-if="t.linkedin_url" :href="t.linkedin_url" target="_blank" rel="noopener" class="btn-outline">LinkedIn</a>
          <a v-if="t.portfolio_url" :href="t.portfolio_url" target="_blank" rel="noopener" class="btn-outline">Portofolio</a>
        </div>
      </section>
      <section v-if="t.bio" class="flex flex-col gap-2"><h2 class="text-lg font-bold">Tentang</h2><p class="leading-relaxed">{{ t.bio }}</p></section>
      <section class="flex flex-col gap-2">
        <h2 class="text-lg font-bold">Skill</h2>
        <div class="flex flex-wrap gap-2"><span v-for="s in t.skills" :key="s" class="badge-olive px-3 py-1.5 text-sm">{{ s }}</span></div>
      </section>
      <section class="flex flex-col gap-3">
        <h2 class="text-lg font-bold">Program yang diselesaikan</h2>
        <div v-for="p in t.programs" :key="p.title" class="card flex flex-wrap items-center justify-between gap-3 p-4">
          <span class="flex flex-col"><b>{{ p.title }}</b><span class="text-sm text-kea-muted">{{ p.cohort_name }}<template v-if="p.completed_at"> · lulus {{ formatDate(p.completed_at) }}</template></span></span>
          <span class="flex items-center gap-3">
            <span v-if="p.final_score !== null" class="text-lg font-extrabold">{{ p.final_score }}</span>
            <NuxtLink v-if="p.certificate_code" :to="`/verify/${p.certificate_code}`" target="_blank" class="text-sm font-bold">Verifikasi sertifikat ↗</NuxtLink>
          </span>
        </div>
      </section>
      <p class="text-xs text-kea-muted">Data talent dibagikan atas persetujuan learner (UU PDP) dan hanya boleh dipakai untuk keperluan rekrutmen.</p>
    </template>
  </div>
</template>
