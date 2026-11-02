<script setup lang="ts">
withDefaults(defineProps<{ value: number; label?: string; thin?: boolean }>(), { label: 'Progres', thin: false })
</script>

<template>
  <span
    role="progressbar"
    :aria-valuenow="value"
    aria-valuemin="0"
    aria-valuemax="100"
    :aria-label="`${label} ${value}%`"
    class="block w-full overflow-hidden rounded-full bg-kea-line"
    :class="thin ? 'h-1.5' : 'h-2'"
  >
    <span class="block h-full rounded-full bg-kea-olive transition-[width] duration-500" :style="{ width: `${value}%` }" />
  </span>
</template>
