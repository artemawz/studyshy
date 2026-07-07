import { ref } from 'vue'
import type { FilterOptions, PlatformStats } from '@/types'
import { api } from '@/services/api'
import { filterOptions as fallbackFilters, platformStats as fallbackStats } from '@/data/mockData'

const filterOptions = ref<FilterOptions>({ ...fallbackFilters })
const platformStats = ref<PlatformStats>({ ...fallbackStats })
const loading = ref(false)

export function useMeta() {
  async function fetchMeta() {
    loading.value = true
    try {
      const [filters, stats] = await Promise.all([api.getFilters(), api.getStats()])
      filterOptions.value = filters
      platformStats.value = stats
    } catch {
      filterOptions.value = { ...fallbackFilters }
      platformStats.value = { ...fallbackStats }
    } finally {
      loading.value = false
    }
  }

  return { filterOptions, platformStats, loading, fetchMeta }
}
