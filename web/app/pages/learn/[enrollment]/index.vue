<script setup lang="ts">
import type { Curriculum } from '~/types/api'

definePageMeta({ layout: 'bare', middleware: 'auth' })
const route = useRoute()
const id = route.params.enrollment as string
const res = await useApi()<{ data: Curriculum }>(`/enrollments/${id}/curriculum`).catch(() => null)
if (res?.data.resume_lesson_id) {
  await navigateTo(`/learn/${id}/${res.data.resume_lesson_id}`, { replace: true })
} else {
  await navigateTo('/dashboard', { replace: true })
}
</script>

<template>
  <div class="p-10 text-kea-muted">Membuka kelas…</div>
</template>
