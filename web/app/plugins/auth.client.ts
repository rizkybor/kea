/** Status login tidak ikut di-render server (halaman publik di-cache ISR), jadi dimuat di client. */
export default defineNuxtPlugin(() => {
  useAuthStore().fetchUser()
})
