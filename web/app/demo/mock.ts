/**
 * Mock API untuk prototype statis (mode demo, `KEA_DEMO=1`).
 *
 * Data awal = snapshot API Laravel asli (`scripts/snapshot-demo.mjs`), lalu semua mutasi (checkout, progres, tugas,
 * grading, lamaran, pipeline mitra, kuesioner MBL) dijalankan di browser dengan aturan yang meniru backend.
 * State disimpan di localStorage supaya tahan reload; `resetDemo()` mengembalikan ke kondisi awal.
 * Semua tanggal digeser relatif ke hari ini, sehingga jadwal cohort/live selalu "akan datang" saat dipresentasikan.
 */
import fixtures from './fixtures.json'

/* eslint-disable @typescript-eslint/no-explicit-any */
type Any = any

const STORAGE_KEY = 'kea-demo-state-v1'
const NEW_USER_TEMPLATE = 'learner@kea.test'

// Dari api/database/seeders/CatalogSeeder.php (tidak ada di respons publik API).
const CAREER_TRACKS: Record<string, string[]> = {
  'fullstack-web-development': ['software_engineer', 'frontend_developer'],
  'ui-ux-design-product': ['ui_ux_designer'],
  'digital-marketing': ['digital_marketer'],
  'data-analytics': ['data_analyst'],
  'human-resources-management': ['hr_professional'],
  'figma-auto-layout-design-system': ['ui_ux_designer', 'frontend_developer'],
  'laravel-untuk-pemula': ['software_engineer'],
  'seo-fundamental': ['digital_marketer'],
  'webinar-memulai-karier-di-tech': ['software_engineer', 'frontend_developer', 'ui_ux_designer', 'data_analyst', 'digital_marketer'],
}
const PROGRAM_CODES: Record<string, string> = {
  'fullstack-web-development': 'FSWD', 'ui-ux-design-product': 'UIUX', 'digital-marketing': 'DM', 'data-analytics': 'DA',
  'human-resources-management': 'HRM', 'figma-auto-layout-design-system': 'FIGMA', 'laravel-untuk-pemula': 'LRV',
  'seo-fundamental': 'SEO', 'webinar-memulai-karier-di-tech': 'WEB',
}
const TRACK_LABELS: Record<string, string> = {
  software_engineer: 'Backend / Fullstack Developer', frontend_developer: 'Frontend Developer', ui_ux_designer: 'UI/UX Designer',
  digital_marketer: 'Digital Marketer', data_analyst: 'Data Analyst', hr_professional: 'HR Professional',
}
const INTEREST_TRACKS: Record<string, string[]> = {
  build: ['software_engineer', 'frontend_developer'], design: ['ui_ux_designer', 'frontend_developer'],
  data: ['data_analyst'], promote: ['digital_marketer'], people: ['hr_professional'],
}
const STATUS_LABELS: Record<string, string> = {
  applied: 'Dikirim', reviewing: 'Ditinjau', interview: 'Interview', offered: 'Penawaran', hired: 'Diterima', rejected: 'Tidak lolos', withdrawn: 'Dibatalkan',
}
const FINAL = ['hired', 'rejected', 'withdrawn']
const SAMPLE_CERT_PDF = 'demo/sertifikat-contoh.pdf'
const SAMPLE_CV_PDF = 'demo/cv-contoh.pdf'

interface DemoState {
  current: string | null
  db: Any
  seq: number
}

let state: DemoState | null = null

/* ------------------------------------------------------------------ state */

const ISO = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/

/** Geser semua timestamp ISO sebesar `delta` ms, dengan offset zona waktu aslinya dipertahankan. */
function shiftDates(value: Any, delta: number): Any {
  if (typeof value === 'string' && ISO.test(value)) {
    const offset = value.match(/([+-]\d{2}:\d{2}|Z)$/)?.[1] ?? 'Z'
    const shifted = new Date(new Date(value).getTime() + delta)
    if (offset === 'Z' || offset === '+00:00') return shifted.toISOString().replace('.000Z', '+00:00')
    const sign = offset[0] === '-' ? -1 : 1
    const [h, m] = offset.slice(1).split(':').map(Number)
    const local = new Date(shifted.getTime() + sign * (h! * 60 + m!) * 60_000)
    return local.toISOString().slice(0, 19) + offset
  }
  if (Array.isArray(value)) return value.map(v => shiftDates(v, delta))
  if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).map(([k, v]) => [k, shiftDates(v, delta)]))
  return value
}

function initialDb(): Any {
  const fx = fixtures as Any
  // Geser per hari penuh supaya jam tayang (mis. 19.00 WIB) tetap sama.
  const days = Math.round((Date.now() - fx.snapshot_at) / 86_400_000)
  const db = shiftDates(structuredClone({ public: fx.public, users: fx.users }), days * 86_400_000)

  // File statis pengganti endpoint unduhan.
  for (const c of Object.values(db.public.certificates) as Any[]) c.data.pdf_url = SAMPLE_CERT_PDF
  for (const u of Object.values(db.users) as Any[]) {
    for (const c of u.certificates?.data ?? []) c.pdf_url = SAMPLE_CERT_PDF
    for (const list of Object.values(u.applicants ?? {}) as Any[]) for (const a of list.data) a.cv_url = SAMPLE_CV_PDF
  }
  db.mentorQueue = db.users['mentor@kea.test']?.mentorSubmissions ?? { submitted: { data: [] }, revision: { data: [] }, reviewed: { data: [] } }
  return db
}

function load(): DemoState {
  if (state) return state
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved) state = JSON.parse(saved)
  } catch { /* storage tidak tersedia: pakai state di memori */ }
  state ??= { current: null, db: initialDb(), seq: 5000 }
  return state
}

function save() {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
  } catch { /* abaikan */ }
}

export function resetDemo() {
  state = { current: null, db: initialDb(), seq: 5000 }
  save()
}

export function demoLoginAs(email: string | null) {
  load().current = email
  save()
}

export function demoCurrentEmail() {
  return load().current
}

/* ---------------------------------------------------------------- helpers */

