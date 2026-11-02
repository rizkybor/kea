<script setup lang="ts">
import type { User } from '~/types/api'

definePageMeta({ layout: 'learner', middleware: 'auth' })
const api = useApi()
const auth = useAuthStore()
const u = auth.user!
const { values, errors, message, pending, submit } = useForm({
  name: u.name, phone: u.phone ?? '', headline: u.headline ?? '', company: u.company ?? '',
  bio: u.bio ?? '', portfolio_url: u.portfolio_url ?? '', linkedin_url: u.linkedin_url ?? '',
  city: u.city ?? '', skills: [...u.skills], open_to_work: u.open_to_work, talent_consent: u.talent_consent,
})
const saved = ref(false)

async function save() {
  saved.value = false
  await submit(async () => {
    auth.user = (await api<{ data: User }>('/me', { method: 'PUT', body: values })).data
    saved.value = true
  }).catch(() => {})
}

const cvError = ref<string | null>(null)
const cvUploading = ref(false)
async function uploadCv(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  cvError.value = null
  if (file.size > 5 * 1024 * 1024) {
    cvError.value = 'Ukuran CV maksimal 5 MB.'
    return
  }
  const body = new FormData()
  body.append('cv', file)
  cvUploading.value = true
  try {
    auth.user = (await api<{ data: User }>('/me/cv', { method: 'POST', body })).data
  } catch (e) {
    cvError.value = apiError(e).errors?.cv?.[0] ?? apiError(e).message
  } finally {
    cvUploading.value = false
  }
}

useSeoMeta({ title: 'Profil Karier', robots: 'noindex' })
</script>

<template>
  <div class="flex max-w-[760px] flex-col gap-6">
    <div>
      <h1 class="text-[28px] font-extrabold">Profil karier</h1>
      <p class="text-[15px] text-kea-muted">Profil lengkap dipakai untuk talent pool mitra hiring dan klaim garansi kerja.</p>
    </div>
    <form class="card flex flex-col gap-4 p-6" novalidate @submit.prevent="save">
      <ErrorAlert :message="message" />
      <p v-if="saved" role="status" class="rounded-kea-md bg-kea-olive-50 px-4 py-3 text-sm font-medium text-kea-olive-900">Profil tersimpan.</p>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="field">Nama lengkap<input v-model="values.name" class="input" :class="errors.name && 'input-error'"><span v-if="errors.name" class="error-text">{{ errors.name }}</span></label>
        <label class="field">No. WhatsApp<input v-model="values.phone" type="tel" class="input"></label>
        <label class="field">Headline<input v-model="values.headline" placeholder="mis. Junior Web Developer" class="input"></label>
        <label class="field">Perusahaan / kampus<input v-model="values.company" class="input"></label>
        <label class="field">Link portofolio<input v-model="values.portfolio_url" type="url" placeholder="https://" class="input" :class="errors.portfolio_url && 'input-error'"><span v-if="errors.portfolio_url" class="error-text">{{ errors.portfolio_url }}</span></label>
        <label class="field">LinkedIn<input v-model="values.linkedin_url" type="url" placeholder="https://linkedin.com/in/…" class="input" :class="errors.linkedin_url && 'input-error'"><span v-if="errors.linkedin_url" class="error-text">{{ errors.linkedin_url }}</span></label>
      </div>
      <label class="field">Kota domisili<input v-model="values.city" placeholder="mis. Jakarta Timur" class="input"></label>
      <div class="field">
        <label for="skills">Skill <span class="font-normal text-kea-muted">(Enter untuk menambah, maks. 15)</span></label>
        <TagInput id="skills" v-model="values.skills" placeholder="mis. Laravel, Figma, SQL" />
        <span v-if="errors.skills" class="error-text">{{ errors.skills }}</span>
      </div>
      <label class="field">Ringkasan diri<textarea v-model="values.bio" rows="4" class="input" /></label>
      <fieldset class="flex flex-col gap-3 rounded-xl border border-kea-line p-4">
        <legend class="px-1 text-sm font-bold">Talent pool & lowongan</legend>
        <label class="flex items-start gap-3 text-[15px]">
          <input v-model="values.open_to_work" type="checkbox" class="mt-1 size-[18px] accent-kea-forest">
          <span><b>Open to work</b><br><span class="text-sm text-kea-muted">Tampilkan bahwa kamu sedang mencari kerja.</span></span>
        </label>
        <label class="flex items-start gap-3 text-[15px]">
          <input v-model="values.talent_consent" type="checkbox" class="mt-1 size-[18px] accent-kea-forest">
          <span><b>Izinkan profil saya tampil di talent pool mitra hiring</b><br>
            <span class="text-sm text-kea-muted">Hanya untuk alumni yang sudah lulus. Mitra dapat melihat nama, headline, kota, skill, link portofolio/LinkedIn, sertifikat & nilai, serta mengunduh CV. Email & nomor HP tidak ditampilkan. Persetujuan bisa dicabut kapan saja (UU PDP).</span></span>
        </label>
      </fieldset>
      <button type="submit" class="btn-secondary self-start" :disabled="pending">{{ pending ? 'Menyimpan…' : 'Simpan profil' }}</button>
    </form>

    <div class="card flex flex-col gap-3 p-6">
      <h2 class="text-lg font-bold">CV</h2>
      <p class="text-sm text-kea-muted">{{ auth.user?.has_cv ? 'CV sudah diunggah. Unggah lagi untuk mengganti.' : 'Belum ada CV.' }} Format PDF, maks. 5 MB.</p>
      <label class="btn-outline cursor-pointer self-start">
        {{ cvUploading ? 'Mengunggah…' : 'Pilih file PDF' }}
        <input type="file" accept="application/pdf" class="sr-only" :disabled="cvUploading" @change="uploadCv">
      </label>
      <span v-if="cvError" class="error-text">{{ cvError }}</span>
    </div>
  </div>
</template>
