<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useGroups } from '@/composables/useGroups'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/services/api'
import type { Group } from '@/types'

const route = useRoute()
const router = useRouter()
const { currentUser } = useAuth()
const { show } = useToast()
const {
  fetchGroup,
  getMessages,
  fetchMessages,
  syncGroup,
  sendMessage,
  editMessage,
  deleteMessage,
} = useGroups()

const groupId = computed(() => Number(route.params.id))
const group = ref<Group | null>(null)
const messages = computed(() => getMessages(groupId.value))
const loadingError = ref(false)
const sending = ref(false)

const newMessage = ref('')
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

const editingId = ref<number | null>(null)
const editingText = ref('')

let pollTimer: ReturnType<typeof setInterval> | null = null

function lastMessageId(): number {
  const list = messages.value
  return list.length ? list[list.length - 1]!.id : 0
}

async function loadGroup() {
  loadingError.value = false
  try {
    group.value = await fetchGroup(groupId.value)
    await fetchMessages(groupId.value)
    await nextTick()
    messagesEnd.value?.scrollIntoView()
  } catch (e) {
    loadingError.value = true
    if (e instanceof ApiError && e.status === 403) {
      show(e.message, 'error')
      router.push({ name: 'groups' })
    }
  }
}

async function pollGroup() {
  if (!groupId.value) return
  try {
    await syncGroup(groupId.value, lastMessageId())
  } catch {
    /* ignore */
  }
}

function startPolling() {
  stopPolling()
  pollTimer = setInterval(pollGroup, 3500)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

watch(
  messages,
  async () => {
    await nextTick()
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
  },
)

watch(
  groupId,
  () => {
    loadGroup()
    startPolling()
  },
  { immediate: true },
)

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
    show('Erlaubt sind Bilder und PDF-Dateien.', 'error')
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    show('Die Datei darf maximal 5 MB groß sein.', 'error')
    return
  }
  removeSelectedFile()
  selectedFile.value = file
  if (file.type.startsWith('image/')) filePreviewUrl.value = URL.createObjectURL(file)
}

function removeSelectedFile() {
  if (filePreviewUrl.value) URL.revokeObjectURL(filePreviewUrl.value)
  filePreviewUrl.value = null
  selectedFile.value = null
}

async function handleSend() {
  const text = newMessage.value.trim()
  if ((!text && !selectedFile.value) || sending.value) return
  sending.value = true
  try {
    await sendMessage(groupId.value, text, selectedFile.value)
    newMessage.value = ''
    removeSelectedFile()
    showEmojiPicker.value = false
    await nextTick()
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Nachricht konnte nicht gesendet werden.', 'error')
  } finally {
    sending.value = false
  }
}

function startEdit(id: number, text: string | null) {
  editingId.value = id
  editingText.value = text ?? ''
}

function cancelEdit() {
  editingId.value = null
  editingText.value = ''
}

async function saveEdit() {
  if (editingId.value === null) return
  const text = editingText.value.trim()
  if (!text) return
  try {
    await editMessage(groupId.value, editingId.value, text)
    cancelEdit()
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Bearbeiten fehlgeschlagen.', 'error')
  }
}

async function handleDelete(id: number) {
  if (!confirm('Nachricht löschen?')) return
  try {
    await deleteMessage(groupId.value, id)
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Löschen fehlgeschlagen.', 'error')
  }
}

function senderName(msg: (typeof messages.value)[number]) {
  return msg.senderName ?? `Student #${msg.senderId.toString(16)}`
}

function formatTime(value: string | Date) {
  return new Date(value).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="page-narrow chat-page">
    <template v-if="group">
      <div class="chat-header">
        <button type="button" class="back-btn" @click="router.push({ name: 'groups' })">←</button>
        <div class="header-info">
          <h1>{{ group.name }}</h1>
          <p class="members">👥 {{ group.memberCount ?? group.members?.length ?? 0 }} Mitglieder</p>
        </div>
      </div>

      <div class="messages card">
        <div
          v-for="msg in messages"
          :key="msg.id"
          class="message"
          :class="{ own: msg.senderId === currentUser?.id }"
        >
          <span v-if="msg.senderId !== currentUser?.id" class="sender">{{ senderName(msg) }}</span>

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
                <button v-if="msg.text" type="button" class="link-btn" @click="startEdit(msg.id, msg.text)">✏️</button>
                <button type="button" class="link-btn" @click="handleDelete(msg.id)">🗑️</button>
              </span>
            </div>
          </template>
        </div>
        <div v-if="!messages.length" class="no-messages">Noch keine Nachrichten. Schreib die erste!</div>
        <div ref="messagesEnd" />
      </div>

      <div class="composer-wrap">
        <div v-if="selectedFile" class="attachment-preview">
          <img v-if="filePreviewUrl" :src="filePreviewUrl" class="preview-thumb" alt="" />
          <span v-else class="preview-thumb file">📄</span>
          <span class="preview-name">{{ selectedFile.name }}</span>
          <button type="button" class="preview-remove" @click="removeSelectedFile">✕</button>
        </div>

        <div v-if="showEmojiPicker" class="emoji-panel">
          <button v-for="emoji in EMOJIS" :key="emoji" type="button" class="emoji-btn" @click="addEmoji(emoji)">
            {{ emoji }}
          </button>
        </div>

        <form class="composer" @submit.prevent="handleSend">
          <button type="button" class="icon-btn" :class="{ active: showEmojiPicker }" @click="showEmojiPicker = !showEmojiPicker">😊</button>
          <button type="button" class="icon-btn" @click="openFilePicker">📎</button>
          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
            class="sr-only"
            @change="onFileSelected"
          />
          <input v-model="newMessage" type="text" placeholder="Nachricht an die Gruppe…" autocomplete="off" />
          <AppButton type="submit" :disabled="(!newMessage.trim() && !selectedFile) || sending">
            {{ sending ? '…' : 'Senden' }}
          </AppButton>
        </form>
      </div>
    </template>

    <EmptyState v-else-if="loadingError" title="Gruppe nicht gefunden">
      <RouterLink to="/groups" class="btn" style="margin-top: 1rem">Zu den Gruppen</RouterLink>
    </EmptyState>
    <EmptyState v-else title="Gruppe wird geladen…" />
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
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.back-btn {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  border-radius: 0.5rem;
  width: 36px;
  height: 36px;
  cursor: pointer;
  font-size: 1.1rem;
}

.header-info h1 {
  font-size: 1.2rem;
  margin: 0;
}

.members {
  margin: 0;
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

.messages {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.message {
  max-width: 78%;
  align-self: flex-start;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  padding: 0.5rem 0.8rem;
}

.message.own {
  align-self: flex-end;
  background: #b9aaff22;
  border-color: var(--color-accent);
}

.sender {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-accent);
  margin-bottom: 0.2rem;
}

.text {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
}

.text.deleted {
  font-style: italic;
  color: var(--color-text-muted);
}

.message-foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.2rem;
}

.time {
  font-size: 0.7rem;
  color: var(--color-text-muted);
}

.msg-actions {
  display: flex;
  gap: 0.25rem;
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

.composer > input[type='text'] {
  flex: 1;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  padding: 0.6rem 0.8rem;
  color: var(--color-text);
  font-family: inherit;
}

.composer > input[type='text']:focus {
  outline: none;
  border-color: var(--color-accent);
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
</style>
