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
