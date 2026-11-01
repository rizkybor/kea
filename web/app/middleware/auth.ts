/** Halaman learner: wajib login. Halaman ini SPA (ssr: false), jadi cek sesi di client. */
export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore()
  await auth.fetchUser()
  if (!auth.isLoggedIn) {
    return navigateTo({ path: '/masuk', query: { redirect: to.fullPath } })
  }
})
