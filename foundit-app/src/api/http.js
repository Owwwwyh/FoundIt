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

export default http
