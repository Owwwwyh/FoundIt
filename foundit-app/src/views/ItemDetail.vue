<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import http, { imageUrl } from '../api/http'
import { useAuthStore } from '../store/auth'

const route = useRoute()
const auth = useAuthStore()

const item = ref(null)
const loading = ref(true)
const error = ref('')
const claims = ref([])

const claimMessage = ref('')
const claimError = ref('')
const claimOk = ref('')

const isOwner = computed(() => auth.isLoggedIn && item.value && auth.userId === item.value.user_id)
const posterInitial = computed(() => (item.value?.poster_name || '?').trim().charAt(0).toUpperCase())

function formatDate(d) {
  if (!d) return ''
  const dt = new Date(d)
  if (isNaN(dt)) return d
  return dt.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get(`/items/${route.params.id}`)
    item.value = data.item
    if (isOwner.value) await loadClaims()
  } catch (e) {
    error.value = 'Item not found.'
  } finally {
    loading.value = false
  }
}

async function loadClaims() {
  try {
    const { data } = await http.get(`/items/${route.params.id}/claims`)
    claims.value = data.claims || []
  } catch (e) { /* not owner / none */ }
}

async function submitClaim() {
  claimError.value = ''
  claimOk.value = ''
  if (claimMessage.value.trim().length < 10) {
    claimError.value = 'Please describe your proof (at least 10 characters).'
    return
  }
  try {
    await http.post(`/items/${route.params.id}/claims`, { message: claimMessage.value })
    claimOk.value = 'Your claim has been sent to the poster.'
    claimMessage.value = ''
  } catch (e) {
    claimError.value = e.response?.data?.error || 'Could not submit claim.'
  }
}

async function review(claim, status) {
  try {
    await http.put(`/claims/${claim.id}`, { status })
    await load()   // refresh item status + claim list
  } catch (e) {
    alert('Could not update the claim.')
  }
}

onMounted(load)
</script>

<template>
  <router-link to="/" class="back">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Back to browse
  </router-link>

  <div v-if="loading" class="detail-skel"></div>
  <p v-else-if="error" class="err big">{{ error }}</p>

  <div v-else-if="item" class="detail">
    <!-- Main item card -->
    <article class="detail-main panel">
      <div class="card-top">
        <span class="badge" :class="item.type">{{ item.type }}</span>
        <span class="status" :class="item.status">{{ item.status }}</span>
      </div>
      <h1>{{ item.title }}</h1>

      <img v-if="item.image_path" :src="imageUrl(item.image_path)" :alt="item.title" class="item-photo" />

      <div class="facts">
        <div class="fact"><span class="fact-k">Category</span><span class="fact-v">{{ item.category }}</span></div>
        <div class="fact"><span class="fact-k">Location</span><span class="fact-v">{{ item.location }}</span></div>
        <div class="fact"><span class="fact-k">Reported</span><span class="fact-v">{{ formatDate(item.date_reported) }}</span></div>
      </div>

      <p class="description">{{ item.description || 'No description provided.' }}</p>

      <div class="poster">
        <span class="avatar-lg">{{ posterInitial }}</span>
        <div>
          <span class="muted small">Posted by</span>
          <div class="poster-name">{{ item.poster_name }}</div>
        </div>
      </div>
    </article>

    <!-- Side: claim / owner review / login prompt -->
    <aside class="detail-side">
      <div v-if="auth.isLoggedIn && !isOwner" class="panel">
        <h3>This is mine</h3>
        <p class="muted small">Describe proof of ownership — it goes straight to the poster.</p>
        <textarea v-model="claimMessage" rows="4" placeholder="e.g. It has my initials engraved near the cap…"></textarea>
        <p v-if="claimError" class="err">{{ claimError }}</p>
        <p v-if="claimOk" class="ok">{{ claimOk }}</p>
        <button class="btn btn-primary btn-block" @click="submitClaim">Submit claim</button>
      </div>

      <div v-else-if="!auth.isLoggedIn" class="panel login-prompt">
        <h3>Is this yours?</h3>
        <p class="muted">Log in to file a claim on this item.</p>
        <router-link class="btn btn-primary btn-block" to="/login">Log in to claim</router-link>
      </div>

      <div v-if="isOwner" class="panel">
        <div class="panel-head">
          <h3>Claims</h3>
          <span class="count">{{ claims.length }}</span>
        </div>
        <p v-if="claims.length === 0" class="muted small">No claims yet. They'll appear here when someone files one.</p>
        <div v-for="c in claims" :key="c.id" class="claim">
          <div class="claim-head">
            <span class="avatar-sm">{{ (c.claimant_name || '?').charAt(0) }}</span>
            <strong>{{ c.claimant_name }}</strong>
            <span class="status" :class="c.status">{{ c.status }}</span>
          </div>
          <p class="claim-msg">{{ c.message }}</p>
          <div v-if="c.status === 'pending'" class="claim-actions">
            <button class="btn btn-primary btn-sm" @click="review(c, 'approved')">Approve</button>
            <button class="btn btn-danger btn-sm" @click="review(c, 'rejected')">Reject</button>
          </div>
        </div>
      </div>
    </aside>
  </div>
