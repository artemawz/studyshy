import { computed, ref } from 'vue'
import type { FriendEntry, FriendRequest, FriendshipStatus } from '@/types'
import { api } from '@/services/api'

const friends = ref<FriendEntry[]>([])
const incoming = ref<FriendRequest[]>([])
const outgoing = ref<FriendRequest[]>([])
const loading = ref(false)
const loaded = ref(false)
const error = ref<string | null>(null)

const incomingCount = computed(() => incoming.value.length)

async function fetchFriends() {
  loading.value = true
  error.value = null
  try {
    const data = await api.getFriends()
    friends.value = data.friends
    incoming.value = data.incoming
    outgoing.value = data.outgoing
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Freunde konnten nicht geladen werden.'
  } finally {
    loading.value = false
    loaded.value = true
  }
}

async function getStatus(userId: number) {
  return api.getFriendStatus(userId)
}

async function sendRequest(userId: number) {
  const res = await api.requestFriend(userId)
  await fetchFriends()
  return res
}

async function acceptRequest(friendshipId: number) {
  await api.acceptFriend(friendshipId)
  await fetchFriends()
}

async function declineRequest(friendshipId: number) {
  await api.declineFriend(friendshipId)
  await fetchFriends()
}

async function cancelOrRemove(friendshipId: number) {
  await api.removeFriend(friendshipId)
  await fetchFriends()
}

export function useFriends() {
  return {
    friends,
    incoming,
    outgoing,
    incomingCount,
    loading,
    loaded,
    error,
    fetchFriends,
    getStatus,
    sendRequest,
    acceptRequest,
    declineRequest,
    cancelOrRemove,
  }
}
