<script setup lang="ts">
const auth = useAuthStore()
const route = useRoute()
const redirect = computed(() => {
  if (typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')) return route.query.redirect
  return auth.isPartner ? '/partner' : '/dashboard'
})
const { values, errors, message, pending, submit } = useForm({ email: '', password: '', remember: true })

async function onSubmit() {
  await submit(() => auth.login(values)).then(() => navigateTo(redirect.value)).catch(() => {})
}

useSeoMeta({ title: 'Masuk', robots: 'noindex' })
</script>

<template>
  <AuthCard title="Masuk ke KEA" subtitle="Lanjutkan belajar dan pantau progres kelasmu.">
    <form class="flex flex-col gap-4" novalidate @submit.prevent="onSubmit">
      <ErrorAlert :message="message" />
      <label class="field">Email
        <input v-model="values.email" type="email" autocomplete="email" required class="input" :class="errors.email && 'input-error'" :aria-invalid="!!errors.email" aria-describedby="email-error">
        <span v-if="errors.email" id="email-error" class="error-text">{{ errors.email }}</span>
      </label>
      <label class="field">Password
        <input v-model="values.password" type="password" autocomplete="current-password" required class="input" :class="errors.password && 'input-error'">
        <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
      </label>
      <div class="flex items-center justify-between text-sm">
        <label class="flex items-center gap-2"><input v-model="values.remember" type="checkbox" class="size-4 accent-kea-forest"> Ingat saya</label>
        <NuxtLink to="/lupa-password" class="font-semibold">Lupa password?</NuxtLink>
      </div>
      <button type="submit" class="btn-primary w-full" :disabled="pending">{{ pending ? 'Memproses…' : 'Masuk' }}</button>
    </form>
    <p class="text-center text-sm text-kea-muted">Belum punya akun? <NuxtLink :to="{ path: '/daftar', query: route.query }" class="font-bold">Daftar gratis</NuxtLink></p>
  </AuthCard>
</template>
