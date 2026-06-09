<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import FormField from '@/components/FormField.vue'
import AppButton from '@/components/AppButton.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const { login } = useAuth()
const { show } = useToast()

const email = ref('')
const password = ref('')
const error = ref('')

function handleSubmit() {
  error.value = ''
  if (!email.value.includes('@')) {
    error.value = 'Bitte gib eine gültige E-Mail-Adresse ein.'
    return
  }
  if (password.value.length < 4) {
    error.value = 'Das Passwort muss mindestens 4 Zeichen haben.'
    return
  }
  login(email.value, password.value)
  show('Willkommen zurück!', 'success')
  router.push('/')
}
</script>

<template>
  <div class="page-narrow">
    <h1 class="page-title">Willkommen zurück</h1>
    <p class="page-subtitle">Melde dich an, um mit anderen Studierenden zu chatten.</p>

    <form class="card" @submit.prevent="handleSubmit">
      <FormField v-model="email" label="E-Mail" type="email" placeholder="name@hochschule.de" required />
      <FormField v-model="password" label="Passwort" type="password" required />
      <p v-if="error" class="form-error">{{ error }}</p>

      <AppButton type="submit">Anmelden</AppButton>

      <p class="footer-hint">
        Noch kein Konto?
        <RouterLink to="/register" class="textlink">Jetzt registrieren</RouterLink>
      </p>
    </form>
  </div>
</template>

<style scoped>
form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.footer-hint {
  text-align: center;
  margin: 1rem 0 0;
  color: var(--color-text-muted);
  font-size: 0.95rem;
}
</style>
