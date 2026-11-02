<script setup lang="ts">
/** Breadcrumb + JSON-LD BreadcrumbList. */
const props = defineProps<{ items: { label: string; to?: string }[]; light?: boolean }>()
const { public: { siteUrl } } = useRuntimeConfig()

useHead({
  script: [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: props.items.map((item, i) => ({
        '@type': 'ListItem', position: i + 1, name: item.label, ...(item.to ? { item: siteUrl + item.to } : {}),
      })),
    }),
  }],
})
</script>

<template>
  <nav aria-label="Breadcrumb" class="text-sm" :class="light ? 'text-kea-on-forest' : 'text-kea-muted'">
    <ol class="flex flex-wrap gap-1.5">
      <li v-for="(item, i) in items" :key="i" class="flex gap-1.5">
        <NuxtLink v-if="item.to" :to="item.to" :class="light ? 'text-white hover:text-kea-on-forest' : ''">{{ item.label }}</NuxtLink>
        <span v-else aria-current="page">{{ item.label }}</span>
        <span v-if="i < items.length - 1" aria-hidden="true">/</span>
      </li>
    </ol>
  </nav>
</template>
