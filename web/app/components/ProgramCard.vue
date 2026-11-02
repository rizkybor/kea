<script setup lang="ts">
import type { ProgramCard } from '~/types/api'
import { rupiah } from '~/utils/format'

const props = defineProps<{ program: ProgramCard }>()

const meta = computed(() => {
  const p = props.program
  if (p.type === 'video_course') return [`${p.lessons_count ?? 0} materi`, p.format_label].filter(Boolean).join(' · ')
  if (p.next_cohort) return [`Mulai ${formatDate(p.next_cohort.starts_at, { day: 'numeric', month: 'short', year: 'numeric' })}`, p.duration_label, p.format_label].filter(Boolean).join(' · ')
  return [p.duration_label, p.format_label].filter(Boolean).join(' · ')
})
const seats = computed(() => {
  const c = props.program.next_cohort
  return c && c.is_available && c.seats_left <= 5 ? `Sisa ${c.seats_left} kursi` : null
})
</script>

<template>
  <NuxtLink :to="`/program/${program.slug}`" class="card flex flex-col overflow-hidden text-kea-ink no-underline transition-colors hover:border-kea-forest hover:text-kea-ink">
    <ProgramThumb :src="program.thumbnail_url" :title="program.category?.name ?? program.type_label" :type="program.type" />
    <div class="flex flex-1 flex-col gap-3 p-5">
      <div class="flex flex-wrap gap-2">
        <span class="badge-olive">{{ program.type_label }}</span>
        <span v-if="program.category" class="badge-muted">{{ program.category.name }}</span>
        <span v-if="seats" class="badge-ember">{{ seats }}</span>
      </div>
      <h3 class="text-xl leading-snug font-bold">{{ program.title }}</h3>
      <span class="text-sm text-kea-muted">{{ meta }}</span>
      <div class="mt-auto flex items-center justify-between border-t border-kea-line pt-3">
        <span class="flex flex-col">
          <s v-if="program.promo_price" class="text-xs text-kea-muted">{{ rupiah(program.price) }}</s>
          <span class="text-lg font-extrabold">{{ rupiah(program.selling_price) }}</span>
        </span>
        <span class="text-sm font-bold text-kea-forest">Detail →</span>
      </div>
    </div>
  </NuxtLink>
</template>
