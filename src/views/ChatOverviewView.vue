<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import EmptyState from '@/components/EmptyState.vue'
import AppButton from '@/components/AppButton.vue'
import { useChats } from '@/composables/useChats'
import { useToast } from '@/composables/useToast'
import { formatRelativeTime } from '@/misc'
import { ApiError } from '@/services/api'

const router = useRouter()
const { chats, loading, loaded, error, fetchChats, acceptChat, declineChat, deleteChat } = useChats()
const { show } = useToast()

const incomingRequests = computed(() => chats.value.filter((c) => c.isIncomingRequest))
const otherChats = computed(() => chats.value.filter((c) => !c.isIncomingRequest))

// Kein eigener Poll-Timer mehr: Die NotificationBell in der Navigation hält
// `chats` bereits alle paar Sekunden aktuell, solange man eingeloggt ist (was
// hier Voraussetzung ist). Ein zweiter, unsynchronisierter Timer führte zu
// überlappenden Anfragen und sichtbarem Flackern in der Liste.
onMounted(() => {
  fetchChats()
})

function openChat(id: number) {
  router.push({ name: 'chat-view', params: { id: id.toString() } })
}

async function handleAccept(chatId: number, event: Event) {
  event.stopPropagation()
  try {
    await acceptChat(chatId)
    show('Chat-Anfrage angenommen.', 'success')
    openChat(chatId)
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht angenommen werden.', 'error')
  }
}

async function handleDecline(chatId: number, event: Event) {
  event.stopPropagation()
  try {
    await declineChat(chatId)
    show('Chat-Anfrage abgelehnt.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht abgelehnt werden.', 'error')
  }
}

async function handleDelete(chatId: number, event: Event) {
  event.stopPropagation()
  if (!confirm('Diesen Chat wirklich löschen?')) return
  try {
    await deleteChat(chatId)
    show('Chat für dich gelöscht.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Chat konnte nicht gelöscht werden.', 'error')
  }
}
</script>

<template>
  <div class="page-narrow">
    <h1 class="page-title">Deine Chats</h1>
    <p class="page-subtitle">Unterhalte dich anonym mit deinen Matches.</p>

    <EmptyState v-if="!loaded && loading" title="Chats werden geladen…" />

    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else-if="chats.length">
      <section v-if="incomingRequests.length" class="section">
        <h2 class="section-title">Chat-Anfragen</h2>
        <ul class="chat-list">
          <li
            v-for="chat in incomingRequests"
            :key="chat.id"
            class="chat-item request"
            @click="openChat(chat.id)"
          >
            <img
              v-if="chat.partnerAvatarUrl"
              :src="chat.partnerAvatarUrl"
              class="chat-avatar"
              alt=""
            />
            <span v-else class="chat-avatar placeholder">{{ chat.partnerName.charAt(0) }}</span>
            <div class="chat-body">
              <div class="chat-header">
                <span class="partner">{{ chat.partnerName }}</span>
                <span class="badge">Neu</span>
              </div>
              <p class="preview">{{ chat.lastMessage }}</p>
              <div class="item-actions">
                <AppButton @click="handleAccept(chat.id, $event)">Annehmen</AppButton>
                <AppButton variant="secondary" @click="handleDecline(chat.id, $event)">Ablehnen</AppButton>
              </div>
            </div>
          </li>
        </ul>
      </section>

      <section v-if="otherChats.length" class="section">
        <h2 v-if="incomingRequests.length" class="section-title">Chats</h2>
        <ul class="chat-list">
          <li
            v-for="chat in otherChats"
            :key="chat.id"
            class="chat-item"
            :class="{ unread: chat.unread, pending: chat.isOutgoingRequest }"
            @click="openChat(chat.id)"
          >
            <img
              v-if="chat.partnerAvatarUrl"
              :src="chat.partnerAvatarUrl"
              class="chat-avatar"
              alt=""
            />
            <span v-else class="chat-avatar placeholder">{{ chat.partnerName.charAt(0) }}</span>
            <div class="chat-body">
              <div class="chat-header">
                <span class="partner">{{ chat.partnerName }}</span>
                <span class="time">{{ formatRelativeTime(chat.updatedAt) }}</span>
              </div>
              <p class="preview">
                {{ chat.isOutgoingRequest ? 'Anfrage ausstehend' : chat.lastMessage }}
              </p>
              <div class="item-footer">
                <button type="button" class="delete-link" @click="handleDelete(chat.id, $event)">
                  Löschen
                </button>
              </div>
            </div>
          </li>
        </ul>
      </section>
    </template>

    <EmptyState
      v-else
      title="Noch keine Chats"
      hint="Entdecke Studierende auf der Startseite und sende eine Chat-Anfrage."
    >
      <RouterLink to="/" class="btn" style="margin-top: 1rem">Matches entdecken</RouterLink>
    </EmptyState>
  </div>
</template>

<style scoped>
.section {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1rem;
  color: var(--color-text-muted);
  margin: 0 0 0.75rem;
}

.chat-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.chat-item {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  padding: 1rem 1.25rem;
  cursor: pointer;
  transition: border-color 150ms ease;
}

.chat-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.chat-avatar.placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
  font-size: 1.1rem;
  text-transform: uppercase;
}

.chat-body {
  flex: 1;
  min-width: 0;
}

.chat-item:hover {
  border-color: var(--color-accent);
}

.chat-item.unread,
.chat-item.request {
  border-color: var(--color-accent);
  background: #b9aaff11;
}

.chat-item.pending {
  opacity: 0.85;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.35rem;
}

.partner {
  font-weight: bold;
}

.badge {
  font-size: 0.75rem;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  padding: 0.15rem 0.5rem;
  border-radius: 100rem;
}

.time {
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

.preview {
  margin: 0;
  color: var(--color-text-muted);
  font-size: 0.95rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.item-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.delete-link {
  background: none;
  border: none;
  color: var(--color-text-muted);
  font-size: 0.8rem;
  cursor: pointer;
  padding: 0;
}

.delete-link:hover {
  color: #f87171;
}
</style>
