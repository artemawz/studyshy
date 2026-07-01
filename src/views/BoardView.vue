<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppButton from '@/components/AppButton.vue'
import FormField from '@/components/FormField.vue'
import EmptyState from '@/components/EmptyState.vue'
import { useBoard, BOARD_CATEGORIES } from '@/composables/useBoard'
import { useBlocks } from '@/composables/useBlocks'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { ApiError } from '@/services/api'

const router = useRouter()
const {
  posts,
  loading,
  fetchPosts,
  createPost,
  deletePost,
  getComments,
  fetchComments,
  addComment,
  deleteComment,
} = useBoard()
const { reportUser } = useBlocks()
const { isLoggedIn } = useAuth()
const { show } = useToast()

const expandedPosts = ref<Set<number>>(new Set())
const commentDrafts = ref<Record<number, string>>({})
const commentSubmitting = ref<number | null>(null)

async function toggleComments(postId: number) {
  const next = new Set(expandedPosts.value)
  if (next.has(postId)) {
    next.delete(postId)
  } else {
    next.add(postId)
    try {
      await fetchComments(postId)
    } catch (e) {
      show(e instanceof ApiError ? e.message : 'Kommentare konnten nicht geladen werden.', 'error')
    }
  }
  expandedPosts.value = next
}

async function submitComment(postId: number) {
  const body = (commentDrafts.value[postId] ?? '').trim()
  if (!body) return
  commentSubmitting.value = postId
  try {
    await addComment(postId, body)
    commentDrafts.value[postId] = ''
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Kommentar konnte nicht gesendet werden.', 'error')
  } finally {
    commentSubmitting.value = null
  }
}

async function removeComment(postId: number, commentId: number) {
  try {
    await deleteComment(postId, commentId)
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Kommentar konnte nicht gelöscht werden.', 'error')
  }
}

function commentAuthorName(comment: { author?: { pub_name?: string }; authorId: number }) {
  return comment.author?.pub_name ?? `Student #${comment.authorId.toString(16)}`
}

const activeCategory = ref<string>('')
const showForm = ref(false)
const submitting = ref(false)
const searchQuery = ref('')

const form = ref({ category: BOARD_CATEGORIES[0] ?? 'Sonstiges', title: '', body: '' })

const filteredPosts = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return posts.value
  return posts.value.filter((p) => p.title.toLowerCase().includes(query))
})

onMounted(() => fetchPosts())

function selectCategory(cat: string) {
  activeCategory.value = activeCategory.value === cat ? '' : cat
  fetchPosts(activeCategory.value || undefined)
}

async function submitPost() {
  if (!form.value.title.trim() || !form.value.body.trim()) {
    show('Bitte Titel und Text ausfüllen.', 'error')
    return
  }
  submitting.value = true
  try {
    await createPost({
      category: form.value.category,
      title: form.value.title.trim(),
      body: form.value.body.trim(),
    })
    form.value = { category: BOARD_CATEGORIES[0] ?? 'Sonstiges', title: '', body: '' }
    showForm.value = false
    show('Beitrag veröffentlicht.', 'success')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Beitrag konnte nicht erstellt werden.', 'error')
  } finally {
    submitting.value = false
  }
}

async function handleDelete(id: number) {
  if (!confirm('Beitrag wirklich löschen?')) return
  try {
    await deletePost(id)
    show('Beitrag gelöscht.', 'info')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Beitrag konnte nicht gelöscht werden.', 'error')
  }
}

async function handleReport(authorId: number) {
  const reason = prompt('Warum möchtest du diesen Beitrag melden?')
  if (!reason || !reason.trim()) return
  try {
    await reportUser({ reported_user_id: authorId, context: 'board_post', reason: reason.trim() })
    show('Danke für deine Meldung.', 'success')
  } catch (e) {
    show(e instanceof ApiError ? e.message : 'Meldung fehlgeschlagen.', 'error')
  }
}

function authorName(post: (typeof posts.value)[number]) {
  return post.author?.pub_name ?? `Student #${post.authorId.toString(16)}`
}

