import { ref } from 'vue'
import type { ChatPreview, Message } from '@/types'
import { api } from '@/services/api'

const chats = ref<ChatPreview[]>([])
const messagesByChat = ref<Map<number, Message[]>>(new Map())
const loading = ref(false)
const error = ref<string | null>(null)

function parseChat(chat: ChatPreview): ChatPreview {
  return {
    ...chat,
    updatedAt: new Date(chat.updatedAt),
  }
}

function parseMessage(message: Message): Message {
  return {
    ...message,
    sentAt: new Date(message.sentAt),
  }
}

export function useChats() {
  async function fetchChats() {
    loading.value = true
    error.value = null
    try {
      const res = await api.getChats()
      chats.value = res.data.map(parseChat)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Chats konnten nicht geladen werden.'
      chats.value = []
    } finally {
      loading.value = false
    }
  }

  function getChatById(id: number) {
    return chats.value.find((c) => c.id === id)
  }

  function getChatByPartnerId(partnerId: number) {
    return chats.value.find((c) => c.partnerId === partnerId)
  }

  async function ensureChat(partnerId: number): Promise<ChatPreview> {
    const existing = getChatByPartnerId(partnerId)
    if (existing) return existing

    const res = await api.createChat(partnerId)
    const chat = parseChat(res.chat)
    const idx = chats.value.findIndex((c) => c.id === chat.id)
    if (idx >= 0) {
      chats.value[idx] = chat
    } else {
      chats.value.unshift(chat)
    }
    return chat
  }

  async function resolveChatId(routeId: number): Promise<number> {
    const byChat = getChatById(routeId)
    if (byChat) return byChat.id

    const byPartner = getChatByPartnerId(routeId)
    if (byPartner) return byPartner.id

    const chat = await ensureChat(routeId)
    return chat.id
  }

  async function fetchMessages(chatId: number) {
    const res = await api.getMessages(chatId)
    const messages = res.data.map(parseMessage)
    messagesByChat.value.set(chatId, messages)
    return messages
  }

  function getMessagesForChat(chatId: number) {
    return messagesByChat.value.get(chatId) ?? []
  }

  async function sendMessage(chatId: number, text: string) {
    const message = parseMessage(await api.sendMessage(chatId, text))
    const existing = messagesByChat.value.get(chatId) ?? []
    messagesByChat.value.set(chatId, [...existing, message])

    const chat = getChatById(chatId)
    if (chat) {
      chat.lastMessage = message.text
      chat.updatedAt = message.sentAt
    }
  }

  return {
    chats,
    loading,
    error,
    fetchChats,
    getChatById,
    getChatByPartnerId,
    ensureChat,
    resolveChatId,
    fetchMessages,
    getMessagesForChat,
    sendMessage,
  }
}
