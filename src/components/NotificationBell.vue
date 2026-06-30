<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFriends } from '@/composables/useFriends'
import { useChats } from '@/composables/useChats'
import { useEvents } from '@/composables/useEvents'
import { useAuth } from '@/composables/useAuth'
import {
  eventReminderText,
  getActiveEventReminders,
  markEventNotified,
} from '@/misc'

const router = useRouter()
const route = useRoute()
const { isLoggedIn } = useAuth()
const { incoming, fetchFriends } = useFriends()
const { chats, fetchChats } = useChats()
const { events, fetchEvents } = useEvents()

const open = ref(false)
const rootEl = ref<HTMLElement | null>(null)

const POLL_MS = 4000
let pollTimer: ReturnType<typeof setInterval> | null = null

const incomingChatRequests = computed(() => chats.value.filter((c) => c.isIncomingRequest))

const unreadChats = computed(() =>
  chats.value.filter(
    (c) => c.unread && !c.isIncomingRequest && !c.isOutgoingRequest && c.status === 'accepted',
  ),
)

interface NotificationItem {
  key: string
  type: 'friend' | 'chat' | 'message' | 'event'
  name: string
  avatarUrl?: string | null
  text: string
  notifyKey?: string
  action: () => void
}

const items = computed<NotificationItem[]>(() => {
  const list: NotificationItem[] = []

  for (const req of incoming.value) {
    list.push({
      key: `friend-${req.id}`,
      type: 'friend',
      name: req.partner.pub_name ?? `Student #${req.partner.id.toString(16)}`,
      avatarUrl: req.partner.avatarUrl,
      text: 'möchte mit dir befreundet sein',
      action: () => router.push('/friends'),
    })
  }

  for (const chat of incomingChatRequests.value) {
    list.push({
      key: `chat-${chat.id}`,
      type: 'chat',
      name: chat.partnerName,
      avatarUrl: chat.partnerAvatarUrl,
      text: 'hat dir eine Chat-Anfrage geschickt',
      action: () => router.push({ name: 'chat-view', params: { id: chat.id.toString() } }),
    })
  }

  for (const chat of unreadChats.value) {
    list.push({
      key: `message-${chat.id}`,
      type: 'message',
      name: chat.partnerName,
      avatarUrl: chat.partnerAvatarUrl,
      text: 'hat dir eine neue Nachricht geschickt',
      action: () => router.push({ name: 'chat-view', params: { id: chat.id.toString() } }),
    })
  }

  if (isLoggedIn.value) {
    for (const reminder of getActiveEventReminders(events.value)) {
      list.push({
        key: reminder.key,
        type: 'event',
        name: reminder.title,
        avatarUrl: null,
        text: eventReminderText(reminder.kind, reminder.title),
        notifyKey: reminder.key,
        action: () => router.push('/events'),
      })
    }
  }

  return list
})

const count = computed(() => items.value.length)

function toggle() {
  open.value = !open.value
  if (open.value) refresh()
}

function handleItem(item: NotificationItem) {
  open.value = false
  if (item.notifyKey) markEventNotified(item.notifyKey)
  item.action()
}

async function refresh() {
  const tasks: Promise<unknown>[] = [fetchFriends(), fetchChats()]
  if (isLoggedIn.value) tasks.push(fetchEvents())
  await Promise.all(tasks)
}

function handleOutsideClick(event: MouseEvent) {
  if (rootEl.value && !rootEl.value.contains(event.target as Node)) {
    open.value = false
  }
}

watch(
  () => route.fullPath,
  () => refresh(),
)

onMounted(() => {
  refresh()
  pollTimer = setInterval(refresh, POLL_MS)
  document.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  document.removeEventListener('click', handleOutsideClick)
})
</script>

<template>
  <div ref="rootEl" class="bell">
    <button
      type="button"
      class="bell-btn"
      :class="{ active: open }"
      aria-label="Benachrichtigungen"
      @click="toggle"
    >
      <span class="bell-icon">🔔</span>
      <span v-if="count > 0" class="bell-badge">{{ count }}</span>
    </button>

    <div v-if="open" class="dropdown">
      <p class="dropdown-title">Benachrichtigungen</p>

      <p v-if="count === 0" class="dropdown-empty">Keine neuen Benachrichtigungen</p>

      <ul v-else class="dropdown-list">
        <li v-for="item in items" :key="item.key">
          <button type="button" class="dropdown-item" @click="handleItem(item)">
            <img v-if="item.avatarUrl" :src="item.avatarUrl" class="item-avatar" alt="" />
            <span v-else class="item-avatar placeholder">{{ item.type === 'event' ? '📅' : item.name.charAt(0) }}</span>
            <span class="item-text">
              <template v-if="item.type === 'event'">{{ item.text }}</template>
              <template v-else><strong>{{ item.name }}</strong> {{ item.text }}</template>
            </span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.bell {
  position: relative;
}

.bell-btn {
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.25rem;
  padding: 0.25rem;
  line-height: 1;
  border-radius: 0.4rem;
}

.bell-btn.active,
.bell-btn:hover {
  background: var(--color-accent-muted);
}

.bell-badge {
  position: absolute;
  top: -0.2rem;
  right: -0.2rem;
  min-width: 1.1rem;
  height: 1.1rem;
  padding: 0 0.3rem;
  border-radius: 999px;
  background: #f87171;
  color: #fff;
  font-size: 0.7rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: 290px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.6rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
  padding: 0.5rem;
  z-index: 50;
}

.dropdown-title {
  margin: 0.25rem 0.5rem 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-muted);
}

.dropdown-empty {
  margin: 0;
  padding: 1rem 0.5rem;
  text-align: center;
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

.dropdown-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  max-height: 320px;
  overflow-y: auto;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.45rem;
  color: var(--color-text);
  font: inherit;
}

.dropdown-item:hover {
  background: var(--color-bg);
}

.item-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.item-avatar.placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
}

.item-text {
  font-size: 0.85rem;
  line-height: 1.3;
}
</style>