class DemoError {
  constructor(public statusCode: number, public data: { message: string; code?: string; errors?: Record<string, string[]> }) {}
}
const fail = (status: number, message: string, code?: string, errors?: Record<string, string[]>): never => {
  throw new DemoError(status, { message, code, errors })
}
const invalid = (field: string, message: string): never => fail(422, message, undefined, { [field]: [message] })
const nowIso = () => new Date().toISOString().replace(/\.\d{3}Z$/, '+00:00')
const nextId = () => ++load().seq
const db = () => load().db
const me = (): Any => {
  const email = load().current
  if (!email || !db().users[email]) fail(401, 'Unauthenticated.')
  return db().users[email!]
}
const roles = (): string[] => me().me.data.roles

function paginate(items: Any[], page = 1, perPage = 9) {
  const last = Math.max(1, Math.ceil(items.length / perPage))
  return {
    data: items.slice((page - 1) * perPage, page * perPage),
    meta: { current_page: page, last_page: last, total: items.length, per_page: perPage },
    links: {},
  }
}

function programDetail(slug: string): Any {
  return db().public.programDetails[slug]?.data
}

function allUsers(): [string, Any][] {
  return Object.entries(db().users)
}

/* ---------------------------------------------------------------- catalog */

function listPrograms(q: Any) {
  let items: Any[] = db().public.programs.data.map((p: Any) => ({ ...p, next_cohort: nextCohort(p.slug) }))
  if (q.type) items = items.filter(p => p.type === q.type)
  const cats = ([] as string[]).concat(q['category[]'] ?? q.category ?? [])
  if (cats.length) items = items.filter(p => cats.includes(p.category?.slug))
  if (q.schedule) items = items.filter(p => (programDetail(p.slug)?.cohorts ?? []).some((c: Any) => c.is_available && c.schedule_type === q.schedule))
  if (q.q) {
    const term = String(q.q).toLowerCase()
    items = items.filter(p => p.title.toLowerCase().includes(term) || p.summary.toLowerCase().includes(term))
  }
  if (q.sort === 'price') items.sort((a, b) => a.selling_price - b.selling_price)
  else if (q.sort === 'popular') items.sort((a, b) => b.rating_count - a.rating_count)
  else items.sort((a, b) => (a.next_cohort?.starts_at ?? '9999').localeCompare(b.next_cohort?.starts_at ?? '9999'))
  return paginate(items, Number(q.page) || 1, Number(q.per_page) || 9)
}

function nextCohort(slug: string) {
  const upcoming = (programDetail(slug)?.cohorts ?? []).filter((c: Any) => new Date(c.starts_at) > new Date())
  return upcoming[0] ?? null
}

/* --------------------------------------------------------------- commerce */

const VOUCHERS: Record<string, (subtotal: number) => number> = {
  KEAHEMAT: s => Math.min(Math.floor(s * 0.1), 500_000),
  KEA250K: s => (s >= 2_000_000 ? 250_000 : fail(422, 'Total belanja belum memenuhi minimal transaksi voucher.', 'voucher_invalid', { code: ['Total belanja belum memenuhi minimal transaksi voucher.'] })),
}

function findOrder(uuid: string): Any {
  const order = (me().orders.data as Any[]).find(o => o.uuid === uuid)
  return order ?? fail(404, 'Order tidak ditemukan.')
}

function createOrder(body: Any) {
  const user = me()
  const detail = programDetail(body.program) ?? fail(404, 'Program tidak tersedia.', 'program_unavailable')
  const needsCohort = detail.type !== 'video_course'
  const cohort = body.cohort_id ? detail.cohorts.find((c: Any) => c.id === Number(body.cohort_id)) : null
  if (needsCohort && !cohort) invalid('cohort_id', 'Cohort wajib dipilih.')
  if (user.enrollments.data.some((e: Any) => e.program.slug === detail.slug && (e.cohort?.id ?? null) === (cohort?.id ?? null) && e.status !== 'refunded')) {
    fail(409, 'Kamu sudah terdaftar di program ini.', 'already_enrolled')
  }
  const pending = user.orders.data.find((o: Any) => o.status === 'pending' && o.items[0]?.program_slug === detail.slug && new Date(o.reserved_until) > new Date())
  if (pending) return { data: pending }
  if (cohort && !cohort.is_available) fail(409, 'Kuota cohort sudah penuh.', 'cohort_full')
  if (cohort) {
    cohort.seats_left -= 1
    cohort.is_available = cohort.seats_left > 0
  }
  const price = detail.selling_price
  const order = {
    uuid: crypto.randomUUID(),
    invoice_number: `INV/${new Date().toISOString().slice(0, 10).replace(/-/g, '')}/${Math.random().toString(36).slice(2, 10).toUpperCase()}`,
    status: 'pending', subtotal: price, discount: 0, total: price, voucher_code: null,
    customer_name: user.me.data.name, customer_phone: user.me.data.phone, payment_method: null, payment_instruction: null,
    reserved_until: new Date(Date.now() + 30 * 60_000).toISOString(), paid_at: null, created_at: nowIso(),
    items: [{
      program_title: detail.title, program_slug: detail.slug, program_type: detail.type, program_type_label: detail.type_label,
      thumbnail_url: detail.thumbnail_url, cohort_name: cohort?.name ?? null, cohort_starts_at: cohort?.starts_at ?? null,
      cohort_id: cohort?.id ?? null, price,
    }],
    simulator: true,
  }
  user.orders.data.unshift(order)
  return { data: order }
}

function applyVoucher(order: Any, code: string) {
  if (order.payment_instruction) fail(409, 'Voucher tidak bisa diubah setelah instruksi bayar dibuat.', 'order_locked')
  const key = String(code ?? '').trim().toUpperCase()
  if (key === 'EXPIRED') invalid('code', 'Voucher sudah tidak berlaku.')
  const rule = VOUCHERS[key] ?? invalid('code', 'Kode voucher tidak ditemukan.')
  const discount = Math.min(rule!(order.subtotal), order.subtotal)
  Object.assign(order, { voucher_code: key, discount, total: order.subtotal - discount })
  return { data: order }
}

