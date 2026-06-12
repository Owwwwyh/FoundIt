<script setup>
import { ref } from 'vue'
import http from '../api/http'

const email = ref('')
const loading = ref(false)
const done = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  if (!email.value.trim()) { error.value = 'Please enter your email.'; return }
  loading.value = true
  try {
    await http.post('/forgot-password', { email: email.value })
    done.value = true
  } catch (e) {
    error.value = 'Something went wrong. Please try again.'
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
      <h1>Forgot password?</h1>
      <p class="auth-sub">Enter your email and we'll send you a link to set a new one.</p>

      <div v-if="done" class="ok">
        If that email is registered, a reset link is on its way — check your inbox (and your spam folder).
        The link is valid for 30 minutes.
      </div>
      <form v-else @submit.prevent="submit">
        <div class="field">
          <label>Email</label>
          <input type="email" v-model="email" placeholder="you@example.com" autocomplete="email" />
        </div>
        <p v-if="error" class="err">{{ error }}</p>
        <button class="btn btn-primary btn-block" :disabled="loading">{{ loading ? 'Sending…' : 'Send reset link' }}</button>
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
