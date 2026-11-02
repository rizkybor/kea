import { defineStore } from 'pinia'
import type { User } from '~/types/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loaded = ref(false)

  const isLoggedIn = computed(() => user.value !== null)
  const isPartner = computed(() => user.value?.roles.includes('partner') ?? false)
  const isMentor = computed(() => user.value?.roles.some(r => ['mentor', 'pm', 'admin'].includes(r)) ?? false)

  async function fetchUser(force = false) {
    if (loaded.value && !force) return user.value
    try {
      user.value = (await useApi()<{ data: User }>('/me')).data
    } catch {
      user.value = null
    }
    loaded.value = true
    return user.value
  }

  async function login(payload: { email: string; password: string; remember?: boolean }) {
    user.value = (await useApi()<{ data: User }>('/auth/login', { method: 'POST', body: payload })).data
    loaded.value = true
  }

  async function register(payload: Record<string, string>) {
    user.value = (await useApi()<{ data: User }>('/auth/register', { method: 'POST', body: payload })).data
    loaded.value = true
  }

  async function logout() {
    await useApi()('/auth/logout', { method: 'POST' }).catch(() => {})
    user.value = null
  }

  return { user, loaded, isLoggedIn, isMentor, isPartner, fetchUser, login, register, logout }
})
