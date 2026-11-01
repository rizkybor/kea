<script setup lang="ts">
import type { Curriculum, DiscussionThread, LessonDetail, Submission } from '~/types/api'
import { formatDate, formatDateTime } from '~/utils/format'

definePageMeta({ layout: 'bare', middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const api = useApi()
const enrollmentId = route.params.enrollment as string
const lessonId = computed(() => Number(route.params.lesson))

const { data: curriculum, error: curriculumError } = await useAsyncData(`curriculum-${enrollmentId}`, () => api<{ data: Curriculum }>(`/enrollments/${enrollmentId}/curriculum`))
const { data: lessonRes, status: lessonStatus, error: lessonError } = await useAsyncData(
  () => `lesson-${enrollmentId}-${lessonId.value}`,
  () => api<{ data: LessonDetail }>(`/enrollments/${enrollmentId}/lessons/${lessonId.value}`),
)
const lesson = computed(() => lessonRes.value?.data ?? null)
const enrollment = computed(() => curriculum.value?.data.enrollment ?? null)
const canLearn = computed(() => enrollment.value?.status === 'active' || enrollment.value?.status === 'completed')

const tabs = [{ id: 'materi', label: 'Materi' }, { id: 'tugas', label: 'Tugas' }, { id: 'diskusi', label: 'Diskusi' }] as const
type Tab = typeof tabs[number]['id']
const tab = computed<Tab>(() => (tabs.some(t => t.id === route.query.tab) ? route.query.tab as Tab : 'materi'))
const setTab = (id: Tab) => router.replace({ query: { ...route.query, tab: id === 'materi' ? undefined : id } })

// Simpan lesson terakhir yang dibuka (resume).
watch(lessonId, (id) => { api(`/enrollments/${enrollmentId}/lessons/${id}/progress`, { method: 'PUT', body: {} }).catch(() => {}) }, { immediate: true })

const marking = ref(false)
async function toggleDone() {
  if (!lesson.value || !curriculum.value) return
  marking.value = true
  const completed = !lesson.value.progress.completed
  try {
    const res = await api<{ data: { progress_pct: number; status: string } }>(`/enrollments/${enrollmentId}/lessons/${lessonId.value}/progress`, { method: 'PUT', body: { completed } })
    lesson.value.progress.completed = completed
    curriculum.value.data.enrollment.progress_pct = res.data.progress_pct
    curriculum.value.data.enrollment.status = res.data.status as never
    for (const s of curriculum.value.data.sections) for (const l of s.lessons) if (l.id === lessonId.value) l.completed = completed
    if (completed && lesson.value.next_lesson_id && !lesson.value.assignment) {
      await router.push(`/learn/${enrollmentId}/${lesson.value.next_lesson_id}`)
    }
  } finally {
    marking.value = false
  }
}

// Tugas
const submission = ref<Submission | null>(null)
watch(lesson, (l) => { submission.value = l?.submission ?? null }, { immediate: true })
const linkUrl = ref('')
const note = ref('')
const file = ref<File | null>(null)
const dragging = ref(false)
const submitErrors = ref<Record<string, string>>({})
const submitMessage = ref<string | null>(null)
const submitting = ref(false)
const resubmit = ref(false)

function pickFile(f: File | undefined | null) {
  submitErrors.value.file = ''
  if (!f) return
  if (f.size > 20 * 1024 * 1024) {
    submitErrors.value.file = 'Ukuran file maksimal 20 MB.'
    return
  }
  file.value = f
}

async function submitAssignment() {
  submitErrors.value = {}
  submitMessage.value = null
  if (!linkUrl.value && !file.value) {
    submitErrors.value.link_url = 'Isi link atau unggah file.'
    return
  }
  const body = new FormData()
  if (linkUrl.value) body.append('link_url', linkUrl.value)
  if (file.value) body.append('file', file.value)
  if (note.value) body.append('note', note.value)
  submitting.value = true
  try {
    submission.value = (await api<{ data: Submission }>(`/enrollments/${enrollmentId}/lessons/${lessonId.value}/submissions`, { method: 'POST', body })).data
    resubmit.value = false
    file.value = null
  } catch (e) {
    const err = apiError(e)
    submitErrors.value = Object.fromEntries(Object.entries(err.errors ?? {}).map(([k, v]) => [k, v[0] ?? '']))
    submitMessage.value = Object.keys(submitErrors.value).length ? null : err.message
  } finally {
    submitting.value = false
  }
}

// Diskusi
const threads = ref<DiscussionThread[]>([])
const discussionLoaded = ref<number | null>(null)
async function loadDiscussions() {
  threads.value = (await api<{ data: DiscussionThread[] }>(`/enrollments/${enrollmentId}/lessons/${lessonId.value}/discussions`)).data
  discussionLoaded.value = lessonId.value
}
watch([tab, lessonId], () => { if (tab.value === 'diskusi' && discussionLoaded.value !== lessonId.value) loadDiscussions() }, { immediate: true })
const question = ref('')
const replyTo = ref<number | null>(null)
const reply = ref('')
const posting = ref(false)
async function post(body: string, parentId: number | null = null) {
  if (!body.trim()) return
  posting.value = true
  try {
    await api(`/enrollments/${enrollmentId}/lessons/${lessonId.value}/discussions`, { method: 'POST', body: { body, parent_id: parentId } })
    question.value = ''
    reply.value = ''
    replyTo.value = null
    await loadDiscussions()
  } finally {
    posting.value = false
  }
}

// Live session
const joinError = ref<string | null>(null)
async function joinLive() {
  if (!lesson.value?.live_session) return
  joinError.value = null
  try {
    const res = await api<{ data: { join_url: string } }>(`/enrollments/${enrollmentId}/live-sessions/${lesson.value.live_session.id}/join`)
    window.open(res.data.join_url, '_blank', 'noopener')
  } catch (e) {
    joinError.value = apiError(e).message
  }
}

const lessonNumbers = computed(() => {
  const map = new Map<number, number>()
  let n = 0
  for (const s of curriculum.value?.data.sections ?? []) for (const l of s.lessons) map.set(l.id, ++n)
  return map
})

useSeoMeta({ title: () => lesson.value?.title ?? 'Ruang Kelas', robots: 'noindex' })
</script>

<template>
  <div class="min-h-screen">
    <header class="sticky top-0 z-10 border-b border-kea-line bg-white">
      <div class="flex flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 sm:px-6">
        <NuxtLink to="/dashboard" class="font-semibold">← Dashboard</NuxtLink>
        <span v-if="enrollment" class="min-w-0 flex-1 truncate font-bold">{{ enrollment.program.title }}<template v-if="enrollment.cohort"> · {{ enrollment.cohort.name }}</template></span>
        <span v-if="enrollment" class="flex w-full items-center gap-3 text-sm font-semibold sm:w-56">
          <ProgressBar :value="enrollment.progress_pct" label="Progres kelas" />
          {{ enrollment.progress_pct }}%
        </span>
      </div>
    </header>

    <EmptyState v-if="curriculumError || lessonError" class="m-6" title="Lesson tidak bisa dibuka" body="Kelas ini bukan milikmu, sudah tidak aktif, atau lesson tidak ditemukan.">
      <NuxtLink to="/dashboard" class="btn-primary">Ke dashboard</NuxtLink>
    </EmptyState>

    <div v-else class="flex flex-col lg:flex-row">
      <main class="flex min-w-0 flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <template v-if="lesson">
          <p v-if="enrollment?.status === 'completed'" class="rounded-kea-md bg-kea-olive-50 px-4 py-3 text-sm font-semibold text-kea-olive-900">
            Selamat, kamu sudah lulus program ini! <NuxtLink to="/dashboard/sertifikat">Lihat sertifikat →</NuxtLink>
          </p>

          <div v-if="lesson.type === 'live' && lesson.live_session" class="flex flex-col gap-4 rounded-kea-lg bg-kea-forest p-6 text-white">
            <span class="text-sm font-semibold text-kea-on-forest">Live session · {{ formatDateTime(lesson.live_session.starts_at) }}</span>
            <span class="text-xl font-extrabold">{{ lesson.live_session.title }}</span>
            <div class="flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" :disabled="!lesson.live_session.is_joinable" @click="joinLive">Gabung Zoom</button>
              <a v-if="lesson.live_session.recording_url" :href="lesson.live_session.recording_url" target="_blank" rel="noopener" class="btn border-[1.5px] border-white text-white hover:text-white">Tonton rekaman</a>
              <span v-else-if="!lesson.live_session.is_joinable" class="text-sm text-kea-on-forest">Link aktif 15 menit sebelum mulai.</span>
            </div>
            <span v-if="joinError" class="text-sm text-kea-ember-50">{{ joinError }}</span>
          </div>
          <VideoPlayer v-else :url="lesson.playback.url" :watermark="lesson.playback.watermark" :title="lesson.title" />

          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
              <span class="text-[13px] font-bold tracking-wide text-kea-moss uppercase">{{ lesson.section_title }}</span>
              <h1 class="text-[26px] leading-tight font-extrabold">{{ lesson.title }}</h1>
              <span class="text-sm text-kea-muted">{{ lesson.kind_label }}</span>
            </div>
            <button
              v-if="canLearn"
              type="button"
              class="btn min-h-12"
              :class="lesson.progress.completed ? 'border border-kea-olive bg-kea-olive-50 text-kea-olive-900' : 'border border-kea-forest bg-kea-forest text-white hover:text-white'"
              :aria-pressed="lesson.progress.completed"
              :disabled="marking"
              @click="toggleDone"
            >{{ lesson.progress.completed ? 'Selesai ✓' : 'Tandai selesai' }}</button>
          </div>

          <div role="tablist" aria-label="Konten lesson" class="flex gap-1 border-b border-kea-line">
            <button
              v-for="t in tabs"
              :id="`tab-${t.id}`"
              :key="t.id"
              type="button"
              role="tab"
              :aria-selected="tab === t.id"
              :aria-controls="`panel-${t.id}`"
              class="-mb-px min-h-11 border-b-[3px] px-4 py-2.5 text-[15px] font-semibold"
              :class="tab === t.id ? 'border-kea-ember text-kea-ink' : 'border-transparent text-kea-muted'"
              @click="setTab(t.id)"
            >{{ t.label }}<span v-if="t.id === 'tugas' && lesson.assignment" class="ml-1.5 inline-block size-2 rounded-full bg-kea-ember align-middle" aria-label="ada tugas" /></button>
          </div>

          <section v-if="tab === 'materi'" id="panel-materi" role="tabpanel" aria-labelledby="tab-materi" class="flex flex-col gap-3">
            <h2 class="text-lg font-bold">Ringkasan materi</h2>
            <p class="leading-relaxed whitespace-pre-line text-kea-muted">{{ lesson.summary ?? 'Belum ada ringkasan untuk lesson ini.' }}</p>
            <a v-if="lesson.resource_url" :href="lesson.resource_url" target="_blank" rel="noopener" class="font-bold">Unduh slide / resource</a>
            <div class="mt-4 flex justify-between gap-3 border-t border-kea-line pt-4">
              <NuxtLink v-if="lesson.prev_lesson_id" :to="`/learn/${enrollmentId}/${lesson.prev_lesson_id}`" class="btn-ghost">← Sebelumnya</NuxtLink><span v-else />
              <NuxtLink v-if="lesson.next_lesson_id" :to="`/learn/${enrollmentId}/${lesson.next_lesson_id}`" class="btn-outline">Berikutnya →</NuxtLink>
            </div>
          </section>

          <section v-else-if="tab === 'tugas'" id="panel-tugas" role="tabpanel" aria-labelledby="tab-tugas" class="flex flex-col gap-4">
            <template v-if="lesson.assignment">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-bold">{{ lesson.assignment.title }} <span v-if="lesson.assignment.is_final_project" class="badge-ember ml-1">Final project</span></h2>
                <span v-if="lesson.assignment.due_at" class="text-sm font-semibold text-kea-ember-900">Deadline {{ formatDate(lesson.assignment.due_at, { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' }) }}</span>
              </div>
              <p class="leading-relaxed whitespace-pre-line text-kea-muted">{{ lesson.assignment.instructions }}</p>

              <div v-if="submission && !resubmit" class="flex flex-col gap-2 rounded-xl p-5" :class="submission.status === 'reviewed' ? 'bg-kea-olive-50' : submission.status === 'revision' ? 'bg-kea-ember-50' : 'bg-kea-surface'" role="status">
                <template v-if="submission.status === 'submitted'">
                  <b>Terkirim · menunggu review mentor</b>
                  <span class="text-sm text-kea-muted">Mentor mereview maksimal 72 jam. Kamu akan dapat notifikasi saat nilai keluar.</span>
                </template>
                <template v-else-if="submission.status === 'reviewed'">
                  <b class="text-kea-olive-900">Sudah dinilai · {{ submission.score }}/100</b>
                </template>
                <template v-else>
                  <b class="text-kea-ember-900">Perlu revisi · nilai {{ submission.score }}</b>
                </template>
                <span v-if="submission.feedback" class="text-sm"><b>Feedback mentor:</b> {{ submission.feedback }}</span>
                <span class="text-sm text-kea-muted">
                  <a v-if="submission.link_url" :href="submission.link_url" target="_blank" rel="noopener">{{ submission.link_url }}</a>
                  <template v-if="submission.file_name"> · {{ submission.file_name }}</template>
                  · dikirim {{ formatDate(submission.submitted_at, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                </span>
                <button v-if="submission.status !== 'reviewed' && canLearn" type="button" class="btn-outline mt-1 self-start" @click="resubmit = true">Kirim ulang</button>
              </div>

              <form v-else-if="canLearn" class="flex flex-col gap-4" novalidate @submit.prevent="submitAssignment">
                <ErrorAlert :message="submitMessage" />
                <label class="field">Link repository / file
                  <input v-model="linkUrl" type="url" placeholder="https://github.com/..." class="input" :class="submitErrors.link_url && 'input-error'">
                  <span v-if="submitErrors.link_url" class="error-text">{{ submitErrors.link_url }}</span>
                </label>
                <label
                  class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-dashed px-4 py-7 text-center text-[15px]"
                  :class="dragging ? 'border-kea-forest bg-kea-olive-50' : 'border-kea-field'"
                  @dragover.prevent="dragging = true"
                  @dragleave="dragging = false"
                  @drop.prevent="dragging = false; pickFile($event.dataTransfer?.files[0])"
                >
                  <span v-if="file"><b>{{ file.name }}</b> ({{ (file.size / 1024 / 1024).toFixed(1) }} MB)</span>
                  <span v-else>Tarik file ke sini atau <b class="text-kea-forest">pilih file</b> (maks. 20 MB)</span>
                  <span class="text-xs text-kea-muted">PDF, ZIP, DOC, PPT, gambar, atau .fig</span>
                  <input type="file" class="sr-only" accept=".pdf,.zip,.doc,.docx,.ppt,.pptx,.png,.jpg,.jpeg,.fig" @change="pickFile(($event.target as HTMLInputElement).files?.[0])">
                </label>
                <span v-if="submitErrors.file" class="error-text -mt-2">{{ submitErrors.file }}</span>
                <label class="field">Catatan untuk mentor <span class="font-normal text-kea-muted">(opsional)</span>
                  <textarea v-model="note" rows="2" class="input" />
                </label>
                <div class="flex gap-3">
                  <button type="submit" class="btn-primary" :disabled="submitting">{{ submitting ? 'Mengirim…' : 'Kumpulkan tugas' }}</button>
                  <button v-if="resubmit" type="button" class="btn-ghost" @click="resubmit = false">Batal</button>
                </div>
              </form>
            </template>
            <p v-else class="text-kea-muted">Lesson ini tidak punya tugas.</p>
          </section>

          <section v-else id="panel-diskusi" role="tabpanel" aria-labelledby="tab-diskusi" class="flex flex-col gap-4">
            <form v-if="canLearn" class="flex flex-col gap-3" @submit.prevent="post(question)">
              <label class="field">Tulis pertanyaan
                <textarea v-model="question" rows="3" placeholder="Tanya ke mentor atau teman se-cohort" class="input" />
              </label>
              <button type="submit" class="btn-secondary self-start" :disabled="posting || !question.trim()">Kirim</button>
            </form>
            <div v-for="t in threads" :key="t.id" class="card flex flex-col gap-3 p-4">
              <div class="flex gap-3">
                <span class="flex size-10 flex-none items-center justify-center rounded-full bg-kea-mist font-bold" aria-hidden="true">{{ t.author.charAt(0) }}</span>
                <span class="flex flex-col gap-1">
                  <b>{{ t.author }} <span v-if="t.is_mentor" class="badge-olive ml-1">Mentor</span></b>
                  <span class="text-[15px] whitespace-pre-line">{{ t.body }}</span>
                  <span class="text-xs text-kea-muted">{{ formatDate(t.created_at, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }} · {{ t.replies.length }} balasan</span>
                </span>
              </div>
              <div v-for="r in t.replies" :key="r.id" class="ml-12 flex flex-col gap-1 border-l-2 border-kea-line pl-4">
                <b class="text-sm">{{ r.author }} <span v-if="r.is_mentor" class="badge-olive ml-1">Mentor</span></b>
                <span class="text-[15px] whitespace-pre-line">{{ r.body }}</span>
              </div>
              <form v-if="replyTo === t.id" class="ml-12 flex flex-col gap-2" @submit.prevent="post(reply, t.id)">
                <label class="sr-only" :for="`reply-${t.id}`">Balasan</label>
                <textarea :id="`reply-${t.id}`" v-model="reply" rows="2" class="input" />
                <div class="flex gap-2"><button type="submit" class="btn-secondary" :disabled="posting || !reply.trim()">Balas</button><button type="button" class="btn-ghost" @click="replyTo = null">Batal</button></div>
              </form>
              <button v-else-if="canLearn" type="button" class="ml-12 self-start text-sm font-bold text-kea-forest" @click="replyTo = t.id">Balas</button>
            </div>
            <p v-if="discussionLoaded === lessonId && !threads.length" class="text-kea-muted">Belum ada diskusi di lesson ini. Jadilah yang pertama bertanya.</p>
            <a v-if="enrollment?.cohort?.community_url" :href="enrollment.cohort.community_url" target="_blank" rel="noopener" class="text-sm font-bold">Gabung grup komunitas cohort →</a>
          </section>
        </template>
        <div v-else-if="lessonStatus === 'pending'" class="aspect-video animate-pulse rounded-kea-lg bg-kea-line" />
      </main>

      <aside aria-label="Kurikulum" class="border-kea-line bg-white p-4 sm:p-6 lg:sticky lg:top-[57px] lg:h-[calc(100vh-57px)] lg:w-[360px] lg:flex-none lg:overflow-y-auto lg:border-l">
        <h2 class="mb-3 text-lg font-extrabold">Kurikulum</h2>
        <div v-for="s in curriculum?.data.sections ?? []" :key="s.title" class="mb-3">
          <span class="block px-2 pt-2 pb-1 text-xs font-bold tracking-wide text-kea-moss uppercase">{{ s.title }}</span>
          <LessonItem v-for="l in s.lessons" :key="l.id" :lesson="l" :index="lessonNumbers.get(l.id) ?? 0" :current="l.id === lessonId" :to="`/learn/${enrollmentId}/${l.id}`" />
        </div>
      </aside>
    </div>
  </div>
</template>
