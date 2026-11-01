<script setup lang="ts">
import type { Cohort } from '~/types/api'
import { formatDate, seatsLabel } from '~/utils/format'

defineProps<{ cohort: Cohort; selected: boolean }>()
defineEmits<{ select: [] }>()
</script>

<template>
  <button
    type="button"
    :aria-pressed="selected"
    :disabled="!cohort.is_available"
    class="flex min-h-14 w-full items-center justify-between gap-3 rounded-xl px-3.5 py-3 text-left text-kea-ink"
    :class="!cohort.is_available
      ? 'cursor-not-allowed border border-kea-line bg-kea-mist opacity-70'
      : selected ? 'border-2 border-kea-forest bg-kea-olive-50' : 'cursor-pointer border border-kea-field bg-white hover:border-kea-forest'"
    @click="$emit('select')"
  >
    <span class="flex flex-col">
      <b>{{ cohort.name }}</b>
      <span class="text-[13px] text-kea-muted">Mulai {{ formatDate(cohort.starts_at) }}<template v-if="cohort.schedule_label"> · {{ cohort.schedule_label }}</template></span>
    </span>
    <span class="shrink-0 text-[13px] font-bold" :class="cohort.is_available ? 'text-kea-ember-900' : 'text-kea-muted'">
      {{ seatsLabel(cohort.seats_left, cohort.is_available) }}
    </span>
  </button>
</template>
