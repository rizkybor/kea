import type { RouterConfig } from '@nuxt/schema'
import { createWebHashHistory } from 'vue-router'

/**
 * Mode demo (prototype statis): hash routing relatif terhadap lokasi index.html, sehingga build bisa dibuka
 * dari subfolder hosting statis mana pun tanpa konfigurasi rewrite. Mode normal memakai history bawaan Nuxt.
 */
export default <RouterConfig>{
  history: () => (import.meta.client && useRuntimeConfig().public.demo ? createWebHashHistory() : null),
}