function pay(order: Any, body: Any) {
  if (order.status !== 'pending') fail(409, 'Order sudah tidak bisa dibayar. Silakan buat order baru.', 'order_not_payable')
  if (!body.customer_name) invalid('customer_name', 'Nama lengkap wajib diisi.')
  if (!/^[0-9+\-\s]{8,20}$/.test(body.customer_phone ?? '')) invalid('customer_phone', 'Format nomor WhatsApp tidak valid.')
  Object.assign(order, { customer_name: body.customer_name, customer_phone: body.customer_phone, payment_method: body.method })
  if (order.total === 0) return markPaid(order)
  if (!order.payment_instruction) {
    const labels: Record<string, string> = { va: 'Virtual Account', qris: 'QRIS', ewallet: 'E-Wallet', cc: 'Kartu Kredit / Cicilan' }
    const channels: Record<string, string> = { va: 'BCA Virtual Account (simulasi)', qris: 'QRIS (simulasi)', ewallet: 'GoPay (simulasi)', cc: 'Kartu kredit (simulasi)' }
    order.payment_instruction = {
      method: body.method, method_label: labels[body.method], channel: channels[body.method],
      va_number: body.method === 'va' ? '8808' + String(Math.floor(Math.random() * 1e10)).padStart(10, '0') : null,
      qr_string: ['qris', 'ewallet'].includes(body.method) ? `KEA-SIMULATOR|${order.uuid}|${order.total}` : null,
      redirect_url: null,
      expires_at: new Date(Date.now() + 30 * 60_000).toISOString(),
    }
  }
  return { data: order }
}

function markPaid(order: Any) {
  Object.assign(order, { status: 'paid', paid_at: nowIso(), reserved_until: null })
  enroll(me(), order.items[0])
  return { data: order }
}

/** Buat enrollment + kurikulum + detail lesson dari silabus publik program (meniru ActivateEnrollment). */
function enroll(user: Any, item: Any) {
  const detail = programDetail(item.program_slug)
  const id = nextId()
  const cohort = item.cohort_id ? detail.cohorts.find((c: Any) => c.id === item.cohort_id) : null
  const enrollment = {
    id, status: 'active', progress_pct: 0, resume_lesson_id: null, completed_at: null,
    program: { slug: detail.slug, title: detail.title, type: detail.type, type_label: detail.type_label, thumbnail_url: detail.thumbnail_url },
    cohort: cohort ? { id: cohort.id, name: cohort.name, starts_at: cohort.starts_at, community_url: 'https://chat.whatsapp.com/kea-demo' } : null,
    attendance: cohort ? { attended: 0, held: 0 } : null, average_score: null, certificate_code: null,
  }
  const ids: number[] = []
  const sections = detail.sections.map((s: Any) => ({
    title: s.title,
    lessons: s.lessons.map((l: Any) => {
      const lessonId = nextId()
      ids.push(lessonId)
      const assignment = l.kind_label.match(/(Tugas [^·]*|Final Project[^·]*)$/)?.[1]?.trim() ?? (l.type === 'assignment' ? l.title : null)
      return { id: lessonId, title: l.title, type: l.type, kind_label: l.kind_label, completed: false, has_assignment: !!assignment, submission_status: null, _assignment: assignment, _section: s.title }
    }),
  }))
  const flat = sections.flatMap((s: Any) => s.lessons)
  flat.forEach((l: Any, i: number) => {
    user.lessons[`${id}/${l.id}`] = {
      data: {
        id: l.id, title: l.title, section_title: l._section, type: l.type, kind_label: l.kind_label, duration_minutes: null,
        summary: `Ringkasan materi ${l.title}: poin penting, contoh kode/kasus, dan link resource tambahan.`, resource_url: null,
        playback: { url: null, expires_at: null, watermark: user.me.data.email },
        progress: { completed: false, last_position_sec: 0 },
        assignment: l._assignment ? {
          title: l._assignment,
          instructions: `Kerjakan ${l._assignment} sesuai brief. Kumpulkan link repository/Figma atau file.`,
          due_at: cohort ? new Date(new Date(cohort.starts_at).getTime() + (i + 1) * 7 * 86_400_000).toISOString() : null,
          is_final_project: /final/i.test(l._assignment),
        } : null,
        submission: null,
        live_session: null,
        prev_lesson_id: flat[i - 1]?.id ?? null,
        next_lesson_id: flat[i + 1]?.id ?? null,
      },
    }
    user.discussions[`${id}/${l.id}`] = { data: [] }
    delete l._assignment
    delete l._section
  })
  user.enrollments.data.unshift(enrollment)
  user.curriculum[id] = { data: { enrollment, sections, resume_lesson_id: ids[0] ?? null } }
}

/* --------------------------------------------------------------- learning */

function enrollmentOf(user: Any, id: number | string): Any {
  return user.enrollments.data.find((e: Any) => e.id === Number(id)) ?? fail(403, 'This action is unauthorized.')
}

function lessonOf(user: Any, enrollmentId: string, lessonId: string): Any {
  return user.lessons[`${enrollmentId}/${lessonId}`]?.data ?? fail(404, 'Lesson tidak ditemukan.')
}

/** Sinkronkan progres & cek syarat lulus (progres 100%, tugas wajib ≥ 70) seperti EvaluateCompletion. */
function recalculate(user: Any, enrollmentId: number) {
  const e = enrollmentOf(user, enrollmentId)
  const cur = user.curriculum[enrollmentId].data
  const lessons = cur.sections.flatMap((s: Any) => s.lessons)
  const done = lessons.filter((l: Any) => l.completed).length
  e.progress_pct = lessons.length ? Math.floor((done * 100) / lessons.length) : 0
  cur.enrollment = e

  const details = lessons.map((l: Any) => user.lessons[`${enrollmentId}/${l.id}`]?.data).filter(Boolean)
  const scores = details.map((d: Any) => d.submission).filter((s: Any) => s?.status === 'reviewed').map((s: Any) => s.score)
  e.average_score = scores.length ? Math.round(scores.reduce((a: number, b: number) => a + b, 0) / scores.length) : null

  const assignmentsPassed = details.filter((d: Any) => d.assignment).every((d: Any) => d.submission?.status === 'reviewed' && d.submission.score >= 70)
  if (e.status === 'active' && e.progress_pct === 100 && assignmentsPassed) {
    const code = `KEA-${PROGRAM_CODES[e.program.slug] ?? 'KEA'}-${new Date().getFullYear()}-${String(e.id).padStart(6, '0')}`
    Object.assign(e, { status: 'completed', completed_at: nowIso(), certificate_code: code })
    const cert = {
      code, is_valid: true, recipient_name: user.me.data.name, program_title: e.program.title, cohort_name: e.cohort?.name ?? null,
      final_score: e.average_score, issued_at: nowIso(), verify_url: `${location.origin}${location.pathname}#/verify/${code}`,
      pdf_url: SAMPLE_CERT_PDF, linkedin_url: 'https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME',
    }
    user.certificates.data.unshift(cert)
    db().public.certificates[code] = { data: cert }
  }
}

