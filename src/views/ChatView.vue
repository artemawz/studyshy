<script setup lang="ts">
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useChats } from '@/composables/useChats'
import { useStudents } from '@/composables/useStudents'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/services/api'
import type { User } from '@/types'

const route = useRoute()
const router = useRouter()
const {
  resolveChatId,
  fetchMessages,
  fetchChats,
  getMessagesForChat,
  getChatById,
  sendMessage,
  editMessage,
  deleteMessage,
  syncChat,
  notifyTyping,
  acceptChat,
  declineChat,
  deleteChat,
} = useChats()
const { fetchStudentById } = useStudents()
const { currentUser } = useAuth()
const { show } = useToast()

const POLL_MS = 2000
const TYPING_PING_MS = 2000

const routeId = computed(() => parseInt(route.params.id as string, 10))
const chatId = ref<number | null>(null)
const partner = ref<User | undefined>(undefined)
const loading = ref(true)
const sending = ref(false)
const acting = ref(false)
const newMessage = ref('')
const partnerTyping = ref(false)
const messagesEnd = ref<HTMLElement | null>(null)

const EMOJIS = [
  '😀', '😁', '😂', '🤣', '😊', '😍', '😎', '🤩', '😘', '😉',
  '🙂', '🤔', '😐', '😅', '😭', '😡', '👍', '👎', '👏', '🙌',
  '🙏', '💪', '🔥', '🎉', '❤️', '💯', '✅', '❌', '☕', '🍕',
  '⚽', '🎮', '📚', '💻', '🎓', '😴', '🤝', '👀', '✨', '🥳',
]

const showEmojiPicker = ref(false)
const selectedFile = ref<File | null>(null)
const filePreviewUrl = ref<string | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

let pollTimer: ReturnType<typeof setInterval> | null = null
let lastTypingPing = 0

const messages = computed(() => (chatId.value ? getMessagesForChat(chatId.value) : []))
const activeChat = computed(() => (chatId.value ? getChatById(chatId.value) : undefined))

const partnerName = computed(
  () => partner.value?.pub_name ?? `Student #${(partner.value?.id ?? routeId.value).toString(16)}`,
)

const isIncomingRequest = computed(() => activeChat.value?.isIncomingRequest ?? false)
const isOutgoingRequest = computed(() => activeChat.value?.isOutgoingRequest ?? false)
const isAccepted = computed(() => activeChat.value?.status === 'accepted')
const isFriend = computed(() => activeChat.value?.isFriend ?? false)

const requesterHasSent = computed(() =>
  messages.value.some((msg) => msg.senderId === currentUser.value?.id),
)

const canSend = computed(() => {
  if (!activeChat.value) return false
  if (!isFriend.value) return false
  if (isAccepted.value) return true
  if (isIncomingRequest.value) return true
  if (isOutgoingRequest.value) return !requesterHasSent.value
  return false
})

const composerPlaceholder = computed(() => {
  if (isIncomingRequest.value) {
    return 'Antworten und Anfrage annehmen…'
  }
  if (isOutgoingRequest.value && !requesterHasSent.value) {
    return 'Nachricht als Chat-Anfrage senden…'
  }
  return 'Nachricht schreiben…'
})

function lastMessageId(): number {
  const list = messages.value
  return list.length ? list[list.length - 1]!.id : 0
}

async function loadChat() {
  loading.value = true
  partnerTyping.value = false
  try {
    await fetchChats()
    const id = await resolveChatId(routeId.value)
    chatId.value = id
    await fetchMessages(id)

    const chat = getChatById(id)
    const partnerId = chat?.partnerId ?? routeId.value
    partner.value = await fetchStudentById(partnerId)
  } catch (e) {
    partner.value = undefined
    chatId.value = null
    if (e instanceof ApiError && e.status === 403) {
      show(e.message, 'error')
      router.push({ name: 'profile-view', params: { id: routeId.value.toString() } })
    }
  } finally {
    loading.value = false
  }
}

async function pollChat() {
  if (!chatId.value) return
  try {
    const res = await syncChat(chatId.value, lastMessageId())
    partnerTyping.value = res.partner_typing
  } catch {
    // Polling-Fehler still ignorieren
  }
}

