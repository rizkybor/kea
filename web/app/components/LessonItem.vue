<script setup lang="ts">
import type { CurriculumLesson } from '~/types/api'

defineProps<{ lesson: CurriculumLesson; index: number; current: boolean; to: string }>()
</script>

<template>
  <NuxtLink
    :to="to"
    :aria-current="current ? 'page' : undefined"
    class="flex min-h-[52px] w-full items-center gap-3 rounded-kea-md px-2 py-2.5 text-sm text-kea-ink no-underline hover:bg-kea-surface hover:text-kea-ink"
    :class="current ? 'bg-kea-olive-50 hover:bg-kea-olive-50' : ''"
  >
    <span
      class="flex size-7 flex-none items-center justify-center rounded-full text-[13px] font-bold"
      :class="lesson.completed ? 'bg-kea-forest text-white' : 'bg-kea-mist text-kea-muted'"
    >
      <span v-if="lesson.completed" aria-label="Selesai">✓</span>
      <span v-else>{{ index }}</span>
    </span>
    <span class="flex min-w-0 flex-col">
      <span class="font-semibold">{{ lesson.title }}</span>
      <span class="text-xs text-kea-muted">{{ lesson.kind_label }}</span>
    </span>
  </NuxtLink>
</template>
