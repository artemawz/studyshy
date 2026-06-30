<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '@/components/FormField.vue'
import AppButton from '@/components/AppButton.vue'
import FilterTag from '@/components/FilterTag.vue'
import TagList from '@/components/TagList.vue'
import { useAuth } from '@/composables/useAuth'
import { useMeta } from '@/composables/useMeta'
import { useToast } from '@/composables/useToast'
import {
  toggleSelection,
  OTHER_INTEREST_LABEL,
  isValidRegistrationEmail,
  isValidPassword,
  MAX_INTERESTS,
  MAX_CUSTOM_INTEREST_LENGTH,
} from '@/misc'
import { ApiError } from '@/services/api'

const route = useRoute()
const router = useRouter()
const { register, updateProfile, uploadAvatar, currentUser, isLoggedIn, loading } = useAuth()

const isEditMode = computed(() => route.name === 'profile-edit')
const { filterOptions, fetchMeta } = useMeta()
const { show } = useToast()

const email = ref('')
const password = ref('')
const pubName = ref('')
const avatarUrl = ref('')
const previewUrl = ref<string | null>(null)
const avatarInput = ref<HTMLInputElement | null>(null)
const uploadingAvatar = ref(false)
const uni = ref('')
const course = ref('')
const degree = ref('Bachelor')
const semester = ref('3')
const bio = ref('')
const selectedInterests = ref<number[]>([])
const customInterests = ref<string[]>([])
const showOtherInput = ref(false)
const otherInterestInput = ref('')
const error = ref('')
const saving = ref(false)
const formPopulated = ref(false)

const coursesForSelectedUni = computed(() => {
  const byUni = filterOptions.value.coursesByUni?.[uni.value]
  if (byUni && byUni.length) return byUni
  return filterOptions.value.courses
})

watch(uni, () => {
  // Beim Wechsel der Hochschule den Studiengang auf einen gültigen Wert setzen
  if (!coursesForSelectedUni.value.includes(course.value)) {
    course.value = coursesForSelectedUni.value[0] ?? ''
  }
})

function randomAvatarUrl() {
  return `https://i.pravatar.cc/150?img=${Math.floor(Math.random() * 64) + 1}`
}

async function shuffleAvatar() {
  const url = randomAvatarUrl()
  previewUrl.value = null
  try {
    await updateProfile({ avatar_url: url })
    avatarUrl.value = url
    show('Profilbild aktualisiert.', 'success')
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Profilbild konnte nicht gesetzt werden.'
  }
}

function openAvatarPicker() {
  avatarInput.value?.click()
}

async function onAvatarSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) return

  if (!file.type.startsWith('image/')) {
    error.value = 'Bitte wähle eine Bilddatei (JPEG, PNG, WebP oder GIF).'
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    error.value = 'Das Bild darf maximal 2 MB groß sein.'
    return
  }

  error.value = ''
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = URL.createObjectURL(file)

  uploadingAvatar.value = true
  try {
    const user = await uploadAvatar(file)
    avatarUrl.value = user.avatarUrl ?? ''
    show('Profilbild hochgeladen.', 'success')
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Upload fehlgeschlagen.'
    previewUrl.value = null
  } finally {
    uploadingAvatar.value = false
  }
}

onUnmounted(() => {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
})

onMounted(async () => {
  await fetchMeta()
  if (!isEditMode.value) {
    if (filterOptions.value.unis[0]) uni.value = filterOptions.value.unis[0]
    if (!course.value && coursesForSelectedUni.value[0]) {
      course.value = coursesForSelectedUni.value[0]
    }
  }
})

function populateFormFromUser(user: typeof currentUser.value) {
  if (!user) return
  pubName.value = user.pub_name ?? ''
  avatarUrl.value = user.avatarUrl ?? ''
  uni.value = user.uni ?? uni.value
  course.value = user.courses?.[0]?.name ?? course.value
  degree.value = user.courses?.[0]?.degree ?? degree.value
  semester.value = String(user.courses?.[0]?.semester ?? 3)
  bio.value = user.bio ?? ''
  syncInterestsFromUser(user.interests ?? [])
}

watch(
  [currentUser, isEditMode],
  ([user, edit]) => {
    if (edit && user && !formPopulated.value) {
      populateFormFromUser(user)
      formPopulated.value = true
    }
  },
  { immediate: true },
)

watch(
  () => route.name,
  (name) => {
    if (name === 'profile-edit') {
      formPopulated.value = false
    }
  },
)

function syncInterestsFromUser(interests: string[]) {
  selectedInterests.value = interests
    .map((name) => filterOptions.value.interests.indexOf(name))
    .filter((idx) => idx >= 0)
  customInterests.value = interests.filter((name) => !filterOptions.value.interests.includes(name))
}

