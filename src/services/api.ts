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

  createChat(partnerId: number) {
    return request<{ chat: import('@/types').ChatPreview }>('/chats', {
      method: 'POST',
      body: JSON.stringify({ partner_id: partnerId }),
    })
  },

  getMessages(chatId: number) {
    return request<{ data: import('@/types').Message[] }>(`/chats/${chatId}/messages`)
  },

  sendMessage(chatId: number, text: string) {
    return request<{ data: import('@/types').Message }>(`/chats/${chatId}/messages`, {
      method: 'POST',
      body: JSON.stringify({ text }),
    }).then((res) => res.data)
  },
}
