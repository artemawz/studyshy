import type { StudentFilters, User } from './types'

export function getRandomInt(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

export function getUserTags(user: User): string[] {
  const tags: string[] = []
  user.courses?.forEach((c) => tags.push(`${c.name} (${c.semester}. Sem.)`))
  user.interests?.forEach((i) => tags.push(i))
  return tags
}

export function toggleSelection(list: number[], idx: number): number[] {
  return list.includes(idx) ? list.filter((i) => i !== idx) : [...list, idx]
}

function semesterMatches(selected: string[], semester: number): boolean {
  if (selected.length === 0) return true
  return selected.some((s) => {
    if (s === '10+') return semester >= 10
    return parseInt(s, 10) === semester
  })
}

export function filterStudents(students: User[], filters: StudentFilters): User[] {
  return students.filter((student) => {
    if (filters.unis.length > 0 && (!student.uni || !filters.unis.includes(student.uni))) {
      return false
    }

    if (filters.courses.length > 0) {
      const courseNames = student.courses?.map((c) => c.name) ?? []
      if (!filters.courses.some((c) => courseNames.includes(c))) return false
    }

    if (filters.interests.length > 0) {
      const studentInterests = student.interests ?? []
      if (!filters.interests.some((i) => studentInterests.includes(i))) return false
    }

    if (filters.semesters.length > 0) {
      const semesters = student.courses?.map((c) => c.semester) ?? []
      if (!semesters.some((s) => semesterMatches(filters.semesters, s))) return false
    }

    return true
  })
}

export function formatRelativeTime(date: Date): string {
  const diffMs = Date.now() - date.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  if (diffMin < 1) return 'Gerade eben'
  if (diffMin < 60) return `Vor ${diffMin} Min.`
  const diffHours = Math.floor(diffMin / 60)
  if (diffHours < 24) return `Vor ${diffHours} Std.`
  const diffDays = Math.floor(diffHours / 24)
  return `Vor ${diffDays} Tag${diffDays === 1 ? '' : 'en'}`
}

export interface MatchScore {
  matched: number
  total: number
}

export function getMatchScore(student: User, filters: StudentFilters): MatchScore {
  let matched = 0
  let total = 0

  if (filters.unis.length > 0) {
    total++
    if (student.uni && filters.unis.includes(student.uni)) matched++
  }
  if (filters.courses.length > 0) {
    total++
    const names = student.courses?.map((c) => c.name) ?? []
    if (filters.courses.some((c) => names.includes(c))) matched++
  }
  if (filters.interests.length > 0) {
    total++
    const studentInterests = student.interests ?? []
    if (filters.interests.some((i) => studentInterests.includes(i))) matched++
  }
  if (filters.semesters.length > 0) {
    total++
    const semesters = student.courses?.map((c) => c.semester) ?? []
    if (semesters.some((s) => semesterMatches(filters.semesters, s))) matched++
  }

  return { matched, total }
}
