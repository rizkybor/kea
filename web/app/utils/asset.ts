/** URL aset di /public yang tetap benar saat di-host di subfolder (mode demo memakai baseURL relatif). */
export function asset(path: string): string {
  const config = useRuntimeConfig()
  if (config.public.demo) return path.replace(/^\//, '')
  const base = config.app.baseURL || '/'
  return base.replace(/\/?$/, '/') + path.replace(/^\//, '')
}
