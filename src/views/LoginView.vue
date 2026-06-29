<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '@/components/FormField.vue'
import AppButton from '@/components/AppButton.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/services/api'

const route = useRoute()
const router = useRouter()
const { login, loading } = useAuth()
const { show } = useToast()

const email = ref('')
const password = ref('')
const error = ref('')

async function handleSubmit() {
  error.value = ''
  if (!email.value.includes('@')) {
    error.value = 'Bitte gib eine gültige E-Mail-Adresse ein.'
    return
  }
  if (password.value.length < 8) {
    error.value = 'Das Passwort muss mindestens 8 Zeichen haben.'
    return
  }

  try {
    await login(email.value, password.value)
    show('Willkommen zurück!', 'success')
    const redirect = route.query.redirect as string | undefined
    router.push(redirect || '/')
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Anmeldung fehlgeschlagen.'
  }
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

      <AppButton type="submit" :disabled="loading">{{ loading ? 'Anmelden…' : 'Anmelden' }}</AppButton>

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
