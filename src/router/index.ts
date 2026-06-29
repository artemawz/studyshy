import { createRouter, createWebHistory } from 'vue-router'
import DiscoverView from '../views/DiscoverView.vue'
import WhyUsView from '@/views/WhyUsView.vue'
import AboutView from '@/views/AboutView.vue'
import LoginView from '@/views/LoginView.vue'
import RegistrationView from '@/views/RegistrationView.vue'
import StudentProfileView from '@/views/StudentProfileView.vue'
import ChatView from '@/views/ChatView.vue'
import ChatOverviewView from '@/views/ChatOverviewView.vue'
import { getToken } from '@/services/api'
import { getStoredUserId } from '@/composables/useAuth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
  routes: [
    {
      path: '/',
      name: 'discover',
      component: DiscoverView,
    },
    {
      path: '/why-studyshy',
      name: 'why-studyshy',
      component: WhyUsView,
    },
    {
      path: '/about',
      name: 'about',
      component: AboutView,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/register',
      name: 'register',
      component: RegistrationView,
    },
    {
      path: '/profile/edit',
      name: 'profile-edit',
      component: RegistrationView,
      meta: { requiresAuth: true },
    },
    {
      path: '/student/:id',
      name: 'profile-view',
      component: StudentProfileView,
    },
    {
      path: '/chat/:id',
      name: 'chat-view',
      component: ChatView,
      meta: { requiresAuth: true },
    },
    {
      path: '/chats',
      name: 'chats',
      component: ChatOverviewView,
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach((to) => {
  if (to.meta.requiresAuth && !getToken()) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'register' && getToken()) {
    const userId = getStoredUserId()
    if (userId) {
      return { name: 'profile-view', params: { id: userId.toString() } }
    }
  }
})

export default router
