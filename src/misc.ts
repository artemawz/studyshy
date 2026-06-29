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

export const OTHER_INTEREST_LABEL = 'Andere'

function normalize(value: string | undefined | null): string {
  return (value ?? '').trim().toLowerCase()
}

function semesterMatches(selected: string[], semester: number): boolean {
  if (selected.length === 0) return true
  return selected.some((s) => {
    if (s === '10+') return semester >= 10
    return parseInt(s, 10) === semester
  })
}

export function semesterProximityBetween(a: number, b: number): number {
  const diff = Math.abs(a - b)
  if (diff === 0) return 1
  if (diff === 1) return 0.75
  if (diff === 2) return 0.5
  if (diff === 3) return 0.25
  return 0
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

export function formatRelativeTime(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date
  const diffMs = Date.now() - d.getTime()
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

function studentHasInterest(student: User, interest: string): boolean {
  const target = normalize(interest)
  return (student.interests ?? []).some((item) => normalize(item) === target)
}

/**
 * Jede Übereinstimmung zählt einzeln: Hochschule, Studiengang, Semester und jedes Interesse.
 * Volle Übereinstimmung in allen Punkten ergibt 100 %.
 */
export function getMatchScore(student: User, reference: User): MatchScore {
  let matched = 0
  let total = 0

  if (reference.uni) {
    total += 1
    if (normalize(student.uni) === normalize(reference.uni)) matched += 1
  }

  const refCourse = reference.courses?.[0]
  const studentCourse = student.courses?.[0]

  if (refCourse?.name) {
    total += 1
    if (normalize(studentCourse?.name) === normalize(refCourse.name)) matched += 1
  }

  if (refCourse?.semester !== undefined) {
    total += 1
    if (studentCourse?.semester !== undefined) {
      matched += semesterProximityBetween(refCourse.semester, studentCourse.semester)
    }
  }

  for (const interest of reference.interests ?? []) {
    total += 1
    if (studentHasInterest(student, interest)) matched += 1
  }

  if (total === 0) {
    return { matched: 0, total: 0 }
  }

  return { matched, total }
}

export function getMatchPercent(score: MatchScore): number {
  if (score.total === 0) return 0
  return Math.round((score.matched / score.total) * 100)
}
