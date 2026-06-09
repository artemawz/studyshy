<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import FormField from '@/components/FormField.vue'
import AppButton from '@/components/AppButton.vue'
import FilterTag from '@/components/FilterTag.vue'
import TagList from '@/components/TagList.vue'
import { filterOptions } from '@/data/mockData'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { toggleSelection } from '@/misc'

const router = useRouter()
const { register, currentUser, isLoggedIn } = useAuth()
const { show } = useToast()

const email = ref('')
const uni = ref(filterOptions.unis[0] ?? '')
const course = ref(filterOptions.courses[0] ?? '')
const semester = ref('3')
const selectedInterests = ref<number[]>([])
const error = ref('')

function handleSubmit() {
  error.value = ''
  if (!email.value.includes('@')) {
    error.value = 'Bitte gib eine gültige E-Mail-Adresse ein.'
    return
  }

  register({
    email: email.value,
    uni: uni.value,
    course: course.value,
    semester: parseInt(semester.value, 10),
    interests: selectedInterests.value.map((i) => filterOptions.interests[i]!),
  })
  show('Profil erstellt – viel Erfolg beim Matching!', 'success')
  router.push('/')
}
</script>

<template>
  <div class="page-narrow">
    <h1 class="page-title">{{ isLoggedIn ? 'Dein Profil' : 'Registrieren' }}</h1>
    <p class="page-subtitle">
      {{
        isLoggedIn
          ? 'So sehen andere dein anonymes Profil. (MVP – noch nicht editierbar)'
          : 'Erstelle dein anonymes Profil und finde Gleichgesinnte.'
      }}
    </p>

    <div v-if="isLoggedIn && currentUser" class="card profile-preview">
      <img :src="currentUser.avatarUrl" width="80" height="80" alt="" class="avatar" />
      <h2>{{ currentUser.pub_name }}</h2>
      <p class="uni">{{ currentUser.uni }}</p>
      <p v-if="currentUser.bio" class="bio">{{ currentUser.bio }}</p>
      <TagList>
        <FilterTag
          v-for="course in currentUser.courses"
          :key="course.name"
          :text="`${course.name} (${course.semester}. Sem.)`"
        />
        <FilterTag v-for="interest in currentUser.interests" :key="interest" :text="interest" />
      </TagList>
    </div>

    <form v-else class="card" @submit.prevent="handleSubmit">
      <FormField v-model="email" label="E-Mail" type="email" placeholder="name@hochschule.de" required />

      <FormField v-model="uni" label="Universität/Hochschule" as="select">
        <template #options>
          <option v-for="u in filterOptions.unis" :key="u" :value="u">{{ u }}</option>
        </template>
      </FormField>

      <FormField v-model="course" label="Studiengang" as="select">
        <template #options>
          <option v-for="c in filterOptions.courses" :key="c" :value="c">{{ c }}</option>
        </template>
      </FormField>

      <FormField v-model="semester" label="Semester" as="select">
        <template #options>
          <option v-for="s in filterOptions.semesters" :key="s" :value="s === '10+' ? '10' : s">
            {{ s }}
          </option>
        </template>
      </FormField>

      <div class="form-group">
        <label>Interessen (optional)</label>
        <TagList>
          <FilterTag
            v-for="(interest, idx) in filterOptions.interests"
            :key="interest"
            :text="interest"
            :selected="selectedInterests.includes(idx)"
            @click="selectedInterests = toggleSelection(selectedInterests, idx)"
          />
        </TagList>
      </div>

      <p v-if="error" class="form-error">{{ error }}</p>
      <AppButton type="submit">Profil erstellen</AppButton>

      <p class="footer-hint">
        Bereits registriert?
        <RouterLink to="/login" class="textlink">Zum Login</RouterLink>
      </p>
    </form>
  </div>
</template>

<style scoped>
form {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.profile-preview {
  text-align: center;
}

.avatar {
  border-radius: 50%;
  object-fit: cover;
}

.profile-preview h2 {
  margin: 1rem 0 0.25rem;
}

.uni {
  color: var(--color-text-muted);
  margin: 0 0 1rem;
}

.bio {
  margin-bottom: 1rem;
}

.footer-hint {
  text-align: center;
  margin: 1rem 0 0;
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

.form-group label {
  font-size: 0.9rem;
  color: var(--color-text-muted);
  display: block;
  margin-bottom: 0.5rem;
}
</style>
