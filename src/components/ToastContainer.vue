<script setup lang="ts">
import { useToast } from '@/composables/useToast'

const { toasts, dismiss } = useToast()
</script>

<template>
  <div class="toast-container" aria-live="polite">
    <div
      v-for="toast in toasts"
      :key="toast.id"
      class="toast"
      :class="toast.type"
      @click="dismiss(toast.id)"
    >
      {{ toast.message }}
    </div>
  </div>
</template>

<style scoped>
.toast-container {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-width: min(360px, 90vw);
}

.toast {
  padding: 0.75rem 1rem;
  border-radius: 0.4rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  cursor: pointer;
  font-size: 0.95rem;
  box-shadow: 0 4px 16px #00000066;
}

.toast.success {
  border-color: #4ade80;
}

.toast.error {
  border-color: #ff6b6b;
}

.toast.info {
  border-color: var(--color-accent);
}
</style>
