import { ref } from 'vue'
import type { BoardComment, BoardPost } from '@/types'
import { api } from '@/services/api'

export const BOARD_CATEGORIES = ['Lernpartner', 'Material', 'Wohnen', 'Mitfahrt', 'Sonstiges']

const posts = ref<BoardPost[]>([])
const loading = ref(false)
const commentsByPost = ref<Map<number, BoardComment[]>>(new Map())

// Eigener, von der (ggf. gefilterten) Pinnwand-Ansicht unabhängiger State für die
// Glocke: eigene Beiträge mit ungelesenen Kommentaren. So überschreibt das
// Hintergrund-Polling nicht die auf der Pinnwand aktive Kategorie-Filterung.
const unreadCommentPosts = ref<BoardPost[]>([])

export function useBoard() {
  async function fetchPosts(category?: string) {
    loading.value = true
    try {
      const res = await api.getBoardPosts(category)
      posts.value = res.data
    } finally {
      loading.value = false
    }
  }

  async function fetchUnreadCommentPosts() {
    const res = await api.getBoardPosts()
    unreadCommentPosts.value = res.data.filter((p) => p.isOwner && p.hasUnreadComments)
  }

  async function createPost(data: { category: string; title: string; body: string }) {
    const post = await api.createBoardPost(data)
    posts.value.unshift(post)
    return post
  }

  async function deletePost(id: number) {
    await api.deleteBoardPost(id)
    posts.value = posts.value.filter((p) => p.id !== id)
  }

  function getComments(postId: number) {
    return commentsByPost.value.get(postId) ?? []
  }

  async function fetchComments(postId: number) {
    const res = await api.getBoardComments(postId)
    commentsByPost.value.set(postId, res.data)
    // Öffnen der Kommentare markiert sie serverseitig als gelesen (nur relevant
    // für die/den Ersteller:in) – das lokal sofort widerspiegeln.
    const post = posts.value.find((p) => p.id === postId)
    if (post) post.hasUnreadComments = false
    unreadCommentPosts.value = unreadCommentPosts.value.filter((p) => p.id !== postId)
    return getComments(postId)
  }

  async function addComment(postId: number, body: string) {
    const comment = await api.addBoardComment(postId, body)
    const list = commentsByPost.value.get(postId) ?? []
    commentsByPost.value.set(postId, [...list, comment])
    const post = posts.value.find((p) => p.id === postId)
    if (post) post.commentCount = (post.commentCount ?? 0) + 1
    return comment
  }

  async function deleteComment(postId: number, commentId: number) {
    await api.deleteBoardComment(commentId)
    const list = commentsByPost.value.get(postId) ?? []
    commentsByPost.value.set(postId, list.filter((c) => c.id !== commentId))
    const post = posts.value.find((p) => p.id === postId)
    if (post && post.commentCount) post.commentCount -= 1
  }

  return {
    posts,
    unreadCommentPosts,
    loading,
    fetchPosts,
    fetchUnreadCommentPosts,
    createPost,
    deletePost,
    getComments,
    fetchComments,
    addComment,
    deleteComment,
  }
}
