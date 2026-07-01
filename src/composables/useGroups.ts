import { computed, ref } from 'vue'
import type { Group, GroupMessage } from '@/types'
import { api } from '@/services/api'

const groups = ref<Group[]>([])
const loading = ref(false)
const loaded = ref(false)
const messagesByGroup = ref<Map<number, GroupMessage[]>>(new Map())

const unreadCount = computed(() => groups.value.filter((g) => g.unread).length)

function parseMessage(message: GroupMessage): GroupMessage {
  return { ...message, sentAt: new Date(message.sentAt) }
}

export function useGroups() {
  async function fetchGroups() {
    loading.value = true
    try {
      const res = await api.getGroups()
      groups.value = res.data
    } finally {
      loading.value = false
      loaded.value = true
    }
  }

  function getGroupById(id: number) {
    return groups.value.find((g) => g.id === id)
  }

  async function fetchGroup(id: number) {
    const group = await api.getGroup(id)
    upsertGroup(group)
    return group
  }

  function upsertGroup(group: Group) {
    const idx = groups.value.findIndex((g) => g.id === group.id)
    if (idx >= 0) groups.value[idx] = group
    else groups.value.unshift(group)
    return group
  }

  async function createGroup(data: { name: string; description?: string; uni?: string; course?: string }) {
    return upsertGroup(await api.createGroup(data))
  }

  async function joinGroup(id: number) {
    return upsertGroup(await api.joinGroup(id))
  }

  async function leaveGroup(id: number) {
    await api.leaveGroup(id)
    await fetchGroup(id)
  }

  async function deleteGroup(id: number) {
    await api.deleteGroup(id)
    groups.value = groups.value.filter((g) => g.id !== id)
  }

  function getMessages(groupId: number) {
    return messagesByGroup.value.get(groupId) ?? []
  }

  function mergeMessages(groupId: number, incoming: GroupMessage[]) {
    if (incoming.length === 0) return
    const existing = messagesByGroup.value.get(groupId) ?? []
    const byId = new Map(existing.map((m) => [m.id, m]))
    for (const m of incoming) byId.set(m.id, parseMessage(m))
    const merged = [...byId.values()].sort(
      (a, b) => new Date(a.sentAt).getTime() - new Date(b.sentAt).getTime(),
    )
    messagesByGroup.value.set(groupId, merged)
  }

  async function fetchMessages(groupId: number) {
    const res = await api.getGroupMessages(groupId)
    messagesByGroup.value.set(groupId, res.data.map(parseMessage))
    return getMessages(groupId)
  }

  async function syncGroup(groupId: number, afterId: number) {
    const res = await api.syncGroup(groupId, afterId)
    mergeMessages(groupId, res.messages)
  }

  async function sendMessage(groupId: number, text: string, file?: File | null) {
    const message = await api.sendGroupMessage(groupId, text, file)
    mergeMessages(groupId, [message])
  }

  async function editMessage(groupId: number, messageId: number, text: string) {
    const message = await api.editGroupMessage(groupId, messageId, text)
    mergeMessages(groupId, [message])
  }

  async function deleteMessage(groupId: number, messageId: number) {
    await api.deleteGroupMessage(groupId, messageId)
    const list = messagesByGroup.value.get(groupId)
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
        messagesByGroup.value.set(groupId, [...list])
      }
    }
  }

  return {
    groups,
    unreadCount,
    loading,
    loaded,
    fetchGroups,
    getGroupById,
    fetchGroup,
    createGroup,
    joinGroup,
    leaveGroup,
    deleteGroup,
    getMessages,
    fetchMessages,
    syncGroup,
    sendMessage,
    editMessage,
    deleteMessage,
  }
}
