<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import FormField from '@/components/FormField.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useEvents } from '@/composables/useEvents'
import { useMeta } from '@/composables/useMeta'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/services/api'

const router = useRouter()
const { events, loading, fetchEvents, createEvent, joinEvent, leaveEvent, deleteEvent } = useEvents()
const { filterOptions, fetchMeta } = useMeta()
const { isLoggedIn } = useAuth()
const { show } = useToast()

const showForm = ref(false)
const submitting = ref(false)
const expandedEvents = ref<Set<number>>(new Set())
const form = ref({ title: '', description: '', location: '', uni: '', starts_at: '' })

onMounted(() => {
  fetchEvents()
  fetchMeta()
})

function toggleExpand(eventId: number) {
  const next = new Set(expandedEvents.value)
  if (next.has(eventId)) next.delete(eventId)
  else next.add(eventId)
  expandedEvents.value = next
}

function participantName(p: { pub_name?: string; id: number }) {
  return p.pub_name ?? `Student #${p.id.toString(16)}`
}

function openProfile(userId: number) {
  router.push({ name: 'profile-view', params: { id: userId.toString() } })
}

async function submitEvent() {
  if (!form.value.title.trim() || !form.value.starts_at) {
    show('Bitte Titel und Datum angeben.', 'error')
    return
  }
  submitting.value = true
  try {
    await createEvent({
      title: form.value.title.trim(),
      description: form.value.description.trim() || undefined,
      location: form.value.location.trim() || undefined,
      uni: form.value.uni || undefined,
      starts_at: new Date(form.value.starts_at).toISOString(),
    })
    form.value = { title: '', description: '', location: '', uni: '', starts_at: '' }
    showForm.value = false
    show('Event erstellt.', 'success')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Event konnte nicht erstellt werden.', 'error')
  } finally {
    submitting.value = false
  }
}

async function toggleAttend(event: (typeof events.value)[number]) {
  try {
    if (event.isAttending) {
      await leaveEvent(event.id)
      show('Teilnahme zurückgezogen.', 'info')
    } else {
      await joinEvent(event.id)
      show('Du nimmst teil!', 'success')
    }
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Aktion fehlgeschlagen.', 'error')
  }
}

async function handleDelete(id: number) {
  if (!confirm('Event wirklich löschen?')) return
  try {
    await deleteEvent(id)
    show('Event gelöscht.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Event konnte nicht gelöscht werden.', 'error')
  }
}

