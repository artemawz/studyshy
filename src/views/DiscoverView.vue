<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import FilterTag from '@/components/FilterTag.vue'
import TagList from '@/components/TagList.vue'
import MatchesList from '@/components/MatchesList.vue'
import StudentCard from '@/components/StudentCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import AppButton from '@/components/AppButton.vue'
import { useStudents } from '@/composables/useStudents'
import { useMeta } from '@/composables/useMeta'
import {
  filterStudents,
  getFilterMatchScore,
  getMatchPercent,
  getWeightedMatchScore,
  toggleSelection,
} from '@/misc'
import type { StudentFilters, User } from '@/types'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { currentUser, isLoggedIn } = useAuth()
const { students, loading, error, fetchStudents } = useStudents()
const { filterOptions, platformStats, fetchMeta } = useMeta()

const selectedUnis = ref<number[]>([])
const selectedCourses = ref<number[]>([])
const selectedDegrees = ref<number[]>([])
const selectedInterests = ref<number[]>([])
const selectedSemesters = ref<number[]>([])
const courseSearch = ref('')
const sortBy = ref<'match' | 'name'>('match')

onMounted(async () => {
  await Promise.all([fetchMeta(), fetchStudents()])
})

const activeFilters = computed<StudentFilters>(() => ({
  unis: selectedUnis.value.map((i) => filterOptions.value.unis[i]!),
  courses: selectedCourses.value.map((i) => filterOptions.value.courses[i]!),
  degrees: selectedDegrees.value.map((i) => filterOptions.value.degrees[i]!),
  interests: selectedInterests.value.map((i) => filterOptions.value.interests[i]!),
  semesters: selectedSemesters.value.map((i) => filterOptions.value.semesters[i]!),
}))

const COURSE_RESULT_LIMIT = 25

// Studiengänge erst nach Sucheingabe anzeigen (sonst zu lang), eingegrenzt auf die gewählte Hochschule.
const courseSearchActive = computed(() => courseSearch.value.trim().length > 0)

const allVisibleCourses = computed(() => {
  let names = filterOptions.value.courses

  const selectedUniNames = selectedUnis.value
    .map((i) => filterOptions.value.unis[i])
    .filter((u): u is string => Boolean(u))

  if (selectedUniNames.length > 0) {
    const allowed = new Set(
      selectedUniNames.flatMap((u) => filterOptions.value.coursesByUni?.[u] ?? []),
    )
    names = names.filter((n) => allowed.has(n))
  }

  const query = courseSearch.value.trim().toLowerCase()
  if (!query) return []

  names = names.filter((n) => n.toLowerCase().includes(query))

  return names.map((name) => ({ name, idx: filterOptions.value.courses.indexOf(name) }))
})

const visibleCourses = computed(() => allVisibleCourses.value.slice(0, COURSE_RESULT_LIMIT))

const courseOverflow = computed(() =>
  Math.max(0, allVisibleCourses.value.length - COURSE_RESULT_LIMIT),
)

// Bereits ausgewählte Studiengänge immer als Tag zeigen, auch ohne Suche.
const selectedCourseTags = computed(() =>
  selectedCourses.value
    .map((idx) => ({ name: filterOptions.value.courses[idx], idx }))
    .filter((c): c is { name: string; idx: number } => Boolean(c.name)),
)

const filteredStudents = computed(() => filterStudents(students.value, activeFilters.value))

function studentMatchScore(student: User) {
  // Eingeloggt: gewichtete Kompatibilität zum eigenen Profil (funktioniert auch ohne Filter).
  // Ausgeloggt: Rückfall auf filterbasierten Score (nur sinnvoll mit gesetzten Filtern).
  if (currentUser.value) {
    return getWeightedMatchScore(student, currentUser.value)
  }
  return getFilterMatchScore(student, activeFilters.value)
}

const sortedStudents = computed(() => {
  const list = [...filteredStudents.value]
  if (sortBy.value === 'name') {
    return list.sort((a, b) =>
      (a.pub_name ?? '').localeCompare(b.pub_name ?? '', 'de'),
    )
  }
  return list.sort((a, b) => getMatchPercent(studentMatchScore(b)) - getMatchPercent(studentMatchScore(a)))
})

const activeFilterChips = computed(() => {
  const chips: { label: string; group: keyof typeof toggles; idx: number }[] = []
  selectedUnis.value.forEach((idx) =>
    chips.push({ label: filterOptions.value.unis[idx]!, group: 'unis', idx }),
  )
  selectedCourses.value.forEach((idx) =>
    chips.push({ label: filterOptions.value.courses[idx]!, group: 'courses', idx }),
  )
  selectedDegrees.value.forEach((idx) =>
    chips.push({ label: filterOptions.value.degrees[idx]!, group: 'degrees', idx }),
  )
  selectedInterests.value.forEach((idx) =>
    chips.push({ label: filterOptions.value.interests[idx]!, group: 'interests', idx }),
  )
  selectedSemesters.value.forEach((idx) =>
    chips.push({ label: `Sem. ${filterOptions.value.semesters[idx]}`, group: 'semesters', idx }),
  )
  return chips
})

