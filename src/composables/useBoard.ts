import { ref } from 'vue'
import type { BoardComment, BoardPost } from '@/types'
import { api } from '@/services/api'

export const BOARD_CATEGORIES = ['Lernpartner', 'Material', 'Wohnen', 'Mitfahrt', 'Sonstiges']

const posts = ref<BoardPost[]>([])
const loading = ref(false)
const commentsByPost = ref<Map<number, BoardComment[]>>(new Map())

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
    loading,
    fetchPosts,
    createPost,
    deletePost,
    getComments,
    fetchComments,
    addComment,
    deleteComment,
  }
}
