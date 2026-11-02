/** Bentuk respons API Laravel /api/v1 (lihat API Resources di api/app/Modules). */

export type ProgramType = 'bootcamp' | 'video_course' | 'short_class' | 'event'

export interface Cohort {
  id: number
  name: string
  schedule_type: 'weekday' | 'weekend'
  schedule_label: string | null
  starts_at: string
  ends_at: string | null
  quota: number
  seats_left: number
  is_available: boolean
}

export interface ProgramCard {
  slug: string
  title: string
  type: ProgramType
  type_label: string
  category?: { name: string; slug: string }
  summary: string
  level: string
  duration_label: string | null
  format_label: string | null
  price: number
  promo_price: number | null
  selling_price: number
  has_job_guarantee: boolean
  thumbnail_url: string | null
  rating: number | null
  rating_count: number
  lessons_count?: number
  next_cohort?: Cohort | null
}

export interface ProgramDetail extends ProgramCard {
  description: string | null
  installment_from: number | null
  preview_video_url: string | null
  benefits: { title: string; body: string }[]
  faqs: { q: string; a: string }[]
  cohorts: Cohort[]
  sections: { title: string; topics: string[]; lessons: { title: string; type: string; kind_label: string; is_preview: boolean }[] }[]
  mentors: { name: string; headline: string | null; company: string | null; avatar_url: string | null }[]
  testimonials: Testimonial[]
}

