<script setup lang="ts">
import type { CareerQuestion, CareerResult } from '~/types/api'

const api = useApi()
const auth = useAuthStore()
const { public: { siteUrl } } = useRuntimeConfig()

const { data: questionsRes } = await useAsyncData('career-questions', () => api<{ data: CareerQuestion[] }>('/career/questions'))
const questions = computed(() => questionsRes.value?.data ?? [])

const step = ref(0)
const answers = reactive<Record<string, string | string[]>>({})
const result = ref<CareerResult | null>(null)
const previous = ref<CareerResult | null>(null)
const submitting = ref(false)
const errorMessage = ref<string | null>(null)

const current = computed(() => questions.value[step.value])
const progress = computed(() => Math.round((step.value / Math.max(1, questions.value.length)) * 100))
const answered = computed(() => {
  const q = current.value
  if (!q) return false
  const a = answers[q.id]
  return q.type === 'multi' ? Array.isArray(a) && a.length > 0 : typeof a === 'string'
})

function choose(value: string) {
  const q = current.value!
  if (q.type === 'single') {
    answers[q.id] = value
    setTimeout(next, 180)
    return
  }
  const list = (answers[q.id] as string[] | undefined) ?? []
  answers[q.id] = list.includes(value) ? list.filter(v => v !== value) : list.length < (q.max ?? 3) ? [...list, value] : list
}
const isChosen = (value: string) => {
  const a = answers[current.value!.id]
  return Array.isArray(a) ? a.includes(value) : a === value
}

