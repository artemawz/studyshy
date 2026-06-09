import { ref } from 'vue'
import { mockChats, mockMessages } from '@/data/mockData'
import type { Message } from '@/types'

const messages = ref<Message[]>([...mockMessages])

export function useChats() {
  function getChatById(id: number) {
    return mockChats.find((c) => c.id === id)
  }

  function getChatByPartnerId(partnerId: number) {
    return mockChats.find((c) => c.partnerId === partnerId)
  }

  function resolveChatId(routeId: number): number {
    const byChat = getChatById(routeId)
    if (byChat) return byChat.id
    const byPartner = getChatByPartnerId(routeId)
    if (byPartner) return byPartner.id
    return routeId
  }

  function getMessagesForChat(chatId: number) {
    return messages.value.filter((m) => m.chatId === chatId)
  }

  function sendMessage(chatId: number, text: string) {
    messages.value.push({
      id: messages.value.length + 1,
      chatId,
      senderId: 0,
      text,
      sentAt: new Date(),
    })
  }

  return { chats: mockChats, getChatById, getChatByPartnerId, resolveChatId, getMessagesForChat, sendMessage }
}