function startPolling() {
  stopPolling()
  pollTimer = setInterval(pollChat, POLL_MS)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

function pingTyping() {
  if (!chatId.value || !newMessage.value.trim() || !isAccepted.value) return
  const now = Date.now()
  if (now - lastTypingPing < TYPING_PING_MS) return
  lastTypingPing = now
  notifyTyping(chatId.value).catch(() => {})
}

watch(
  messages,
  async () => {
    await nextTick()
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
  },
  { immediate: true },
)

watch(routeId, loadChat, { immediate: true })

watch(chatId, (id) => {
  if (id) {
    startPolling()
    pollChat()
  } else {
    stopPolling()
  }
})

onUnmounted(() => {
  stopPolling()
  if (filePreviewUrl.value) URL.revokeObjectURL(filePreviewUrl.value)
})

function addEmoji(emoji: string) {
  newMessage.value += emoji
}

function openFilePicker() {
  fileInput.value?.click()
}

function onFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) return

  const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf']
  if (!allowed.includes(file.type)) {
    show('Erlaubt sind Bilder (JPG, PNG, WebP, GIF) und PDF-Dateien.', 'error')
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    show('Die Datei darf maximal 5 MB groß sein.', 'error')
    return
  }

  removeSelectedFile()
  selectedFile.value = file
  if (file.type.startsWith('image/')) {
    filePreviewUrl.value = URL.createObjectURL(file)
  }
}

function removeSelectedFile() {
  if (filePreviewUrl.value) URL.revokeObjectURL(filePreviewUrl.value)
  filePreviewUrl.value = null
  selectedFile.value = null
}

const editingId = ref<number | null>(null)
const editingText = ref('')

function startEdit(id: number, text: string | null) {
  editingId.value = id
  editingText.value = text ?? ''
}

function cancelEdit() {
  editingId.value = null
  editingText.value = ''
}

async function saveEdit() {
  if (editingId.value === null || !chatId.value) return
  const text = editingText.value.trim()
  if (!text) return
  try {
    await editMessage(chatId.value, editingId.value, text)
    cancelEdit()
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Bearbeiten fehlgeschlagen.', 'error')
  }
}

async function handleDeleteMessage(id: number) {
  if (!chatId.value || !confirm('Nachricht löschen?')) return
  try {
    await deleteMessage(chatId.value, id)
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Löschen fehlgeschlagen.', 'error')
  }
}

async function handleSend() {
  const text = newMessage.value.trim()
  if ((!text && !selectedFile.value) || !chatId.value || sending.value || !canSend.value) return

  sending.value = true
  try {
    await sendMessage(chatId.value, text, selectedFile.value)
    newMessage.value = ''
    removeSelectedFile()
    showEmojiPicker.value = false
    partnerTyping.value = false
    await nextTick()
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Nachricht konnte nicht gesendet werden.', 'error')
  } finally {
    sending.value = false
  }
}

async function handleAccept() {
  if (!chatId.value || acting.value) return
  acting.value = true
  try {
    await acceptChat(chatId.value)
    show('Chat-Anfrage angenommen.', 'success')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht angenommen werden.', 'error')
  } finally {
    acting.value = false
  }
}

async function handleDecline() {
  if (!chatId.value || acting.value) return
  acting.value = true
  try {
    await declineChat(chatId.value)
    show('Chat-Anfrage abgelehnt.', 'info')
    router.push('/chats')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht abgelehnt werden.', 'error')
  } finally {
    acting.value = false
  }
}

async function handleDelete() {
  if (!chatId.value || acting.value) return
  if (!confirm('Diesen Chat wirklich löschen?')) return
  acting.value = true
  try {
    await deleteChat(chatId.value)
    show('Chat für dich gelöscht.', 'info')
    router.push('/chats')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Chat konnte nicht gelöscht werden.', 'error')
  } finally {
    acting.value = false
  }
}