function dashboard() {
  const user = me()
  const enrollments = user.enrollments.data.filter((e: Any) => ['active', 'completed'].includes(e.status))
  const tasks: Any[] = []
  for (const e of enrollments.filter((x: Any) => x.status === 'active')) {
    for (const s of user.curriculum[e.id]?.data.sections ?? []) for (const l of s.lessons) {
      const d = user.lessons[`${e.id}/${l.id}`]?.data
      if (!d?.assignment) continue
      tasks.push({
        enrollment_id: e.id, lesson_id: l.id, title: d.assignment.title, program_title: e.program.title, due_at: d.assignment.due_at,
        status: d.submission?.status ?? 'pending', score: d.submission?.score ?? null,
      })
    }
  }
  const rank: Record<string, number> = { pending: 0, revision: 0, submitted: 1, reviewed: 2 }
  tasks.sort((a, b) => rank[a.status]! - rank[b.status]! || (a.due_at ?? '9999').localeCompare(b.due_at ?? '9999'))
  const base = user.dashboard?.data ?? { next_live_session: null }
  const live = base.next_live_session && new Date(base.next_live_session.ends_at) > new Date() ? { ...base.next_live_session } : null
  if (live) live.is_joinable = isJoinable(live)
  return {
    data: {
      next_live_session: live,
      enrollments,
      tasks: tasks.slice(0, 6),
      profile_complete: user.me.data.has_cv && !!user.me.data.headline,
    },
  }
}

function isJoinable(session: Any) {
  const now = Date.now()
  return now >= new Date(session.join_opens_at).getTime() && now <= new Date(session.ends_at).getTime()
}

function submitAssignment(user: Any, enrollmentId: string, lessonId: string, body: Any) {
  const lesson = lessonOf(user, enrollmentId, lessonId)
  const link = form(body, 'link_url')
  const file = body instanceof FormData ? (body.get('file') as File | null) : null
  if (!link && !file) invalid('link_url', 'Isi link atau unggah file.')
  if (lesson.submission?.status === 'reviewed') fail(409, 'Tugas sudah dinilai dan tidak bisa dikirim ulang.', 'already_reviewed')
  const submission = {
    id: lesson.submission?.id ?? nextId(), status: 'submitted', link_url: link || null, file_name: file?.name ?? null,
    note: form(body, 'note') || null, score: null, feedback: null, submitted_at: nowIso(), graded_at: null,
  }
  lesson.submission = submission
  setCurriculumSubmission(user, enrollmentId, lessonId, 'submitted')

  // Masuk antrean review mentor.
  const e = enrollmentOf(user, enrollmentId)
  const queue = db().mentorQueue
  for (const key of ['submitted', 'revision']) queue[key].data = queue[key].data.filter((s: Any) => s.id !== submission.id)
  queue.submitted.data.push({
    ...submission,
    lesson: { id: lesson.id, title: lesson.title, assignment_title: lesson.assignment.title, assignment_instructions: lesson.assignment.instructions },
    learner: { name: user.me.data.name, program_title: e.program.title, cohort_name: e.cohort?.name ?? null },
  })
  return { data: submission }
}

function setCurriculumSubmission(user: Any, enrollmentId: string | number, lessonId: string | number, status: string) {
  for (const s of user.curriculum[enrollmentId]?.data.sections ?? []) for (const l of s.lessons) if (l.id === Number(lessonId)) l.submission_status = status
}

function grade(id: string, body: Any) {
  const score = Number(body.score)
  if (!Number.isInteger(score) || score < 0 || score > 100) invalid('score', 'Nilai harus 0–100.')
  const queue = db().mentorQueue
  let item: Any
  for (const key of ['submitted', 'revision', 'reviewed']) {
    const idx = queue[key].data.findIndex((s: Any) => s.id === Number(id))
    if (idx >= 0) item = queue[key].data.splice(idx, 1)[0]
  }
  if (!item) fail(404, 'Submission tidak ditemukan.')
  Object.assign(item, { score, feedback: body.feedback ?? null, status: body.status, graded_at: nowIso() })
  queue[body.status].data.unshift(item)

  // Teruskan ke data learner pemilik tugas, lalu evaluasi kelulusan.
  for (const [, user] of allUsers()) {
    for (const [key, lesson] of Object.entries(user.lessons ?? {}) as [string, Any][]) {
      if (lesson.data.submission?.id !== item.id) continue
      Object.assign(lesson.data.submission, { score, feedback: item.feedback, status: item.status, graded_at: item.graded_at })
      const [enrollmentId, lessonId] = key.split('/')
      setCurriculumSubmission(user, enrollmentId!, lessonId!, item.status)
      recalculate(user, Number(enrollmentId))
    }
  }
  return { data: item }
}

/* ----------------------------------------------------------------- career */

