<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import FormField from '@/components/FormField.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useGroups } from '@/composables/useGroups'
import { useMeta } from '@/composables/useMeta'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/services/api'

const router = useRouter()
const { groups, loading, loaded, fetchGroups, createGroup, joinGroup, leaveGroup, deleteGroup } = useGroups()
const { filterOptions, fetchMeta } = useMeta()
const { isLoggedIn } = useAuth()
const { show } = useToast()

const showForm = ref(false)
const submitting = ref(false)
const expandedGroups = ref<Set<number>>(new Set())
const form = ref({ name: '', description: '', uni: '', course: '' })
const searchQuery = ref('')

onMounted(() => {
  fetchGroups()
  fetchMeta()
})

const filteredGroups = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return groups.value
  return groups.value.filter((g) => g.name.toLowerCase().includes(query))
})

const myGroups = computed(() => filteredGroups.value.filter((g) => g.isMember))
const otherGroups = computed(() => filteredGroups.value.filter((g) => !g.isMember))

const coursesForUni = computed(() => {
  if (!form.value.uni) return filterOptions.value.courses
  return filterOptions.value.coursesByUni[form.value.uni] ?? []
})

function toggleExpand(groupId: number) {
  const next = new Set(expandedGroups.value)
  if (next.has(groupId)) next.delete(groupId)
  else next.add(groupId)
  expandedGroups.value = next
}

function memberName(m: { pub_name?: string; id: number }) {
  return m.pub_name ?? `Student #${m.id.toString(16)}`
}

function openProfile(userId: number) {
  router.push({ name: 'profile-view', params: { id: userId.toString() } })
}

async function submitGroup() {
  if (form.value.name.trim().length < 3) {
    show('Der Name muss mindestens 3 Zeichen haben.', 'error')
    return
  }
  submitting.value = true
  try {
    const group = await createGroup({
      name: form.value.name.trim(),
      description: form.value.description.trim() || undefined,
      uni: form.value.uni || undefined,
      course: form.value.course || undefined,
    })
    form.value = { name: '', description: '', uni: '', course: '' }
    showForm.value = false
    show('Gruppe erstellt.', 'success')
    router.push({ name: 'group-chat', params: { id: group.id.toString() } })
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Gruppe konnte nicht erstellt werden.', 'error')
  } finally {
    submitting.value = false
  }
}

async function handleJoin(id: number) {
  try {
    await joinGroup(id)
    show('Gruppe beigetreten.', 'success')
    expandedGroups.value = new Set([...expandedGroups.value, id])
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Beitritt fehlgeschlagen.', 'error')
  }
}

async function handleLeave(id: number) {
  if (!confirm('Gruppe wirklich verlassen?')) return
  try {
    await leaveGroup(id)
    const next = new Set(expandedGroups.value)
    next.delete(id)
    expandedGroups.value = next
    show('Gruppe verlassen.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Aktion fehlgeschlagen.', 'error')
  }
}

async function handleDelete(id: number) {
  if (!confirm('Gruppe wirklich löschen?')) return
  try {
    await deleteGroup(id)
    const next = new Set(expandedGroups.value)
    next.delete(id)
    expandedGroups.value = next
    show('Gruppe gelöscht.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Gruppe konnte nicht gelöscht werden.', 'error')
  }
}

function openChat(id: number) {
  router.push({ name: 'group-chat', params: { id: id.toString() } })
}
</script>

