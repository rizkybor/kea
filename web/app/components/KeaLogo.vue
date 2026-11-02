<script setup lang="ts">
import { asset } from '~/utils/asset'
withDefaults(defineProps<{ size?: number; light?: boolean; to?: string }>(), { size: 36, light: false, to: '/' })
</script>

<template>
  <NuxtLink :to="to" class="flex items-center gap-2.5 no-underline" :class="light ? 'text-white hover:text-white' : 'text-kea-ink hover:text-kea-ink'">
    <img :src="asset('kea-icon.svg')" alt="" :width="size" :height="size">
    <span class="text-[22px] font-extrabold tracking-[0.5px]">KEA</span>
  </NuxtLink>
</template>
