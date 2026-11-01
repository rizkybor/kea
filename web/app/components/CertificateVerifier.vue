<script setup lang="ts">
import type { Certificate } from '~/types/api'
import { formatDate } from '~/utils/format'

const props = defineProps<{ code?: string }>()
const router = useRouter()
const api = useApi()
const input = ref(props.code ?? '')

const { data, error, status } = await useAsyncData(
  () => `certificate-${props.code ?? ''}`,
  () => (props.code ? api<{ data: Certificate }>(`/certificates/verify/${encodeURIComponent(props.code)}`) : Promise.resolve(null)),
)
const cert = computed(() => data.value?.data ?? null)

function check() {
  const code = input.value.trim().toUpperCase()
  if (code) router.push(`/verify/${code}`)
}
</script>

<template>
  <main class="mx-auto flex max-w-[1100px] flex-col gap-8 px-6 py-12">
    <div class="flex flex-col gap-2">
      <h1 class="text-[32px] font-extrabold md:text-[40px]">Verifikasi sertifikat</h1>
      <p class="text-[17px] text-kea-muted">Masukkan kode sertifikat atau scan QR di sertifikat untuk memastikan keasliannya.</p>
    </div>
    <form class="flex max-w-[640px] flex-wrap items-end gap-3" @submit.prevent="check">
      <label class="field flex-[1_1_320px]">Kode sertifikat
        <input v-model="input" placeholder="KEA-FSWD-2026-000123" class="input font-mono uppercase" autocomplete="off" spellcheck="false">
      </label>
      <button type="submit" class="btn-secondary" :disabled="!input.trim()">Cek</button>
    </form>

    <div v-if="status === 'pending' && code" class="h-80 animate-pulse rounded-kea-lg bg-kea-line" />

    <div v-else-if="code && (error || !cert)" role="alert" class="rounded-kea-lg border border-[#f2c4bd] bg-[#fdf0ee] p-6">
      <b class="text-lg text-[#8a1c12]">Sertifikat tidak ditemukan</b>
      <p class="mt-1 text-[15px] text-[#8a1c12]">Kode <span class="font-mono">{{ code }}</span> tidak terdaftar di KEA. Periksa kembali penulisan kode.</p>
    </div>

    <div v-else-if="cert" class="grid gap-6 lg:grid-cols-[1fr_340px]">
      <section aria-label="Pratinjau sertifikat" class="relative flex aspect-[1.414] flex-col justify-between overflow-hidden rounded-kea-lg border-[3px] border-kea-forest bg-white p-6 sm:p-10">
        <span class="absolute inset-x-0 top-0 h-2 bg-kea-ember" aria-hidden="true" />
        <div class="flex items-center justify-between">
          <span class="flex items-center gap-2"><img src="/kea-icon.svg" alt="KEA" width="40" height="40"><b class="text-xl text-kea-forest">KEA</b></span>
          <span class="text-xs text-kea-muted">by Jendela Cakra Digital</span>
        </div>
        <div class="flex flex-col gap-2">
          <span class="text-xs font-bold tracking-[4px] text-kea-moss">SERTIFIKAT KELULUSAN</span>
          <span class="text-2xl font-extrabold sm:text-4xl">{{ cert.recipient_name }}</span>
          <span class="text-sm text-kea-muted">telah menyelesaikan</span>
          <span class="text-lg font-extrabold text-kea-forest sm:text-2xl">{{ cert.program_title }}</span>
        </div>
        <div class="flex items-end justify-between gap-4">
          <span class="flex flex-col text-xs text-kea-muted"><span>Diterbitkan {{ formatDate(cert.issued_at) }}</span><span class="font-mono">Kode {{ cert.code }}</span></span>
          <QrCode :value="cert.verify_url" :size="84" :label="`QR verifikasi ${cert.code}`" />
        </div>
      </section>
      <aside class="card flex flex-col gap-4 p-6">
        <span v-if="cert.is_valid" class="badge-olive self-start px-3 py-1.5 text-sm">✓ Sertifikat valid</span>
        <span v-else class="badge-ember self-start px-3 py-1.5 text-sm">Sertifikat dicabut</span>
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-[15px]">
          <dt class="text-kea-muted">Nama</dt><dd class="font-semibold">{{ cert.recipient_name }}</dd>
          <dt class="text-kea-muted">Program</dt><dd class="font-semibold">{{ cert.program_title }}</dd>
          <dt v-if="cert.cohort_name" class="text-kea-muted">Cohort</dt><dd v-if="cert.cohort_name" class="font-semibold">{{ cert.cohort_name }}</dd>
          <dt v-if="cert.final_score !== null" class="text-kea-muted">Nilai akhir</dt><dd v-if="cert.final_score !== null" class="font-semibold">{{ cert.final_score }}</dd>
          <dt class="text-kea-muted">Terbit</dt><dd class="font-semibold">{{ formatDate(cert.issued_at) }}</dd>
        </dl>
        <template v-if="cert.is_valid">
          <a :href="cert.linkedin_url" target="_blank" rel="noopener" class="btn-secondary">Tambahkan ke LinkedIn</a>
          <a :href="cert.pdf_url" class="btn-outline">Unduh PDF</a>
        </template>
      </aside>
    </div>
  </main>
</template>
