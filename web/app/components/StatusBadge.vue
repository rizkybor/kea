<script setup lang="ts">
const props = defineProps<{ status: string }>()
const info = computed(() => submissionStatusLabel[props.status] ?? { label: props.status, tone: 'muted' as const })
</script>

<template>
  <span :class="`badge-${info.tone}`">{{ info.label }}</span>
</template>
