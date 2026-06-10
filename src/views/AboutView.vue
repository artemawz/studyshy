<script setup lang="ts">
const team = [
  { name: 'Oskar Junghans', role: 'Student 🤓' },
  { name: 'Isaak Akritidis', role: 'Student 🤓' },
  { name: 'Tim Große-Brockhoff', role: 'Student 🤓' },
  { name: 'Felix Schrage', role: 'Student 🤓' },
]

const values = [
  { title: 'Anonymität zuerst', text: 'Keine Pflicht-Fotos, keine echten Namen in der Öffentlichkeit.' },
  { title: 'Studium im Fokus', text: 'Matching über das, was Studierende wirklich verbindet.' },
  { title: 'Offen & inklusiv', text: 'Für alle Studiengänge, Semester und Hochschulen.' },
]
</script>

<template>
  <div class="page">
    <h1 class="page-title">Über Studyshy</h1>
    <p class="page-subtitle">
      Eine Plattform von Studierenden für Studierende – entwickelt, um echte Verbindungen am Campus zu
      erleichtern.
    </p>

    <section class="card about-block">
      <h2>Unsere Mission</h2>
      <p>
        Viele Studierende fühlen sich isoliert, besonders in den ersten Semestern oder nach einem
        Hochschulwechsel. Studyshy macht es einfacher, Gleichgesinnte zu finden – ohne den Druck,
        sofort alles über sich preiszugeben.
      </p>
    </section>

    <section class="values">
      <article v-for="value in values" :key="value.title" class="card value-card">
        <h3>{{ value.title }}</h3>
        <p>{{ value.text }}</p>
      </article>
    </section>

    <section class="card about-block">
      <h2>Das Team</h2>
      <ul class="team-list">
        <li v-for="member in team" :key="member.name">
          <strong>{{ member.name }}</strong> – {{ member.role }}
        </li>
      </ul>
    </section>

    <section class="card about-block contact">
      <h2>Kontakt</h2>
      <p>Fragen, Feedback oder Interesse an einer Kooperation?</p>
      <a href="mailto:hello@studyshy.de" class="textlink">hello@studyshy.de</a>
    </section>
  </div>
</template>

<style scoped>
.about-block h2 {
  margin-top: 0;
}

.about-block p {
  line-height: 1.6;
  color: var(--color-text-muted);
}

.values {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
  margin: 2rem 0;
}

.value-card h3 {
  margin: 0 0 0.5rem;
  color: var(--color-accent);
}

.value-card p {
  margin: 0;
  color: var(--color-text-muted);
  font-size: 0.95rem;
  line-height: 1.5;
}

.team-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.team-list li {
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--color-border-subtle);
}

.team-list li:last-child {
  border-bottom: none;
}

.contact {
  margin-top: 2rem;
  text-align: center;
}
</style>
