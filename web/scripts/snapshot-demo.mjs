// Mengambil snapshot data dari API Laravel yang sudah di-seed (`php artisan migrate:fresh --seed`)
// menjadi fixture untuk prototype statis. Jalankan: node scripts/snapshot-demo.mjs [http://localhost:8000]
import { mkdirSync, writeFileSync } from 'node:fs'

const API = process.argv[2] ?? 'http://localhost:8000'
const FRONT = 'http://localhost:3000'
const out = { public: {}, users: {} }

function client() {
  const jar = new Map()
  const cookieHeader = () => [...jar].map(([k, v]) => `${k}=${v}`).join('; ')
  async function call(method, path, body) {
    const headers = { Accept: 'application/json', Referer: `${FRONT}/`, Origin: FRONT, Cookie: cookieHeader() }
    const xsrf = jar.get('XSRF-TOKEN')
    if (xsrf) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf)
    if (body) headers['Content-Type'] = 'application/json'
    const res = await fetch(API + path, { method, headers, body: body ? JSON.stringify(body) : undefined, redirect: 'manual' })
    for (const c of res.headers.getSetCookie()) {
      const [pair] = c.split(';')
      const i = pair.indexOf('=')
      jar.set(pair.slice(0, i), pair.slice(i + 1))
    }
    const type = res.headers.get('content-type') ?? ''
    if (!type.includes('json')) return { status: res.status, buffer: Buffer.from(await res.arrayBuffer()) }
    return { status: res.status, json: await res.json() }
  }
  return {
    async login(email) {
      await call('GET', '/sanctum/csrf-cookie')
      const r = await call('POST', '/api/v1/auth/login', { email, password: 'password' })
      if (r.status !== 200) throw new Error(`login ${email}: ${r.status}`)
    },
    get: async (p) => {
      const r = await call('GET', '/api/v1' + p)
      if (r.status >= 400) throw new Error(`GET ${p}: ${r.status}`)
      return r.json ?? r.buffer
    },
  }
}

const pub = client()
out.public.categories = await pub.get('/categories')
out.public.testimonials = await pub.get('/testimonials')
out.public.stats = await pub.get('/stats')
out.public.programs = await pub.get('/programs?per_page=24&sort=start')
out.public.programDetails = {}
for (const p of out.public.programs.data) out.public.programDetails[p.slug] = await pub.get(`/programs/${p.slug}`)
out.public.jobs = await pub.get('/jobs?per_page=30')
out.public.jobDetails = {}
for (const j of out.public.jobs.data) out.public.jobDetails[j.slug] = await pub.get(`/jobs/${j.slug}`)
out.public.careerQuestions = await pub.get('/career/questions')
out.public.certificates = { 'KEA-FSWD-2026-000123': await pub.get('/certificates/verify/KEA-FSWD-2026-000123') }

mkdirSync('public/demo', { recursive: true })
writeFileSync('public/demo/sertifikat-contoh.pdf', await pub.get('/certificates/KEA-FSWD-2026-000123/pdf'))

for (const email of ['learner@kea.test', 'alumni@kea.test', 'mentor@kea.test', 'partner@kea.test']) {
  const c = client()
  await c.login(email)
  const u = { me: await c.get('/me') }
  const roles = u.me.data.roles
  if (roles.includes('partner')) {
    u.partnerCompany = await c.get('/partner/company')
    u.partnerJobs = await c.get('/partner/jobs')
    u.partnerJobDetails = {}
    u.applicants = {}
    for (const j of u.partnerJobs.data) {
      u.partnerJobDetails[j.id] = await c.get(`/partner/jobs/${j.id}`)
      u.applicants[j.id] = await c.get(`/partner/jobs/${j.id}/applicants`)
    }
    u.talents = await c.get('/partner/talents')
    u.talentDetails = {}
    for (const t of u.talents.data) u.talentDetails[t.id] = await c.get(`/partner/talents/${t.id}`)
    const anyApplicant = Object.values(u.applicants).flatMap(a => a.data)[0]
    if (anyApplicant) writeFileSync('public/demo/cv-contoh.pdf', await c.get(`/partner/applications/${anyApplicant.id}/cv`))
  } else {
    u.dashboard = await c.get('/dashboard')
    u.enrollments = await c.get('/enrollments')
    u.curriculum = {}
    u.lessons = {}
    u.discussions = {}
    for (const e of u.enrollments.data) {
      const cur = await c.get(`/enrollments/${e.id}/curriculum`)
      u.curriculum[e.id] = cur
      for (const s of cur.data.sections) for (const l of s.lessons) {
        u.lessons[`${e.id}/${l.id}`] = await c.get(`/enrollments/${e.id}/lessons/${l.id}`)
        u.discussions[`${e.id}/${l.id}`] = await c.get(`/enrollments/${e.id}/lessons/${l.id}/discussions`)
      }
    }
    u.certificates = await c.get('/certificates')
    u.orders = await c.get('/orders')
    u.applications = await c.get('/me/applications')
    u.careerAssessment = await c.get('/me/career-assessment')
    if (roles.includes('mentor')) {
      u.mentorSubmissions = {}
      for (const s of ['submitted', 'revision', 'reviewed']) u.mentorSubmissions[s] = await c.get(`/mentor/submissions?status=${s}`)
    }
  }
  out.users[email] = u
}

writeFileSync('app/demo/fixtures.json', JSON.stringify(out))
console.log('fixtures:', (JSON.stringify(out).length / 1024).toFixed(0), 'KB', Object.keys(out.users))
