<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <main class="flex justify-center bg-kea-surface px-4 py-12 md:py-20">
    <div class="card flex w-full max-w-[440px] flex-col gap-6 p-6 md:p-8">
      <div class="flex flex-col gap-2">
        <h1 class="text-[28px] leading-tight font-extrabold">{{ title }}</h1>
        <p v-if="subtitle" class="text-[15px] text-kea-muted">{{ subtitle }}</p>
      </div>
      <slot />
    </div>
  </main>
</template>
