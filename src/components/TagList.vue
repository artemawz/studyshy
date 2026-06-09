<script setup lang="ts">
defineProps<{
  title?: string
}>()
</script>

<template>
  <h3 v-if="title">{{ title }}</h3>
  <div class="filter-tag-list">
    <slot />
  </div>
</template>

<style scoped>
.filter-tag-list {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: center;
}

h3 {
  padding: 0.5rem;
  border: 1px dashed var(--color-border);
  border-radius: 0.5rem;
  color: var(--color-text);
  text-align: center;
}
</style>
