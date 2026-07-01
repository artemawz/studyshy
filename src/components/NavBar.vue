<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import LinkedHeaderLogo from './LinkedHeaderLogo.vue'
import NotificationBell from './NotificationBell.vue'
import { useAuth } from '@/composables/useAuth'
import { useFriends } from '@/composables/useFriends'
import { useChats } from '@/composables/useChats'
import { useGroups } from '@/composables/useGroups'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const { isLoggedIn, logout, currentUser } = useAuth()
const { incomingCount, fetchFriends } = useFriends()
const { unreadCount: unreadChatCount } = useChats()
const { unreadCount: unreadGroupCount } = useGroups()
const { show } = useToast()

const menuOpen = ref(false)

onMounted(() => {
  if (isLoggedIn.value) {
    fetchFriends()
  }
})

// Menü beim Seitenwechsel automatisch schließen (mobil).
watch(
  () => route.fullPath,
  () => {
    menuOpen.value = false
  },
)

async function handleLogout() {
  const onProtectedRoute = Boolean(route.meta.requiresAuth)
  menuOpen.value = false
  await logout()
  show('Erfolgreich abgemeldet.', 'info')
  if (onProtectedRoute) {
    await router.push({ name: 'discover' })
  }
}

function isActive(path: string) {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<template>
  <nav>
    <div class="nav-bar-row">
      <LinkedHeaderLogo />

      <div id="nav-collapsible" class="nav-collapsible" :class="{ open: menuOpen }">
        <div class="nav-links">
          <RouterLink to="/" class="textlink" :class="{ active: isActive('/') && route.path === '/' }">
            Entdecken
          </RouterLink>
          <RouterLink to="/groups" class="textlink" :class="{ active: isActive('/groups') }">
            Gruppen
            <span v-if="isLoggedIn && unreadGroupCount > 0" class="badge">{{ unreadGroupCount }}</span>
          </RouterLink>
          <RouterLink to="/board" class="textlink" :class="{ active: isActive('/board') }">
            Pinnwand
          </RouterLink>
          <RouterLink to="/events" class="textlink" :class="{ active: isActive('/events') }">
            Events
          </RouterLink>
          <RouterLink to="/why-studyshy" v-if="!isLoggedIn" class="textlink" :class="{ active: isActive('/why-studyshy') }">
            Warum?
          </RouterLink>
          <RouterLink v-if="isLoggedIn" to="/friends" class="textlink" :class="{ active: isActive('/friends') }">
            Freunde
            <span v-if="incomingCount > 0" class="badge">{{ incomingCount }}</span>
          </RouterLink>
          <RouterLink v-if="isLoggedIn" to="/chats" class="textlink" :class="{ active: isActive('/chats') }">
            Chats
            <span v-if="unreadChatCount > 0" class="badge">{{ unreadChatCount }}</span>
          </RouterLink>
        </div>

        <div class="nav-actions">
          <template v-if="isLoggedIn">
            <NotificationBell />
            <RouterLink
              v-if="currentUser"
              :to="{ name: 'profile-view', params: { id: currentUser.id.toString() } }"
              class="textlink"
              :class="{
                active:
                  (route.name === 'profile-view' && route.params.id === currentUser.id.toString()) ||
                  route.name === 'profile-edit',
              }"
            >
              Profil
            </RouterLink>
            <button class="btn btn-secondary" @click="handleLogout">Logout</button>
          </template>
          <template v-else>
            <RouterLink to="/login" class="btn">Login</RouterLink>
            <RouterLink to="/register" class="btn btn-secondary">Registrieren</RouterLink>
          </template>
        </div>
      </div>

      <button
        type="button"
        class="menu-toggle"
        aria-controls="nav-collapsible"
        :aria-expanded="menuOpen"
        aria-label="Menü öffnen"
        @click="menuOpen = !menuOpen"
      >
        {{ menuOpen ? '✕' : '☰' }}
      </button>
    </div>
  </nav>
</template>

<style scoped>
nav {
  padding: 0.75rem 1rem;
  border-bottom: 1px dashed var(--color-border);
}

.nav-bar-row {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav-collapsible {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex: 1;
  min-width: 0;
}

.nav-links {
  display: flex;
  gap: 1.25rem;
  flex-wrap: wrap;
}

.nav-actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.menu-toggle {
  display: none;
  align-items: center;
  justify-content: center;
  background: none;
  border: 1px solid var(--color-border);
  border-radius: 0.4rem;
  color: var(--color-text);
  width: 2.25rem;
  height: 2.25rem;
  font-size: 1.1rem;
  line-height: 1;
  cursor: pointer;
  flex-shrink: 0;
  margin-left: auto;
}

.menu-toggle:hover {
  border-color: var(--color-accent);
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.35rem;
  margin-left: 0.35rem;
  border-radius: 999px;
  background: var(--color-accent-hover);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 1;
}

/* Unterhalb dieser Breite passt die volle Nav (Logo + alle Links + Aktionen)
   nicht mehr überschneidungsfrei in eine Zeile -> Hamburger-Menü. */
@media (max-width: 960px) {
  .nav-bar-row {
    flex-wrap: wrap;
  }

  .menu-toggle {
    display: inline-flex;
  }

  .nav-collapsible {
    display: none;
    flex-basis: 100%;
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px dashed var(--color-border);
  }

  .nav-collapsible.open {
    display: flex;
  }

  .nav-links {
    flex-direction: column;
    gap: 0.9rem;
  }

  .nav-actions {
    margin-left: 0;
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }

  .nav-actions .btn,
  .nav-actions .textlink {
    text-align: center;
  }
}
</style>