</template>

<style scoped>
.back{ display:inline-flex; align-items:center; gap:6px; font-weight:600; color:var(--ink-2); margin-bottom:20px; }
.back:hover{ color:var(--brand); }
.detail{ display:grid; grid-template-columns:1.6fr 1fr; gap:22px; align-items:start; }
.detail-main h1{ margin:16px 0 20px; }
.item-photo{ width:100%; max-height:380px; object-fit:cover; border-radius:14px; border:1px solid var(--line);
  box-shadow:var(--shadow-sm); margin:0 0 22px; display:block; }
.facts{ display:flex; flex-wrap:wrap; gap:10px; margin:0 0 22px; }
.fact{ background:var(--paper); border:1px solid var(--line); border-radius:12px; padding:10px 15px; min-width:118px; }
.fact-k{ display:block; font-size:.7rem; text-transform:uppercase; letter-spacing:.6px; color:var(--ink-2); font-weight:700; margin-bottom:3px; }
.fact-v{ font-weight:600; }
.description{ font-size:1.05rem; line-height:1.72; margin:0 0 24px; }
.poster{ display:flex; align-items:center; gap:13px; border-top:1px solid var(--line); padding-top:20px; }
.avatar-lg{ width:46px; height:46px; border-radius:50%; background:var(--accent-100); color:#A8631A; display:grid; place-items:center; font-weight:800; font-size:1.15rem; }
.poster-name{ font-weight:700; }
.small{ font-size:.82rem; }

.detail-side{ display:flex; flex-direction:column; gap:18px; position:sticky; top:84px; }
.detail-side textarea{ margin-bottom:10px; }
.panel-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.panel-head h3{ margin:0; }
.count{ background:var(--brand-100); color:var(--brand-700); font-weight:800; font-size:.8rem; padding:2px 11px; border-radius:999px; }
.claim-head{ display:flex; align-items:center; gap:9px; margin-bottom:7px; }
.avatar-sm{ width:30px; height:30px; border-radius:50%; background:var(--paper-2); color:var(--ink); display:grid; place-items:center; font-weight:700; font-size:.82rem; text-transform:uppercase; }
.claim-head strong{ flex:1; }
.claim-msg{ color:var(--ink-2); margin:0 0 11px; line-height:1.55; }
.claim-actions{ display:flex; gap:8px; }
.login-prompt{ text-align:center; }
.detail-skel{ height:300px; border-radius:var(--r); background:var(--card); border:1px solid var(--line); animation:pulse 1.4s infinite; }
@keyframes pulse{ 0%,100%{ opacity:1; } 50%{ opacity:.55; } }
.err.big{ font-size:1.05rem; }

@media (max-width:820px){
  .detail{ grid-template-columns:1fr; }
  .detail-side{ position:static; }
}
</style>
