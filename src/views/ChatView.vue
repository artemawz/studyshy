<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useChats } from '@/composables/useChats'
import { useStudents } from '@/composables/useStudents'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const { getChatById, getChatByPartnerId, resolveChatId, getMessagesForChat, sendMessage } = useChats()
const { getStudentById } = useStudents()
const { isLoggedIn } = useAuth()
const { show } = useToast()

const routeId = computed(() => parseInt(route.params.id as string, 10))
const chatId = computed(() => resolveChatId(routeId.value))

const partner = computed(() => {
  const byPartner = getChatByPartnerId(routeId.value)
  if (byPartner) return getStudentById(byPartner.partnerId)
  const byChat = getChatById(routeId.value)
  if (byChat) return getStudentById(byChat.partnerId)
  return getStudentById(routeId.value)
})

const messages = computed(() => getMessagesForChat(chatId.value))
const newMessage = ref('')
const messagesEnd = ref<HTMLElement | null>(null)

watch(
  messages,
  async () => {
    await nextTick()
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
  },
  { immediate: true },
)

function handleSend() {
  const text = newMessage.value.trim()
  if (!text) return
  sendMessage(chatId.value, text)
  newMessage.value = ''
  show('Nachricht gesendet.', 'success')
}

function formatTime(date: Date) {
  return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="page-narrow chat-page">
    <EmptyState
      v-if="!isLoggedIn"
      title="Bitte anmelden"
      hint="Melde dich an, um Nachrichten zu senden."
    >
      <RouterLink to="/login" class="btn" style="margin-top: 1rem">Zum Login</RouterLink>
    </EmptyState>

    <template v-else-if="partner">
      <header class="chat-header">
        <button class="back-btn" @click="router.push('/chats')">←</button>
        <div>
          <h1>{{ partner.pub_name ?? `Student #${partner.id.toString(16)}` }}</h1>
          <p class="uni">{{ partner.uni }}</p>
        </div>
        <RouterLink :to="`/student/${partner.id}`" class="textlink profile-link">Profil</RouterLink>
      </header>

      <div class="messages card">
        <div
          v-for="msg in messages"
          :key="msg.id"
          class="message"
          :class="{ own: msg.senderId === 0 }"
        >
          <p class="text">{{ msg.text }}</p>
          <span class="time">{{ formatTime(msg.sentAt) }}</span>
        </div>
        <div v-if="messages.length === 0" class="no-messages">
          Noch keine Nachrichten. Schreib die erste!
        </div>
        <div ref="messagesEnd" />
      </div>

      <form class="composer" @submit.prevent="handleSend">
        <input
          v-model="newMessage"
          type="text"
          placeholder="Nachricht schreiben…"
          autocomplete="off"
        />
        <AppButton type="submit" :disabled="!newMessage.trim()">Senden</AppButton>
      </form>
    </template>

    <EmptyState v-else title="Chat nicht gefunden">
      <RouterLink to="/chats" class="btn" style="margin-top: 1rem">Zur Chat-Übersicht</RouterLink>
    </EmptyState>
  </div>
</template>

<style scoped>
.chat-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 120px);
  max-height: 720px;
}

.chat-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.chat-header h1 {
  font-size: 1.2rem;
  margin: 0;
}

.uni {
  margin: 0;
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

.back-btn {
  background: none;
  border: 1px solid var(--color-border);
  color: white;
  border-radius: 0.4rem;
  padding: 0.4rem 0.7rem;
  cursor: pointer;
  font-size: 1rem;
}

.back-btn:hover {
  border-color: var(--color-accent);
}

.profile-link {
  margin-left: auto;
  font-size: 0.9rem;
}

.messages {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  margin-bottom: 1rem;
}

.message {
  max-width: 75%;
  align-self: flex-start;
  background: var(--color-bg);
  border: 1px solid var(--color-border-subtle);
  border-radius: 0.75rem;
  padding: 0.6rem 0.9rem;
}

.message.own {
  align-self: flex-end;
  background: #b9aaff22;
  border-color: var(--color-accent);
}

.text {
  margin: 0 0 0.25rem;
}

.time {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.no-messages {
  text-align: center;
  color: var(--color-text-muted);
  padding: 2rem;
}

.composer {
  display: flex;
  gap: 0.75rem;
}

.composer input {
  flex: 1;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.4rem;
  padding: 0.6rem 0.9rem;
  color: white;
  font-size: 1rem;
  font-family: inherit;
}

.composer input:focus {
  outline: none;
  border-color: var(--color-accent);
}
</style>