function resolveInterestNames(): string[] {
  const fromTags = selectedInterests.value
    .map((i) => filterOptions.value.interests[i]!)
    .filter((name) => name !== OTHER_INTEREST_LABEL)
  return [...fromTags, ...customInterests.value]
}

const interestCount = computed(() => resolveInterestNames().length)

function toggleInterest(idx: number) {
  const isSelected = selectedInterests.value.includes(idx)
  if (!isSelected && interestCount.value >= MAX_INTERESTS) {
    error.value = `Du kannst maximal ${MAX_INTERESTS} Interessen auswählen.`
    return
  }
  error.value = ''
  selectedInterests.value = toggleSelection(selectedInterests.value, idx)
}

function addCustomInterest() {
  const name = otherInterestInput.value.trim()
  if (name.length < 2) {
    error.value = 'Interesse muss mindestens 2 Zeichen haben.'
    return
  }
  if (name.length > MAX_CUSTOM_INTEREST_LENGTH) {
    error.value = `Ein Interesse darf höchstens ${MAX_CUSTOM_INTEREST_LENGTH} Zeichen haben.`
    return
  }
  if (interestCount.value >= MAX_INTERESTS) {
    error.value = `Du kannst maximal ${MAX_INTERESTS} Interessen auswählen.`
    return
  }
  if (name.toLowerCase() === OTHER_INTEREST_LABEL.toLowerCase()) {
    error.value = 'Bitte gib ein konkretes Interesse ein.'
    return
  }
  const existing = resolveInterestNames()
  if (existing.some((item) => item.toLowerCase() === name.toLowerCase())) {
    error.value = 'Dieses Interesse ist bereits ausgewählt.'
    return
  }
  customInterests.value = [...customInterests.value, name]
  otherInterestInput.value = ''
  showOtherInput.value = false
  error.value = ''
}

function removeCustomInterest(name: string) {
  customInterests.value = customInterests.value.filter((item) => item !== name)
}

async function handleRegister() {
  error.value = ''
  const trimmedEmail = email.value.trim()

  if (!isValidRegistrationEmail(trimmedEmail)) {
    error.value = 'Bitte gib eine gültige E-Mail-Adresse ein (mit @ und .de oder .com).'
    return
  }
  if (!isValidPassword(password.value)) {
    error.value =
      'Das Passwort muss mindestens 8 Zeichen, einen Großbuchstaben und ein Sonderzeichen enthalten.'
    return
  }
  if (resolveInterestNames().length === 0) {
    error.value = 'Bitte wähle mindestens ein Interesse.'
    return
  }

  try {
    await register({
      email: trimmedEmail,
      password: password.value,
      uni: uni.value,
      course: course.value,
      degree: degree.value,
      semester: parseInt(semester.value, 10),
      interests: resolveInterestNames(),
    })
    await fetchMeta()
    show('Profil erstellt – viel Erfolg beim Matching!', 'success')
    router.push('/')
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Registrierung fehlgeschlagen.'
  }
}

