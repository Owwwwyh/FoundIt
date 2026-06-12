import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'

import Home from '../views/Home.vue'
import ItemDetail from '../views/ItemDetail.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import ForgotPassword from '../views/ForgotPassword.vue'
import ResetPassword from '../views/ResetPassword.vue'
import PostItem from '../views/PostItem.vue'
import Dashboard from '../views/Dashboard.vue'

const routes = [
  { path: '/', name: 'home', component: Home },
  { path: '/items/:id', name: 'item', component: ItemDetail },
  { path: '/login', name: 'login', component: Login },
  { path: '/register', name: 'register', component: Register },
  { path: '/forgot-password', name: 'forgot-password', component: ForgotPassword },
  { path: '/reset-password', name: 'reset-password', component: ResetPassword },
  { path: '/post', name: 'post', component: PostItem, meta: { requiresAuth: true } },
  { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true } }
]

const router = createRouter({ history: createWebHistory(), routes })

// Redirect to /login if a protected page is opened without a token
router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
})

export default router
