<script setup lang="ts">
import { RouterLink } from 'vue-router'

const features = [
  {
    icon: '🔒',
    title: '100% anonym',
    text: 'Dein echter Name bleibt privat. Du entscheidest, was andere sehen – nur Studiengang, Semester und Interessen.',
  },
  {
    icon: '🎯',
    title: 'Passende Matches',
    text: 'Filtere nach Hochschule, Studiengang, Semester und Hobbys, um Kommilitonen zu finden, die wirklich zu dir passen.',
  },
  {
    icon: '💬',
    title: 'Direkt chatten',
    text: 'Schreib anderen Studierenden direkt an – ohne Umwege über Social Media oder Gruppenchats.',
  },
  {
    icon: '🎓',
    title: 'Für alle Hochschulen',
    text: 'Ob Uni, FH oder TU – Studyshy wächst mit jeder Hochschule, die dazukommt.',
  },
]
</script>

<template>
  <div class="page">
    <section class="hero">
      <h1 class="page-title">
        Warum
        <RouterLink to="/"><img src="/studyshy-logo-text-only.svg" alt="Studyshy" /></RouterLink>
        ?
      </h1>
      <p class="page-subtitle">
        Studyshy verbindet Studierende anonym – basierend auf Studiengang, Semester und Interessen.
      </p>
    </section>

    <section class="features">
      <article v-for="feature in features" :key="feature.title" class="card feature-card">
        <span class="feature-icon">{{ feature.icon }}</span>
        <h2>{{ feature.title }}</h2>
        <p>{{ feature.text }}</p>
      </article>
    </section>

    <section class="cta section">
      <h2>Bereit, neue Leute kennenzulernen?</h2>
      <div class="btn-group">
        <RouterLink to="/register" class="btn">Kostenlos registrieren</RouterLink>
        <RouterLink to="/" class="btn btn-secondary">Matches entdecken</RouterLink>
      </div>
    </section>
  </div>
</template>

<style scoped>
.hero {
  text-align: center;
}

.hero img {
  height: 2.5rem;
  vertical-align: middle;
  margin: 0 0.5rem;
}

.features {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1.25rem;
  margin-top: 2rem;
}

.feature-card {
  text-align: center;
}

.feature-icon {
  font-size: 2rem;
}

.feature-card h2 {
  font-size: 1.2rem;
  margin: 0.75rem 0 0.5rem;
}

.feature-card p {
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.5;
  font-size: 0.95rem;
}

.cta {
  text-align: center;
}

.cta h2 {
  margin-bottom: 1.5rem;
}
</style>
