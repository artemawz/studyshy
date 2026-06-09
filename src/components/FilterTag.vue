<script setup lang="ts">
defineProps<{
  text: string
  selected?: boolean
}>()
</script>

<template>
  <button class="filter-tag" :class="{ selected: selected }">{{ text }}</button>
</template>

<style scoped>
.filter-tag {
  background-color: var(--color-surface);
  color: var(--color-text);
  width: fit-content;
  padding: 0.4rem 1.2rem;
  border: 1px solid var(--color-border-subtle);
  border-radius: 10000rem;
  display: flex;
  cursor: pointer;
  transition:
    border-color 100ms ease-in-out,
    background-color 100ms ease-in-out;
  font-size: 1rem;
  white-space: nowrap;
}

.filter-tag:hover {
  border-color: #ffffff5f;
}

.selected {
  color: var(--color-text);
  border-color: var(--color-accent);
  border-width: 2px;
  background-color: var(--color-accent-muted);
}
</style>
