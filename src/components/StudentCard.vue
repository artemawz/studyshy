<script setup lang="ts">
import { computed } from 'vue'
import { type User } from '@/types'
import { getUserTags, type MatchScore } from '@/misc'
import FilterTag from './FilterTag.vue'
import TagList from './TagList.vue'

const props = defineProps<{
  student: User
  matchScore?: MatchScore
}>()

const displayName = computed(
  () => props.student.pub_name ?? `Student #${props.student.id.toString(16)}`,
)
const displayUni = computed(() => props.student.uni ?? 'Nicht eingeschrieben')
const tags = computed(() => getUserTags(props.student))

const matchLabel = computed(() => {
  if (!props.matchScore || props.matchScore.total === 0) return null
  const pct = Math.round((props.matchScore.matched / props.matchScore.total) * 100)
  return `${pct}% Match`
})
</script>

<template>
  <div class="student-card">
    <span v-if="matchLabel" class="match-badge">{{ matchLabel }}</span>
    <img v-if="student.avatarUrl" :src="student.avatarUrl" width="64" height="64" alt="" />
    <p class="name">{{ displayName }}</p>
    <p class="uni">{{ displayUni }}</p>
    <TagList>
      <FilterTag v-for="tag in tags" :key="tag" :text="tag" />
    </TagList>
  </div>
</template>

<style scoped>
.student-card {
  position: relative;
  padding: 1rem;
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  cursor: pointer;
  transition:
    border-color 150ms ease,
    transform 150ms ease;
}

.student-card:hover {
  border-color: var(--color-accent);
  transform: translateY(-2px);
}

.match-badge {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  font-size: 0.75rem;
  font-weight: bold;
  color: var(--color-accent);
  background: var(--color-accent-muted);
  padding: 0.2rem 0.5rem;
  border-radius: 100rem;
}

img {
  border-radius: 50%;
  object-fit: cover;
}

.name {
  font-weight: bold;
  margin: 0.75rem 0 0.25rem;
  color: var(--color-text);
}

.uni {
  color: var(--color-text-muted);
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
}
</style>
