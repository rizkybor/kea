export default defineNuxtRouteMiddleware(async () => {
  const auth = useAuthStore()
  await auth.fetchUser()
  if (!auth.isMentor) {
    return navigateTo('/dashboard')
  }
})