function recommend(answers: Any) {
  const target = answers.target_track === 'unsure' ? null : answers.target_track
  const weights: Record<string, number> = {}
  for (const i of answers.interests ?? []) (INTEREST_TRACKS[i] ?? []).forEach((t, idx) => { weights[t] = Math.max(weights[t] ?? 0, idx === 0 ? 25 : 10) })
  const budget: Record<string, number> = { low: 500_000, mid: 3_000_000, high: Infinity }
  const jobSeeking = ['student', 'fresh_graduate', 'career_switcher'].includes(answers.status)

  const scored: Any[] = []
  for (const card of db().public.programs.data) {
    const p = programDetail(card.slug)
    const tracks = CAREER_TRACKS[p.slug] ?? []
    const reasons: string[] = []
    let score = 0
    const matched = tracks.filter(t => weights[t])
    const interest = matched.reduce((a, t) => a + weights[t]!, 0)
    if (target) {
      if (tracks.includes(target)) { score += 50; reasons.push(`Menyiapkan kamu menjadi ${TRACK_LABELS[target]}`) }
      score += Math.min(20, Math.round(interest / 2))
    } else {
      score += Math.min(50, interest)
      if (matched.length) reasons.push('Sesuai minatmu: ' + [...matched].sort((a, b) => weights[b]! - weights[a]!).map(t => TRACK_LABELS[t]).join(', '))
    }
    if (!score) continue

    let cohort: Any = null
    if (p.type !== 'video_course') {
      const available = p.cohorts.filter((c: Any) => c.is_available && new Date(c.starts_at) > new Date())
      if (!available.length) continue
      cohort = answers.availability === 'flexible' ? available[0] : available.find((c: Any) => c.schedule_type === answers.availability) ?? null
    }
    const style = answers.learning_style
    score += style === 'live' ? ({ bootcamp: 15, short_class: 10, event: 10, video_course: -5 } as Any)[p.type]
      : style === 'self_paced' ? ({ video_course: 15, bootcamp: -5 } as Any)[p.type] ?? 6 : 6
    if (style === 'live' && p.type !== 'video_course') reasons.push('Belajar live bareng mentor praktisi')
    else if (style === 'self_paced' && p.type === 'video_course') reasons.push('Bisa dipelajari mandiri, akses selamanya')

    if (p.type !== 'video_course') {
      if (cohort) {
        score += 10
        reasons.push(`Ada jadwal ${cohort.name} mulai ${new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', timeZone: 'Asia/Jakarta' }).format(new Date(cohort.starts_at))}`)
      } else score -= 10
    } else if (answers.availability === 'flexible') score += 10

    if (p.selling_price <= budget[answers.budget]!) { score += 10; reasons.push(p.selling_price === 0 ? 'Gratis' : 'Masuk budget kamu') }
    else if (p.installment_from && answers.budget === 'mid') { score += 5; reasons.push(`Bisa dicicil mulai Rp${p.installment_from.toLocaleString('id-ID')}/bulan`) }
    else score -= 15

    if (p.level === 'pemula' && answers.experience !== 'related_job') { score += 5; reasons.push('Dirancang untuk pemula') }
    if (p.has_job_guarantee && jobSeeking) { score += 15; reasons.push('Ada garansi kerja & penyaluran ke mitra hiring') }
    scored.push({ p, card, score, reasons, cohort })
  }
  scored.sort((a, b) => b.score - a.score)
  let main = scored.filter(r => r.p.type !== 'event')
  let primary = main[0]
  if (primary && jobSeeking) {
    const bootcamp = main.find(r => r.p.type === 'bootcamp')
    if (bootcamp && bootcamp.score >= primary.score - 15) {
      primary = bootcamp
      main = [bootcamp, ...main.filter(r => r !== bootcamp)]
    }
  }
  const warm = primary ? scored.filter(r => r.p.slug !== primary.p.slug && r.p.type !== 'bootcamp' && r.p.selling_price < primary.p.selling_price)[0] : null
  const present = (r: Any) => ({
    program: { ...r.card, next_cohort: nextCohort(r.card.slug) },
    match_pct: Math.max(10, Math.min(99, Math.round((r.score * 100) / 110))),
    reasons: r.reasons,
    suggested_cohort_id: r.cohort?.id ?? null,
  })
  const fallbackTrack = primary ? (CAREER_TRACKS[primary.p.slug] ?? []).find(t => weights[t]) : Object.keys(weights)[0]
  return {
    target_track: target,
    target_label: target ? TRACK_LABELS[target] : fallbackTrack ? TRACK_LABELS[fallbackTrack] : 'Karier digital',
    tracks: target ? [target] : Object.keys(weights),
    primary: primary ? present(primary) : null,
    alternatives: main.slice(1, 3).filter(r => !warm || r.p.slug !== warm.p.slug).map(present),
    warm_up: warm ? present(warm) : null,
  }
}

function partnerJobs(): Any {
  if (!roles().includes('partner')) fail(403, 'User does not have the right roles.')
  return me()
}

function apply(slug: string, body: Any) {
  const user = me()
  const job = db().public.jobDetails[slug]?.data
  if (!job || !job.is_open) fail(409, 'Lowongan ini sudah ditutup.', 'job_closed')
  if (!user.me.data.has_cv) fail(422, 'Unggah CV di Profil Karier sebelum melamar.', 'cv_required')
  if (job.alumni_only && !user.enrollments.data.some((e: Any) => e.status === 'completed')) {
    fail(403, 'Lowongan ini khusus alumni KEA yang sudah lulus program.', 'alumni_only')
  }
  if (user.applications.data.some((a: Any) => a.job.slug === slug)) fail(409, 'Kamu sudah melamar lowongan ini.', 'already_applied')

  const id = nextId()
  const event = { status: 'applied', status_label: STATUS_LABELS.applied, note: null, at: nowIso() }
  const application = {
    id, status: 'applied', status_label: STATUS_LABELS.applied, cover_letter: body.cover_letter ?? null,
    portfolio_url: body.portfolio_url ?? user.me.data.portfolio_url, applied_at: nowIso(), hired_at: null,
    job: { ...job, description: undefined, requirements: undefined }, events: [event],
  }
  user.applications.data.unshift(application)

  // Masuk ke pipeline mitra pemilik lowongan.
  for (const [, partner] of allUsers()) {
    const pj = (partner.partnerJobs?.data ?? []).find((j: Any) => j.slug === slug)
    if (!pj) continue
    pj.applications_count = (pj.applications_count ?? 0) + 1
    const detail = partner.partnerJobDetails?.[pj.id]?.data
    if (detail && detail !== pj) detail.applications_count = pj.applications_count
    const talent = talentFor(user)
    ;(partner.applicants[pj.id] ??= { data: [] }).data.push({
      id, status: 'applied', status_label: STATUS_LABELS.applied, cover_letter: application.cover_letter, portfolio_url: application.portfolio_url,
      applied_at: application.applied_at, has_cv: true, cv_url: SAMPLE_CV_PDF, email: user.me.data.email, phone: user.me.data.phone,
      talent, events: [{ ...event, actor: user.me.data.name }],
    })
  }
  return { data: application }
}

function talentFor(user: Any) {
  const m = user.me.data
  return {
    id: m.id, name: m.name, headline: m.headline, city: m.city, bio: m.bio, skills: m.skills, open_to_work: m.open_to_work,
    portfolio_url: m.portfolio_url, linkedin_url: m.linkedin_url, has_cv: m.has_cv,
    best_score: Math.max(0, ...user.certificates.data.map((c: Any) => c.final_score ?? 0)) || null,
    programs: user.enrollments.data.filter((e: Any) => e.status === 'completed').map((e: Any) => ({
      title: e.program.title, cohort_name: e.cohort?.name ?? null, completed_at: e.completed_at, final_score: e.average_score,
      certificate_code: e.certificate_code,
    })),
  }
}

