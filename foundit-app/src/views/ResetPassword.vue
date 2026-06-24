<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import http from '../api/http'

const route = useRoute()
const router = useRouter()
const token = ref('')
const password = ref('')
const confirm = ref('')
const errors = ref({})
const serverError = ref('')
const loading = ref(false)

onMounted(() => { token.value = route.query.token || '' })

function validate() {
  const e = {}
  if (password.value.length < 6) e.password = 'Password must be at least 6 characters.'
  if (confirm.value !== password.value) e.confirm = 'Passwords do not match.'
  errors.value = e
  return Object.keys(e).length === 0
}

async function submit() {
  serverError.value = ''
  if (!validate()) return
  loading.value = true
  try {
    await http.post('/reset-password', { token: token.value, password: password.value })
    router.push({ name: 'login', query: { reset: '1' } })
  } catch (err) {
    if (err.response?.data?.errors) errors.value = err.response.data.errors
    else serverError.value = err.response?.data?.error || 'Could not reset your password. Please try again.'
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
          <rect x="4" y="10.5" width="16" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
          <path d="M8 10.5V7a4 4 0 0 1 8 0v3.5" stroke="currentColor" stroke-width="2"/>
        </svg>
      </div>
      <h1>Set a new password</h1>
      <p class="auth-sub">Choose a new password for your FoundIt account.</p>

      <p v-if="!token" class="err">
        This reset link is missing or invalid. Please request a new one from the
        <router-link to="/forgot-password">forgot password</router-link> page.
      </p>
      <form v-else @submit.prevent="submit">
        <div class="field">
          <label>New password</label>
          <input type="password" v-model="password" placeholder="At least 6 characters" autocomplete="new-password" />
          <p v-if="errors.password" class="err">{{ errors.password }}</p>
        </div>
        <div class="field">
          <label>Confirm password</label>
          <input type="password" v-model="confirm" placeholder="Re-enter your new password" autocomplete="new-password" />
          <p v-if="errors.confirm" class="err">{{ errors.confirm }}</p>
        </div>
        <p v-if="serverError" class="err">{{ serverError }}</p>
        <button class="btn btn-primary btn-block" :disabled="loading">{{ loading ? 'Saving…' : 'Reset password' }}</button>
      </form>

      <p class="auth-alt"><router-link to="/login">&larr; Back to log in</router-link></p>
    </div>
  </div>
</template>

<style scoped>
.auth{ max-width:440px; margin:20px auto; }
.auth-card{ background:var(--card); border:1px solid var(--line); border-radius:20px;
  padding:38px 34px; box-shadow:var(--shadow); animation:rise .4s ease both; }
.auth-mark{ width:52px; height:52px; border-radius:15px; background:var(--brand); color:#fff;
  display:grid; place-items:center; margin-bottom:20px; box-shadow:var(--shadow-sm); }
.auth-mark svg{ width:26px; height:26px; }
.auth-card h1{ font-size:1.85rem; margin:0 0 6px; }
.auth-sub{ color:var(--ink-2); margin:0 0 24px; }
.auth-alt{ margin:22px 0 0; color:var(--ink-2); font-size:.94rem; }
@keyframes rise{ from{ opacity:0; transform:translateY(12px); } to{ opacity:1; transform:none; } }
</style>