<template>
  <div class="page-narrow">
    <div class="groups-header">
      <h1 class="page-title">Lerngruppen</h1>
      <p class="page-subtitle">Tritt Gruppen bei oder gründe deine eigene – lernen, austauschen, vernetzen.</p>
    </div>

    <p v-if="!isLoggedIn" class="hint-box card">Melde dich an, um Gruppen beizutreten oder zu gründen.</p>

    <div v-if="isLoggedIn" class="toolbar-row">
      <input
        v-model="searchQuery"
        type="search"
        class="search-input"
        placeholder="Gruppen nach Namen durchsuchen…"
      />
      <AppButton @click="showForm = !showForm">
        {{ showForm ? 'Abbrechen' : 'Gruppe gründen' }}
      </AppButton>
    </div>

    <form v-if="showForm && isLoggedIn" class="card group-form" @submit.prevent="submitGroup">
      <FormField v-model="form.name" label="Name" placeholder="z. B. Analysis I – Lerngruppe" />
      <FormField v-model="form.description" label="Beschreibung" as="textarea" placeholder="Worum geht's in der Gruppe?" />
      <FormField v-model="form.uni" label="Hochschule (optional)" as="select">
        <template #options>
          <option value="">Keine Angabe</option>
          <option v-for="uni in filterOptions.unis" :key="uni" :value="uni">{{ uni }}</option>
        </template>
      </FormField>
      <FormField v-model="form.course" label="Studiengang (optional)" as="select">
        <template #options>
          <option value="">Keine Angabe</option>
          <option v-for="course in coursesForUni" :key="course" :value="course">{{ course }}</option>
        </template>
      </FormField>
      <AppButton type="submit" :disabled="submitting">
        {{ submitting ? 'Wird erstellt…' : 'Gruppe gründen' }}
      </AppButton>
    </form>

    <EmptyState v-if="!loaded && loading" title="Gruppen werden geladen…" />

    <template v-else>
      <section v-if="myGroups.length" class="section">
        <h2 class="section-title">Deine Gruppen</h2>
        <ul class="group-list">
          <li
            v-for="group in myGroups"
            :key="group.id"
            class="card group-item"
            :class="{ expanded: expandedGroups.has(group.id), unread: group.unread }"
          >
            <button type="button" class="group-toggle" @click="toggleExpand(group.id)">
              <div class="group-title-row">
                <span class="group-name-wrap">
                  <h3 class="group-name">{{ group.name }}</h3>
                  <span v-if="group.unread" class="badge">Neu</span>
                </span>
                <span class="expand-icon">{{ expandedGroups.has(group.id) ? '▲' : '▼' }}</span>
              </div>
              <p v-if="group.description && !expandedGroups.has(group.id)" class="group-desc clamp">
                {{ group.description }}
              </p>
              <div class="group-meta">
                <span v-if="group.course">{{ group.course }}</span>
                <span v-if="group.uni">{{ group.uni }}</span>
                <span>👥 {{ group.memberCount ?? group.members?.length ?? 0 }}</span>
              </div>
            </button>

            <div v-if="expandedGroups.has(group.id)" class="group-details">
              <p v-if="group.description" class="group-desc">{{ group.description }}</p>
              <div class="members">
                <h4 class="members-title">Mitglieder</h4>
                <EmptyState
                  v-if="!group.members?.length"
                  title="Noch keine Mitglieder"
                  hint="Lade andere ein!"
                />
                <ul v-else class="member-list list-scroll-4">
                  <li v-for="m in group.members" :key="m.id">
                    <button type="button" class="member" @click="openProfile(m.id)">
                      <img v-if="m.avatarUrl" :src="m.avatarUrl" class="member-avatar" alt="" />
                      <span v-else class="member-avatar placeholder">{{ memberName(m).charAt(0) }}</span>
                      <span class="member-name">{{ memberName(m) }}</span>
                      <span v-if="m.role === 'owner'" class="member-role">Admin</span>
                    </button>
                  </li>
                </ul>
              </div>
            </div>

            <div class="group-footer">
              <AppButton @click.stop="openChat(group.id)">Öffnen</AppButton>
              <AppButton v-if="group.isOwner" variant="secondary" @click.stop="handleDelete(group.id)">
                Löschen
              </AppButton>
              <AppButton v-else variant="secondary" @click.stop="handleLeave(group.id)">Verlassen</AppButton>
            </div>
          </li>
        </ul>
      </section>

      <section class="section">
        <h2 class="section-title">Gruppen entdecken</h2>
        <EmptyState
          v-if="!otherGroups.length && searchQuery.trim()"
          title="Keine Gruppen gefunden"
          hint="Passe deine Suche an oder gründe eine neue Gruppe."
        />
        <EmptyState
          v-else-if="!otherGroups.length"
          title="Keine weiteren Gruppen"
          hint="Gründe eine neue Gruppe und lade andere ein."
        />
        <ul v-else class="group-list">
          <li
            v-for="group in otherGroups"
            :key="group.id"
            class="card group-item"
            :class="{ expanded: expandedGroups.has(group.id) }"
          >
            <button type="button" class="group-toggle" @click="toggleExpand(group.id)">
              <div class="group-title-row">
                <h3 class="group-name">{{ group.name }}</h3>
                <span class="expand-icon">{{ expandedGroups.has(group.id) ? '▲' : '▼' }}</span>
              </div>
              <p v-if="group.description && !expandedGroups.has(group.id)" class="group-desc clamp">
                {{ group.description }}
              </p>
              <div class="group-meta">
                <span v-if="group.course">{{ group.course }}</span>
                <span v-if="group.uni">{{ group.uni }}</span>
                <span>👥 {{ group.memberCount ?? group.members?.length ?? 0 }}</span>
              </div>
            </button>

            <div v-if="expandedGroups.has(group.id)" class="group-details">
              <p v-if="group.description" class="group-desc">{{ group.description }}</p>
              <div class="members">
                <h4 class="members-title">Mitglieder</h4>
                <EmptyState
                  v-if="!group.members?.length"
                  title="Noch keine Mitglieder"
                  hint="Sei die erste Person!"
                />
                <ul v-else class="member-list list-scroll-4">
                  <li v-for="m in group.members" :key="m.id">
                    <button type="button" class="member" @click="openProfile(m.id)">
                      <img v-if="m.avatarUrl" :src="m.avatarUrl" class="member-avatar" alt="" />
                      <span v-else class="member-avatar placeholder">{{ memberName(m).charAt(0) }}</span>
                      <span class="member-name">{{ memberName(m) }}</span>
                      <span v-if="m.role === 'owner'" class="member-role">Admin</span>
                    </button>
                  </li>
                </ul>
              </div>
            </div>

            <div class="group-footer">
              <AppButton v-if="isLoggedIn" @click.stop="handleJoin(group.id)">Beitreten</AppButton>
            </div>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>

