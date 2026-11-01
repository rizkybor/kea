<script setup lang="ts">
defineProps<{ title: string; body?: string }>()
</script>

<template>
  <div class="flex flex-col items-center gap-3 rounded-kea-lg border border-dashed border-kea-field bg-kea-surface px-6 py-12 text-center">
    <b class="text-lg">{{ title }}</b>
    <p v-if="body" class="max-w-md text-[15px] text-kea-muted">{{ body }}</p>
    <slot />
  </div>
</template>
