const BASE = import.meta.env.VITE_API_URL ?? '/api'
const TOKEN_KEY = 'studyshy-token'

export class ApiError extends Error {
  constructor(
    public status: number,
    public body: Record<string, unknown>,
  ) {
    const message =
      (body.message as string) ||
      (body.errors && typeof body.errors === 'object'
        ? Object.values(body.errors as Record<string, string[]>).flat()[0]
        : undefined) ||
      `API-Fehler (${status})`
    super(message)
  }
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = localStorage.getItem(TOKEN_KEY)
  const isFormData = options.body instanceof FormData
  const headers: Record<string, string> = {
    Accept: 'application/json',
    ...(!isFormData && options.body ? { 'Content-Type': 'application/json' } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  }

  const res = await fetch(`${BASE}${path}`, {
    ...options,
    headers: { ...headers, ...(options.headers as Record<string, string>) },
  })

  const body = res.status === 204 ? {} : await res.json().catch(() => ({}))

  if (!res.ok) {
    throw new ApiError(res.status, body as Record<string, unknown>)
  }

  return body as T
}

export function setToken(token: string | null) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }
}

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export const api = {
  register(data: {
    email: string
    password: string
    uni: string
    course: string
    degree?: string | null
    semester: number
    interests: string[]
  }) {
    return request<{ token: string; user: import('@/types').User }>('/auth/register', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  login(email: string, password: string) {
    return request<{ token: string; user: import('@/types').User }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    })
  },

  logout() {
    return request<{ message: string }>('/auth/logout', { method: 'POST' })
  },

  me() {
    return request<{ data: import('@/types').User }>('/auth/me').then((res) => res.data)
  },

  getStudents(params?: Record<string, string[]>) {
    const search = new URLSearchParams()
    if (params) {
      for (const [key, values] of Object.entries(params)) {
        values.forEach((v) => search.append(`${key}[]`, v))
      }
    }
    const query = search.toString()
    return request<{ data: import('@/types').User[] }>(`/students${query ? `?${query}` : ''}`)
  },

  getStudent(id: number) {
    return request<{ data: import('@/types').User }>(`/students/${id}`).then((res) => res.data)
  },

  getFilters() {
    return request<import('@/types').FilterOptions>('/filters')
  },

  getStats() {
    return request<import('@/types').PlatformStats>('/stats')
  },

  updateProfile(data: {
    pub_name?: string
    avatar_url?: string | null
    bio?: string
    uni?: string
    course?: string
    degree?: string | null
    semester?: number
    interests?: string[]
  }) {
    return request<{ data: import('@/types').User }>('/users/me', {
      method: 'PATCH',
      body: JSON.stringify(data),
    }).then((res) => res.data)
  },

  uploadAvatar(file: File) {
    const formData = new FormData()
    formData.append('avatar', file)
    return request<{ data: import('@/types').User }>('/users/me/avatar', {
      method: 'POST',
      body: formData,
    }).then((res) => res.data)
  },

  getChats() {
    return request<{ data: import('@/types').ChatPreview[] }>('/chats')
  },

  createChat(partnerId: number, message?: string) {
    return request<{ chat: import('@/types').ChatPreview }>('/chats', {
      method: 'POST',
      body: JSON.stringify({ partner_id: partnerId, ...(message ? { message } : {}) }),
    })
  },

  acceptChat(chatId: number) {
    return request<{ chat: import('@/types').ChatPreview }>(`/chats/${chatId}/accept`, {
      method: 'POST',
    })
  },

  declineChat(chatId: number) {
    return request<{ message: string }>(`/chats/${chatId}/decline`, { method: 'POST' })
  },

  deleteChat(chatId: number) {
    return request<{ message: string }>(`/chats/${chatId}`, { method: 'DELETE' })
  },

  getMessages(chatId: number) {
    return request<{ data: import('@/types').Message[] }>(`/chats/${chatId}/messages`)
  },

  sendMessage(chatId: number, text: string, file?: File | null) {
    if (file) {
      const formData = new FormData()
      if (text) formData.append('text', text)
      formData.append('attachment', file)
      return request<{ data: import('@/types').Message }>(`/chats/${chatId}/messages`, {
        method: 'POST',
        body: formData,
      }).then((res) => res.data)
    }
    return request<{ data: import('@/types').Message }>(`/chats/${chatId}/messages`, {
      method: 'POST',
      body: JSON.stringify({ text }),
    }).then((res) => res.data)
  },

  editMessage(chatId: number, messageId: number, text: string) {
    return request<{ data: import('@/types').Message }>(
      `/chats/${chatId}/messages/${messageId}`,
      { method: 'PATCH', body: JSON.stringify({ text }) },
    ).then((res) => res.data)
  },

  deleteMessage(chatId: number, messageId: number) {
    return request<{ message: string }>(`/chats/${chatId}/messages/${messageId}`, {
      method: 'DELETE',
    })
  },

  syncChat(chatId: number, afterMessageId: number) {
    const query = afterMessageId > 0 ? `?after=${afterMessageId}` : ''
    return request<{
      messages: import('@/types').Message[]
      partner_typing: boolean
      status: import('@/types').ChatPreview['status']
      requested_by: number | null
      is_incoming_request: boolean
      is_outgoing_request: boolean
      is_friend: boolean
    }>(`/chats/${chatId}/sync${query}`)
  },

  sendTyping(chatId: number) {
    return request<{ ok: boolean }>(`/chats/${chatId}/typing`, { method: 'POST' })
  },

  getFriends() {
    return request<{
      friends: import('@/types').FriendEntry[]
      incoming: import('@/types').FriendRequest[]
      outgoing: import('@/types').FriendRequest[]
    }>('/friends')
  },

  getFriendStatus(userId: number) {
    return request<{
      status: import('@/types').FriendshipStatus
      friendship_id: number | null
    }>(`/friends/status/${userId}`)
  },

  requestFriend(userId: number) {
    return request<{
      status: import('@/types').FriendshipStatus
      friendship_id: number
    }>('/friends/request', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId }),
    })
  },

  acceptFriend(friendshipId: number) {
    return request<{ status: import('@/types').FriendshipStatus }>(
      `/friends/${friendshipId}/accept`,
      { method: 'POST' },
    )
  },

  declineFriend(friendshipId: number) {
    return request<{ status: import('@/types').FriendshipStatus }>(
      `/friends/${friendshipId}/decline`,
      { method: 'POST' },
    )
  },

  removeFriend(friendshipId: number) {
    return request<{ status: import('@/types').FriendshipStatus }>(
      `/friends/${friendshipId}`,
      { method: 'DELETE' },
    )
  },

  // Blockieren & Melden
  getBlocks() {
    return request<{ data: import('@/types').BlockedUser[] }>('/blocks')
  },

  blockUser(userId: number) {
    return request<{ message: string }>('/blocks', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId }),
    })
  },

  unblockUser(userId: number) {
    return request<{ message: string }>(`/blocks/${userId}`, { method: 'DELETE' })
  },

  reportUser(data: { reported_user_id?: number; context?: string; reason: string; details?: string }) {
    return request<{ message: string }>('/reports', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  },

  // Lerngruppen
  getGroups() {
    return request<{ data: import('@/types').Group[] }>('/groups')
  },

  getGroup(groupId: number) {
    return request<{ data: import('@/types').Group }>(`/groups/${groupId}`).then((res) => res.data)
  },

  createGroup(data: { name: string; description?: string; uni?: string; course?: string }) {
    return request<{ data: import('@/types').Group }>('/groups', {
      method: 'POST',
      body: JSON.stringify(data),
    }).then((res) => res.data)
  },

  deleteGroup(groupId: number) {
    return request<{ message: string }>(`/groups/${groupId}`, { method: 'DELETE' })
  },

  joinGroup(groupId: number) {
    return request<{ data: import('@/types').Group }>(`/groups/${groupId}/join`, {
      method: 'POST',
    }).then((res) => res.data)
  },

  leaveGroup(groupId: number) {
    return request<{ message: string }>(`/groups/${groupId}/leave`, { method: 'POST' })
  },

  getGroupMessages(groupId: number) {
    return request<{ data: import('@/types').GroupMessage[] }>(`/groups/${groupId}/messages`)
  },

  syncGroup(groupId: number, afterMessageId: number) {
    const query = afterMessageId > 0 ? `?after=${afterMessageId}` : ''
    return request<{ messages: import('@/types').GroupMessage[] }>(
      `/groups/${groupId}/sync${query}`,
    )
  },

  sendGroupMessage(groupId: number, text: string, file?: File | null) {
    if (file) {
      const formData = new FormData()
      if (text) formData.append('text', text)
      formData.append('attachment', file)
      return request<{ data: import('@/types').GroupMessage }>(`/groups/${groupId}/messages`, {
        method: 'POST',
        body: formData,
      }).then((res) => res.data)
    }
    return request<{ data: import('@/types').GroupMessage }>(`/groups/${groupId}/messages`, {
      method: 'POST',
      body: JSON.stringify({ text }),
    }).then((res) => res.data)
  },

  editGroupMessage(groupId: number, messageId: number, text: string) {
    return request<{ data: import('@/types').GroupMessage }>(
      `/groups/${groupId}/messages/${messageId}`,
      { method: 'PATCH', body: JSON.stringify({ text }) },
    ).then((res) => res.data)
  },

  deleteGroupMessage(groupId: number, messageId: number) {
    return request<{ message: string }>(`/groups/${groupId}/messages/${messageId}`, {
      method: 'DELETE',
    })
  },

  // Schwarzes Brett / Pinnwand
  getBoardPosts(category?: string) {
    const query = category ? `?category=${encodeURIComponent(category)}` : ''
    return request<{ data: import('@/types').BoardPost[] }>(`/board${query}`)
  },

  createBoardPost(data: { category: string; title: string; body: string }) {
    return request<{ data: import('@/types').BoardPost }>('/board', {
      method: 'POST',
      body: JSON.stringify(data),
    }).then((res) => res.data)
  },

  deleteBoardPost(postId: number) {
    return request<{ message: string }>(`/board/${postId}`, { method: 'DELETE' })
  },

  getBoardComments(postId: number) {
    return request<{ data: import('@/types').BoardComment[] }>(`/board/${postId}/comments`)
  },

  addBoardComment(postId: number, body: string) {
    return request<{ data: import('@/types').BoardComment }>(`/board/${postId}/comments`, {
      method: 'POST',
      body: JSON.stringify({ body }),
    }).then((res) => res.data)
  },

  deleteBoardComment(commentId: number) {
    return request<{ message: string }>(`/board/comments/${commentId}`, { method: 'DELETE' })
  },

  // Events / Treffen
  getEvents() {
    return request<{ data: import('@/types').EventItem[] }>('/events')
  },

  createEvent(data: {
    title: string
    description?: string
    location?: string
    uni?: string
    starts_at: string
  }) {
    return request<{ data: import('@/types').EventItem }>('/events', {
      method: 'POST',
      body: JSON.stringify(data),
    }).then((res) => res.data)
  },

  deleteEvent(eventId: number) {
    return request<{ message: string }>(`/events/${eventId}`, { method: 'DELETE' })
  },

  joinEvent(eventId: number) {
    return request<{ data: import('@/types').EventItem }>(`/events/${eventId}/join`, {
      method: 'POST',
    }).then((res) => res.data)
  },

  leaveEvent(eventId: number) {
    return request<{ data: import('@/types').EventItem }>(`/events/${eventId}/leave`, {
      method: 'POST',
    }).then((res) => res.data)
  },
}