<style scoped>
.groups-header {
  margin-bottom: 1rem;
}

.hint-box {
  margin-top: 1rem;
  color: var(--color-text-muted);
}

.toolbar-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 1rem 0;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 200px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 100rem;
  padding: 0.55rem 1.1rem;
  color: var(--color-text);
  font-family: inherit;
  font-size: 0.95rem;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-accent);
}

.group-form {
  margin: 1rem 0 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.section {
  margin-top: 1.5rem;
}

.section-title {
  font-size: 1.1rem;
  margin-bottom: 0.75rem;
}

.group-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.group-item {
  padding: 0;
  overflow: hidden;
}

.group-toggle {
  display: block;
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  padding: 1rem 1rem 0.5rem;
  font: inherit;
  color: inherit;
  cursor: pointer;
}

.group-toggle:hover {
  background: var(--color-bg);
}

.group-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.group-name-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}

.group-name {
  margin: 0;
  font-size: 1.1rem;
  color: var(--color-accent);
}

.group-item.unread {
  border-color: var(--color-accent);
  background: #b9aaff11;
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  background: var(--color-accent-hover);
  color: #fff;
  font-size: 0.7rem;
  font-weight: 700;
  line-height: 1.4;
  flex-shrink: 0;
}

.expand-icon {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  flex-shrink: 0;
}

.group-desc {
  margin: 0.4rem 0 0.5rem;
  color: var(--color-text-muted);
  line-height: 1.4;
  white-space: pre-wrap;
}

.group-desc.clamp {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.group-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.group-details {
  padding: 0 1rem 0.5rem;
  border-top: 1px solid var(--color-border-subtle);
}

.members {
  margin-top: 1rem;
}

.members-title {
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
  color: var(--color-text-muted);
}

.member-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.member {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: none;
  border: none;
  padding: 0.4rem 0.5rem;
  border-radius: 0.4rem;
  font: inherit;
  cursor: pointer;
  width: 100%;
  text-align: left;
}

.member:hover {
  background: var(--color-bg);
}

.member-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.member-avatar.placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
  text-transform: uppercase;
}

.member-name {
  font-weight: 600;
  color: var(--color-accent);
}

.member:hover .member-name {
  text-decoration: underline;
}

.member-role {
  margin-left: auto;
  font-size: 0.75rem;
  color: var(--color-text-muted);
  background: var(--color-accent-muted);
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
}

.group-footer {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  padding: 0.75rem 1rem 1rem;
  border-top: 1px solid var(--color-border-subtle);
}
</style>
