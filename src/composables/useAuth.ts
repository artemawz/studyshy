import { computed, ref } from 'vue'
import type { User } from '@/types'

const STORAGE_KEY = 'studyshy-user'

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

export function useAuth() {
  const isLoggedIn = computed(() => currentUser.value !== null)

  function login(email: string, _password: string): boolean {
    currentUser.value = {
      id: 0,
      pub_name: email.split('@')[0] ?? 'Du',
      uni: 'Hochschule Bochum',
      courses: [{ name: 'Informatik', semester: 3 }],
      interests: ['Musik'],
      avatarUrl: 'https://i.pravatar.cc/150?img=5',
      bio: 'Dein anonymes Profil – bearbeite es in den Einstellungen.',
    }
    saveUser(currentUser.value)
    return true
  }

  function register(data: {
    email: string
    uni: string
    course: string
    semester: number
    interests: string[]
  }): boolean {
    currentUser.value = {
      id: 0,
      pub_name: `Student #${Math.random().toString(16).slice(2, 5)}`,
      uni: data.uni,
      courses: [{ name: data.course, semester: data.semester }],
      interests: data.interests,
      avatarUrl: `https://i.pravatar.cc/150?img=${Math.floor(Math.random() * 64) + 1}`,
      bio: '',
    }
    saveUser(currentUser.value)
    return true
  }

  function logout() {
    currentUser.value = null
    saveUser(null)
  }

  return { currentUser, isLoggedIn, login, register, logout }
}
