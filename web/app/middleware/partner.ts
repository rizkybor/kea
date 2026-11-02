export default defineNuxtRouteMiddleware(async () => {
  const auth = useAuthStore()
  await auth.fetchUser()
  if (!auth.isPartner) {
    return navigateTo('/dashboard')
  }
})
