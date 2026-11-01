<script setup lang="ts">
defineProps<{ message?: string | null }>()
</script>

<template>
  <p v-if="message" role="alert" class="rounded-kea-md border border-[#f2c4bd] bg-[#fdf0ee] px-4 py-3 text-sm font-medium text-[#8a1c12]">{{ message }}</p>
</template>
