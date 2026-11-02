<script setup lang="ts">
import type { Job } from '~/types/api'
import { employmentTypes, workModes } from '~/utils/format'

const props = defineProps<{ job?: Job }>()
const emit = defineEmits<{ saved: [job: Job] }>()
const api = useApi()
const { data: categories } = await useAsyncData('categories', () => api<{ data: { name: string; slug: string }[] }>('/categories'))

const j = props.job
const form = reactive({
  title: j?.title ?? '',
  category_slug: j?.category?.slug ?? '',
  employment_type: j?.employment_type ?? 'full_time',
  work_mode: j?.work_mode ?? 'hybrid',
  location: j?.location ?? '',
  salary_min: j?.salary_min ?? null as number | null,
  salary_max: j?.salary_max ?? null as number | null,
  show_salary: j?.show_salary ?? true,
  description: j?.description ?? '',
  requirements: [...(j?.requirements ?? [])],
  skills: [...(j?.skills ?? [])],
  alumni_only: j?.alumni_only ?? true,
  status: j?.status ?? 'open',
  closes_at: j?.closes_at?.slice(0, 10) ?? '',
})
const errors = ref<Record<string, string>>({})
const message = ref<string | null>(null)
const saving = ref(false)

async function save() {
  errors.value = {}
  message.value = null
  saving.value = true
  try {
    const body = { ...form, closes_at: form.closes_at || null, location: form.location || null }
    const res = props.job
      ? await api<{ data: Job }>(`/partner/jobs/${props.job.id}`, { method: 'PUT', body })
      : await api<{ data: Job }>('/partner/jobs', { method: 'POST', body })
    emit('saved', res.data)
  } catch (e) {
    const err = apiError(e)
    errors.value = Object.fromEntries(Object.entries(err.errors ?? {}).map(([k, v]) => [k.split('.')[0]!, v[0] ?? '']))
    if (!Object.keys(errors.value).length) message.value = err.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="card flex flex-col gap-5 p-6" novalidate @submit.prevent="save">
    <ErrorAlert :message="message" />
    <div class="grid gap-4 md:grid-cols-2">
      <label class="field md:col-span-2">Judul posisi
        <input v-model="form.title" class="input" :class="errors.title && 'input-error'" placeholder="mis. Junior Backend Engineer">
        <span v-if="errors.title" class="error-text">{{ errors.title }}</span>
      </label>
      <label class="field">Bidang
        <select v-model="form.category_slug" class="input">
          <option value="">—</option>
          <option v-for="c in categories?.data ?? []" :key="c.slug" :value="c.slug">{{ c.name }}</option>
        </select>
      </label>
      <label class="field">Lokasi<input v-model="form.location" class="input" placeholder="mis. Jakarta Selatan"></label>
      <label class="field">Tipe pekerjaan
        <select v-model="form.employment_type" class="input"><option v-for="t in employmentTypes" :key="t.value" :value="t.value">{{ t.label }}</option></select>
      </label>
      <label class="field">Mode kerja
        <select v-model="form.work_mode" class="input"><option v-for="m in workModes" :key="m.value" :value="m.value">{{ m.label }}</option></select>
      </label>
      <label class="field">Gaji minimum /bulan<input v-model.number="form.salary_min" type="number" min="0" step="500000" class="input"></label>
      <label class="field">Gaji maksimum /bulan
        <input v-model.number="form.salary_max" type="number" min="0" step="500000" class="input" :class="errors.salary_max && 'input-error'">
        <span v-if="errors.salary_max" class="error-text">{{ errors.salary_max }}</span>
      </label>
      <label class="flex items-center gap-2 text-[15px] md:col-span-2"><input v-model="form.show_salary" type="checkbox" class="size-[18px] accent-kea-forest"> Tampilkan rentang gaji ke pelamar</label>
      <label class="field md:col-span-2">Deskripsi pekerjaan
        <textarea v-model="form.description" rows="6" class="input" :class="errors.description && 'input-error'" />
        <span v-if="errors.description" class="error-text">{{ errors.description }}</span>
      </label>
      <div class="field md:col-span-2"><label for="req">Kualifikasi <span class="font-normal text-kea-muted">(Enter per poin)</span></label><TagInput id="req" v-model="form.requirements" placeholder="mis. Punya portofolio minimal 2 proyek" /></div>
      <div class="field md:col-span-2"><label for="skills">Skill yang dicari</label><TagInput id="skills" v-model="form.skills" placeholder="mis. Laravel" /></div>
      <label class="field">Batas lamaran<input v-model="form.closes_at" type="date" class="input" :class="errors.closes_at && 'input-error'"><span v-if="errors.closes_at" class="error-text">{{ errors.closes_at }}</span></label>
      <label class="field">Status
        <select v-model="form.status" class="input"><option value="open">Dibuka (tayang)</option><option value="draft">Draft</option><option value="closed">Ditutup</option></select>
      </label>
      <label class="flex items-start gap-2 text-[15px] md:col-span-2"><input v-model="form.alumni_only" type="checkbox" class="mt-1 size-[18px] accent-kea-forest"> <span>Khusus alumni KEA <span class="block text-sm text-kea-muted">Hanya learner yang sudah lulus program dan tervalidasi yang bisa melamar.</span></span></label>
    </div>
    <button type="submit" class="btn-primary self-start" :disabled="saving">{{ saving ? 'Menyimpan…' : job ? 'Simpan perubahan' : 'Pasang lowongan' }}</button>
  </form>
</template>
