import axios from 'axios'
import { useAuthStore } from '../store/auth'

const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || 'http://localhost:8081/api'
})

// Attach the JWT to every request if we have one
http.interceptors.request.use((config) => {
  const auth = useAuthStore()
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

// Origin for static assets (strip the trailing /api from the API base)
export const assetOrigin = (import.meta.env.VITE_API_BASE || 'http://localhost:8081/api').replace(/\/api\/?$/, '')

// Build a full URL for an uploaded image path like "/uploads/x.jpg"
export function imageUrl(path) {
  return path ? assetOrigin + path : ''
}

export default http
