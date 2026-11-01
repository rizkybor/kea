<script setup lang="ts">
import type { Job } from '~/types/api'
import { formatDate, salaryRange } from '~/utils/format'

defineProps<{ job: Job; to?: string }>()
</script>

<template>
  <NuxtLink :to="to ?? `/lowongan/${job.slug}`" class="card flex flex-col gap-3 p-5 text-kea-ink no-underline hover:border-kea-forest hover:text-kea-ink">
    <div class="flex items-start gap-3">
      <span class="flex size-12 flex-none items-center justify-center rounded-xl bg-kea-olive-50 text-lg font-extrabold text-kea-olive-900" aria-hidden="true">{{ job.company?.name.charAt(0) }}</span>
      <span class="flex min-w-0 flex-col">
        <h3 class="text-lg leading-snug font-bold">{{ job.title }}</h3>
        <span class="text-sm text-kea-muted">{{ job.company?.name }}<template v-if="job.company?.is_verified"> · <span class="text-kea-moss">✓ Mitra terverifikasi</span></template></span>
      </span>
    </div>
    <div class="flex flex-wrap gap-2">
      <span class="badge-olive">{{ job.employment_type_label }}</span>
      <span class="badge-muted">{{ job.work_mode_label }}<template v-if="job.location && job.work_mode !== 'remote'"> · {{ job.location }}</template></span>
      <span v-if="job.alumni_only" class="badge-ember">Khusus alumni</span>
      <span v-if="job.status && job.status !== 'open'" class="badge-muted">{{ job.status === 'draft' ? 'Draft' : 'Ditutup' }}</span>
    </div>
    <div v-if="job.skills.length" class="flex flex-wrap gap-1.5 text-xs text-kea-muted">
      <span v-for="s in job.skills.slice(0, 5)" :key="s" class="rounded-md border border-kea-line px-2 py-0.5">{{ s }}</span>
    </div>
    <div class="mt-auto flex items-center justify-between border-t border-kea-line pt-3 text-sm">
      <span class="font-bold">{{ salaryRange(job.salary_min, job.salary_max) ?? 'Gaji kompetitif' }}</span>
      <span v-if="job.applications_count !== undefined" class="text-kea-muted">{{ job.applications_count }} pelamar</span>
      <span v-else-if="job.closes_at" class="text-kea-muted">Tutup {{ formatDate(job.closes_at, { day: 'numeric', month: 'short' }) }}</span>
    </div>
  </NuxtLink>
</template>
