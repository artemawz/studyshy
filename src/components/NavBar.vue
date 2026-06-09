<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import LinkedHeaderLogo from './LinkedHeaderLogo.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const { isLoggedIn, logout } = useAuth()
const { show } = useToast()

function handleLogout() {
  logout()
  show('Erfolgreich abgemeldet.', 'info')
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
      <RouterLink to="/why-studyshy" class="textlink" :class="{ active: isActive('/why-studyshy') }">
        Warum?
      </RouterLink>
      <RouterLink to="/about" class="textlink" :class="{ active: isActive('/about') }">
        Über uns
      </RouterLink>
      <RouterLink v-if="isLoggedIn" to="/chats" class="textlink" :class="{ active: isActive('/chats') }">
        Chats
      </RouterLink>
    </div>

    <div class="nav-actions">
      <template v-if="isLoggedIn">
        <RouterLink to="/register" class="textlink">Profil</RouterLink>
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
