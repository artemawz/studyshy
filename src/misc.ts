import type { StudentFilters, User } from './types'

export function getRandomInt(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

export function getUserTags(user: User): string[] {
  const tags: string[] = []
  user.courses?.forEach((c) => {
    const degree = c.degree ? `${c.degree}, ` : ''
    tags.push(`${c.name} (${degree}${c.semester}. Sem.)`)
  })
  user.interests?.forEach((i) => tags.push(i))
  return tags
}

export function toggleSelection(list: number[], idx: number): number[] {
  return list.includes(idx) ? list.filter((i) => i !== idx) : [...list, idx]
}

export const OTHER_INTEREST_LABEL = 'Andere'

const REGISTRATION_EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.(de|com)$/i

export function isValidRegistrationEmail(email: string): boolean {
  return REGISTRATION_EMAIL_PATTERN.test(email.trim())
}

export const MAX_INTERESTS = 4
export const MAX_CUSTOM_INTEREST_LENGTH = 30

export function isValidPassword(password: string): boolean {
  return password.length >= 8 && /[A-Z]/.test(password) && /[^A-Za-z0-9]/.test(password)
}

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

    if (filters.degrees.length > 0) {
      const studentDegrees = student.courses?.map((c) => c.degree).filter(Boolean) ?? []
      if (!filters.degrees.some((d) => studentDegrees.includes(d))) return false
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

/**
 * Gewichtung der Match-Kriterien (Summe = 100). Hier zentral justierbar.
 */
export const MATCH_WEIGHTS = {
  uni: 25,
  course: 30,
  semester: 15,
  interests: 30,
} as const

/**
 * Verwandte Studiengänge: teilen sie ein Themenfeld, zählt das halb.
 * Ein Studiengang kann mehreren Feldern angehören (Stichwort-basiert).
 */
const COURSE_FAMILIES: string[][] = [
  ['informatik'],
  ['elektrotechnik', 'mechatron', 'maschinenbau', 'ingenieurwesen'],
  ['wirtschaft', 'management', 'business', 'betriebswirt', 'accounting', 'taxation', 'ökonomie'],
  ['bau', 'architektur', 'geodäsie', 'vermessung'],
  ['gesundheit', 'pflege', 'hebamme', 'therapie', 'logopädie', 'medizin', 'psycholog', 'sozial'],
  ['nachhalt', 'umwelt', 'energie'],
]

function courseFamilies(name: string): number[] {
  const n = normalize(name)
  const fams: number[] = []
  COURSE_FAMILIES.forEach((keywords, i) => {
    if (keywords.some((k) => n.includes(k))) fams.push(i)
  })
  return fams
}

function courseRelatedness(a: string | undefined, b: string | undefined): number {
  if (!a || !b) return 0
  if (normalize(a) === normalize(b)) return 1
  const famA = courseFamilies(a)
  const famB = courseFamilies(b)
  return famA.some((f) => famB.includes(f)) ? 0.5 : 0
}

/**
 * Gewichteter Kompatibilitäts-Score zwischen einem Studierenden und einem
 * Referenzprofil (i. d. R. der eingeloggte Nutzer). Liefert 0–100 % auch ohne
 * gesetzte Filter. Gleiche Uni, (verwandter) Studiengang, Semester-Nähe und
 * gemeinsame Interessen fließen gewichtet ein.
 */
export function getWeightedMatchScore(student: User, reference: User): MatchScore {
  let matched = 0
  let total = 0

  if (reference.uni) {
    total += MATCH_WEIGHTS.uni
    if (normalize(student.uni) === normalize(reference.uni)) matched += MATCH_WEIGHTS.uni
  }

  const refCourse = reference.courses?.[0]
  const studentCourse = student.courses?.[0]

  if (refCourse?.name) {
    total += MATCH_WEIGHTS.course
    matched += MATCH_WEIGHTS.course * courseRelatedness(refCourse.name, studentCourse?.name)
  }

  if (refCourse?.semester !== undefined) {
    total += MATCH_WEIGHTS.semester
    if (studentCourse?.semester !== undefined) {
      matched += MATCH_WEIGHTS.semester * semesterProximityBetween(refCourse.semester, studentCourse.semester)
    }
  }

  const refInterests = (reference.interests ?? []).map(normalize)
  const studentInterests = (student.interests ?? []).map(normalize)
  if (refInterests.length > 0 || studentInterests.length > 0) {
    total += MATCH_WEIGHTS.interests
    const studentSet = new Set(studentInterests)
    const intersection = refInterests.filter((i) => studentSet.has(i)).length
    const union = new Set([...refInterests, ...studentInterests]).size
    if (union > 0) matched += MATCH_WEIGHTS.interests * (intersection / union)
  }

  return { matched, total }
}

/**
 * Match-Score auf Basis der aktiven Filter (nicht des eigenen Profils).
 * Jedes ausgewählte Kriterium zählt einzeln: Uni, Studiengang, Interesse, Semester.
 */
export function getFilterMatchScore(student: User, filters: StudentFilters): MatchScore {
  let matched = 0
  let total = 0

  const studentUni = normalize(student.uni)
  for (const uni of filters.unis) {
    total += 1
    if (studentUni === normalize(uni)) matched += 1
  }

  const courseNames = (student.courses ?? []).map((c) => normalize(c.name))
  for (const course of filters.courses) {
    total += 1
    if (courseNames.includes(normalize(course))) matched += 1
  }

  const degrees = (student.courses ?? []).map((c) => normalize(c.degree ?? ''))
  for (const degree of filters.degrees) {
    total += 1
    if (degrees.includes(normalize(degree))) matched += 1
  }

  for (const interest of filters.interests) {
    total += 1
    if (studentHasInterest(student, interest)) matched += 1
  }

  const semesters = (student.courses ?? []).map((c) => c.semester)
  for (const sem of filters.semesters) {
    total += 1
    if (semesters.some((s) => semesterMatches([sem], s))) matched += 1
  }

  return { matched, total }
}

export const EVENT_NOTIF_STORAGE_KEY = 'studyshy-event-notifs'

const MS_HOUR = 60 * 60 * 1000

export type EventReminderKind = '1d' | '1h' | 'start'

export interface EventReminder {
  key: string
  eventId: number
  title: string
  kind: EventReminderKind
}

function loadNotifiedEventKeys(): Set<string> {
  try {
    const raw = localStorage.getItem(EVENT_NOTIF_STORAGE_KEY)
    return new Set(raw ? (JSON.parse(raw) as string[]) : [])
  } catch {
    return new Set()
  }
}

export function markEventNotified(key: string) {
  const set = loadNotifiedEventKeys()
  set.add(key)
  localStorage.setItem(EVENT_NOTIF_STORAGE_KEY, JSON.stringify([...set]))
}

/** Aktive Erinnerungen für Events, an denen der Nutzer teilnimmt. */
export function getActiveEventReminders(
  events: { id: number; title: string; startsAt: string; isAttending?: boolean }[],
): EventReminder[] {
  const notified = loadNotifiedEventKeys()
  const now = Date.now()
  const reminders: EventReminder[] = []

  for (const event of events) {
    if (!event.isAttending) continue

    const diff = new Date(event.startsAt).getTime() - now

    const checks: { kind: EventReminderKind; min: number; max: number }[] = [
      { kind: '1d', min: 23 * MS_HOUR, max: 25 * MS_HOUR },
      { kind: '1h', min: 55 * 60 * 1000, max: 65 * 60 * 1000 },
      { kind: 'start', min: -5 * 60 * 1000, max: 10 * 60 * 1000 },
    ]

    for (const { kind, min, max } of checks) {
      const key = `event-${event.id}-${kind}`
      if (diff >= min && diff <= max && !notified.has(key)) {
        reminders.push({ key, eventId: event.id, title: event.title, kind })
      }
    }
  }

  return reminders
}

export function eventReminderText(kind: EventReminderKind, title: string): string {
  switch (kind) {
    case '1d':
      return `"${title}" startet morgen`
    case '1h':
      return `"${title}" startet in 1 Stunde`
    case 'start':
      return `"${title}" startet jetzt`
  }
}
