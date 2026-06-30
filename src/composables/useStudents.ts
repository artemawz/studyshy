import { ref } from 'vue'
import type { User } from '@/types'
import { api } from '@/services/api'

const students = ref<User[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const studentCache = new Map<number, User>()

function cacheStudent(user: User) {
  studentCache.set(user.id, user)
}

export function useStudents() {
  async function fetchStudents(filters?: {
    unis?: string[]
    courses?: string[]
    degrees?: string[]
    interests?: string[]
    semesters?: string[]
  }) {
    loading.value = true
    error.value = null
    try {
      const params: Record<string, string[]> = {}
      if (filters?.unis?.length) params.uni = filters.unis
      if (filters?.courses?.length) params.course = filters.courses
      if (filters?.degrees?.length) params.degree = filters.degrees
      if (filters?.interests?.length) params.interest = filters.interests
      if (filters?.semesters?.length) params.semester = filters.semesters

      const res = await api.getStudents(params)
      students.value = res.data
      res.data.forEach(cacheStudent)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Studierende konnten nicht geladen werden.'
      students.value = []
    } finally {
      loading.value = false
    }
  }

  function getStudentById(id: number): User | undefined {
    return studentCache.get(id) ?? students.value.find((s) => s.id === id)
  }

  async function fetchStudentById(id: number): Promise<User | undefined> {
    const cached = getStudentById(id)
    if (cached) return cached

    try {
      const user = await api.getStudent(id)
      cacheStudent(user)
      return user
    } catch {
      return undefined
    }
  }

  return { students, loading, error, fetchStudents, getStudentById, fetchStudentById }
}
