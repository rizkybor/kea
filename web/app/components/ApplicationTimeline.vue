<script setup lang="ts">
import type { ApplicationEvent } from '~/types/api'
import { formatDate } from '~/utils/format'

defineProps<{ events: ApplicationEvent[] }>()
</script>

<template>
  <ol class="flex flex-col gap-3" aria-label="Riwayat status">
    <li v-for="(e, i) in events" :key="i" class="relative flex gap-3 pl-1">
      <span class="mt-1.5 size-2.5 flex-none rounded-full" :class="i === events.length - 1 ? 'bg-kea-ember' : 'bg-kea-field'" aria-hidden="true" />
      <span class="flex flex-col">
        <span class="text-sm font-bold">{{ e.status_label }}</span>
        <span class="text-xs text-kea-muted">{{ formatDate(e.at, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}<template v-if="e.actor"> · {{ e.actor }}</template></span>
        <span v-if="e.note" class="mt-1 text-sm">{{ e.note }}</span>
      </span>
    </li>
  </ol>
</template>
