<script setup lang="ts">
import type { Recommendation } from '~/types/api'
import { rupiah } from '~/utils/format'

const props = defineProps<{ item: Recommendation; highlight?: boolean; warmUp?: boolean }>()
const link = computed(() => `/program/${props.item.program.slug}${props.item.suggested_cohort_id ? `?cohort=${props.item.suggested_cohort_id}` : ''}`)
</script>

<template>
  <div class="card flex flex-col gap-4 overflow-hidden" :class="highlight ? 'border-2 border-kea-forest' : ''">
    <ProgramThumb :src="item.program.thumbnail_url" :title="item.program.category?.name ?? item.program.type_label" :type="item.program.type" height="h-28" />
    <div class="flex flex-1 flex-col gap-3 px-5 pb-5">
      <div class="flex items-center justify-between gap-2">
        <span class="badge-olive">{{ item.program.type_label }}</span>
        <span v-if="warmUp" class="text-sm font-bold text-kea-muted">Langkah awal · lebih ringan</span>
        <span v-else class="text-sm font-extrabold text-kea-moss">{{ item.match_pct }}% cocok</span>
      </div>
      <h3 class="text-lg leading-snug font-bold">{{ item.program.title }}</h3>
      <ul class="flex flex-col gap-1.5 text-sm">
        <li v-for="r in item.reasons" :key="r" class="flex gap-2"><span class="text-kea-moss" aria-hidden="true">✓</span>{{ r }}</li>
      </ul>
      <div class="mt-auto flex items-center justify-between gap-3 border-t border-kea-line pt-3">
        <span class="font-extrabold">{{ rupiah(item.program.selling_price) }}</span>
        <NuxtLink :to="link" :class="highlight ? 'btn-primary' : 'btn-outline'">Lihat program</NuxtLink>
      </div>
    </div>
  </div>
</template>
