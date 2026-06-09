<script setup lang="ts">
defineProps<{
  title: string
  hint?: string
}>()
</script>

<template>
  <div class="empty-state">
    <p class="empty-title">{{ title }}</p>
    <p v-if="hint" class="empty-hint">{{ hint }}</p>
    <slot />
  </div>
</template>

<style scoped>
.empty-title {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
  color: white;
}

.empty-hint {
  margin: 0;
  font-size: 0.95rem;
}
</style>
