<script setup lang="ts">
const demo = useRuntimeConfig().public.demo
</script>

<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
  <DemoBar v-if="demo" />
</template>