const toggles = {
  unis: selectedUnis,
  courses: selectedCourses,
  degrees: selectedDegrees,
  interests: selectedInterests,
  semesters: selectedSemesters,
}

const hasActiveFilters = computed(() => activeFilterChips.value.length > 0)

function scrollToFilters() {
  document.getElementById('filter')?.scrollIntoView({ behavior: 'smooth' })
}

function studentClicked(id: number) {
  router.push({ name: 'profile-view', params: { id: id.toString() } })
}

function clearFilters() {
  selectedUnis.value = []
  selectedCourses.value = []
  selectedDegrees.value = []
  selectedInterests.value = []
  selectedSemesters.value = []
}

function removeChip(group: keyof typeof toggles, idx: number) {
  toggles[group].value = toggleSelection(toggles[group].value, idx)
}
</script>

<template>
  <div class="page">
    <section v-if="!isLoggedIn" id="why" class="hero">
      <h1>Warum<i>Studyshy</i>?</h1>
      <p>
        Findest auch du es schwer, während dem Studium neue Leute kennenzulernen, Freundschaften zu
        schließen und dich mit Kommilitonen zu vernetzen? Studyshy hilft dir dabei – anonym und auf
        dich zugeschnitten.
      </p>
      <div class="btn-group">
        <AppButton @click="scrollToFilters">Jetzt Matching starten</AppButton>
        <RouterLink to="/why-studyshy" class="btn btn-secondary">Mehr erfahren</RouterLink>
      </div>
    </section>

    <section id="stats" class="section">
      <p v-if="loading" class="loading-hint">Daten werden geladen…</p>
      <p v-if="error" class="form-error">{{ error }}</p>
      <dl class="stat-list">
        <div class="stat-card">
          <dt class="stat-title">Studierende</dt>
          <dd class="stat-value">{{ platformStats.students }}</dd>
          <div class="stat-icon">&#129489;</div>
        </div>
        <div class="stat-card">
          <dt class="stat-title">Hochschulen</dt>
          <dd class="stat-value">{{ platformStats.universities }}</dd>
          <div class="stat-icon">🎓</div>
        </div>
        <div v-if="!isLoggedIn" class="stat-card">
          <dt class="stat-title">Studenten verbunden</dt>
          <dd class="stat-value">{{ platformStats.connections }}</dd>
          <div class="stat-icon">🤝</div>
        </div>
        <div class="stat-card">
          <dt class="stat-title">Anonym</dt>
          <dd class="stat-value">{{ platformStats.anonymous }}</dd>
          <div class="stat-icon">🔒</div>
        </div>
      </dl>
    </section>

    <section id="filter" class="section">
      <h2 class="section-title">Finde andere Studierende und schließe Freundschaften!</h2>

      <TagList title="Universität/Hochschule">
        <FilterTag
          v-for="(uni, idx) in filterOptions.unis"
          :key="uni"
          :text="uni"
          :selected="selectedUnis.includes(idx)"
          @click="selectedUnis = toggleSelection(selectedUnis, idx)"
        />
      </TagList>

      <TagList title="Studiengänge">
        <div class="course-search">
          <input
            v-model="courseSearch"
            type="search"
            class="course-search-input"
            placeholder="Studiengang suchen…"
          />
        </div>

        <FilterTag
          v-for="c in selectedCourseTags"
          :key="`sel-${c.name}`"
          :text="c.name"
          selected
          @click="selectedCourses = toggleSelection(selectedCourses, c.idx)"
        />

        <FilterTag
          v-for="c in visibleCourses"
          v-show="!selectedCourses.includes(c.idx)"
          :key="c.name"
          :text="c.name"
          :selected="false"
          @click="selectedCourses = toggleSelection(selectedCourses, c.idx)"
        />

        <p v-if="!courseSearchActive" class="no-courses">
          Tippe oben, um Studiengänge zu suchen.
        </p>
        <p v-else-if="allVisibleCourses.length === 0" class="no-courses">
          Keine Studiengänge gefunden.
        </p>
        <p v-else-if="courseOverflow > 0" class="no-courses">
          … {{ courseOverflow }} weitere – Suche verfeinern.
        </p>
      </TagList>

      <TagList title="Abschluss">
        <FilterTag
          v-for="(degree, idx) in filterOptions.degrees"
          :key="degree"
          :text="degree"
          :selected="selectedDegrees.includes(idx)"
          @click="selectedDegrees = toggleSelection(selectedDegrees, idx)"
        />
      </TagList>

      <TagList title="Interessen">
        <FilterTag
          v-for="(interest, idx) in filterOptions.interests"
          :key="interest"
          :text="interest"
          :selected="selectedInterests.includes(idx)"
          @click="selectedInterests = toggleSelection(selectedInterests, idx)"
        />
      </TagList>

      <TagList title="Semester">
        <FilterTag
          v-for="(semester, idx) in filterOptions.semesters"
          :key="semester"
          :text="semester"
          :selected="selectedSemesters.includes(idx)"
          @click="selectedSemesters = toggleSelection(selectedSemesters, idx)"
        />
      </TagList>

      <div v-if="hasActiveFilters" class="active-filters">
        <span class="active-label">Aktive Filter:</span>
        <button
          v-for="chip in activeFilterChips"
          :key="`${chip.group}-${chip.idx}`"
          class="filter-chip"
          @click="removeChip(chip.group, chip.idx)"
        >
          {{ chip.label }} ×
        </button>
        <AppButton variant="secondary" @click="clearFilters">Alle löschen</AppButton>
      </div>
    </section>

    <section id="results" class="section">
      <div class="results-toolbar">
        <div class="sort-control">
          <label for="sort">Sortieren:</label>
          <select id="sort" v-model="sortBy">
            <option value="match">Beste Übereinstimmung</option>
            <option value="name">Name</option>
          </select>
        </div>
      </div>

      <MatchesList :count="sortedStudents.length">
        <StudentCard
          v-for="student in sortedStudents"
          :key="student.id"
          :student="student"
          :match-score="studentMatchScore(student)"
          @click="studentClicked(student.id)"
        />
      </MatchesList>

      <EmptyState
        v-if="sortedStudents.length === 0"
        title="Keine Matches gefunden"
        hint="Passe deine Filter an oder setze sie zurück, um mehr Studierende zu sehen."
      />
    </section>
  </div>
