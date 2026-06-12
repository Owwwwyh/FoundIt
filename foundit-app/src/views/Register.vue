<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import http from '../api/http'

const name = ref('')
const email = ref('')
const password = ref('')
const errors = ref({})
const serverError = ref('')
const loading = ref(false)
const router = useRouter()

function validate() {
  const e = {}
  if (!name.value.trim()) e.name = 'Name is required.'
  if (!email.value.trim()) e.email = 'Email is required.'
  else if (!/^\S+@\S+\.\S+$/.test(email.value)) e.email = 'Email is not valid.'
  if (password.value.length < 6) e.password = 'Password must be at least 6 characters.'
  errors.value = e
  return Object.keys(e).length === 0
}

async function submit() {
  serverError.value = ''
  if (!validate()) return
  loading.value = true
  try {
    await http.post('/register', { name: name.value, email: email.value, password: password.value })
    router.push({ name: 'login', query: { registered: '1' } })
  } catch (err) {
    if (err.response?.status === 409) serverError.value = 'That email is already registered.'
    else if (err.response?.data?.errors) errors.value = err.response.data.errors
    else serverError.value = 'Something went wrong. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth">
    <div class="auth-card">
      <div class="auth-mark">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 21s7-5.686 7-11a7 7 0 1 0-14 0c0 5.314 7 11 7 11Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
          <circle cx="12" cy="10" r="2.6" fill="currentColor"/>
        </svg>
      </div>
      <h1>Create your account</h1>
      <p class="auth-sub">Join FoundIt to report items and file claims.</p>

      <form @submit.prevent="submit">
        <div class="field">
          <label>Name</label>
          <input v-model="name" placeholder="Your full name" autocomplete="name" />
          <p v-if="errors.name" class="err">{{ errors.name }}</p>
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" v-model="email" placeholder="you@example.com" autocomplete="email" />
          <p v-if="errors.email" class="err">{{ errors.email }}</p>
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" v-model="password" placeholder="At least 6 characters" autocomplete="new-password" />
          <p v-if="errors.password" class="err">{{ errors.password }}</p>
        </div>
        <p v-if="serverError" class="err">{{ serverError }}</p>
        <button class="btn btn-primary btn-block" :disabled="loading">{{ loading ? 'Creating…' : 'Create account' }}</button>
      </form>

      <p class="auth-alt">Already have an account? <router-link to="/login">Log in</router-link></p>
    </div>
  </div>
</template>

<style scoped>
.auth{ max-width:440px; margin:20px auto; }
.auth-card{ background:var(--card); border:1px solid var(--line); border-radius:20px;
  padding:38px 34px; box-shadow:var(--shadow); animation:rise .4s ease both; }
.auth-mark{ width:52px; height:52px; border-radius:15px; background:var(--brand); color:#fff;
  display:grid; place-items:center; margin-bottom:20px; box-shadow:var(--shadow-sm); }
.auth-mark svg{ width:27px; height:27px; }
.auth-card h1{ font-size:1.85rem; margin:0 0 6px; }
.auth-sub{ color:var(--ink-2); margin:0 0 24px; }
.auth-alt{ margin:22px 0 0; color:var(--ink-2); font-size:.94rem; }
@keyframes rise{ from{ opacity:0; transform:translateY(12px); } to{ opacity:1; transform:none; } }
</style>
