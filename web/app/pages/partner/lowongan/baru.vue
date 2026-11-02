<script setup lang="ts">
definePageMeta({ layout: 'partner', middleware: ['auth', 'partner'] })
useSeoMeta({ title: 'Pasang lowongan', robots: 'noindex' })
</script>

<template>
  <div class="flex max-w-[860px] flex-col gap-5">
    <NuxtLink to="/partner" class="text-sm font-semibold">← Kembali</NuxtLink>
    <h1 class="text-[28px] font-extrabold">Pasang lowongan</h1>
    <JobForm @saved="job => navigateTo(`/partner/lowongan/${job.id}`)" />
  </div>
</template>
