<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import FilterTag from '@/components/FilterTag.vue'
import TagList from '@/components/TagList.vue'
import MatchesList from '@/components/MatchesList.vue'
import StudentCard from '@/components/StudentCard.vue'
import EmptyState from '@/components/EmptyState.vue'
import AppButton from '@/components/AppButton.vue'
import { filterOptions, platformStats } from '@/data/mockData'
import { useStudents } from '@/composables/useStudents'
import { filterStudents, getMatchScore, toggleSelection } from '@/misc'
import type { StudentFilters } from '@/types'

const router = useRouter()
const { students } = useStudents()

const selectedUnis = ref<number[]>([])
const selectedCourses = ref<number[]>([])
const selectedInterests = ref<number[]>([])
const selectedSemesters = ref<number[]>([])
const sortBy = ref<'match' | 'name'>('match')

const activeFilters = computed<StudentFilters>(() => ({
  unis: selectedUnis.value.map((i) => filterOptions.unis[i]!),
  courses: selectedCourses.value.map((i) => filterOptions.courses[i]!),
  interests: selectedInterests.value.map((i) => filterOptions.interests[i]!),
  semesters: selectedSemesters.value.map((i) => filterOptions.semesters[i]!),
}))

const filteredStudents = computed(() => filterStudents(students, activeFilters.value))

const sortedStudents = computed(() => {
  const list = [...filteredStudents.value]
  if (sortBy.value === 'name') {
    return list.sort((a, b) =>
      (a.pub_name ?? '').localeCompare(b.pub_name ?? '', 'de'),
    )
  }
  return list.sort((a, b) => {
    const scoreA = getMatchScore(a, activeFilters.value)
    const scoreB = getMatchScore(b, activeFilters.value)
    const pctA = scoreA.total ? scoreA.matched / scoreA.total : 0
    const pctB = scoreB.total ? scoreB.matched / scoreB.total : 0
    return pctB - pctA
  })
})

const activeFilterChips = computed(() => {
  const chips: { label: string; group: keyof typeof toggles; idx: number }[] = []
  selectedUnis.value.forEach((idx) =>
    chips.push({ label: filterOptions.unis[idx]!, group: 'unis', idx }),
  )
  selectedCourses.value.forEach((idx) =>
    chips.push({ label: filterOptions.courses[idx]!, group: 'courses', idx }),
  )
  selectedInterests.value.forEach((idx) =>
    chips.push({ label: filterOptions.interests[idx]!, group: 'interests', idx }),
  )
  selectedSemesters.value.forEach((idx) =>
    chips.push({ label: `Sem. ${filterOptions.semesters[idx]}`, group: 'semesters', idx }),
  )
  return chips
})

const toggles = {
  unis: selectedUnis,
  courses: selectedCourses,
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
  selectedInterests.value = []
  selectedSemesters.value = []
}

function removeChip(group: keyof typeof toggles, idx: number) {
  toggles[group].value = toggleSelection(toggles[group].value, idx)
}
</script>

<template>
  <div class="page">
    <section id="why" class="hero">
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
        <div class="stat-card">
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
        <FilterTag
          v-for="(courseName, idx) in filterOptions.courses"
          :key="courseName"
          :text="courseName"
          :selected="selectedCourses.includes(idx)"
          @click="selectedCourses = toggleSelection(selectedCourses, idx)"
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
          :match-score="getMatchScore(student, activeFilters)"
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
</style>
