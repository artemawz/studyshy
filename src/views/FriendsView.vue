<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import EmptyState from '@/components/EmptyState.vue'
import AppButton from '@/components/AppButton.vue'
import { useFriends } from '@/composables/useFriends'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/services/api'

const router = useRouter()
const {
  friends,
  incoming,
  outgoing,
  loading,
  loaded,
  error,
  fetchFriends,
  acceptRequest,
  declineRequest,
  cancelOrRemove,
} = useFriends()
const { show } = useToast()

onMounted(fetchFriends)

function partnerName(partner: { pub_name?: string; id: number }) {
  return partner.pub_name ?? `Student #${partner.id.toString(16)}`
}

function partnerInitial(partner: { pub_name?: string; id: number }) {
  return partnerName(partner).charAt(0)
}

function openProfile(userId: number) {
  router.push({ name: 'profile-view', params: { id: userId.toString() } })
}

async function handleAccept(friendshipId: number) {
  try {
    await acceptRequest(friendshipId)
    show('Freundschaftsanfrage angenommen.', 'success')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht angenommen werden.', 'error')
  }
}

async function handleDecline(friendshipId: number) {
  try {
    await declineRequest(friendshipId)
    show('Freundschaftsanfrage abgelehnt.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht abgelehnt werden.', 'error')
  }
}

async function handleCancel(friendshipId: number) {
  try {
    await cancelOrRemove(friendshipId)
    show('Anfrage zurückgezogen.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht zurückgezogen werden.', 'error')
  }
}

async function handleRemove(friendshipId: number) {
  if (!confirm('Freundschaft wirklich beenden?')) return
  try {
    await cancelOrRemove(friendshipId)
    show('Freundschaft beendet.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Freundschaft konnte nicht beendet werden.', 'error')
  }
}
</script>

<template>
  <div class="page-narrow">
    <h1 class="page-title">Freunde</h1>
    <p class="page-subtitle">Freundschaftsanfragen verwalten und verbundene Studierende sehen.</p>

    <EmptyState v-if="!loaded && loading" title="Freunde werden geladen…" />

    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <section v-if="incoming.length" class="section">
        <h2 class="section-title">Eingehende Anfragen</h2>
        <ul class="friend-list">
          <li v-for="req in incoming" :key="req.id" class="friend-item">
            <button type="button" class="partner" @click="openProfile(req.partner.id)">
              <img v-if="req.partner.avatarUrl" :src="req.partner.avatarUrl" class="friend-avatar" alt="" />
              <span v-else class="friend-avatar placeholder">{{ partnerInitial(req.partner) }}</span>
              <span class="partner-name">{{ partnerName(req.partner) }}</span>
            </button>
            <div class="item-actions">
              <AppButton @click="handleAccept(req.id)">Annehmen</AppButton>
              <AppButton variant="secondary" @click="handleDecline(req.id)">Ablehnen</AppButton>
            </div>
          </li>
        </ul>
      </section>

      <section v-if="outgoing.length" class="section">
        <h2 class="section-title">Ausstehende Anfragen</h2>
        <ul class="friend-list">
          <li v-for="req in outgoing" :key="req.id" class="friend-item">
            <button type="button" class="partner" @click="openProfile(req.partner.id)">
              <img v-if="req.partner.avatarUrl" :src="req.partner.avatarUrl" class="friend-avatar" alt="" />
              <span v-else class="friend-avatar placeholder">{{ partnerInitial(req.partner) }}</span>
              <span class="partner-name">{{ partnerName(req.partner) }}</span>
            </button>
            <div class="item-actions">
              <AppButton variant="secondary" @click="handleCancel(req.id)">Zurückziehen</AppButton>
            </div>
          </li>
        </ul>
      </section>

      <section class="section">
        <h2 class="section-title">Deine Freunde</h2>
        <EmptyState
          v-if="!friends.length"
          title="Noch keine Freunde"
          hint="Sende auf dem Profil eines Studierenden eine Freundschaftsanfrage."
        />
        <ul v-else class="friend-list">
          <li v-for="entry in friends" :key="entry.id" class="friend-item">
            <button type="button" class="partner" @click="openProfile(entry.partner.id)">
              <img v-if="entry.partner.avatarUrl" :src="entry.partner.avatarUrl" class="friend-avatar" alt="" />
              <span v-else class="friend-avatar placeholder">{{ partnerInitial(entry.partner) }}</span>
              <span class="partner-name">{{ partnerName(entry.partner) }}</span>
            </button>
            <div class="item-actions">
              <AppButton @click="router.push({ name: 'chat-view', params: { id: entry.partner.id.toString() } })">
                Chat
              </AppButton>
              <AppButton variant="secondary" @click="handleRemove(entry.id)">Entfernen</AppButton>
            </div>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>

<style scoped>
.section {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1.1rem;
  margin-bottom: 0.75rem;
}

.friend-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.friend-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 1rem;
  border: 1px solid var(--color-border-subtle);
  border-radius: 0.5rem;
  background: var(--color-surface);
}

.partner {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  cursor: pointer;
  text-align: left;
  min-width: 0;
}

.friend-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.friend-avatar.placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
  text-transform: uppercase;
}

.partner-name {
  font-weight: 600;
  color: var(--color-accent);
}

.partner:hover .partner-name {
  text-decoration: underline;
}

.item-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
</style>
