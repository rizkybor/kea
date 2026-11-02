/** State form sederhana: nilai, error per field (422), pesan umum, dan status kirim. */
export function useForm<T extends Record<string, unknown>>(initial: T) {
  const values = reactive({ ...initial }) as T
  const errors = ref<Record<string, string>>({})
  const message = ref<string | null>(null)
  const pending = ref(false)

  async function submit(fn: () => Promise<unknown>) {
    errors.value = {}
    message.value = null
    pending.value = true
    try {
      return await fn()
    } catch (e) {
      const err = apiError(e)
      errors.value = Object.fromEntries(Object.entries(err.errors ?? {}).map(([k, v]) => [k, v[0] ?? '']))
      message.value = Object.keys(errors.value).length ? null : err.message
      throw e
    } finally {
      pending.value = false
    }
  }

  return { values, errors, message, pending, submit }
}
