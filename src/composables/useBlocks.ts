import { ref } from 'vue'
import type { BlockedUser } from '@/types'
import { api } from '@/services/api'

const blocked = ref<BlockedUser[]>([])
const loading = ref(false)

export function useBlocks() {
  async function fetchBlocks() {
    loading.value = true
    try {
      const res = await api.getBlocks()
      blocked.value = res.data
    } finally {
      loading.value = false
    }
  }

  async function blockUser(userId: number) {
    await api.blockUser(userId)
  }

  async function unblockUser(userId: number) {
    await api.unblockUser(userId)
    blocked.value = blocked.value.filter((b) => b.user.id !== userId)
  }

  async function reportUser(data: {
    reported_user_id?: number
    context?: string
    reason: string
    details?: string
  }) {
    await api.reportUser(data)
  }

  return { blocked, loading, fetchBlocks, blockUser, unblockUser, reportUser }
}