function formatDate(value?: string) {
  if (!value) return ''
  return new Date(value).toLocaleString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="page-narrow">
    <div class="board-header">
      <h1 class="page-title">Schwarzes Brett</h1>
      <p class="page-subtitle">Lernpartner finden, Material teilen, Wohnen & mehr – ganz ohne Druck.</p>
    </div>

    <p v-if="!isLoggedIn" class="hint-box card">
      Melde dich an, um eigene Beiträge zu erstellen.
    </p>

    <div v-if="isLoggedIn" class="toolbar-row">
      <input
        v-model="searchQuery"
        type="search"
        class="search-input"
        placeholder="Beiträge nach Titel durchsuchen…"
      />
      <AppButton @click="showForm = !showForm">
        {{ showForm ? 'Abbrechen' : 'Beitrag erstellen' }}
      </AppButton>
    </div>

    <form v-if="showForm && isLoggedIn" class="card post-form" @submit.prevent="submitPost">
      <FormField v-model="form.category" label="Kategorie" as="select">
        <template #options>
          <option v-for="cat in BOARD_CATEGORIES" :key="cat" :value="cat">{{ cat }}</option>
        </template>
      </FormField>
      <FormField v-model="form.title" label="Titel" placeholder="z. B. Suche Lernpartner für Statistik" />
      <FormField v-model="form.body" label="Beschreibung" as="textarea" placeholder="Worum geht es?" />
      <AppButton type="submit" :disabled="submitting">
        {{ submitting ? 'Wird veröffentlicht…' : 'Veröffentlichen' }}
      </AppButton>
    </form>

    <div class="filter-row">
      <button
        v-for="cat in BOARD_CATEGORIES"
        :key="cat"
        type="button"
        class="cat-chip"
        :class="{ active: activeCategory === cat }"
        @click="selectCategory(cat)"
      >
        {{ cat }}
      </button>
    </div>

    <EmptyState v-if="loading && !posts.length" title="Beiträge werden geladen…" />
    <EmptyState
      v-else-if="!posts.length"
      title="Noch keine Beiträge"
      hint="Sei die erste Person, die hier etwas postet."
    />
    <EmptyState
      v-else-if="!filteredPosts.length"
      title="Keine Beiträge gefunden"
      hint="Passe deine Suche an."
    />

    <ul v-else class="post-list">
      <li v-for="post in filteredPosts" :key="post.id" class="card post-item" :class="{ unread: post.hasUnreadComments }">
        <div class="post-top">
          <span class="cat-badge">{{ post.category }}</span>
          <span class="post-date">{{ formatDate(post.createdAt) }}</span>
        </div>
        <h3 class="post-title">{{ post.title }}</h3>
        <p class="post-body">{{ post.body }}</p>
        <div class="post-footer">
          <button type="button" class="author" @click="router.push({ name: 'profile-view', params: { id: post.authorId.toString() } })">
            <img v-if="post.author?.avatarUrl" :src="post.author.avatarUrl" class="author-avatar" alt="" />
            <span v-else class="author-avatar placeholder">{{ authorName(post).charAt(0) }}</span>
            <span class="author-name">{{ authorName(post) }}</span>
          </button>
          <div class="post-actions">
            <button type="button" class="link-btn" @click="toggleComments(post.id)">
              💬 {{ post.commentCount ?? 0 }}
              <span v-if="post.hasUnreadComments" class="badge">Neu</span>
            </button>
            <button v-if="post.isOwner" type="button" class="link-btn danger" @click="handleDelete(post.id)">
              Löschen
            </button>
            <button v-else-if="isLoggedIn" type="button" class="link-btn" @click="handleReport(post.authorId)">
              Melden
            </button>
          </div>
        </div>

        <div v-if="expandedPosts.has(post.id)" class="comments">
          <ul v-if="getComments(post.id).length" class="comment-list">
            <li v-for="comment in getComments(post.id)" :key="comment.id" class="comment">
              <img v-if="comment.author?.avatarUrl" :src="comment.author.avatarUrl" class="comment-avatar" alt="" />
              <span v-else class="comment-avatar placeholder">{{ commentAuthorName(comment).charAt(0) }}</span>
              <div class="comment-body">
                <span class="comment-author">{{ commentAuthorName(comment) }}</span>
                <p class="comment-text">{{ comment.body }}</p>
              </div>
              <button v-if="comment.isOwner" type="button" class="link-btn danger" @click="removeComment(post.id, comment.id)">
                ✕
              </button>
            </li>
          </ul>
          <p v-else class="no-comments">Noch keine Kommentare. Sei die erste Person!</p>

          <form v-if="isLoggedIn" class="comment-form" @submit.prevent="submitComment(post.id)">
            <input
              v-model="commentDrafts[post.id]"
              type="text"
              placeholder="Kommentar schreiben…"
              autocomplete="off"
            />
            <AppButton type="submit" :disabled="commentSubmitting === post.id || !(commentDrafts[post.id] ?? '').trim()">
              Senden
            </AppButton>
          </form>
        </div>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.board-header {
  margin-bottom: 1rem;
}

.hint-box {
  margin-top: 1rem;
  color: var(--color-text-muted);
}

.toolbar-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 200px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 100rem;
  padding: 0.55rem 1.1rem;
  color: var(--color-text);
  font-family: inherit;
  font-size: 0.95rem;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-accent);
}

