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
    status: chat.status ?? 'accepted',
    requestedBy: chat.requestedBy ?? null,
    isIncomingRequest: chat.isIncomingRequest ?? false,
    isOutgoingRequest: chat.isOutgoingRequest ?? false,
    isFriend: chat.isFriend ?? false,
    updatedAt: new Date(chat.updatedAt),
  }
}

function parseMessage(message: Message): Message {
  return {
    ...message,
    sentAt: new Date(message.sentAt),
  }
}

function messagePreview(message: Message): string {
  if (message.deleted) return 'Nachricht gelöscht'
  if (message.text) return message.text
  if (message.attachmentUrl) {
    return message.attachmentType === 'image' ? '📷 Bild' : `📎 ${message.attachmentName ?? 'Datei'}`
  }
  return ''
}

function upsertChat(chat: ChatPreview) {
  const parsed = parseChat(chat)
  const idx = chats.value.findIndex((c) => c.id === parsed.id)
  if (idx >= 0) {
    chats.value[idx] = parsed
  } else {
    chats.value.unshift(parsed)
  }
  return parsed
}

function applySyncMeta(chatId: number, meta: {
  status: ChatPreview['status']
  requested_by: number | null
  is_incoming_request: boolean
  is_outgoing_request: boolean
  is_friend: boolean
}) {
  const chat = chats.value.find((c) => c.id === chatId)
  if (!chat) return
  chat.status = meta.status
  chat.requestedBy = meta.requested_by
  chat.isIncomingRequest = meta.is_incoming_request
  chat.isOutgoingRequest = meta.is_outgoing_request
  chat.isFriend = meta.is_friend
  if (meta.status === 'accepted') {
    chat.unread = false
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

  async function ensureChat(partnerId: number, message?: string): Promise<ChatPreview> {
    const existing = getChatByPartnerId(partnerId)
    if (existing) return existing

    const res = await api.createChat(partnerId, message)
    return upsertChat(res.chat)
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

  function appendMessages(chatId: number, newMessages: Message[]) {
    if (newMessages.length === 0) return

    const existing = messagesByChat.value.get(chatId) ?? []
    const byId = new Map(existing.map((m) => [m.id, m]))

    for (const message of newMessages) {
      byId.set(message.id, message)
    }

    const merged = [...byId.values()]
    merged.sort((a, b) => new Date(a.sentAt).getTime() - new Date(b.sentAt).getTime())
    messagesByChat.value.set(chatId, merged)

    const last = merged[merged.length - 1]
    const chat = getChatById(chatId)
    if (chat && last) {
      chat.lastMessage = messagePreview(last)
      chat.updatedAt = last.sentAt
    }
  }

  async function syncChat(chatId: number, afterMessageId: number) {
    const res = await api.syncChat(chatId, afterMessageId)
    appendMessages(chatId, res.messages.map(parseMessage))
    applySyncMeta(chatId, res)
    return res
  }

  async function notifyTyping(chatId: number) {
    await api.sendTyping(chatId)
  }

  async function sendMessage(chatId: number, text: string, file?: File | null) {
    const message = parseMessage(await api.sendMessage(chatId, text, file))
    appendMessages(chatId, [message])
    const chat = getChatById(chatId)
    if (chat?.status === 'pending' && chat.isIncomingRequest) {
      chat.status = 'accepted'
      chat.isIncomingRequest = false
      chat.isOutgoingRequest = false
    }
  }

  async function editMessage(chatId: number, messageId: number, text: string) {
    const message = parseMessage(await api.editMessage(chatId, messageId, text))
    appendMessages(chatId, [message])
  }

  async function deleteMessage(chatId: number, messageId: number) {
    await api.deleteMessage(chatId, messageId)
    const list = messagesByChat.value.get(chatId)
    if (list) {
      const idx = list.findIndex((m) => m.id === messageId)
      if (idx >= 0) {
        list[idx] = {
          ...list[idx]!,
          text: null,
          attachmentUrl: null,
          attachmentType: null,
          attachmentName: null,
          deleted: true,
        }
        messagesByChat.value.set(chatId, [...list])
      }
    }
  }

  async function acceptChat(chatId: number) {
    const res = await api.acceptChat(chatId)
    upsertChat(res.chat)
  }

  async function declineChat(chatId: number) {
    await api.declineChat(chatId)
    chats.value = chats.value.filter((c) => c.id !== chatId)
    messagesByChat.value.delete(chatId)
  }

  async function deleteChat(chatId: number) {
    await api.deleteChat(chatId)
    chats.value = chats.value.filter((c) => c.id !== chatId)
    messagesByChat.value.delete(chatId)
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
    appendMessages,
    syncChat,
    notifyTyping,
    sendMessage,
    editMessage,
    deleteMessage,
    acceptChat,
    declineChat,
    deleteChat,
  }
}
