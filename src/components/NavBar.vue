<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import LinkedHeaderLogo from './LinkedHeaderLogo.vue'
import NotificationBell from './NotificationBell.vue'
import { useAuth } from '@/composables/useAuth'
import { useFriends } from '@/composables/useFriends'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const { isLoggedIn, logout, currentUser } = useAuth()
const { incomingCount, fetchFriends } = useFriends()
const { show } = useToast()

onMounted(() => {
  if (isLoggedIn.value) {
    fetchFriends()
  }
})

async function handleLogout() {
  const onProtectedRoute = Boolean(route.meta.requiresAuth)
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
    <LinkedHeaderLogo />

    <div class="nav-links">
      <RouterLink to="/" class="textlink" :class="{ active: isActive('/') && route.path === '/' }">
        Entdecken
      </RouterLink>
      <RouterLink to="/groups" class="textlink" :class="{ active: isActive('/groups') }">
        Gruppen
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
  </nav>
</template>

<style scoped>
nav {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px dashed var(--color-border);
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

@media (max-width: 720px) {
  nav {
    flex-wrap: wrap;
  }

  .nav-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
