<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import EmptyState from '@/components/EmptyState.vue'
import AppButton from '@/components/AppButton.vue'
import { useBlocks } from '@/composables/useBlocks'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/services/api'

const router = useRouter()
const { blocked, loading, fetchBlocks, unblockUser } = useBlocks()
const { show } = useToast()

onMounted(fetchBlocks)

function partnerName(user: { pub_name?: string; id: number }) {
  return user.pub_name ?? `Student #${user.id.toString(16)}`
}

function partnerInitial(user: { pub_name?: string; id: number }) {
  return partnerName(user).charAt(0)
}

function openProfile(userId: number) {
  router.push({ name: 'profile-view', params: { id: userId.toString() } })
}

async function handleUnblock(userId: number) {
  if (!confirm('Blockierung wirklich aufheben?')) return
  try {
    await unblockUser(userId)
    show('Blockierung aufgehoben.', 'success')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Aktion fehlgeschlagen.', 'error')
  }
}
</script>

<template>
  <div class="page-narrow">
    <h1 class="page-title">Blockierte Nutzer</h1>
    <p class="page-subtitle">Hier kannst du Blockierungen wieder aufheben.</p>

    <EmptyState v-if="loading && !blocked.length" title="Blockliste wird geladen…" />

    <EmptyState
      v-else-if="!blocked.length"
      title="Keine blockierten Nutzer"
      hint="Nutzer, die du blockierst, tauchen hier auf."
    />

    <ul v-else class="friend-list">
      <li v-for="entry in blocked" :key="entry.id" class="friend-item">
        <button type="button" class="partner" @click="openProfile(entry.user.id)">
          <img v-if="entry.user.avatarUrl" :src="entry.user.avatarUrl" class="friend-avatar" alt="" />
          <span v-else class="friend-avatar placeholder">{{ partnerInitial(entry.user) }}</span>
          <span class="partner-name">{{ partnerName(entry.user) }}</span>
        </button>
        <div class="item-actions">
          <AppButton variant="secondary" @click="handleUnblock(entry.user.id)">
            Blockierung aufheben
          </AppButton>
        </div>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.friend-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.friend-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 1rem;
  border: 1px solid var(--color-border-subtle);
  border-radius: 0.5rem;
  background: var(--color-surface);
}

.partner {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  cursor: pointer;
  text-align: left;
  min-width: 0;
}

.friend-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.friend-avatar.placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
  text-transform: uppercase;
}

.partner-name {
  font-weight: 600;
  color: var(--color-accent);
}

.partner:hover .partner-name {
  text-decoration: underline;
}

.item-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
</style>
