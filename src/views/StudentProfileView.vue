<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FilterTag from '@/components/FilterTag.vue'
import TagList from '@/components/TagList.vue'
import AppButton from '@/components/AppButton.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useStudents } from '@/composables/useStudents'
import { useAuth } from '@/composables/useAuth'
import { useFriends } from '@/composables/useFriends'
import { useBlocks } from '@/composables/useBlocks'
import { useToast } from '@/composables/useToast'
import { getUserTags } from '@/misc'
import { ApiError, api } from '@/services/api'
import type { FriendshipStatus, User } from '@/types'

const route = useRoute()
const router = useRouter()
const { fetchStudentById } = useStudents()
const { isLoggedIn, currentUser } = useAuth()
const { getStatus, sendRequest, acceptRequest, declineRequest, cancelOrRemove } = useFriends()
const { blockUser, unblockUser, reportUser } = useBlocks()
const { show } = useToast()

const isBlocked = ref(false)

const isOwnProfile = computed(
  () => isLoggedIn.value && currentUser.value?.id === studentId.value,
)

const studentId = computed(() => parseInt(route.params.id as string, 10))
const student = ref<User | undefined>(undefined)
const loading = ref(true)
const friendshipStatus = ref<FriendshipStatus>('none')
const friendshipId = ref<number | null>(null)
const actionLoading = ref(false)

const displayName = computed(
  () => student.value?.pub_name ?? `Student #${studentId.value.toString(16)}`,
)

const isFriend = computed(() => friendshipStatus.value === 'accepted')

async function loadStudent() {
  loading.value = true
  if (isOwnProfile.value && currentUser.value) {
    student.value = currentUser.value
  } else {
    student.value = await fetchStudentById(studentId.value)
  }
  loading.value = false
}

async function loadFriendStatus() {
  if (!isLoggedIn.value || isOwnProfile.value) {
    friendshipStatus.value = 'none'
    friendshipId.value = null
    return
  }

  try {
    const data = await getStatus(studentId.value)
    friendshipStatus.value = data.status
    friendshipId.value = data.friendship_id
  } catch {
    friendshipStatus.value = 'none'
    friendshipId.value = null
  }
}

async function loadBlockStatus() {
  if (!isLoggedIn.value || isOwnProfile.value) {
    isBlocked.value = false
    return
  }
  try {
    const res = await api.getBlocks()
    isBlocked.value = res.data.some((b) => b.user.id === studentId.value)
  } catch {
    isBlocked.value = false
  }
}

watch([studentId, currentUser], loadStudent, { immediate: true })
watch([studentId, isLoggedIn, isOwnProfile], loadFriendStatus, { immediate: true })
watch([studentId, isLoggedIn, isOwnProfile], loadBlockStatus, { immediate: true })