</template>

<style scoped>
.hero {
  text-align: center;
  padding-top: 1rem;
}

.hero h1 {
  margin-top: 0;
  color: var(--color-text);
}

.hero i {
  margin: 0 0.5rem;
  padding: 0.5rem;
  text-decoration: underline;
  font-style: italic;
  color: var(--color-accent);
  border: 1px dashed var(--color-border);
  border-radius: 0.5rem;
}

.hero p {
  font-size: 1.2rem;
  max-width: 640px;
  margin: 0 auto 1.5rem;
  color: var(--color-text);
}

.stat-list {
  display: flex;
  flex-direction: row;
  justify-content: center;
  gap: 1rem;
  padding: 0;
  flex-wrap: wrap;
  width: 100%;
  margin: 0;
}

.stat-card {
  display: flex;
  flex-direction: column-reverse;
  align-items: start;
  padding: 2rem 4rem 1rem 1rem;
  background: #ffffff08;
  border: 1px solid var(--color-border-subtle);
  border-radius: 0.5rem;
  width: 20%;
  min-width: 140px;
  max-width: 180px;
}

.stat-title {
  color: var(--color-text-muted);
}

.stat-value {
  font-size: 1.6rem;
  font-weight: bold;
  margin: 0;
  color: var(--color-text);
}

.active-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  justify-content: center;
  margin-top: 1.5rem;
  padding: 1rem;
  border: 1px dashed var(--color-border);
  border-radius: 0.5rem;
}

.active-label {
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

.filter-chip {
  background: var(--color-accent-muted);
  color: var(--color-text);
  border: 1px solid var(--color-accent);
  border-radius: 100rem;
  padding: 0.25rem 0.75rem;
  font-size: 0.85rem;
  cursor: pointer;
}

.filter-chip:hover {
  background: var(--color-accent-hover);
}

.results-toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 0.5rem;
}

.sort-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sort-control label {
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

.sort-control select {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.4rem;
  padding: 0.4rem 0.6rem;
  color: var(--color-text);
  font-family: inherit;
}

.section-title {
  color: var(--color-text);
}

.course-search {
  flex-basis: 100%;
  display: flex;
  justify-content: center;
  margin-bottom: 0.5rem;
}

.course-search-input {
  width: 100%;
  max-width: 360px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 100rem;
  padding: 0.5rem 1rem;
  color: var(--color-text);
  font-family: inherit;
  font-size: 0.95rem;
}

.course-search-input:focus {
  outline: none;
  border-color: var(--color-accent);
}

.no-courses {
  flex-basis: 100%;
  text-align: center;
  color: var(--color-text-muted);
  font-size: 0.9rem;
  margin: 0;
}

.loading-hint {
  text-align: center;
  color: var(--color-text-muted);
  margin-bottom: 1rem;
}
</style>