async function handleUpdate() {
  error.value = ''
  if (pubName.value.trim().length < 2) {
    error.value = 'Der Nickname muss mindestens 2 Zeichen haben.'
    return
  }
  if (resolveInterestNames().length === 0) {
    error.value = 'Bitte wähle mindestens ein Interesse.'
    return
  }

  saving.value = true
  try {
    await updateProfile({
      pub_name: pubName.value.trim(),
      bio: bio.value,
      uni: uni.value,
      course: course.value,
      degree: degree.value,
      semester: parseInt(semester.value, 10),
      interests: resolveInterestNames(),
    })
    await fetchMeta()
    show('Profil gespeichert.', 'success')
    if (currentUser.value) {
      router.push({ name: 'profile-view', params: { id: currentUser.value.id.toString() } })
    }
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Profil konnte nicht gespeichert werden.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="page-narrow">
    <h1 class="page-title">{{ isEditMode ? 'Profil bearbeiten' : 'Registrieren' }}</h1>
    <p class="page-subtitle">
      {{
        isEditMode
          ? 'Passe Nickname, Bio, Interessen und Profilbild an – so sehen dich andere Studierende.'
          : 'Erstelle dein anonymes Profil und finde Gleichgesinnte.'
      }}
    </p>

    <p v-if="isEditMode" class="back-link">
      <RouterLink
        v-if="currentUser"
        :to="{ name: 'profile-view', params: { id: currentUser.id.toString() } }"
        class="textlink"
      >
        ← Zur Profilansicht
      </RouterLink>
    </p>

    <form class="card" @submit.prevent="isEditMode ? handleUpdate() : handleRegister()">
      <FormField
        v-if="!isLoggedIn"
        v-model="email"
        label="E-Mail"
        type="email"
        placeholder="name@hochschule.de"
        required
      />

      <div v-if="!isLoggedIn">
        <FormField
          v-model="password"
          label="Passwort"
          type="password"
          placeholder="Mindestens 8 Zeichen"
          required
        />
        <p class="char-hint">Mind. 8 Zeichen, 1 Großbuchstabe und 1 Sonderzeichen.</p>
      </div>

      <template v-if="isEditMode">
        <FormField
          v-model="pubName"
          label="Nickname"
          placeholder="z. B. Student #a3f"
          required
        />

        <div class="form-group avatar-edit">
          <label>Profilbild</label>
          <input
            ref="avatarInput"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="sr-only"
            @change="onAvatarSelected"
          />
          <div class="avatar-actions">
            <AppButton type="button" :disabled="uploadingAvatar" @click="openAvatarPicker">
              {{ uploadingAvatar ? 'Wird hochgeladen…' : 'Bild hochladen' }}
            </AppButton>
            <AppButton type="button" variant="secondary" @click="shuffleAvatar">
              Zufälliges Bild
            </AppButton>
          </div>
          <p class="avatar-hint">JPEG, PNG, WebP oder GIF · max. 2 MB</p>
        </div>
      </template>

      <FormField v-model="uni" label="Universität/Hochschule" as="select">
        <template #options>
          <option v-for="u in filterOptions.unis" :key="u" :value="u">{{ u }}</option>
        </template>
      </FormField>

      <FormField v-model="course" label="Studiengang" as="select">
        <template #options>
          <option v-for="c in coursesForSelectedUni" :key="c" :value="c">{{ c }}</option>
        </template>
      </FormField>

      <FormField v-model="degree" label="Abschluss" as="select">
        <template #options>
          <option v-for="d in filterOptions.degrees" :key="d" :value="d">{{ d }}</option>
        </template>
      </FormField>

      <FormField v-model="semester" label="Semester" as="select">
        <template #options>
          <option v-for="s in filterOptions.semesters" :key="s" :value="s === '10+' ? '10' : s">
            {{ s }}
          </option>
        </template>
      </FormField>

      <FormField v-if="isEditMode" v-model="bio" label="Bio" as="textarea" placeholder="Erzähl kurz etwas über dich…" />

      <div class="form-group">
        <label>Interessen ({{ interestCount }}/{{ MAX_INTERESTS }})</label>
        <TagList>
          <FilterTag
            v-for="(interest, idx) in filterOptions.interests"
            :key="interest"
            :text="interest"
            :selected="selectedInterests.includes(idx)"
            @click="toggleInterest(idx)"
          />
          <FilterTag
            :text="OTHER_INTEREST_LABEL"
            :selected="showOtherInput"
            @click="showOtherInput = !showOtherInput"
          />
          <FilterTag
            v-for="name in customInterests"
            :key="`custom-${name}`"
            :text="name"
            selected
            @click="removeCustomInterest(name)"
          />
        </TagList>

        <div v-if="showOtherInput" class="other-interest">
          <div class="form-group">
            <label>Eigenes Interesse</label>
            <input
              v-model="otherInterestInput"
              type="text"
              :maxlength="MAX_CUSTOM_INTEREST_LENGTH"
              placeholder="z. B. Schach, Yoga, …"
              @keydown.enter.prevent="addCustomInterest"
            />
            <p class="char-hint">{{ otherInterestInput.length }}/{{ MAX_CUSTOM_INTEREST_LENGTH }} Zeichen</p>
          </div>
          <AppButton type="button" variant="secondary" @click="addCustomInterest">
            Hinzufügen
          </AppButton>
        </div>
      </div>

      <p v-if="error" class="form-error">{{ error }}</p>
      <AppButton type="submit" :disabled="loading || saving">
        {{ isEditMode ? (saving ? 'Speichern…' : 'Profil speichern') : loading ? 'Erstellen…' : 'Profil erstellen' }}
      </AppButton>

      <p v-if="!isLoggedIn" class="footer-hint">
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

.back-link {
  margin: -0.5rem 0 1rem;
}

.footer-hint {
  text-align: center;
  margin: 1rem 0 0;
  color: var(--color-text-muted);
  font-size: 0.95rem;
}

.char-hint {
  margin: 0.25rem 0 0;
  color: var(--color-text-muted);
  font-size: 0.8rem;
}

.form-group label {
  font-size: 0.9rem;
  color: var(--color-text-muted);
  display: block;
  margin-bottom: 0.5rem;
}

.avatar-edit {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.avatar-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.avatar-hint {
  margin: 0;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

.other-interest {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px dashed var(--color-border-subtle);
}

.other-interest input {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.4rem;
  padding: 0.6rem 0.9rem;
  color: white;
  font-size: 1rem;
  font-family: inherit;
  width: 100%;
}

.other-interest input:focus {
  outline: none;
  border-color: var(--color-accent);
}
</style>
