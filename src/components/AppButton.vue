<script setup lang="ts">
defineProps<{
  variant?: 'primary' | 'secondary'
  type?: 'button' | 'submit'
  disabled?: boolean
}>()
</script>

<template>
  <button
    class="btn"
    :class="{ 'btn-secondary': variant === 'secondary' }"
    :type="type ?? 'button'"
    :disabled="disabled"
  >
    <slot />
  </button>
</template>
