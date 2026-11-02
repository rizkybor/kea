<script setup lang="ts">
import type { Applicant, Job } from '~/types/api'
import { applicationPipeline, applicationTone, formatDate } from '~/utils/format'

definePageMeta({ layout: 'partner', middleware: ['auth', 'partner'] })
const route = useRoute()
const router = useRouter()
const api = useApi()
const id = route.params.id as string

const { data: jobRes, error } = await useAsyncData(`partner-job-${id}`, () => api<{ data: Job }>(`/partner/jobs/${id}`))
const job = computed(() => jobRes.value?.data ?? null)
const tab = computed(() => (route.query.tab === 'edit' ? 'edit' : 'pelamar'))

const statusFilter = ref('')
const { data: applicantsRes, refresh } = await useAsyncData(`partner-applicants-${id}`, () => api<{ data: Applicant[] }>(`/partner/jobs/${id}/applicants`, { query: { status: statusFilter.value || undefined } }), { watch: [statusFilter] })
const applicants = computed(() => applicantsRes.value?.data ?? [])

const openId = ref<number | null>(null)
const form = reactive({ status: 'reviewing', note: '' })
const message = ref<string | null>(null)
const busy = ref(false)

function openUpdate(a: Applicant) {
  openId.value = a.id
  const next = applicationPipeline.findIndex(p => p.value === a.status)
  form.status = applicationPipeline[Math.min(next + 1, 3)]!.value
  form.note = ''
  message.value = null
}

async function updateStatus(a: Applicant) {
  busy.value = true
  message.value = null
  try {
    await api(`/partner/applications/${a.id}`, { method: 'PUT', body: { status: form.status, note: form.note || null } })
    openId.value = null
    await refresh()
  } catch (e) {
    message.value = apiError(e).message
  } finally {
    busy.value = false
  }
}

function onSaved(updated: Job) {
  jobRes.value = { data: updated }
  router.replace({ query: {} })
}

const finalStatuses = ['hired', 'rejected', 'withdrawn']
useSeoMeta({ title: () => job.value?.title ?? 'Lowongan', robots: 'noindex' })
</script>

<template>
  <div class="flex flex-col gap-6">
    <NuxtLink to="/partner" class="text-sm font-semibold">← Semua lowongan</NuxtLink>
    <EmptyState v-if="error || !job" title="Lowongan tidak ditemukan" />
    <template v-else>
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="text-[28px] font-extrabold">{{ job.title }}</h1>
          <p class="text-[15px] text-kea-muted">{{ job.employment_type_label }} · {{ job.work_mode_label }}<template v-if="job.location"> · {{ job.location }}</template> · status <b>{{ job.status === 'open' ? 'Dibuka' : job.status === 'draft' ? 'Draft' : 'Ditutup' }}</b></p>
        </div>
        <NuxtLink v-if="job.status === 'open'" :to="`/lowongan/${job.slug}`" target="_blank" class="btn-ghost">Lihat halaman publik ↗</NuxtLink>
      </div>

      <div role="tablist" aria-label="Lowongan" class="flex gap-1 border-b border-kea-line">
        <button v-for="t in [['pelamar', `Pelamar (${job.applications_count ?? 0})`], ['edit', 'Edit lowongan']]" :key="t[0]" type="button" role="tab" :aria-selected="tab === t[0]" class="-mb-px min-h-11 border-b-[3px] px-4 py-2.5 text-[15px] font-semibold" :class="tab === t[0] ? 'border-kea-ember' : 'border-transparent text-kea-muted'" @click="router.replace({ query: t[0] === 'edit' ? { tab: 'edit' } : {} })">{{ t[1] }}</button>
      </div>

      <JobForm v-if="tab === 'edit'" :job="job" @saved="onSaved" />

      <section v-else class="flex flex-col gap-4">
        <div role="group" aria-label="Filter status" class="flex flex-wrap gap-2">
          <button type="button" class="pill" :aria-pressed="!statusFilter" @click="statusFilter = ''">Semua</button>
          <button v-for="s in [{ value: 'applied', label: 'Baru' }, ...applicationPipeline]" :key="s.value" type="button" class="pill" :aria-pressed="statusFilter === s.value" @click="statusFilter = s.value">{{ s.label }}</button>
        </div>

        <article v-for="a in applicants" :key="a.id" class="card flex flex-col gap-4 p-5 lg:flex-row">
          <div class="flex min-w-0 flex-1 flex-col gap-3">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div class="flex flex-col">
                <NuxtLink :to="`/partner/talent/${a.talent.id}`" class="text-lg font-bold">{{ a.talent.name }}</NuxtLink>
                <span class="text-sm text-kea-muted">{{ [a.talent.headline, a.talent.city].filter(Boolean).join(' · ') }} · melamar {{ formatDate(a.applied_at, { day: 'numeric', month: 'short' }) }}</span>
              </div>
              <span :class="`badge-${applicationTone[a.status]}`">{{ a.status_label }}</span>
            </div>
            <div class="flex flex-wrap gap-1.5"><span v-for="s in a.talent.skills" :key="s" class="rounded-md border border-kea-line px-2 py-0.5 text-xs">{{ s }}</span></div>
            <ul class="text-sm">
              <li v-for="p in a.talent.programs" :key="p.title">🎓 {{ p.title }}<template v-if="p.final_score !== null"> · nilai {{ p.final_score }}</template><template v-if="p.certificate_code"> · <NuxtLink :to="`/verify/${p.certificate_code}`" target="_blank">cek sertifikat</NuxtLink></template></li>
            </ul>
            <p v-if="a.cover_letter" class="rounded-kea-md bg-kea-surface p-3 text-sm whitespace-pre-line">{{ a.cover_letter }}</p>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold">
              <a v-if="a.cv_url" :href="a.cv_url">Unduh CV</a>
              <a v-if="a.portfolio_url" :href="a.portfolio_url" target="_blank" rel="noopener">Portofolio ↗</a>
              <a v-if="a.talent.linkedin_url" :href="a.talent.linkedin_url" target="_blank" rel="noopener">LinkedIn ↗</a>
              <a :href="`mailto:${a.email}`">{{ a.email }}</a>
              <span v-if="a.phone" class="text-kea-muted">{{ a.phone }}</span>
            </div>

            <form v-if="openId === a.id" class="flex flex-col gap-3 border-t border-kea-line pt-4" @submit.prevent="updateStatus(a)">
              <ErrorAlert :message="message" />
              <label class="field max-w-[260px]">Status baru
                <select v-model="form.status" class="input"><option v-for="s in applicationPipeline" :key="s.value" :value="s.value">{{ s.label }}</option></select>
              </label>
              <label class="field">Catatan untuk kandidat <span class="font-normal text-kea-muted">(mis. jadwal interview)</span><textarea v-model="form.note" rows="2" class="input" /></label>
              <div class="flex gap-2"><button type="submit" class="btn-secondary" :disabled="busy">Simpan status</button><button type="button" class="btn-ghost" @click="openId = null">Batal</button></div>
            </form>
            <button v-else-if="!finalStatuses.includes(a.status)" type="button" class="btn-outline self-start" @click="openUpdate(a)">Perbarui status</button>
          </div>
          <div class="lg:w-[260px] lg:border-l lg:border-kea-line lg:pl-5">
            <ApplicationTimeline :events="a.events" />
          </div>
        </article>
        <EmptyState v-if="applicantsRes && !applicants.length" title="Belum ada pelamar di tahap ini" />
      </section>
    </template>
  </div>
</template>