function formatDate(value: string) {
  return new Date(value).toLocaleString('de-DE', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="page-narrow">
    <div class="events-header">
      <div>
        <h1 class="page-title">Events & Treffen</h1>
        <p class="page-subtitle">Lerntreffen, Stammtische und Campus-Events – finde Anschluss.</p>
      </div>
      <AppButton v-if="isLoggedIn" @click="showForm = !showForm">
        {{ showForm ? 'Abbrechen' : 'Event erstellen' }}
      </AppButton>
    </div>

    <p v-if="!isLoggedIn" class="hint-box card">Melde dich an, um Events zu erstellen oder teilzunehmen.</p>

    <form v-if="showForm && isLoggedIn" class="card event-form" @submit.prevent="submitEvent">
      <FormField v-model="form.title" label="Titel" placeholder="z. B. Gemeinsames Lernen in der Bib" />
      <FormField v-model="form.description" label="Beschreibung" as="textarea" placeholder="Worum geht es?" />
      <FormField v-model="form.location" label="Ort" placeholder="z. B. Mensa-Vorplatz" />
      <FormField v-model="form.uni" label="Hochschule (optional)" as="select">
        <template #options>
          <option value="">Keine Angabe</option>
          <option v-for="uni in filterOptions.unis" :key="uni" :value="uni">{{ uni }}</option>
        </template>
      </FormField>
      <FormField v-model="form.starts_at" label="Datum & Uhrzeit" type="datetime-local" />
      <AppButton type="submit" :disabled="submitting">
        {{ submitting ? 'Wird erstellt…' : 'Event erstellen' }}
      </AppButton>
    </form>

    <EmptyState v-if="loading && !events.length" title="Events werden geladen…" />
    <EmptyState
      v-else-if="!events.length"
      title="Keine anstehenden Events"
      hint="Erstelle das erste Event und lade andere ein."
    />

    <ul v-else class="event-list">
      <li v-for="event in events" :key="event.id" class="card event-item" :class="{ expanded: expandedEvents.has(event.id) }">
        <button type="button" class="event-toggle" @click="toggleExpand(event.id)">
          <div class="event-date">{{ formatDate(event.startsAt) }}</div>
          <div class="event-title-row">
            <h3 class="event-title">{{ event.title }}</h3>
            <span class="expand-icon">{{ expandedEvents.has(event.id) ? '▲' : '▼' }}</span>
          </div>
          <p v-if="event.description && !expandedEvents.has(event.id)" class="event-desc clamp">
            {{ event.description }}
          </p>
          <div class="event-meta">
            <span v-if="event.location">📍 {{ event.location }}</span>
            <span v-if="event.uni">🎓 {{ event.uni }}</span>
            <span>👥 {{ event.participantCount ?? 0 }} dabei</span>
          </div>
        </button>

        <div v-if="expandedEvents.has(event.id)" class="event-details">
          <p v-if="event.description" class="event-desc">{{ event.description }}</p>

          <div class="participants">
            <h4 class="participants-title">Teilnehmende</h4>
            <EmptyState
              v-if="!event.participants?.length"
              title="Noch niemand dabei"
              hint="Sei die erste Person!"
            />
            <ul v-else class="participant-list list-scroll-4">
              <li v-for="p in event.participants" :key="p.id">
                <button type="button" class="participant" @click="openProfile(p.id)">
                  <img v-if="p.avatarUrl" :src="p.avatarUrl" class="participant-avatar" alt="" />
                  <span v-else class="participant-avatar placeholder">{{ participantName(p).charAt(0) }}</span>
                  <span class="participant-name">{{ participantName(p) }}</span>
                </button>
              </li>
            </ul>
          </div>
        </div>

        <div class="event-footer">
          <span class="creator">von {{ event.creatorName ?? 'jemandem' }}</span>
          <div class="event-actions">
            <AppButton
              v-if="isLoggedIn && !event.isOwner"
              :variant="event.isAttending ? 'secondary' : 'primary'"
              @click.stop="toggleAttend(event)"
            >
              {{ event.isAttending ? 'Absagen' : 'Teilnehmen' }}
            </AppButton>
            <AppButton v-if="event.isOwner" variant="secondary" @click.stop="handleDelete(event.id)">
              Löschen
            </AppButton>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.events-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.hint-box {
  margin-top: 1rem;
  color: var(--color-text-muted);
}

.event-form {
  margin: 1rem 0 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.event-list {
  list-style: none;
  padding: 0;
  margin: 1.5rem 0 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.event-item {
  padding: 0;
  overflow: hidden;
}

.event-toggle {
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

.event-toggle:hover {
  background: var(--color-bg);
}

.event-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.expand-icon {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  flex-shrink: 0;
}

.event-desc.clamp {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.event-details {
  padding: 0 1rem 0.5rem;
  border-top: 1px solid var(--color-border-subtle);
}

.participants {
  margin-top: 1rem;
}

.participants-title {
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
  color: var(--color-text-muted);
}

.participant-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.participant {
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

.participant:hover {
  background: var(--color-bg);
}

.participant-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.participant-avatar.placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
  text-transform: uppercase;
}

.participant-name {
  font-weight: 600;
  color: var(--color-accent);
}

.participant:hover .participant-name {
  text-decoration: underline;
}

.event-date {
  color: var(--color-accent);
  font-weight: 700;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.event-title {
  margin: 0 0 0.4rem;
  font-size: 1.15rem;
}

.event-desc {
  margin: 0 0 0.75rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  white-space: pre-wrap;
}

.event-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  font-size: 0.85rem;
  color: var(--color-text-muted);
  margin-bottom: 1rem;
}

.event-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 0.75rem 1rem 1rem;
  border-top: 1px solid var(--color-border-subtle);
}

.creator {
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.event-actions {
  display: flex;
  gap: 0.5rem;
}
</style>
