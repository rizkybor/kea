import type { ApiErrorBody } from '~/types/api'

/**
 * Klien API Laravel. Semua request lewat proxy same-origin `/api/v1`, cookie sesi Sanctum ikut,
 * dan header X-XSRF-TOKEN diisi dari cookie untuk request yang mengubah data.
 */
export function useApi() {
  const headers = import.meta.server ? useRequestHeaders(['cookie']) : {}

  return $fetch.create({
    baseURL: '/api/v1',
    credentials: 'include',
    headers: { Accept: 'application/json', ...headers },
    async onRequest({ options }) {
      const method = (options.method ?? 'GET').toUpperCase()
      if (import.meta.server || method === 'GET') return

      let token = readXsrfCookie()
      if (!token) {
        await $fetch('/sanctum/csrf-cookie', { credentials: 'include' })
        token = readXsrfCookie()
      }
      if (token) {
        const h = new Headers(options.headers as HeadersInit)
        h.set('X-XSRF-TOKEN', token)
        options.headers = h
      }
    },
  })
}

function readXsrfCookie(): string | null {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  return match?.[1] ? decodeURIComponent(match[1]) : null
}

/** Ambil body error `{ message, code, errors }` dari FetchError. */
export function apiError(error: unknown): ApiErrorBody & { status?: number } {
  const e = error as { data?: ApiErrorBody; statusCode?: number; status?: number }
  return {
    message: e?.data?.message ?? 'Terjadi kesalahan. Coba lagi.',
    code: e?.data?.code,
    errors: e?.data?.errors,
    status: e?.statusCode ?? e?.status,
  }
}
