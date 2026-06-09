<script setup lang="ts">
defineProps<{
  count?: number
}>()
</script>

<template>
  <div class="matches-list">
    <h2>
      Deine Matches
      <span v-if="count !== undefined" class="count">({{ count }})</span>
    </h2>
    <div class="matches-grid">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.matches-list h2 {
  margin-bottom: 1rem;
  color: var(--color-text);
}

.count {
  color: var(--color-accent);
  font-size: 1rem;
}

.matches-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}
</style>
