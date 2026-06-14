<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import http, { imageUrl } from '../api/http'
import { useAuthStore } from '../store/auth'
import LocationMap from '../components/LocationMap.vue'

const route = useRoute()
const auth = useAuthStore()

const item = ref(null)
const loading = ref(true)
const error = ref('')
const claims = ref([])
const matches = ref([])
const hintsLoading = ref(false)

// The pinned point ({ lat, lng } | null) and the AI location-hint payload.
const point = computed(() => {
  const lat = item.value?.latitude, lng = item.value?.longitude
  return lat != null && lng != null ? { lat: +lat, lng: +lng } : null
})
const ai = computed(() => item.value?.ai_location_hints || null)
const aiHints = computed(() => ai.value?.hints || [])
const hasMap = computed(() => !!point.value || aiHints.value.some(h => h.latitude != null))

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
  matches.value = []
  try {
    const { data } = await http.get(`/items/${route.params.id}`)
    item.value = data.item
    loadMatches()
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

async function loadMatches() {
  try {
    const { data } = await http.get(`/items/${route.params.id}/matches`)
    matches.value = data.matches || []
  } catch (e) { /* suggestions are optional — never block the page */ }
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

async function refreshHints() {
  if (!item.value) return
  hintsLoading.value = true
  try {
    const { data } = await http.post(`/items/${item.value.id}/ai-hints`)
    item.value = { ...item.value, ai_location_hints: data.ai_location_hints }
  } catch (e) {
    alert('Could not refresh suggestions.')
  } finally {
    hintsLoading.value = false
  }
}

onMounted(load)
// Re-fetch when navigating between item pages (e.g. clicking a match card) —
// Vue reuses this component, so onMounted alone won't fire again.
watch(() => route.params.id, load)
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

      <div v-if="hasMap" class="map-block">
        <h3 class="map-title">On the campus map</h3>
        <LocationMap :modelValue="point" :hints="aiHints" :editable="false" height="300px" />
        <p v-if="aiHints.some(h => h.latitude != null)" class="map-legend muted small">
          <template v-if="point"><span class="dot dot-pin"></span> Reported point</template>
          <span class="dot dot-hint"></span> AI-suggested places
        </p>
      </div>

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

      <!-- AI location suggestions (local probabilistic scorer) -->
      <div v-if="ai" class="panel ai-panel">
        <div class="panel-head">
          <h3>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v2M12 19v2M5 12H3M21 12h-2M6.3 6.3 4.9 4.9M19.1 19.1l-1.4-1.4M17.7 6.3l1.4-1.4M4.9 19.1l1.4-1.4"/><circle cx="12" cy="12" r="4"/></svg>
            AI location hints
          </h3>
          <span v-if="ai.source === 'openai'" class="ai-tag">OpenAI</span>
          <span v-else class="ai-tag ai-tag--local">On-device</span>
        </div>

        <p v-if="ai.summary" class="ai-summary">{{ ai.summary }}</p>

        <ol v-if="aiHints.length" class="ai-list">
          <li v-for="(h, i) in aiHints" :key="i" class="ai-hint">
            <div class="ai-hint-top">
              <span class="ai-rank">{{ i + 1 }}</span>
              <strong class="ai-loc">{{ h.location }}</strong>
              <span class="ai-score">{{ h.score }}%</span>
            </div>
            <div class="ai-bar"><span :style="{ width: h.score + '%' }"></span></div>
            <div class="ai-why">
              <span v-for="(r, ri) in h.reasons" :key="ri" class="why-pill">{{ r }}</span>
            </div>
          </li>
        </ol>
        <p v-else class="muted small">No location hints yet.</p>

        <button v-if="isOwner" class="btn btn-sm ai-refresh" :disabled="hintsLoading" @click="refreshHints">
          {{ hintsLoading ? 'Analysing…' : 'Refresh suggestions' }}
        </button>
      </div>
    </aside>

    <!-- Smart match suggestions (Feature #3) -->
    <section v-if="matches.length" class="matches">
      <div class="matches-head">
        <h2>Possible matches</h2>
        <p class="muted small">
          {{ item.type === 'lost' ? 'Found items' : 'Lost reports' }} that might be the same thing.
        </p>
      </div>
      <div class="match-grid">
        <router-link v-for="m in matches" :key="m.id" :to="`/items/${m.id}`" class="match-card">
          <img v-if="m.image_path" :src="imageUrl(m.image_path)" :alt="m.title" class="match-thumb" loading="lazy" />
          <div v-else class="match-thumb match-thumb--empty" aria-hidden="true">🔍</div>
          <div class="match-body">
            <span class="badge" :class="m.type">{{ m.type }}</span>
            <h4>{{ m.title }}</h4>
            <p class="match-loc">{{ m.category }} · {{ m.location }}</p>
            <div class="match-why">
              <span v-for="(r, i) in m.match_reasons" :key="i" class="why-pill">{{ r }}</span>
            </div>
          </div>
        </router-link>
      </div>
    </section>
  </div>
</template>

<style scoped>
.back{ display:inline-flex; align-items:center; gap:6px; font-weight:600; color:var(--ink-2); margin-bottom:20px; }
.back:hover{ color:var(--brand); }
.detail{ display:grid; grid-template-columns:1.6fr 1fr; gap:22px; align-items:start; }
.detail-main h1{ margin:16px 0 20px; }
.item-photo{ width:100%; height:340px; object-fit:contain; background:var(--paper-2); border-radius:14px;
  border:1px solid var(--line); box-shadow:var(--shadow-sm); margin:0 0 22px; display:block; }
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

/* Smart match suggestions (Feature #3) */
.matches{ grid-column:1 / -1; margin-top:10px; padding-top:24px; border-top:1px solid var(--line); }
.matches-head{ margin-bottom:15px; }
.matches-head h2{ margin:0 0 2px; }
.match-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(248px, 1fr)); gap:14px; }
.match-card{ display:flex; gap:13px; padding:13px; background:var(--card); border:1px solid var(--line);
  border-radius:14px; box-shadow:var(--shadow-sm); transition:border-color .15s, transform .15s; }
.match-card:hover{ border-color:var(--brand); transform:translateY(-2px); }
.match-thumb{ width:74px; height:74px; flex:none; object-fit:contain; background:var(--paper-2); border-radius:11px; border:1px solid var(--line); }
.match-thumb--empty{ display:grid; place-items:center; font-size:1.7rem; background:var(--paper); }
.match-body{ min-width:0; }
.match-body .badge{ margin-bottom:6px; }
.match-body h4{ margin:0 0 3px; font-size:1rem; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.match-loc{ color:var(--ink-2); font-size:.82rem; margin:0 0 9px; }
.match-why{ display:flex; flex-wrap:wrap; gap:5px; }
.why-pill{ background:var(--brand-100); color:var(--brand-700); font-size:.72rem; font-weight:600;
  padding:2px 9px; border-radius:999px; }

/* Campus map block */
.map-block{ margin:0 0 24px; }
.map-title{ margin:0 0 10px; font-size:1.05rem; }
.map-legend{ display:flex; align-items:center; gap:8px; margin:10px 0 0; flex-wrap:wrap; }
.map-legend .dot{ width:11px; height:11px; border-radius:50%; display:inline-block; margin-left:6px; }
.map-legend .dot-pin{ background:#2A81CB; margin-left:0; }
.map-legend .dot-hint{ background:var(--accent); }

/* AI location hints panel */
.ai-panel .panel-head{ gap:8px; }
.ai-panel .panel-head h3{ display:inline-flex; align-items:center; gap:7px; }
.ai-tag{ font-size:.66rem; font-weight:800; text-transform:uppercase; letter-spacing:.6px;
  background:var(--brand-100); color:var(--brand-700); padding:3px 9px; border-radius:999px; }
.ai-tag--local{ background:var(--accent-100); color:#A8631A; }
.ai-summary{ background:var(--paper); border:1px solid var(--line); border-radius:11px;
  padding:11px 13px; margin:4px 0 14px; font-size:.92rem; line-height:1.5; }
.ai-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:14px; }
.ai-hint-top{ display:flex; align-items:center; gap:8px; margin-bottom:6px; }
.ai-rank{ width:21px; height:21px; flex:none; border-radius:50%; background:var(--brand); color:#fff;
  display:grid; place-items:center; font-size:.74rem; font-weight:800; }
.ai-loc{ flex:1; min-width:0; font-size:.96rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ai-score{ font-weight:800; color:var(--brand-700); font-size:.86rem; }
.ai-bar{ height:7px; background:var(--paper-2); border-radius:999px; overflow:hidden; margin-bottom:8px; }
.ai-bar span{ display:block; height:100%; background:linear-gradient(90deg, var(--accent), var(--brand)); border-radius:999px; }
.ai-why{ display:flex; flex-wrap:wrap; gap:5px; }
.ai-why .why-pill{ background:var(--brand-100); color:var(--brand-700); font-size:.72rem; font-weight:600;
  padding:2px 9px; border-radius:999px; }
.ai-refresh{ margin-top:16px; width:100%; }

@media (max-width:820px){
  .detail{ grid-template-columns:1fr; }
  .detail-side{ position:static; }
}
</style>
