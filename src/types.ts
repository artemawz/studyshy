export interface Course {
  name: string
  semester: number
}

export interface User {
  id: number
  pub_name?: string
  uni?: string
  interests?: string[]
  courses?: Course[]
  avatarUrl?: string
  bio?: string
}

export interface ChatPreview {
  id: number
  partnerId: number
  partnerName: string
  lastMessage: string
  updatedAt: Date
  unread: boolean
}

export interface Message {
  id: number
  chatId: number
  senderId: number
  text: string
  sentAt: Date
}

export interface FilterOptions {
  unis: string[]
  courses: string[]
  interests: string[]
  semesters: string[]
}

export interface StudentFilters {
  unis: string[]
  courses: string[]
  interests: string[]
  semesters: string[]
}
