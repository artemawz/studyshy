export interface Course {
  name: string
  degree?: string | null
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

export interface PlatformStats {
  students: string
  universities: string
  connections: string
  anonymous: string
}

export interface ChatPreview {
  id: number
  partnerId: number
  partnerName: string
  partnerAvatarUrl?: string | null
  lastMessage: string
  updatedAt: string | Date
  unread: boolean
  status: 'pending' | 'accepted' | 'declined'
  requestedBy: number | null
  isIncomingRequest: boolean
  isOutgoingRequest: boolean
  isFriend: boolean
}

export interface Message {
  id: number
  chatId: number
  senderId: number
  text: string | null
  attachmentUrl?: string | null
  attachmentType?: 'image' | 'pdf' | null
  attachmentName?: string | null
  edited?: boolean
  deleted?: boolean
  sentAt: string | Date
}

export interface GroupMember {
  id: number
  pub_name?: string
  avatarUrl?: string | null
  role: string
}

export interface Group {
  id: number
  name: string
  description?: string | null
  uni?: string | null
  course?: string | null
  createdBy: number
  isOwner: boolean
  memberCount?: number
  isMember?: boolean
  members?: GroupMember[]
  unread?: boolean
  lastMessageAt?: string | null
  createdAt?: string
}

export interface GroupMessage {
  id: number
  groupId: number
  senderId: number
  senderName?: string
  senderAvatarUrl?: string | null
  text: string | null
  attachmentUrl?: string | null
  attachmentType?: 'image' | 'pdf' | null
  attachmentName?: string | null
  edited?: boolean
  deleted?: boolean
  sentAt: string | Date
}

export interface BoardPost {
  id: number
  category: string
  title: string
  body: string
  authorId: number
  isOwner: boolean
  commentCount?: number
  hasUnreadComments?: boolean
  author?: {
    id: number
    pub_name?: string
    avatarUrl?: string | null
    uni?: string | null
  }
  createdAt?: string
}

export interface BoardComment {
  id: number
  postId: number
  body: string
  authorId: number
  isOwner: boolean
  author?: {
    id: number
    pub_name?: string
    avatarUrl?: string | null
  }
  createdAt?: string
}

export interface EventItem {
  id: number
  title: string
  description?: string | null
  location?: string | null
  uni?: string | null
  startsAt: string
  createdBy: number
  isOwner: boolean
  creatorName?: string
  participantCount?: number
  isAttending?: boolean
  participants?: { id: number; pub_name?: string; avatarUrl?: string | null }[]
}

export interface BlockedUser {
  id: number
  user: User
}

export interface FilterOptions {
  unis: string[]
  courses: string[]
  coursesByUni: Record<string, string[]>
  degrees: string[]
  interests: string[]
  semesters: string[]
}

export interface StudentFilters {
  unis: string[]
  courses: string[]
  degrees: string[]
  interests: string[]
  semesters: string[]
}

export type FriendshipStatus =
  | 'none'
  | 'pending_outgoing'
  | 'pending_incoming'
  | 'accepted'
  | 'declined'

export interface FriendRequest {
  id: number
  status: string
  partner: User
  created_at?: string
}

export interface FriendEntry {
  id: number
  partner: User
  since?: string
}