function transition(applicationId: number, status: string, note: string | null, actor: string | null) {
  const event = { status, status_label: STATUS_LABELS[status], note, at: nowIso() }
  for (const [, user] of allUsers()) {
    const mine = (user.applications?.data ?? []).find((a: Any) => a.id === applicationId)
    if (mine) {
      Object.assign(mine, { status, status_label: STATUS_LABELS[status], hired_at: status === 'hired' ? nowIso() : mine.hired_at })
      mine.events.push(event)
    }
    for (const list of Object.values(user.applicants ?? {}) as Any[]) {
      const a = list.data.find((x: Any) => x.id === applicationId)
      if (a) {
        Object.assign(a, { status, status_label: STATUS_LABELS[status] })
        a.events.push({ ...event, actor })
      }
    }
  }
}

function talents(q: Any) {
  partnerJobs()
  // Talent pool = learner lulus yang memberi consent (data dari snapshot + perubahan profil di demo).
  const fromUsers = allUsers()
    .filter(([, u]) => u.me?.data.talent_consent && u.enrollments?.data.some((e: Any) => e.status === 'completed'))
    .map(([, u]) => talentFor(u))
  let items: Any[] = fromUsers
  if (q.q) items = items.filter(t => `${t.name} ${t.headline ?? ''}`.toLowerCase().includes(String(q.q).toLowerCase()))
  if (q.skill) items = items.filter(t => t.skills.some((s: string) => s.toLowerCase() === String(q.skill).trim().toLowerCase()))
  if (q.program) {
    const title = programDetail(q.program)?.title
    items = items.filter(t => t.programs.some((p: Any) => p.title === title))
  }
  if (q.min_score !== undefined) items = items.filter(t => t.programs.some((p: Any) => (p.final_score ?? 0) >= Number(q.min_score)))
  if (q.open_to_work) items = items.filter(t => t.open_to_work)
  items.sort((a, b) => Number(b.open_to_work) - Number(a.open_to_work) || a.name.localeCompare(b.name))
  return paginate(items, Number(q.page) || 1, 12)
}

function saveJob(body: Any, existing?: Any) {
  const partner = partnerJobs()
  if (!body.title) invalid('title', 'Judul posisi wajib diisi.')
  if (!body.description) invalid('description', 'Deskripsi wajib diisi.')
  if (body.salary_min && body.salary_max && body.salary_max < body.salary_min) invalid('salary_max', 'Gaji maksimum harus ≥ gaji minimum.')
  const types: Record<string, string> = { full_time: 'Full-time', contract: 'Kontrak', internship: 'Magang', part_time: 'Part-time' }
  const modes: Record<string, string> = { onsite: 'On-site', hybrid: 'Hybrid', remote: 'Remote' }
  const company = partner.partnerCompany.data
  const category = db().public.categories.data.find((c: Any) => c.slug === body.category_slug) ?? null
  const job = existing ?? { id: nextId(), slug: `${String(body.title).toLowerCase().replace(/[^a-z0-9]+/g, '-')}-${Math.random().toString(36).slice(2, 7)}`, applications_count: 0 }
  Object.assign(job, {
    title: body.title, category, employment_type: body.employment_type, employment_type_label: types[body.employment_type],
    work_mode: body.work_mode, work_mode_label: modes[body.work_mode], location: body.location, salary_min: body.salary_min || null,
    salary_max: body.salary_max || null, show_salary: !!body.show_salary, skills: body.skills ?? [], requirements: body.requirements ?? [],
    alumni_only: !!body.alumni_only, description: body.description, status: body.status, closes_at: body.closes_at ? `${body.closes_at}T23:59:00+07:00` : null,
    published_at: job.published_at ?? (body.status === 'open' ? nowIso() : null),
    company: { name: company.name, slug: company.slug, industry: company.industry, city: company.city, logo_url: null, website: company.website, description: null, is_verified: company.is_verified },
  })
  job.is_open = job.status === 'open' && (!job.closes_at || new Date(job.closes_at) > new Date())
  if (!existing) {
    partner.partnerJobs.data.unshift(job)
    partner.applicants[job.id] = { data: [] }
  }
  partner.partnerJobDetails[job.id] = { data: job }

  // Sinkronkan ke job board publik.
  const pub = db().public
  pub.jobs.data = pub.jobs.data.filter((j: Any) => j.id !== job.id)
  delete pub.jobDetails[job.slug]
  if (job.is_open) {
    pub.jobs.data.unshift({ ...job, salary_min: job.show_salary ? job.salary_min : null, salary_max: job.show_salary ? job.salary_max : null, status: undefined, applications_count: undefined })
    pub.jobDetails[job.slug] = { data: pub.jobs.data[0] }
  }
  return { data: job }
}

/* ----------------------------------------------------------------- router */

function form(body: Any, key: string): string {
  if (body instanceof FormData) return String(body.get(key) ?? '')
  return body?.[key] ?? ''
}