.post-form {
  margin: 1rem 0 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 1rem 0 1.5rem;
}

.cat-chip {
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text);
  border-radius: 999px;
  padding: 0.35rem 0.85rem;
  font-size: 0.85rem;
  cursor: pointer;
}

.cat-chip.active {
  background: var(--color-accent);
  border-color: var(--color-accent);
  color: #fff;
}

.post-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.post-item.unread {
  border-color: var(--color-accent);
  background: #b9aaff11;
}

.post-actions .badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.1rem 0.5rem;
  margin-left: 0.35rem;
  border-radius: 999px;
  background: var(--color-accent-hover);
  color: #fff;
  font-size: 0.7rem;
  font-weight: 700;
  line-height: 1.4;
}

.post-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.cat-badge {
  background: var(--color-accent-muted);
  color: var(--color-accent);
  border-radius: 999px;
  padding: 0.15rem 0.6rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.post-date {
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.post-title {
  margin: 0 0 0.4rem;
  font-size: 1.1rem;
}

.post-body {
  margin: 0 0 1rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  white-space: pre-wrap;
}

.post-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.author {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  cursor: pointer;
}

.author-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.author-avatar.placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
  text-transform: uppercase;
}

.author-name {
  font-weight: 600;
  color: var(--color-accent);
  font-size: 0.9rem;
}

.post-actions {
  display: flex;
  gap: 0.75rem;
}

.link-btn {
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: 0.85rem;
}

.link-btn:hover {
  text-decoration: underline;
}

.link-btn.danger:hover {
  color: #f87171;
}

.comments {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border-subtle);
}

.comment-list {
  list-style: none;
  padding: 0;
  margin: 0 0 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.comment {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
}

.comment-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.comment-avatar.placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
  font-size: 0.8rem;
  text-transform: uppercase;
}

.comment-body {
  flex: 1;
  min-width: 0;
}

.comment-author {
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--color-accent);
}

.comment-text {
  margin: 0.1rem 0 0;
  font-size: 0.9rem;
  line-height: 1.4;
  white-space: pre-wrap;
  word-break: break-word;
}

.no-comments {
  color: var(--color-text-muted);
  font-size: 0.85rem;
  margin: 0 0 0.75rem;
}

.comment-form {
  display: flex;
  gap: 0.5rem;
}

.comment-form input {
  flex: 1;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  padding: 0.5rem 0.7rem;
  color: var(--color-text);
  font-family: inherit;
}

.comment-form input:focus {
  outline: none;
  border-color: var(--color-accent);
}
</style>
