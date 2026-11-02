<script setup lang="ts">
const api = useApi()
const { values, errors, message, pending, submit } = useForm({ email: '' })
const sent = ref<string | null>(null)

async function onSubmit() {
  await submit(() => api<{ message: string }>('/auth/forgot-password', { method: 'POST', body: values }))
    .then(res => { sent.value = (res as { message: string }).message })
    .catch(() => {})
}

useSeoMeta({ title: 'Lupa password', robots: 'noindex' })
</script>

<template>
  <AuthCard title="Lupa password" subtitle="Masukkan email akunmu. Link reset berlaku 60 menit dan hanya bisa dipakai sekali.">
    <p v-if="sent" role="status" class="rounded-kea-md bg-kea-olive-50 px-4 py-3 text-sm font-medium text-kea-olive-900">{{ sent }}</p>
    <form v-else class="flex flex-col gap-4" novalidate @submit.prevent="onSubmit">
      <ErrorAlert :message="message" />
      <label class="field">Email
        <input v-model="values.email" type="email" autocomplete="email" required class="input" :class="errors.email && 'input-error'">
        <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
      </label>
      <button type="submit" class="btn-primary w-full" :disabled="pending">Kirim link reset</button>
    </form>
    <NuxtLink to="/masuk" class="text-center text-sm font-bold">← Kembali ke halaman masuk</NuxtLink>
  </AuthCard>
</template>