function formatTime(date: Date | string) {
  const d = typeof date === 'string' ? new Date(date) : date
  return d.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="page-narrow chat-page">
    <EmptyState v-if="loading" title="Chat wird geladen…" />

    <template v-else-if="partner && chatId">
      <header class="chat-header">
        <button type="button" class="back-btn" @click="router.push('/chats')">←</button>
        <img
          v-if="partner.avatarUrl"
          :src="partner.avatarUrl"
          class="header-avatar"
          alt=""
        />
        <span v-else class="header-avatar placeholder">{{ partnerName.charAt(0) }}</span>
        <div>
          <h1>{{ partnerName }}</h1>
          <p class="uni">{{ partner.uni }}</p>
          <p v-if="isIncomingRequest" class="request-banner">Chat-Anfrage – bitte annehmen oder ablehnen</p>
          <p v-else-if="isOutgoingRequest" class="request-banner outgoing">Anfrage gesendet – warte auf Antwort</p>
          <p v-else-if="partnerTyping" class="typing-status">{{ partnerName }} schreibt …</p>
        </div>
        <div class="header-actions">
          <RouterLink :to="`/student/${partner.id}`" class="textlink">Profil</RouterLink>
          <button type="button" class="delete-btn" :disabled="acting" @click="handleDelete">Löschen</button>
        </div>
      </header>

      <div v-if="isIncomingRequest" class="request-actions card">
        <p>Jemand möchte mit dir chatten. Du kannst annehmen oder mit einer Antwort direkt starten.</p>
        <div class="request-buttons">
          <AppButton :disabled="acting" @click="handleAccept">Annehmen</AppButton>
          <AppButton variant="secondary" :disabled="acting" @click="handleDecline">Ablehnen</AppButton>
        </div>
      </div>

      <div class="messages card">
        <div
          v-for="msg in messages"
          :key="msg.id"
          class="message"
          :class="{ own: msg.senderId === currentUser?.id }"
        >
          <template v-if="editingId === msg.id">
            <div class="edit-box">
              <input v-model="editingText" type="text" @keyup.enter="saveEdit" />
              <div class="edit-actions">
                <button type="button" class="link-btn" @click="saveEdit">Speichern</button>
                <button type="button" class="link-btn" @click="cancelEdit">Abbrechen</button>
              </div>
            </div>
          </template>
          <template v-else-if="msg.deleted">
            <p class="text deleted">Nachricht wurde gelöscht</p>
          </template>
          <template v-else>
            <a
              v-if="msg.attachmentType === 'image' && msg.attachmentUrl"
              :href="msg.attachmentUrl"
              target="_blank"
              rel="noopener"
            >
              <img :src="msg.attachmentUrl" class="attachment-image" :alt="msg.attachmentName ?? 'Bild'" />
            </a>
            <a
              v-else-if="msg.attachmentUrl"
              :href="msg.attachmentUrl"
              target="_blank"
              rel="noopener"
              class="attachment-file"
            >
              <span class="file-icon">📄</span>
              <span class="file-name">{{ msg.attachmentName ?? 'Datei' }}</span>
            </a>
            <p v-if="msg.text" class="text">{{ msg.text }}</p>
            <div class="message-foot">
              <span class="time">{{ formatTime(msg.sentAt) }}<span v-if="msg.edited"> · bearbeitet</span></span>
              <span v-if="msg.senderId === currentUser?.id" class="msg-actions">
                <button v-if="msg.text" type="button" class="link-btn" title="Bearbeiten" @click="startEdit(msg.id, msg.text)">✏️</button>
                <button type="button" class="link-btn" title="Löschen" @click="handleDeleteMessage(msg.id)">🗑️</button>
              </span>
            </div>
          </template>
        </div>
        <div v-if="partnerTyping && isAccepted" class="message typing-bubble">
          <span class="typing-dots"><span></span><span></span><span></span></span>
        </div>
        <div v-if="messages.length === 0 && !partnerTyping" class="no-messages">
          {{
            isOutgoingRequest
              ? 'Schreib eine Nachricht, um deine Chat-Anfrage zu senden.'
              : 'Noch keine Nachrichten.'
          }}
        </div>
        <div ref="messagesEnd" />
      </div>

      <div v-if="!isFriend && !isIncomingRequest" class="not-friend-banner card">
        <p>Ihr seid nicht (mehr) befreundet. Sende eine Freundschaftsanfrage, um wieder zu chatten.</p>
        <RouterLink :to="`/student/${partner.id}`" class="btn">Zum Profil</RouterLink>
      </div>

      <div v-if="canSend" class="composer-wrap">
        <div v-if="selectedFile" class="attachment-preview">
          <img v-if="filePreviewUrl" :src="filePreviewUrl" class="preview-thumb" alt="" />
          <span v-else class="preview-thumb file">📄</span>
          <span class="preview-name">{{ selectedFile.name }}</span>
          <button type="button" class="preview-remove" @click="removeSelectedFile">✕</button>
        </div>

        <div v-if="showEmojiPicker" class="emoji-panel">
          <button
            v-for="emoji in EMOJIS"
            :key="emoji"
            type="button"
            class="emoji-btn"
            @click="addEmoji(emoji)"
          >
            {{ emoji }}
          </button>
        </div>

        <form class="composer" @submit.prevent="handleSend">
          <button
            type="button"
            class="icon-btn"
            :class="{ active: showEmojiPicker }"
            title="Emoji"
            @click="showEmojiPicker = !showEmojiPicker"
          >
            😊
          </button>
          <button type="button" class="icon-btn" title="Datei anhängen" @click="openFilePicker">
            📎
          </button>
          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
            class="sr-only"
            @change="onFileSelected"
          />
          <input
            v-model="newMessage"
            type="text"
            :placeholder="composerPlaceholder"
            autocomplete="off"
            @input="pingTyping"
          />
          <AppButton type="submit" :disabled="(!newMessage.trim() && !selectedFile) || sending">
            {{ sending ? '…' : isIncomingRequest ? 'Antworten' : 'Senden' }}
          </AppButton>
        </form>
      </div>
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
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.chat-header h1 {
  font-size: 1.2rem;
  margin: 0;
}

.header-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.header-avatar.placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
  font-size: 1.2rem;
  text-transform: uppercase;
}

