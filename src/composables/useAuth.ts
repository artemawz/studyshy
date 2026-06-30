import { computed, ref } from 'vue'
import type { User } from '@/types'
import { api, setToken, getToken } from '@/services/api'

const STORAGE_KEY = 'studyshy-user'

export function getStoredUserId(): number | null {
  return loadUser()?.id ?? null
}

function loadUser(): User | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? (JSON.parse(raw) as User) : null
  } catch {
    return null
  }
}

function saveUser(user: User | null) {
  if (user) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(user))
  } else {
    localStorage.removeItem(STORAGE_KEY)
  }
}

const currentUser = ref<User | null>(loadUser())
const loading = ref(false)
const initialized = ref(false)

async function refreshUser() {
  if (!getToken()) {
    currentUser.value = null
    saveUser(null)
    return
  }

  try {
    const user = await api.me()
    currentUser.value = user
    saveUser(user)
  } catch {
    setToken(null)
    currentUser.value = null
    saveUser(null)
  }
}

export function useAuth() {
  const isLoggedIn = computed(() => currentUser.value !== null)

  async function initAuth() {
    if (initialized.value) return
    initialized.value = true
    if (getToken()) {
      loading.value = true
      await refreshUser()
      loading.value = false
    }
  }

  async function login(email: string, password: string): Promise<void> {
    loading.value = true
    try {
      const { token, user } = await api.login(email, password)
      setToken(token)
      currentUser.value = user
      saveUser(user)
    } finally {
      loading.value = false
    }
  }

  async function register(data: {
    email: string
    password: string
    uni: string
    course: string
    degree?: string | null
    semester: number
    interests: string[]
  }): Promise<void> {
    loading.value = true
    try {
      const { token, user } = await api.register(data)
      setToken(token)
      currentUser.value = user
      saveUser(user)
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      if (getToken()) {
        await api.logout()
      }
    } catch {
      // Token may already be invalid
    }
    setToken(null)
    currentUser.value = null
    saveUser(null)
  }

  async function updateProfile(data: {
    pub_name?: string
    avatar_url?: string | null
    bio?: string
    uni?: string
    course?: string
    degree?: string | null
    semester?: number
    interests?: string[]
  }): Promise<void> {
    const user = await api.updateProfile(data)
    currentUser.value = user
    saveUser(user)
  }

  async function uploadAvatar(file: File): Promise<import('@/types').User> {
    const user = await api.uploadAvatar(file)
    currentUser.value = user
    saveUser(user)
    return user
  }

  return {
    currentUser,
    isLoggedIn,
    loading,
    initAuth,
    login,
    register,
    logout,
    updateProfile,
    uploadAvatar,
    refreshUser,
  }
}
