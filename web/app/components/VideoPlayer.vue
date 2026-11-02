<script setup lang="ts">
/** Player Bunny Stream (HLS + signed URL 2 jam) dengan watermark email user (anti-share). */
defineProps<{ url: string | null; watermark: string; title: string }>()
</script>

<template>
  <div class="relative aspect-video w-full overflow-hidden rounded-kea-lg bg-[#1d2420]">
    <iframe v-if="url" :src="url" :title="title" class="absolute inset-0 h-full w-full" allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture" allowfullscreen />
    <div v-else class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center text-kea-on-forest">
      <span class="flex size-16 items-center justify-center rounded-full bg-kea-ember">
        <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="#14110F"><path d="M8 5v14l11-7z" /></svg>
      </span>
      <span class="text-sm">Video belum tersedia untuk lesson ini (Bunny Stream belum dikonfigurasi).</span>
    </div>
    <span class="pointer-events-none absolute right-4 bottom-3 text-xs font-semibold text-white/40 select-none" aria-hidden="true">{{ watermark }}</span>
  </div>
</template>
