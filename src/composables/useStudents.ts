import { mockStudents } from '@/data/mockData'
import type { User } from '@/types'

export function useStudents() {
  function getStudentById(id: number): User | undefined {
    return mockStudents.find((s) => s.id === id)
  }

  return { students: mockStudents, getStudentById }
}