type Handler = (p: string[], q: Any, body: Any) => Any
const routes: [string, RegExp, Handler][] = [
  // Publik
  ['GET', /^\/categories$/, () => db().public.categories],
  ['GET', /^\/testimonials$/, () => db().public.testimonials],
  ['GET', /^\/stats$/, () => db().public.stats],
  ['GET', /^\/programs$/, (_, q) => listPrograms(q)],
  ['GET', /^\/programs\/([^/]+)$/, ([slug]) => db().public.programDetails[slug!] ?? fail(404, 'Program tidak ditemukan.')],
  ['GET', /^\/certificates\/verify\/([^/]+)$/, ([code]) => db().public.certificates[decodeURIComponent(code!).toUpperCase()] ?? fail(404, 'Sertifikat tidak ditemukan.', 'certificate_not_found')],
  ['GET', /^\/jobs$/, (_, q) => {
    let items: Any[] = db().public.jobs.data.filter((j: Any) => j.is_open)
    if (q.q) items = items.filter(j => `${j.title} ${j.location ?? ''} ${j.company?.name}`.toLowerCase().includes(String(q.q).toLowerCase()))
    if (q.category) items = items.filter(j => j.category?.slug === q.category)
    if (q.type) items = items.filter(j => j.employment_type === q.type)
    if (q.mode) items = items.filter(j => j.work_mode === q.mode)
    return paginate(items, Number(q.page) || 1, 12)
  }],
  ['GET', /^\/jobs\/([^/]+)$/, ([slug]) => db().public.jobDetails[slug!] ?? fail(404, 'Lowongan tidak ditemukan.')],
  ['GET', /^\/career\/questions$/, () => db().public.careerQuestions],
  ['POST', /^\/career\/assessments$/, (_, __, body) => {
    const a = body?.answers ?? {}
    const errors: Record<string, string[]> = {}
    for (const k of ['target_track', 'experience', 'status', 'availability', 'learning_style', 'budget']) if (!a[k]) errors[`answers.${k}`] = ['Wajib diisi.']
    if (!a.interests?.length) errors['answers.interests'] = ['Pilih minimal satu.']
    if (Object.keys(errors).length) fail(422, 'Jawaban belum lengkap.', undefined, errors)
    const result = { answers: a, ...recommend(a), saved: false }
    if (load().current) {
      result.saved = true
      me().careerAssessment = { data: { ...result, created_at: nowIso() } }
    }
    return { data: result }
  }],

  // Auth
  ['POST', /^\/auth\/login$/, (_, __, body) => {
    const email = String(body?.email ?? '').toLowerCase()
    if (!db().users[email]) invalid('email', 'Email atau password salah.')
    demoLoginAs(email)
    return me().me
  }],
  ['POST', /^\/auth\/register$/, (_, __, body) => {
    const email = String(body?.email ?? '').toLowerCase()
    if (!body?.name) invalid('name', 'Nama wajib diisi.')
    if (!/.+@.+\..+/.test(email)) invalid('email', 'Format email tidak valid.')
    if (db().users[email]) invalid('email', 'Email sudah terdaftar.')
    if (String(body.password ?? '').length < 8) invalid('password', 'Password minimal 8 karakter.')
    if (body.password !== body.password_confirmation) invalid('password', 'Konfirmasi password tidak cocok.')
    const template = db().users[NEW_USER_TEMPLATE]
    db().users[email] = {
      me: { data: { ...template.me.data, id: nextId(), name: body.name, email, phone: body.phone || null, headline: null, company: null, bio: null, portfolio_url: null, linkedin_url: null, has_cv: false, city: null, skills: [], open_to_work: false, talent_consent: false, roles: ['learner'] } },
      dashboard: { data: { next_live_session: null } }, enrollments: { data: [] }, curriculum: {}, lessons: {}, discussions: {},
      certificates: { data: [] }, orders: { data: [] }, applications: { data: [] }, careerAssessment: { data: null },
    }
    demoLoginAs(email)
    return me().me
  }],
  ['POST', /^\/auth\/logout$/, () => { demoLoginAs(null); return { ok: true } }],
  ['POST', /^\/auth\/(forgot-password|reset-password|email\/resend)$/, () => ({ ok: true, message: 'Jika email terdaftar, link reset sudah dikirim.' })],
  ['GET', /^\/me$/, () => me().me],
  ['PUT', /^\/me$/, (_, __, body) => {
    const m = me().me.data
    const skills = [...new Set<string>((body.skills ?? m.skills).map((s: string) => s.trim()).filter(Boolean))]
    Object.assign(m, { ...body, skills, talent_consent: body.talent_consent ?? m.talent_consent })
    return me().me
  }],
  ['POST', /^\/me\/cv$/, () => { me().me.data.has_cv = true; return me().me }],

  // Checkout
  ['GET', /^\/orders$/, () => me().orders],
  ['POST', /^\/orders$/, (_, __, body) => createOrder(body)],
  ['GET', /^\/orders\/([^/]+)$/, ([uuid]) => ({ data: findOrder(uuid!) })],
  ['POST', /^\/orders\/([^/]+)\/vouchers$/, ([uuid], __, body) => applyVoucher(findOrder(uuid!), body?.code)],
  ['DELETE', /^\/orders\/([^/]+)\/vouchers$/, ([uuid]) => {
    const o = findOrder(uuid!)
    Object.assign(o, { voucher_code: null, discount: 0, total: o.subtotal })
    return { data: o }
  }],
  ['POST', /^\/orders\/([^/]+)\/pay$/, ([uuid], __, body) => pay(findOrder(uuid!), body)],
  ['POST', /^\/orders\/([^/]+)\/simulate-payment$/, ([uuid]) => markPaid(findOrder(uuid!))],

  // LMS
  ['GET', /^\/dashboard$/, () => dashboard()],
  ['GET', /^\/enrollments$/, () => me().enrollments],
  ['GET', /^\/enrollments\/(\d+)\/curriculum$/, ([id]) => {
    const user = me()
    enrollmentOf(user, id!)
    const cur = user.curriculum[id!]
    cur.data.enrollment = enrollmentOf(user, id!)
    return cur
  }],
  ['GET', /^\/enrollments\/(\d+)\/lessons\/(\d+)$/, ([e, l]) => {
    const lesson = lessonOf(me(), e!, l!)
    if (lesson.live_session) lesson.live_session.is_joinable = isJoinable(lesson.live_session)
    return { data: lesson }
  }],
  ['PUT', /^\/enrollments\/(\d+)\/lessons\/(\d+)\/progress$/, ([e, l], __, body) => {
    const user = me()
    const enrollment = enrollmentOf(user, e!)
    const lesson = lessonOf(user, e!, l!)
    enrollment.resume_lesson_id = Number(l)
    user.curriculum[e!].data.resume_lesson_id = Number(l)
    if (typeof body?.completed === 'boolean') {
      lesson.progress.completed = body.completed
      for (const s of user.curriculum[e!].data.sections) for (const x of s.lessons) if (x.id === Number(l)) x.completed = body.completed
      recalculate(user, Number(e))
    }
    return { data: { progress_pct: enrollment.progress_pct, status: enrollment.status } }
  }],
  ['POST', /^\/enrollments\/(\d+)\/lessons\/(\d+)\/submissions$/, ([e, l], __, body) => submitAssignment(me(), e!, l!, body)],
  ['GET', /^\/enrollments\/(\d+)\/lessons\/(\d+)\/discussions$/, ([e, l]) => me().discussions[`${e}/${l}`] ?? { data: [] }],
  ['POST', /^\/enrollments\/(\d+)\/lessons\/(\d+)\/discussions$/, ([e, l], __, body) => {
    const user = me()
    if (!String(body?.body ?? '').trim()) invalid('body', 'Pesan wajib diisi.')
    const list = (user.discussions[`${e}/${l}`] ??= { data: [] }).data
    const entry = { id: nextId(), body: body.body, author: user.me.data.name, is_mentor: user.me.data.roles.includes('mentor'), created_at: nowIso() }
    if (body.parent_id) list.find((t: Any) => t.id === body.parent_id)?.replies.push(entry)
    else list.unshift({ ...entry, replies: [] })
    return { data: entry }
  }],
  ['GET', /^\/enrollments\/(\d+)\/live-sessions\/(\d+)\/join$/, ([e, s]) => {
    const user = me()
    const session = Object.entries(user.lessons).filter(([k]) => k.startsWith(`${e}/`)).map(([, v]: Any) => v.data.live_session).find((x: Any) => x?.id === Number(s))
      ?? (user.dashboard?.data.next_live_session?.id === Number(s) ? user.dashboard.data.next_live_session : null)
    if (!session || !isJoinable(session)) fail(423, 'Link aktif 15 menit sebelum sesi dimulai.', 'session_not_open')
    return { data: { join_url: 'https://zoom.us/j/0000000000' } }
  }],
  ['GET', /^\/certificates$/, () => me().certificates],

  // Mentor
  ['GET', /^\/mentor\/submissions$/, (_, q) => {
    if (!roles().some(r => ['mentor', 'pm', 'admin'].includes(r))) fail(403, 'User does not have the right roles.')
    return paginate(db().mentorQueue[q.status ?? 'submitted']?.data ?? [], 1, 50)
  }],
  ['PUT', /^\/mentor\/submissions\/(\d+)\/grade$/, ([id], __, body) => grade(id!, body)],

  // Karier (learner)
  ['POST', /^\/jobs\/([^/]+)\/apply$/, ([slug], __, body) => apply(slug!, body ?? {})],
  ['GET', /^\/me\/applications$/, () => me().applications],
  ['POST', /^\/me\/applications\/(\d+)\/withdraw$/, ([id]) => {
    const a = me().applications.data.find((x: Any) => x.id === Number(id)) ?? fail(404, 'Lamaran tidak ditemukan.')
    if (FINAL.includes(a.status)) fail(409, 'Lamaran ini sudah selesai diproses.', 'application_final')
    transition(a.id, 'withdrawn', null, me().me.data.name)
    return { data: a }
  }],
  ['GET', /^\/me\/career-assessment$/, () => me().careerAssessment ?? { data: null }],

  // Hiring partner
  ['GET', /^\/partner\/company$/, () => {
    const p = partnerJobs()
    const all = (Object.values(p.applicants) as Any[]).flatMap(l => l.data)
    return { data: { ...p.partnerCompany.data, open_jobs: p.partnerJobs.data.filter((j: Any) => j.status === 'open').length, applications: all.length, hired: all.filter(a => a.status === 'hired').length } }
  }],
  ['GET', /^\/partner\/jobs$/, () => partnerJobs().partnerJobs],
  ['POST', /^\/partner\/jobs$/, (_, __, body) => saveJob(body)],
  ['GET', /^\/partner\/jobs\/(\d+)$/, ([id]) => partnerJobs().partnerJobDetails[id!] ?? fail(404, 'Lowongan tidak ditemukan.')],
  ['PUT', /^\/partner\/jobs\/(\d+)$/, ([id], __, body) => saveJob(body, partnerJobs().partnerJobDetails[id!]?.data ?? fail(404, 'Lowongan tidak ditemukan.'))],
  ['GET', /^\/partner\/jobs\/(\d+)\/applicants$/, ([id], q) => {
    const list = (partnerJobs().applicants[id!] ?? fail(404, 'Lowongan tidak ditemukan.')).data.filter((a: Any) => a.status !== 'withdrawn')
    return { data: q.status ? list.filter((a: Any) => a.status === q.status) : list }
  }],
  ['PUT', /^\/partner\/applications\/(\d+)$/, ([id], __, body) => {
    const p = partnerJobs()
    const a = (Object.values(p.applicants) as Any[]).flatMap(l => l.data).find(x => x.id === Number(id)) ?? fail(404, 'Lamaran tidak ditemukan.')
    if (FINAL.includes(a.status)) fail(409, 'Status lamaran ini sudah final.', 'application_final')
    transition(a.id, body.status, body.note || null, p.me.data.name)
    return { data: a }
  }],
  ['GET', /^\/partner\/talents$/, (_, q) => talents(q)],
  ['GET', /^\/partner\/talents\/(\d+)$/, ([id]) => ({ data: talents({}).data.find((t: Any) => t.id === Number(id)) ?? fail(404, 'Talent tidak ditemukan.') })],
]