export interface Testimonial {
  name: string
  role: string
  company: string
  quote: string
  avatar_url: string | null
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

export interface User {
  id: number
  name: string
  email: string
  email_verified: boolean
  phone: string | null
  headline: string | null
  company: string | null
  bio: string | null
  avatar_url: string | null
  portfolio_url: string | null
  linkedin_url: string | null
  has_cv: boolean
  city: string | null
  skills: string[]
  open_to_work: boolean
  talent_consent: boolean
  partner_company: { id: number; name: string; slug: string } | null
  roles: string[]
}

export type PaymentMethod = 'va' | 'qris' | 'ewallet' | 'cc'

export interface Order {
  uuid: string
  invoice_number: string
  status: 'pending' | 'paid' | 'expired' | 'failed' | 'refunded'
  subtotal: number
  discount: number
  total: number
  voucher_code: string | null
  customer_name: string | null
  customer_phone: string | null
  payment_method: PaymentMethod | null
  payment_instruction: {
    method: PaymentMethod
    method_label: string
    channel: string
    va_number: string | null
    qr_string: string | null
    redirect_url: string | null
    expires_at: string
  } | null
  reserved_until: string | null
  paid_at: string | null
  created_at: string
  items: {
    program_title: string
    program_slug: string | null
    program_type: ProgramType | null
    program_type_label: string | null
    thumbnail_url: string | null
    cohort_name: string | null
    cohort_starts_at: string | null
    price: number
  }[]
  simulator: boolean
}

export interface Enrollment {
  id: number
  status: 'active' | 'completed' | 'failed' | 'refunded'
  progress_pct: number
  resume_lesson_id: number | null
  completed_at: string | null
  program: { slug: string; title: string; type: ProgramType; type_label: string; thumbnail_url: string | null }
  cohort: { id: number; name: string; starts_at: string; community_url: string | null } | null
  attendance: { attended: number; held: number } | null
  average_score: number | null
  certificate_code: string | null
}

export interface LiveSession {
  id: number
  title: string
  starts_at: string
  ends_at: string
  join_opens_at: string
  is_joinable: boolean
  recording_url: string | null
  mentor_name?: string | null
  lesson_id: number | null
}

export interface Task {
  enrollment_id: number
  lesson_id: number
  title: string
  program_title: string
  due_at: string | null
  status: 'pending' | 'submitted' | 'reviewed' | 'revision'
  score: number | null
}

export interface Dashboard {
  next_live_session: (LiveSession & { program_title: string; enrollment_id: number | null }) | null
  enrollments: Enrollment[]
  tasks: Task[]
  profile_complete: boolean
}

export interface CurriculumLesson {
  id: number
  title: string
  type: string
  kind_label: string
  completed: boolean
  has_assignment: boolean
  submission_status: Task['status'] | null
}

export interface Curriculum {
  enrollment: Enrollment
  sections: { title: string; lessons: CurriculumLesson[] }[]
  resume_lesson_id: number | null
}

export interface Submission {
  id: number
  status: 'submitted' | 'reviewed' | 'revision'
  link_url: string | null
  file_name: string | null
  note: string | null
  score: number | null
  feedback: string | null
  submitted_at: string | null
  graded_at: string | null
  lesson?: { id: number; title: string; assignment_title: string; assignment_instructions: string | null }
  learner?: { name: string; program_title: string; cohort_name: string | null }
}

export interface LessonDetail {
  id: number
  title: string
  section_title: string
  type: string
  kind_label: string
  duration_minutes: number | null
  summary: string | null
  resource_url: string | null
  playback: { url: string | null; expires_at: string | null; watermark: string }
  progress: { completed: boolean; last_position_sec: number }
  assignment: { title: string; instructions: string | null; due_at: string | null; is_final_project: boolean } | null
  submission: Submission | null
  live_session: LiveSession | null
  prev_lesson_id: number | null
  next_lesson_id: number | null
}

export interface DiscussionThread {
  id: number
  body: string
  author: string
  is_mentor: boolean
  created_at: string
  replies: Omit<DiscussionThread, 'replies'>[]
}

export interface Certificate {
  code: string
  is_valid: boolean
  recipient_name: string
  program_title: string
  cohort_name: string | null
  final_score: number | null
  issued_at: string
  verify_url: string
  pdf_url: string
  linkedin_url: string
}

export interface ApiErrorBody {
  message: string
  code?: string
  errors?: Record<string, string[]>
}

/* ---------- Phase 2: Job Connector & MBL ---------- */

export type EmploymentType = 'full_time' | 'contract' | 'internship' | 'part_time'
export type WorkMode = 'onsite' | 'hybrid' | 'remote'
export type ApplicationStatus = 'applied' | 'reviewing' | 'interview' | 'offered' | 'hired' | 'rejected' | 'withdrawn'

export interface Job {
  id: number
  slug: string
  title: string
  company?: { name: string; slug: string; industry: string | null; city: string | null; logo_url: string | null; website: string | null; description: string | null; is_verified: boolean }
  category?: { name: string; slug: string } | null
  employment_type: EmploymentType
  employment_type_label: string
  work_mode: WorkMode
  work_mode_label: string
  location: string | null
  salary_min: number | null
  salary_max: number | null
  show_salary: boolean
  skills: string[]
  alumni_only: boolean
  published_at: string | null
  closes_at: string | null
  is_open: boolean
  description?: string
  requirements?: string[]
  status?: 'draft' | 'open' | 'closed'
  applications_count?: number
}

export interface ApplicationEvent {
  status: ApplicationStatus
  status_label: string
  note: string | null
  actor?: string | null
  at: string
}

export interface JobApplication {
  id: number
  status: ApplicationStatus
  status_label: string
  cover_letter: string | null
  portfolio_url: string | null
  applied_at: string
  hired_at: string | null
  job: Job
  events: ApplicationEvent[]
}

export interface Talent {
  id: number
  name: string
  headline: string | null
  city: string | null
  bio: string | null
  skills: string[]
  open_to_work: boolean
  portfolio_url: string | null
  linkedin_url: string | null
  has_cv: boolean
  best_score: number | null
  programs: { title: string; cohort_name: string | null; completed_at: string | null; final_score: number | null; certificate_code: string | null }[]
}

export interface Applicant {
  id: number
  status: ApplicationStatus
  status_label: string
  cover_letter: string | null
  portfolio_url: string | null
  applied_at: string
  has_cv: boolean
  cv_url: string | null
  email: string
  phone: string | null
  talent: Talent
  events: ApplicationEvent[]
}

export interface PartnerCompany {
  id: number
  name: string
  slug: string
  industry: string | null
  city: string | null
  website: string | null
  logo_url: string | null
  is_verified: boolean
  open_jobs: number
  applications: number
  hired: number
}

export interface CareerQuestion {
  id: string
  title: string
  hint?: string
  type: 'single' | 'multi'
  max?: number
  options: { value: string; label: string; hint?: string }[]
}

export interface Recommendation {
  program: ProgramCard
  match_pct: number
  reasons: string[]
  suggested_cohort_id: number | null
}

export interface CareerResult {
  answers: Record<string, string | string[]>
  target_track: string | null
  target_label: string
  tracks: string[]
  primary: Recommendation | null
  alternatives: Recommendation[]
  warm_up: Recommendation | null
  saved?: boolean
  created_at?: string
}