.not-friend-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: auto;
}

.not-friend-banner p {
  margin: 0;
  color: var(--color-text-muted);
}

.uni {
  margin: 0;
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

.request-banner {
  margin: 0.35rem 0 0;
  font-size: 0.85rem;
  color: var(--color-accent);
}

.request-banner.outgoing {
  color: var(--color-text-muted);
}

.typing-status {
  margin: 0.25rem 0 0;
  font-size: 0.85rem;
  color: var(--color-accent);
  font-style: italic;
}

.header-actions {
  margin-left: auto;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.35rem;
}

.delete-btn {
  background: none;
  border: none;
  color: var(--color-text-muted);
  font-size: 0.85rem;
  cursor: pointer;
  padding: 0;
}

.delete-btn:hover:not(:disabled) {
  color: #f87171;
}

.request-actions {
  padding: 1rem;
  margin-bottom: 1rem;
}

.request-actions p {
  margin: 0 0 0.75rem;
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

.request-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
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

.typing-bubble {
  padding: 0.75rem 1rem;
}

.typing-dots {
  display: inline-flex;
  gap: 0.25rem;
  align-items: center;
}

.typing-dots span {
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 50%;
  background: var(--color-text-muted);
  animation: typing-bounce 1.2s infinite ease-in-out;
}

.typing-dots span:nth-child(2) {
  animation-delay: 0.15s;
}

.typing-dots span:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes typing-bounce {
  0%,
  60%,
  100% {
    transform: translateY(0);
    opacity: 0.45;
  }
  30% {
    transform: translateY(-4px);
    opacity: 1;
  }
}

.text {
  margin: 0 0 0.25rem;
  white-space: pre-wrap;
  word-break: break-word;
}

.text.deleted {
  font-style: italic;
  color: var(--color-text-muted);
}

.time {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.message-foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
}

.msg-actions {
  display: inline-flex;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.message:hover .msg-actions {
  opacity: 1;
}

.link-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.8rem;
  color: var(--color-text-muted);
  padding: 0 0.15rem;
}

.link-btn:hover {
  text-decoration: underline;
}

.edit-box {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.edit-box input {
  border: 1px solid var(--color-border);
  border-radius: 0.4rem;
  padding: 0.35rem 0.5rem;
  background: var(--color-surface);
  color: var(--color-text);
  font: inherit;
}

.edit-actions {
  display: flex;
  gap: 0.5rem;
}

.no-messages {
  text-align: center;
  color: var(--color-text-muted);
  padding: 2rem;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.attachment-image {
  display: block;
  max-width: 240px;
  max-height: 240px;
  border-radius: 0.5rem;
  margin-bottom: 0.3rem;
  cursor: pointer;
}

.attachment-file {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  margin-bottom: 0.3rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  text-decoration: none;
  color: var(--color-text);
  max-width: 240px;
}

.attachment-file .file-icon {
  font-size: 1.4rem;
}

.attachment-file .file-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.composer-wrap {
  position: relative;
}

.attachment-preview {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
}

.preview-thumb {
  width: 40px;
  height: 40px;
  border-radius: 0.4rem;
  object-fit: cover;
  flex-shrink: 0;
}

.preview-thumb.file {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg);
  font-size: 1.3rem;
}

.preview-name {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.9rem;
}

.preview-remove {
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: 1rem;
  padding: 0.2rem 0.4rem;
}

.preview-remove:hover {
  color: #f87171;
}

.emoji-panel {
  display: flex;
  flex-wrap: wrap;
  gap: 0.15rem;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  max-height: 160px;
  overflow-y: auto;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
}

.emoji-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.3rem;
  padding: 0.2rem;
  border-radius: 0.3rem;
  line-height: 1;
}

.emoji-btn:hover {
  background: var(--color-bg);
}

.icon-btn {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  cursor: pointer;
  font-size: 1.1rem;
  padding: 0 0.6rem;
  line-height: 1;
  flex-shrink: 0;
}

.icon-btn.active,
.icon-btn:hover {
  border-color: var(--color-accent);
  background: var(--color-accent-muted);
}

.composer {
  display: flex;
  gap: 0.5rem;
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
