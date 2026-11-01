<script setup lang="ts">
const route = useRoute()
const code = computed(() => String(route.params.code).toUpperCase())

useSeoMeta({ title: () => `Verifikasi ${code.value}`, robots: 'noindex' })
</script>

<template>
  <CertificateVerifier :key="code" :code="code" />
</template>
