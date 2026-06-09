<script setup lang="ts">
import { useRouter } from 'vue-router'
import EmptyState from '@/components/EmptyState.vue'
import { useChats } from '@/composables/useChats'
import { useAuth } from '@/composables/useAuth'
import { formatRelativeTime } from '@/misc'

const router = useRouter()
const { chats } = useChats()
const { isLoggedIn } = useAuth()

function openChat(id: number) {
  router.push({ name: 'chat-view', params: { id: id.toString() } })
}
</script>

<template>
  <div class="page-narrow">
    <h1 class="page-title">Deine Chats</h1>
    <p class="page-subtitle">Unterhalte dich anonym mit deinen Matches.</p>

    <EmptyState
      v-if="!isLoggedIn"
      title="Bitte anmelden"
      hint="Melde dich an, um deine Unterhaltungen zu sehen."
    >
      <RouterLink to="/login" class="btn" style="margin-top: 1rem">Zum Login</RouterLink>
    </EmptyState>

    <ul v-else-if="chats.length" class="chat-list">
      <li
        v-for="chat in chats"
        :key="chat.id"
        class="chat-item"
        :class="{ unread: chat.unread }"
        @click="openChat(chat.id)"
      >
        <div class="chat-header">
          <span class="partner">{{ chat.partnerName }}</span>
          <span class="time">{{ formatRelativeTime(chat.updatedAt) }}</span>
        </div>
        <p class="preview">{{ chat.lastMessage }}</p>
      </li>
    </ul>

    <EmptyState
      v-else
      title="Noch keine Chats"
      hint="Entdecke Studierende auf der Startseite und schreib ihnen eine Nachricht."
    >
      <RouterLink to="/" class="btn" style="margin-top: 1rem">Matches entdecken</RouterLink>
    </EmptyState>
  </div>
</template>

<style scoped>
.chat-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.chat-item {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  padding: 1rem 1.25rem;
  cursor: pointer;
  transition: border-color 150ms ease;
}

.chat-item:hover {
  border-color: var(--color-accent);
}

.chat-item.unread {
  border-color: var(--color-accent);
  background: #b9aaff11;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.35rem;
}

.partner {
  font-weight: bold;
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
</style>
