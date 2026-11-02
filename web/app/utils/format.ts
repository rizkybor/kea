const TZ = 'Asia/Jakarta'

export function rupiah(value: number | null | undefined): string {
  if (value === null || value === undefined) return '-'
  if (value === 0) return 'Gratis'
  return 'Rp' + new Intl.NumberFormat('id-ID').format(value)
}

export function formatDate(iso: string | null | undefined, opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' }): string {
  if (!iso) return '-'
  return new Intl.DateTimeFormat('id-ID', { timeZone: TZ, ...opts }).format(new Date(iso))
}

export function formatDateTime(iso: string | null | undefined): string {
  return formatDate(iso, { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' }) + ' WIB'
}

export function formatTime(iso: string): string {
  return formatDate(iso, { hour: '2-digit', minute: '2-digit' })
}

/** Teks status kursi untuk badge/opsi cohort. */
export function seatsLabel(seatsLeft: number, available: boolean): string {
  if (!available) return 'Penuh'
  if (seatsLeft <= 5) return `Sisa ${seatsLeft} kursi`
  return 'Tersedia'
}

export const programTypes = [
  { value: '', label: 'Semua' },
  { value: 'bootcamp', label: 'Bootcamp' },
  { value: 'short_class', label: 'Short Class' },
  { value: 'video_course', label: 'Video Course' },
  { value: 'event', label: 'Event' },
] as const

export const submissionStatusLabel: Record<string, { label: string; tone: 'olive' | 'ember' | 'muted' }> = {
  pending: { label: 'Belum', tone: 'ember' },
  revision: { label: 'Revisi', tone: 'ember' },
  submitted: { label: 'Direview', tone: 'muted' },
  reviewed: { label: 'Selesai', tone: 'olive' },
}

export function salaryRange(min: number | null, max: number | null): string | null {
  if (!min && !max) return null
  const short = (v: number) => (v >= 1_000_000 ? `${(v / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt` : rupiah(v))
  if (min && max) return `Rp${short(min).replace('Rp', '')} – ${short(max).replace('Rp', '')}`
  return `Rp${short((min ?? max)!).replace('Rp', '')}`
}

export const applicationTone: Record<string, 'olive' | 'ember' | 'muted'> = {
  applied: 'muted',
  reviewing: 'muted',
  interview: 'ember',
  offered: 'ember',
  hired: 'olive',
  rejected: 'muted',
  withdrawn: 'muted',
}

export const applicationPipeline = [
  { value: 'reviewing', label: 'Ditinjau' },
  { value: 'interview', label: 'Interview' },
  { value: 'offered', label: 'Penawaran' },
  { value: 'hired', label: 'Diterima' },
  { value: 'rejected', label: 'Tidak lolos' },
] as const

export const employmentTypes = [
  { value: 'full_time', label: 'Full-time' },
  { value: 'contract', label: 'Kontrak' },
  { value: 'internship', label: 'Magang' },
  { value: 'part_time', label: 'Part-time' },
] as const

export const workModes = [
  { value: 'onsite', label: 'On-site' },
  { value: 'hybrid', label: 'Hybrid' },
  { value: 'remote', label: 'Remote' },
] as const
