<script setup lang="ts">
import type { Paginated, Submission } from '~/types/api'
import { formatDate } from '~/utils/format'

definePageMeta({ layout: 'learner', middleware: ['auth', 'mentor'] })
const api = useApi()
const status = ref<'submitted' | 'revision' | 'reviewed'>('submitted')
const { data, refresh } = await useAsyncData('mentor-submissions', () => api<Paginated<Submission>>('/mentor/submissions', { query: { status: status.value } }), { watch: [status] })

const grading = ref<number | null>(null)
const form = reactive({ score: 80, feedback: '', status: 'reviewed' as 'reviewed' | 'revision' })
const error = ref<string | null>(null)
const busy = ref(false)

function open(s: Submission) {
  grading.value = s.id
  form.score = s.score ?? 80
  form.feedback = s.feedback ?? ''
  form.status = 'reviewed'
  error.value = null
}

async function grade() {
  if (!grading.value) return
  busy.value = true
  error.value = null
  try {
    await api(`/mentor/submissions/${grading.value}/grade`, { method: 'PUT', body: form })
    grading.value = null
    await refresh()
  } catch (e) {
    error.value = apiError(e).message
  } finally {
    busy.value = false
  }
}

useSeoMeta({ title: 'Review Tugas', robots: 'noindex' })
</script>

<template>
  <div class="flex max-w-[960px] flex-col gap-5">
    <div>
      <h1 class="text-[28px] font-extrabold">Review tugas</h1>
      <p class="text-[15px] text-kea-muted">Target SLA review ≤ 72 jam. Nilai ≥ 70 dihitung lulus untuk syarat sertifikat.</p>
    </div>
    <div role="group" aria-label="Filter status" class="flex flex-wrap gap-2">
      <button v-for="s in [['submitted', 'Menunggu review'], ['revision', 'Revisi'], ['reviewed', 'Sudah dinilai']] as const" :key="s[0]" type="button" class="pill" :aria-pressed="status === s[0]" @click="status = s[0]">{{ s[1] }}</button>
    </div>

    <div v-for="s in data?.data ?? []" :key="s.id" class="card flex flex-col gap-3 p-5">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex flex-col">
          <b>{{ s.lesson?.assignment_title }}</b>
          <span class="text-sm text-kea-muted">{{ s.learner?.name }} · {{ s.learner?.program_title }}<template v-if="s.learner?.cohort_name"> · {{ s.learner.cohort_name }}</template></span>
          <span class="text-xs text-kea-muted">Dikirim {{ formatDate(s.submitted_at, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}</span>
        </div>
        <span v-if="s.score !== null" class="badge-olive">Nilai {{ s.score }}</span>
      </div>
      <a v-if="s.link_url" :href="s.link_url" target="_blank" rel="noopener" class="text-sm break-all">{{ s.link_url }}</a>
      <span v-if="s.file_name" class="text-sm text-kea-muted">File: {{ s.file_name }} (unduh dari panel admin)</span>
      <p v-if="s.note" class="text-sm"><b>Catatan learner:</b> {{ s.note }}</p>

      <form v-if="grading === s.id" class="flex flex-col gap-3 border-t border-kea-line pt-4" @submit.prevent="grade">
        <ErrorAlert :message="error" />
        <div class="flex flex-wrap gap-4">
          <label class="field w-32">Nilai (0–100)<input v-model.number="form.score" type="number" min="0" max="100" class="input" required></label>
          <label class="field w-56">Hasil
            <select v-model="form.status" class="input"><option value="reviewed">Lulus review</option><option value="revision">Minta revisi</option></select>
          </label>
        </div>
        <label class="field">Feedback<textarea v-model="form.feedback" rows="3" class="input" /></label>
        <div class="flex gap-2"><button type="submit" class="btn-secondary" :disabled="busy">Simpan nilai</button><button type="button" class="btn-ghost" @click="grading = null">Batal</button></div>
      </form>
      <button v-else type="button" class="btn-outline self-start" @click="open(s)">{{ s.score === null ? 'Beri nilai' : 'Ubah nilai' }}</button>
    </div>
    <EmptyState v-if="data && !data.data.length" title="Tidak ada tugas di antrian ini" />
  </div>
</template>
