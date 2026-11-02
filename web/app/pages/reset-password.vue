<script setup lang="ts">
const api = useApi()
const route = useRoute()
const { values, errors, message, pending, submit } = useForm({
  token: String(route.query.token ?? ''),
  email: String(route.query.email ?? ''),
  password: '',
  password_confirmation: '',
})
const done = ref(false)

async function onSubmit() {
  await submit(() => api('/auth/reset-password', { method: 'POST', body: values })).then(() => { done.value = true }).catch(() => {})
}

useSeoMeta({ title: 'Reset password', robots: 'noindex' })
</script>

<template>
  <AuthCard title="Buat password baru">
    <template v-if="done">
      <p role="status" class="rounded-kea-md bg-kea-olive-50 px-4 py-3 text-sm font-medium text-kea-olive-900">Password berhasil diganti.</p>
      <NuxtLink to="/masuk" class="btn-primary w-full">Masuk</NuxtLink>
    </template>
    <form v-else class="flex flex-col gap-4" novalidate @submit.prevent="onSubmit">
      <ErrorAlert :message="message || errors.email" />
      <label class="field">Email
        <input v-model="values.email" type="email" readonly class="input bg-kea-mist">
      </label>
      <label class="field">Password baru
        <input v-model="values.password" type="password" autocomplete="new-password" class="input" :class="errors.password && 'input-error'">
        <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
      </label>
      <label class="field">Ulangi password
        <input v-model="values.password_confirmation" type="password" autocomplete="new-password" class="input">
      </label>
      <button type="submit" class="btn-primary w-full" :disabled="pending">Simpan password</button>
    </form>
  </AuthCard>
</template>
