<script setup lang="ts">
import type { Talent } from '~/types/api'

defineProps<{ talent: Talent }>()
</script>

<template>
  <NuxtLink :to="`/partner/talent/${talent.id}`" class="card flex flex-col gap-3 p-5 text-kea-ink no-underline hover:border-kea-forest hover:text-kea-ink">
    <div class="flex items-start gap-3">
      <span class="flex size-12 flex-none items-center justify-center rounded-full bg-kea-forest text-lg font-bold text-white" aria-hidden="true">{{ talent.name.charAt(0) }}</span>
      <span class="flex min-w-0 flex-1 flex-col">
        <b class="text-lg">{{ talent.name }}</b>
        <span class="text-sm text-kea-muted">{{ [talent.headline, talent.city].filter(Boolean).join(' · ') || 'Alumni KEA' }}</span>
      </span>
      <span v-if="talent.open_to_work" class="badge-olive">Open to work</span>
    </div>
    <div class="flex flex-wrap gap-1.5"><span v-for="s in talent.skills.slice(0, 6)" :key="s" class="rounded-md border border-kea-line px-2 py-0.5 text-xs">{{ s }}</span></div>
    <ul class="mt-auto border-t border-kea-line pt-3 text-sm">
      <li v-for="p in talent.programs" :key="p.title">🎓 {{ p.title }}<template v-if="p.final_score !== null"> · nilai <b>{{ p.final_score }}</b></template></li>
    </ul>
  </NuxtLink>
</template>
