<script setup lang="ts">
import type { ProgramType } from '~/types/api'

/** Thumbnail program; bila belum ada foto, pakai pola brand per tipe (rasio tetap supaya CLS ≈ 0). */
const props = defineProps<{ src: string | null; title: string; type: ProgramType; height?: string }>()
const tone = computed(() => ({
  bootcamp: 'bg-kea-forest text-kea-on-forest',
  short_class: 'bg-kea-olive text-kea-ink',
  video_course: 'bg-kea-moss text-white',
  event: 'bg-kea-ember text-kea-ink',
}[props.type]))
</script>

<template>
  <div class="relative overflow-hidden" :class="height ?? 'h-[180px]'">
    <img v-if="src" :src="src" :alt="''" width="640" height="360" loading="lazy" class="h-full w-full object-cover">
    <div v-else class="flex h-full w-full items-end p-5" :class="tone" aria-hidden="true">
      <svg class="absolute -top-6 -right-6 opacity-20" width="180" height="180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 7l9-4 9 4-9 4-9-4z" /><path d="M7 9v5c0 1.7 2.2 3 5 3s5-1.3 5-3V9" /></svg>
      <span class="relative line-clamp-2 text-lg leading-snug font-extrabold">{{ title }}</span>
    </div>
  </div>
</template>
