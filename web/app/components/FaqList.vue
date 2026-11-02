<script setup lang="ts">
/** Accordion FAQ (`<details>`) + JSON-LD FAQPage. */
const props = defineProps<{ items: { q: string; a: string }[] }>()

useHead({
  script: [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      mainEntity: props.items.map(f => ({ '@type': 'Question', name: f.q, acceptedAnswer: { '@type': 'Answer', text: f.a } })),
    }),
  }],
})
</script>

<template>
  <div class="flex flex-col gap-3">
    <details v-for="(faq, i) in items" :key="i" class="rounded-xl border border-kea-line px-5 py-[18px]">
      <summary class="text-[17px] font-bold">{{ faq.q }}</summary>
      <p class="mt-3 leading-relaxed text-kea-muted">{{ faq.a }}</p>
    </details>
  </div>
</template>
