<script setup lang="ts">
const auth = useAuthStore()
const route = useRoute()
const redirect = computed(() => (typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/') ? route.query.redirect : '/dashboard'))
const { values, errors, message, pending, submit } = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
const done = ref(false)

async function onSubmit() {
  await submit(() => auth.register(values)).then(() => { done.value = true }).catch(() => {})
}

useSeoMeta({ title: 'Daftar', robots: 'noindex' })
</script>

<template>
  <AuthCard v-if="!done" title="Buat akun KEA" subtitle="Satu akun untuk semua bootcamp, kelas, dan sertifikatmu.">
    <form class="flex flex-col gap-4" novalidate @submit.prevent="onSubmit">
      <ErrorAlert :message="message" />
      <label class="field">Nama lengkap
        <input v-model="values.name" autocomplete="name" required class="input" :class="errors.name && 'input-error'">
        <span v-if="errors.name" class="error-text">{{ errors.name }}</span>
      </label>
      <label class="field">Email
        <input v-model="values.email" type="email" autocomplete="email" required class="input" :class="errors.email && 'input-error'">
        <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
      </label>
      <label class="field">No. WhatsApp <span class="font-normal text-kea-muted">(opsional, untuk reminder kelas)</span>
        <input v-model="values.phone" type="tel" autocomplete="tel" placeholder="08xx-xxxx-xxxx" class="input" :class="errors.phone && 'input-error'">
        <span v-if="errors.phone" class="error-text">{{ errors.phone }}</span>
      </label>
      <label class="field">Password
        <input v-model="values.password" type="password" autocomplete="new-password" required class="input" :class="errors.password && 'input-error'" aria-describedby="pw-hint">
        <span id="pw-hint" class="text-xs font-normal text-kea-muted">Minimal 8 karakter, kombinasi huruf dan angka.</span>
        <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
      </label>
      <label class="field">Ulangi password
        <input v-model="values.password_confirmation" type="password" autocomplete="new-password" required class="input">
      </label>
      <button type="submit" class="btn-primary w-full" :disabled="pending">{{ pending ? 'Memproses…' : 'Daftar' }}</button>
      <p class="text-xs text-kea-muted">Dengan mendaftar, kamu menyetujui Syarat & Ketentuan serta Kebijakan Privasi KEA (UU PDP).</p>
    </form>
    <p class="text-center text-sm text-kea-muted">Sudah punya akun? <NuxtLink :to="{ path: '/masuk', query: route.query }" class="font-bold">Masuk</NuxtLink></p>
  </AuthCard>
  <AuthCard v-else title="Cek email kamu" :subtitle="`Kami mengirim link verifikasi ke ${values.email}. Verifikasi email diperlukan sebelum checkout.`">
    <NuxtLink :to="redirect" class="btn-primary w-full">Lanjutkan</NuxtLink>
  </AuthCard>
</template>
