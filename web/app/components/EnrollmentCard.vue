<script setup lang="ts">
import type { Enrollment } from '~/types/api'

const props = defineProps<{ enrollment: Enrollment }>()
const e = computed(() => props.enrollment)
const meta = computed(() => {
  const parts = [`${e.value.progress_pct}% selesai`]
  if (e.value.attendance && e.value.attendance.held) parts.push(`Kehadiran ${e.value.attendance.attended}/${e.value.attendance.held}`)
  if (e.value.average_score !== null) parts.push(`Nilai rata-rata ${e.value.average_score}`)
  return parts.join(' · ')
})
</script>

<template>
  <NuxtLink :to="`/learn/${e.id}`" class="card flex items-center gap-4 p-4 text-kea-ink no-underline hover:border-kea-forest hover:text-kea-ink">
    <ProgramThumb :src="e.program.thumbnail_url" title="" :type="e.program.type" height="hidden h-20 w-28 flex-none rounded-xl sm:block" />
    <span class="flex min-w-0 flex-1 flex-col gap-2">
      <span class="text-xs font-bold tracking-wide text-kea-moss uppercase">{{ e.program.type_label }}<template v-if="e.cohort"> · {{ e.cohort.name }}</template></span>
      <b class="text-[17px]">{{ e.program.title }}</b>
      <ProgressBar :value="e.progress_pct" />
      <span class="text-[13px] text-kea-muted">{{ meta }}</span>
    </span>
    <span v-if="e.status === 'completed'" class="badge-olive hidden sm:inline-flex">Lulus</span>
    <span class="hidden font-bold text-kea-forest sm:inline">{{ e.status === 'completed' ? 'Buka' : 'Lanjutkan' }} →</span>
  </NuxtLink>
</template>