async function handleBlock() {
  if (!isLoggedIn.value) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }
  if (!confirm(`${displayName.value} blockieren? Eure Freundschaft und Chats werden beendet.`)) return
  actionLoading.value = true
  try {
    await blockUser(studentId.value)
    isBlocked.value = true
    friendshipStatus.value = 'none'
    friendshipId.value = null
    show('Nutzer blockiert.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Blockieren fehlgeschlagen.', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleUnblock() {
  actionLoading.value = true
  try {
    await unblockUser(studentId.value)
    isBlocked.value = false
    show('Blockierung aufgehoben.', 'success')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Aktion fehlgeschlagen.', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleReport() {
  if (!isLoggedIn.value) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }
  const reason = prompt(`Warum möchtest du ${displayName.value} melden?`)
  if (!reason || !reason.trim()) return
  try {
    await reportUser({ reported_user_id: studentId.value, context: 'profile', reason: reason.trim() })
    show('Danke für deine Meldung. Wir schauen uns das an.', 'success')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Meldung fehlgeschlagen.', 'error')
  }
}

function startChat() {
  if (!isLoggedIn.value) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }
  router.push({ name: 'chat-view', params: { id: studentId.value.toString() } })
}

async function handleSendFriendRequest() {
  if (!isLoggedIn.value) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }

  actionLoading.value = true
  try {
    await sendRequest(studentId.value)
    friendshipStatus.value = 'pending_outgoing'
    show('Freundschaftsanfrage gesendet.', 'success')
    await loadFriendStatus()
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht gesendet werden.', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleAcceptFriend() {
  if (!friendshipId.value) return
  actionLoading.value = true
  try {
    await acceptRequest(friendshipId.value)
    friendshipStatus.value = 'accepted'
    show('Ihr seid jetzt befreundet.', 'success')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht angenommen werden.', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleDeclineFriend() {
  if (!friendshipId.value) return
  actionLoading.value = true
  try {
    await declineRequest(friendshipId.value)
    friendshipStatus.value = 'declined'
    show('Freundschaftsanfrage abgelehnt.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht abgelehnt werden.', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleCancelFriendRequest() {
  if (!friendshipId.value) return
  actionLoading.value = true
  try {
    await cancelOrRemove(friendshipId.value)
    friendshipStatus.value = 'none'
    friendshipId.value = null
    show('Anfrage zurückgezogen.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Anfrage konnte nicht zurückgezogen werden.', 'error')
  } finally {
    actionLoading.value = false
  }
}
</script>

<template>
  <div v-if="loading" class="page-narrow">
    <EmptyState title="Profil wird geladen…" />
  </div>

  <div v-else-if="student" class="page-narrow">
    <div class="card profile">
      <img v-if="student.avatarUrl" :src="student.avatarUrl" width="96" height="96" alt="student-avatar" class="avatar" />
      <h1 class="page-title">{{ displayName }}</h1>
      <p class="uni">{{ student.uni }}</p>

      <p v-if="student.bio" class="bio">{{ student.bio }}</p>

      <TagList title="Studiengang & Interessen">
        <FilterTag v-for="tag in getUserTags(student)" :key="tag" :text="tag" />
      </TagList>

      <div class="actions">
        <template v-if="isOwnProfile">
          <RouterLink :to="{ name: 'profile-edit' }" class="btn">Profil bearbeiten</RouterLink>
          <RouterLink :to="{ name: 'block-list' }" class="btn btn-secondary">Blockierte Nutzer</RouterLink>
          <RouterLink to="/" class="btn btn-secondary">Zurück zur Suche</RouterLink>
        </template>
        <template v-else-if="isBlocked">
          <span class="status-hint">Du hast diesen Nutzer blockiert.</span>
          <AppButton variant="secondary" :disabled="actionLoading" @click="handleUnblock">
            Blockierung aufheben
          </AppButton>
          <RouterLink to="/" class="btn btn-secondary">Zurück zur Suche</RouterLink>
        </template>
        <template v-else>
          <template v-if="!isLoggedIn">
            <AppButton @click="handleSendFriendRequest">Freund hinzufügen</AppButton>
          </template>
          <template v-else-if="friendshipStatus === 'accepted'">
            <AppButton @click="startChat">Chat starten</AppButton>
          </template>
          <template v-else-if="friendshipStatus === 'pending_outgoing'">
            <AppButton variant="secondary" :disabled="actionLoading" @click="handleCancelFriendRequest">
              Anfrage zurückziehen
            </AppButton>
            <span class="status-hint">Freundschaftsanfrage ausstehend</span>
          </template>
          <template v-else-if="friendshipStatus === 'pending_incoming'">
            <AppButton :disabled="actionLoading" @click="handleAcceptFriend">Annehmen</AppButton>
            <AppButton variant="secondary" :disabled="actionLoading" @click="handleDeclineFriend">
              Ablehnen
            </AppButton>
          </template>
          <template v-else>
            <AppButton :disabled="actionLoading" @click="handleSendFriendRequest">Freund hinzufügen</AppButton>
          </template>
          <RouterLink to="/" class="btn btn-secondary">Zurück zur Suche</RouterLink>
        </template>
      </div>

      <div v-if="isLoggedIn && !isOwnProfile && !isBlocked" class="safety-actions">
        <button type="button" class="link-btn" @click="handleReport">Melden</button>
        <span class="dot">·</span>
        <button type="button" class="link-btn" @click="handleBlock">Blockieren</button>
      </div>
    </div>
  </div>

  <div v-else class="page-narrow">
    <EmptyState title="Profil nicht gefunden" hint="Dieser Studierende existiert nicht oder wurde entfernt.">
      <RouterLink to="/" class="btn" style="margin-top: 1rem">Zur Startseite</RouterLink>
    </EmptyState>
  </div>
</template>

<style scoped>
.profile {
  text-align: center;
}

.avatar {
  border-radius: 50%;
  object-fit: cover;
}

.uni {
  color: var(--color-text-muted);
  margin: -1rem 0 1.5rem;
}

.bio {
  text-align: left;
  line-height: 1.6;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: var(--color-bg);
  border-radius: 0.4rem;
  border: 1px solid var(--color-border-subtle);
}

.actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
  flex-wrap: wrap;
  align-items: center;
}

.status-hint {
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

.safety-actions {
  margin-top: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.safety-actions .link-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-muted);
  font-size: 0.85rem;
}

.safety-actions .link-btn:hover {
  color: #f87171;
  text-decoration: underline;
}

.safety-actions .dot {
  color: var(--color-text-muted);
}
</style>
