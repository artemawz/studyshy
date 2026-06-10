<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FilterTag from '@/components/FilterTag.vue'
import TagList from '@/components/TagList.vue'
import AppButton from '@/components/AppButton.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useStudents } from '@/composables/useStudents'
import { useAuth } from '@/composables/useAuth'
import { getUserTags } from '@/misc'

const route = useRoute()
const router = useRouter()
const { getStudentById } = useStudents()
const { isLoggedIn } = useAuth()

const studentId = computed(() => parseInt(route.params.id as string, 10))
const student = computed(() => getStudentById(studentId.value))

const displayName = computed(
  () => student.value?.pub_name ?? `Student #${studentId.value.toString(16)}`,
)

function startChat() {
  if (!isLoggedIn.value) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }
  router.push({ name: 'chat-view', params: { id: studentId.value.toString() } })
}
</script>

<template>
  <div v-if="student" class="page-narrow">
    <div class="card profile">
      <img v-if="student.avatarUrl" :src="student.avatarUrl" width="96" height="96" alt="student-avatar" class="avatar" />
      <h1 class="page-title">{{ displayName }}</h1>
      <p class="uni">{{ student.uni }}</p>

      <p v-if="student.bio" class="bio">{{ student.bio }}</p>

      <TagList title="Studiengang & Interessen">
        <FilterTag v-for="tag in getUserTags(student)" :key="tag" :text="tag" />
      </TagList>

      <div class="actions">
        <AppButton @click="startChat">Nachricht senden</AppButton>
        <RouterLink to="/" class="btn btn-secondary">Zurück zur Suche</RouterLink>
      </div>
    </div>
  </div>

  <div v-else class="page-narrow">
    <EmptyState title="Profil nicht gefunden" hint="Dieser Studierende existiert nicht oder wurde entfernt.">
      <RouterLink to="/" class="btn" style="margin-top: 1rem">Zur Startseite</RouterLink>
    </EmptyState>
  </div>
</template>

<style scoped>
.profile {
  text-align: center;
}

.avatar {
  border-radius: 50%;
  object-fit: cover;
}

.uni {
  color: var(--color-text-muted);
  margin: -1rem 0 1.5rem;
}

.bio {
  text-align: left;
  line-height: 1.6;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: var(--color-bg);
  border-radius: 0.4rem;
  border: 1px solid var(--color-border-subtle);
}

.actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
  flex-wrap: wrap;
}
</style>
