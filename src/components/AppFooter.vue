<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <footer class="app-footer">
    <div class="footer-inner">
      <p class="tagline">Anonym vernetzen. Echt connecten.</p>
      <nav class="footer-links">
        <RouterLink to="/" class="textlink">Entdecken</RouterLink>
        <RouterLink to="/why-studyshy" class="textlink">Warum Studyshy?</RouterLink>
        <RouterLink to="/about" class="textlink">Über uns</RouterLink>
        <RouterLink to="/register" class="textlink">Registrieren</RouterLink>
      </nav>
      <p class="copy">© {{ new Date().getFullYear() }} Studyshy · Frontend-MVP</p>
    </div>
  </footer>
</template>

<style scoped>
.app-footer {
  border-top: 1px dashed var(--color-border);
  margin-top: 4rem;
  padding: 2rem 1rem;
}

.footer-inner {
  width: min(960px, 90%);
  margin: 0 auto;
  text-align: center;
}

.tagline {
  color: var(--color-accent);
  font-style: italic;
  margin: 0 0 1rem;
}

.footer-links {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.copy {
  color: var(--color-text-muted);
  font-size: 0.85rem;
  margin: 0;
}
</style>
