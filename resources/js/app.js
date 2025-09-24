import './bootstrap';
import 'admin-lte/dist/css/adminlte.css'
import 'admin-lte/dist/js/adminlte.js'

import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia } from 'pinia'
import App from './App.vue'

// Import auth components
import Login from './components/auth/Login.vue'
import Register from './components/auth/Register.vue'
import ForgotPassword from './components/auth/ForgotPassword.vue'
import ResetPassword from './components/auth/ResetPassword.vue'

// Import main components
import TeamList from './components/team/TeamList.vue'
import UserProfile from './components/profile/UserProfile.vue'
import UserManagement from './components/admin/UserManagement.vue'

// Import auth store
import { useAuthStore } from './stores/auth'

// Import toast plugin
import { createToastPlugin } from './plugins/toast'

// Router configuration
const routes = [
  // Auth routes
  { path: '/login', component: Login, name: 'login' },
  { path: '/register', component: Register, name: 'register' },
  { path: '/forgot-password', component: ForgotPassword, name: 'forgot-password' },
  { path: '/reset-password', component: ResetPassword, name: 'reset-password' },

  // Protected routes
  { path: '/dashboard', redirect: '/teams', name: 'dashboard' },
  { path: '/teams', component: TeamList, name: 'teams', meta: { requiresAuth: true } },
  { path: '/profile', component: UserProfile, name: 'profile', meta: { requiresAuth: true } },
  { path: '/admin/users', component: UserManagement, name: 'admin-users', meta: { requiresAuth: true, permission: 'user.view' } },

  { path: '/', redirect: '/login' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard for authentication
router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()

  // Initialize auth if not already done
  if (!authStore.isAuthenticated && authStore.token) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      authStore.logout()
    }
  }

  const publicRoutes = ['login', 'register', 'forgot-password', 'reset-password']
  const isPublicRoute = publicRoutes.includes(to.name?.toString() || '')

  // Check authentication
  if (!authStore.isAuthenticated && !isPublicRoute) {
    next('/login')
    return
  }

  if (authStore.isAuthenticated && isPublicRoute) {
    next('/dashboard')
    return
  }

  // Check permissions for protected routes
  if (to.meta.requiresAuth && to.meta.permission) {
    if (!authStore.hasPermission(to.meta.permission)) {
      next('/dashboard') // Redirect to dashboard if no permission
      return
    }
  }

  next()
})

// Create Pinia store
const pinia = createPinia()

// Create and mount the app
const app = createApp(App)
app.use(router)
app.use(pinia)
app.use(createToastPlugin())

// Initialize auth
const authStore = useAuthStore()
authStore.initializeAuth()

app.mount('#app')
