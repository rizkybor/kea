<script setup lang="ts">
import type { CareerResult, Dashboard } from '~/types/api'
import { formatDate, formatDateTime, formatTime } from '~/utils/format'

definePageMeta({ layout: 'learner', middleware: 'auth' })

const api = useApi()
const auth = useAuthStore()
const { data, status, error, refresh } = await useAsyncData('dashboard', () => api<{ data: Dashboard }>('/dashboard'))
const { data: path } = await useAsyncData('career-path', () => api<{ data: CareerResult | null }>('/me/career-assessment').catch(() => ({ data: null })))
const d = computed(() => data.value?.data)

const firstName = computed(() => auth.user?.name.split(' ')[0] ?? '')
const live = computed(() => d.value?.next_live_session ?? null)

// Tombol Zoom aktif otomatis saat H-15 menit tanpa reload.
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | undefined
onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 30_000) })
onBeforeUnmount(() => clearInterval(timer))
const joinable = computed(() => live.value ? now.value >= new Date(live.value.join_opens_at).getTime() && now.value <= new Date(live.value.ends_at).getTime() : false)

const joinError = ref<string | null>(null)
async function joinLive() {
  if (!live.value?.enrollment_id) return
  joinError.value = null
  try {
    const res = await api<{ data: { join_url: string } }>(`/enrollments/${live.value.enrollment_id}/live-sessions/${live.value.id}/join`)
    window.open(res.data.join_url, '_blank', 'noopener')
  } catch (e) {
    joinError.value = apiError(e).message
  }
}

useSeoMeta({ title: 'Dashboard', robots: 'noindex' })
</script>

<template>
  <div class="flex max-w-[1100px] flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-[28px] font-extrabold md:text-[32px]">Halo, {{ firstName }}</h1>
        <p class="text-[15px] text-kea-muted">Lanjutkan progres belajarmu minggu ini.</p>
      </div>
      <span class="flex size-11 items-center justify-center rounded-full bg-kea-forest font-bold text-white" aria-hidden="true">{{ firstName.charAt(0) }}</span>
    </div>

    <p v-if="auth.user && !auth.user.email_verified" class="rounded-kea-md bg-kea-ember-50 px-4 py-3 text-sm font-medium text-kea-ember-900">
      Email kamu belum diverifikasi. Cek inbox untuk link verifikasi sebelum checkout.
    </p>

    <ErrorAlert v-if="error" message="Gagal memuat dashboard." />
    <div v-if="status === 'pending' && !d" class="flex flex-col gap-4">
      <div v-for="i in 3" :key="i" class="h-28 animate-pulse rounded-kea-lg bg-kea-line" />
    </div>

    <template v-if="d">
      <section v-if="live" id="jadwal" aria-label="Sesi live berikutnya" class="flex scroll-mt-4 flex-wrap items-center justify-between gap-5 rounded-kea-lg bg-kea-forest p-6 text-white">
        <div class="flex flex-col gap-1.5">
          <span class="text-sm font-semibold text-kea-on-forest">Live berikutnya · {{ live.program_title }}</span>
          <span class="text-xl font-extrabold">{{ live.title }}</span>
          <span class="text-[15px] text-kea-on-forest">{{ formatDateTime(live.starts_at) }} – {{ formatTime(live.ends_at) }}<template v-if="live.mentor_name"> · Mentor {{ live.mentor_name }}</template></span>
        </div>
        <div class="flex flex-col items-start gap-2 sm:items-end">
          <span class="text-[13px] text-kea-on-forest">{{ joinable ? 'Link sudah aktif' : 'Link aktif 15 menit sebelum mulai' }}</span>
          <button type="button" class="btn-primary" :disabled="!joinable" @click="joinLive">Gabung Zoom</button>
          <span v-if="joinError" class="text-[13px] text-kea-ember-50">{{ joinError }}</span>
        </div>
      </section>
      <section v-else id="jadwal" class="card scroll-mt-4 p-5 text-[15px] text-kea-muted">Belum ada sesi live terjadwal.</section>

      <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <section aria-label="Kelas saya" class="flex flex-col gap-4">
          <h2 class="text-xl font-extrabold">Kelas saya</h2>
          <EnrollmentCard v-for="e in d.enrollments" :key="e.id" :enrollment="e" />
          <EmptyState v-if="!d.enrollments.length" title="Belum ada kelas" body="Daftar bootcamp atau video course untuk mulai belajar.">
            <NuxtLink to="/program" class="btn-primary">Lihat program</NuxtLink>
          </EmptyState>
        </section>

        <section id="tugas" aria-label="Tugas" class="flex scroll-mt-4 flex-col gap-3">
          <h2 class="text-xl font-extrabold">Tugas</h2>
          <NuxtLink
            v-for="t in d.tasks"
            :key="`${t.enrollment_id}-${t.lesson_id}`"
            :to="`/learn/${t.enrollment_id}/${t.lesson_id}?tab=tugas`"
            class="card flex items-center justify-between gap-3 p-4 text-kea-ink no-underline hover:border-kea-forest hover:text-kea-ink"
          >
            <span class="flex flex-col">
              <b class="text-[15px]">{{ t.title }}</b>
              <span class="text-[13px] text-kea-muted">
                <template v-if="t.status === 'reviewed'">Nilai {{ t.score }}</template>
                <template v-else-if="t.status === 'submitted'">Dikumpulkan · menunggu review</template>
                <template v-else-if="t.due_at">Deadline {{ formatDate(t.due_at, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}</template>
                <template v-else>{{ t.program_title }}</template>
              </span>
            </span>
            <StatusBadge :status="t.status" />
          </NuxtLink>
          <p v-if="!d.tasks.length" class="card p-4 text-sm text-kea-muted">Belum ada tugas.</p>
        </section>
      </div>

      <section aria-label="Jalur karier" class="flex flex-wrap items-center justify-between gap-4 rounded-kea-lg border border-kea-line bg-white p-6">
        <div v-if="path?.data?.primary" class="flex flex-col gap-1">
          <span class="text-sm font-semibold text-kea-moss">Jalur belajarmu · {{ path.data.target_label }}</span>
          <b class="text-lg">Rekomendasi: {{ path.data.primary.program.title }} <span class="text-sm font-semibold text-kea-moss">({{ path.data.primary.match_pct }}% cocok)</span></b>
          <span class="text-[15px] text-kea-muted">{{ path.data.primary.reasons.slice(0, 2).join(' · ') }}</span>
        </div>
        <div v-else class="flex flex-col gap-1">
          <b class="text-lg">Belum yakin program berikutnya?</b>
          <span class="text-[15px] text-kea-muted">Isi kuesioner minat & target karier (±1 menit) untuk rekomendasi jalur belajar.</span>
        </div>
        <NuxtLink to="/jalur-karier" class="btn-outline">{{ path?.data ? 'Lihat jalur' : 'Mulai kuesioner' }}</NuxtLink>
      </section>

      <section v-if="!d.profile_complete" aria-label="Karier" class="flex flex-wrap items-center justify-between gap-4 rounded-kea-lg bg-kea-olive-50 p-6">
        <div class="flex flex-col gap-1">
          <b class="text-lg">Lengkapi profil karier</b>
          <span class="text-[15px] text-kea-olive-900">Upload CV & portofolio supaya siap masuk talent pool mitra.</span>
        </div>
        <NuxtLink to="/dashboard/profil" class="btn-secondary">Lengkapi profil</NuxtLink>
      </section>
    </template>
    <button v-if="error" type="button" class="btn-outline self-start" @click="refresh()">Coba lagi</button>
  </div>
</template>
