<script setup lang="ts">
import { renderSVG } from 'uqr'

const props = defineProps<{ value: string; size?: number; label?: string }>()
const svg = computed(() => renderSVG(props.value, { border: 1 }))
</script>

<template>
  <div role="img" :aria-label="label ?? 'Kode QR'" :style="{ width: `${size ?? 96}px`, height: `${size ?? 96}px` }" class="[&>svg]:h-full [&>svg]:w-full" v-html="svg" />
</template>
