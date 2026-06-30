import { ref } from 'vue'
import type { EventItem } from '@/types'
import { api } from '@/services/api'

const events = ref<EventItem[]>([])
const loading = ref(false)

export function useEvents() {
  async function fetchEvents() {
    loading.value = true
    try {
      const res = await api.getEvents()
      events.value = res.data
    } finally {
      loading.value = false
    }
  }

  function upsert(event: EventItem) {
    const idx = events.value.findIndex((e) => e.id === event.id)
    if (idx >= 0) events.value[idx] = event
    else events.value.push(event)
    events.value.sort((a, b) => new Date(a.startsAt).getTime() - new Date(b.startsAt).getTime())
    return event
  }

  async function createEvent(data: {
    title: string
    description?: string
    location?: string
    uni?: string
    starts_at: string
  }) {
    return upsert(await api.createEvent(data))
  }

  async function joinEvent(id: number) {
    return upsert(await api.joinEvent(id))
  }

  async function leaveEvent(id: number) {
    return upsert(await api.leaveEvent(id))
  }

  async function deleteEvent(id: number) {
    await api.deleteEvent(id)
    events.value = events.value.filter((e) => e.id !== id)
  }

  return { events, loading, fetchEvents, createEvent, joinEvent, leaveEvent, deleteEvent }
}