/** Pengganti `$fetch` saat mode demo. Error dilempar dengan bentuk yang sama seperti FetchError (statusCode + data). */
export async function demoFetch<T = unknown>(url: string, options: { method?: string; query?: Record<string, unknown>; body?: unknown } = {}): Promise<T> {
  const method = (options.method ?? 'GET').toUpperCase()
  const [path, search] = url.replace(/^\/api\/v1/, '').split('?')
  const query = { ...Object.fromEntries(new URLSearchParams(search ?? '')), ...(options.query ?? {}) }
  for (const k of Object.keys(query)) if (query[k] === undefined) delete query[k]

  // Body dari komponen bisa berupa Proxy reaktif Vue: lepaskan dulu (FormData dibiarkan apa adanya).
  const body = options.body instanceof FormData || options.body === undefined ? options.body : JSON.parse(JSON.stringify(options.body))

  await new Promise(r => setTimeout(r, 120)) // terasa seperti request sungguhan, tapi tetap cepat untuk presentasi
  for (const [m, re, handler] of routes) {
    const match = path!.match(re)
    if (m === method && match) {
      const result = handler(match.slice(1), query, body)
      save()
      return structuredClone(result) as T
    }
  }
  throw new DemoError(404, { message: `Endpoint demo belum tersedia: ${method} ${path}` })
}