async function next() {
  if (!answered.value) return
  if (step.value < questions.value.length - 1) {
    step.value++
    return
  }
  submitting.value = true
  errorMessage.value = null
  try {
    result.value = (await api<{ data: CareerResult }>('/career/assessments', { method: 'POST', body: { answers } })).data
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (e) {
    errorMessage.value = apiError(e).message
  } finally {
    submitting.value = false
  }
}

function restart() {
  result.value = null
  step.value = 0
  for (const k of Object.keys(answers)) delete answers[k]
}

onMounted(async () => {
  await auth.fetchUser()
  if (auth.isLoggedIn) previous.value = (await api<{ data: CareerResult | null }>('/me/career-assessment').catch(() => null))?.data ?? null
})

useSeoMeta({
  title: 'Temukan jalur belajar & kariermu',
  description: 'Jawab 7 pertanyaan singkat tentang minat dan target karier. KEA merekomendasikan jalur belajar yang paling cocok (Mission Based Learning).',
})
useHead({ link: [{ rel: 'canonical', href: `${siteUrl}/jalur-karier` }] })
</script>

<template>
  <main class="min-h-[70vh] bg-kea-surface">
    <div v-if="!result" class="mx-auto flex max-w-[720px] flex-col gap-6 px-6 py-12">
      <div class="flex flex-col gap-2">
        <span class="text-sm font-bold tracking-wide text-kea-moss uppercase">Mission Based Learning</span>
        <h1 class="text-[30px] leading-tight font-extrabold md:text-[38px]">Temukan jalur belajar menuju karier impianmu</h1>
        <p class="text-[17px] text-kea-muted">7 pertanyaan, ±1 menit. Rekomendasi disusun dari minat, target karier, waktu, dan budget-mu.</p>
      </div>

      <div v-if="previous && step === 0 && !Object.keys(answers).length" class="flex flex-wrap items-center justify-between gap-3 rounded-kea-lg bg-kea-olive-50 p-4 text-sm">
        <span>Kamu pernah mengisi kuesioner ini (target: <b>{{ previous.target_label }}</b>).</span>
        <button type="button" class="font-bold text-kea-forest underline" @click="result = previous">Lihat hasil terakhir</button>
      </div>

      <div v-if="current" class="card flex flex-col gap-5 p-6 md:p-8">
        <div class="flex items-center gap-3 text-sm font-semibold text-kea-muted">
          <span>Pertanyaan {{ step + 1 }} dari {{ questions.length }}</span>
          <ProgressBar :value="progress" label="Progres kuesioner" thin class="flex-1" />
        </div>
        <fieldset class="flex flex-col gap-3">
          <legend class="mb-1 text-[22px] leading-snug font-extrabold">{{ current.title }}</legend>
          <p v-if="current.hint" class="-mt-1 text-sm text-kea-muted">{{ current.hint }}</p>
          <button
            v-for="o in current.options"
            :key="o.value"
            type="button"
            :aria-pressed="isChosen(o.value)"
            class="flex min-h-14 w-full items-center justify-between gap-3 rounded-xl px-4 py-3 text-left"
            :class="isChosen(o.value) ? 'border-2 border-kea-forest bg-kea-olive-50' : 'border border-kea-field bg-white hover:border-kea-forest'"
            @click="choose(o.value)"
          >
            <span class="flex flex-col"><b class="text-[15px]">{{ o.label }}</b><span v-if="o.hint" class="text-[13px] text-kea-muted">{{ o.hint }}</span></span>
            <span v-if="current.type === 'multi'" class="flex size-5 flex-none items-center justify-center rounded border-2 text-xs" :class="isChosen(o.value) ? 'border-kea-forest bg-kea-forest text-white' : 'border-kea-field'" aria-hidden="true">{{ isChosen(o.value) ? '✓' : '' }}</span>
          </button>
        </fieldset>
        <ErrorAlert :message="errorMessage" />
        <div class="flex justify-between gap-3">
          <button type="button" class="btn-ghost" :disabled="step === 0" @click="step--">← Kembali</button>
          <button v-if="current.type === 'multi' || step === questions.length - 1" type="button" class="btn-primary" :disabled="!answered || submitting" @click="next">
            {{ submitting ? 'Menyusun jalur…' : step === questions.length - 1 ? 'Lihat rekomendasi' : 'Lanjut' }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="container-kea flex flex-col gap-8 py-12">
      <div class="flex flex-col gap-2">
        <span class="text-sm font-bold tracking-wide text-kea-moss uppercase">Jalur belajarmu</span>
        <h1 class="text-[30px] leading-tight font-extrabold md:text-[38px]">Menuju {{ result.target_label }}</h1>
        <p class="text-[15px] text-kea-muted">
          <template v-if="result.saved || result.created_at">Hasil ini tersimpan di akunmu.</template>
          <template v-else-if="!auth.isLoggedIn"><NuxtLink :to="{ path: '/masuk', query: { redirect: '/jalur-karier' } }" class="font-bold">Masuk</NuxtLink> lalu isi ulang supaya hasilnya tersimpan di dashboard.</template>
        </p>
      </div>

      <EmptyState v-if="!result.primary" title="Belum ada program yang pas saat ini" body="Cohort untuk minatmu sedang penuh atau belum dibuka. Coba ubah preferensi jadwal/budget, atau lihat semua program.">
        <div class="flex gap-3"><button type="button" class="btn-outline" @click="restart">Ulangi kuesioner</button><NuxtLink to="/program" class="btn-primary">Lihat program</NuxtLink></div>
      </EmptyState>

      <template v-else>
        <ol class="grid gap-5 lg:grid-cols-3" aria-label="Langkah jalur belajar">
          <li v-if="result.warm_up" class="flex flex-col gap-3">
            <span class="flex items-center gap-2 font-bold"><span class="flex size-8 items-center justify-center rounded-lg bg-kea-forest text-white">1</span>Pemanasan</span>
            <RecommendationCard :item="result.warm_up" warm-up />
          </li>
          <li class="flex flex-col gap-3">
            <span class="flex items-center gap-2 font-bold"><span class="flex size-8 items-center justify-center rounded-lg bg-kea-ember text-kea-ink">{{ result.warm_up ? 2 : 1 }}</span>Program utama</span>
            <RecommendationCard :item="result.primary" highlight />
          </li>
          <li class="flex flex-col gap-3">
            <span class="flex items-center gap-2 font-bold"><span class="flex size-8 items-center justify-center rounded-lg bg-kea-forest text-white">{{ result.warm_up ? 3 : 2 }}</span>Masuk dunia kerja</span>
            <div class="card flex flex-1 flex-col gap-3 p-6">
              <h3 class="text-lg font-bold">Portofolio → talent pool → lowongan mitra</h3>
              <p class="text-sm text-kea-muted">Setelah lulus, profil & sertifikatmu bisa dilihat hiring partner, dan kamu bisa melamar lowongan khusus alumni.</p>
              <NuxtLink to="/lowongan" class="btn-outline mt-auto">Intip lowongan mitra</NuxtLink>
            </div>
          </li>
        </ol>

        <section v-if="result.alternatives.length" class="flex flex-col gap-4">
          <h2 class="text-xl font-extrabold">Alternatif lain yang cocok</h2>
          <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <RecommendationCard v-for="a in result.alternatives" :key="a.program.slug" :item="a" />
          </div>
        </section>
        <button type="button" class="btn-ghost self-start" @click="restart">↺ Ulangi kuesioner</button>
      </template>
    </div>
  </main>
</template>
