<script setup>
// ----------------------------------------------------------------------
// Admin — moderation dashboard (admin role only). Shows campus-wide stats
// and lets an admin moderate ANY item (force status / delete), which is
// deeper than the per-owner checks normal users get.
// ----------------------------------------------------------------------
import { ref, onMounted } from 'vue'
import http from '../api/http'

const stats = ref(null)
const items = ref([])
const loading = ref(true)
const error = ref('')
const filters = ref({ type: '', status: '', search: '' })

async function loadStats() {
  try {
    const { data } = await http.get('/admin/stats')
    stats.value = data.stats
  } catch (e) { error.value = 'Could not load stats.' }
}

async function loadItems() {
  try {
    const params = {}
    if (filters.value.type) params.type = filters.value.type
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.search) params.search = filters.value.search
    const { data } = await http.get('/admin/items', { params })
    items.value = data.items || []
  } catch (e) { error.value = 'Could not load items.' }
}

async function loadAll() {
  loading.value = true
  error.value = ''
  await Promise.all([loadStats(), loadItems()])
  loading.value = false
}

function resetFilters() {
  filters.value = { type: '', status: '', search: '' }
  loadItems()
}

async function setStatus(item, status) {
  try {
    await http.put(`/admin/items/${item.id}`, { status })
    await loadAll()
  } catch (e) { alert('Could not update the item.') }
}

async function removeItem(item) {
  if (!confirm(`Delete "${item.title}"? This permanently removes the item and its claims.`)) return
  try {
    await http.delete(`/admin/items/${item.id}`)
    await loadAll()
  } catch (e) { alert('Could not delete the item.') }
}

function formatDate(d) {
  if (!d) return ''
  const dt = new Date(d)
  return isNaN(dt) ? d : dt.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(loadAll)
</script>

<template>
  <div class="dash-head">
    <h1>Moderation dashboard</h1>
    <p class="muted">Campus-wide overview and item moderation tools.</p>
  </div>

  <p v-if="error" class="err">{{ error }}</p>

  <!-- Stat cards -->
  <section v-if="stats" class="stat-grid">
    <div class="stat-card">
      <span class="stat-label">Resolved rate</span>
      <span class="stat-value">{{ stats.resolved_rate }}%</span>
      <span class="stat-sub">{{ stats.resolved }} of {{ stats.items }} items returned</span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Total items</span>
      <span class="stat-value">{{ stats.items }}</span>
      <span class="stat-sub">{{ stats.lost }} lost · {{ stats.found }} found</span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Users</span>
      <span class="stat-value">{{ stats.users }}</span>
      <span class="stat-sub">{{ stats.admins }} admin<span v-if="stats.admins !== 1">s</span></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Claims</span>
      <span class="stat-value">{{ stats.claims }}</span>
      <span class="stat-sub">{{ stats.claims_pending }} pending · {{ stats.claims_approved }} approved</span>
    </div>
  </section>

  <!-- Status + category breakdown -->
  <section v-if="stats" class="break-grid">
    <div class="panel">
      <h3>Items by status</h3>
      <div class="bar-row"><span class="bar-k"><span class="status open">Open</span></span><span class="bar-v">{{ stats.open }}</span></div>
      <div class="bar-row"><span class="bar-k"><span class="status claimed">Claimed</span></span><span class="bar-v">{{ stats.claimed }}</span></div>
      <div class="bar-row"><span class="bar-k"><span class="status resolved">Resolved</span></span><span class="bar-v">{{ stats.resolved }}</span></div>
    </div>
    <div class="panel">
      <h3>Items by category</h3>
      <p v-if="!stats.by_category.length" class="muted small">No items yet.</p>
      <div v-for="c in stats.by_category" :key="c.category" class="bar-row">
        <span class="bar-k">{{ c.category }}</span>
        <span class="bar-v">{{ c.count }}</span>
      </div>
    </div>
  </section>

  <!-- Item moderation -->
  <h2 class="mod-title">All items</h2>
  <form class="filters" @submit.prevent="loadItems">
    <div class="search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2" stroke-linecap="round"/></svg>
      <input v-model="filters.search" placeholder="Search title, location or poster…" />
    </div>
    <select v-model="filters.type" @change="loadItems">
      <option value="">All types</option>
      <option value="lost">Lost</option>
      <option value="found">Found</option>
    </select>
    <select v-model="filters.status" @change="loadItems">
      <option value="">All statuses</option>
      <option value="open">Open</option>
      <option value="claimed">Claimed</option>
      <option value="resolved">Resolved</option>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Search</button>
    <button class="btn btn-ghost btn-sm" type="button" @click="resetFilters">Reset</button>
  </form>

  <p v-if="loading" class="muted">Loading…</p>
  <p v-else-if="!items.length" class="muted">No items match your filters.</p>

  <div v-for="item in items" :key="item.id" class="row dash-row">
    <div class="dash-info">
      <div class="dash-title-row">
        <router-link :to="`/items/${item.id}`" class="dash-title">{{ item.title }}</router-link>
        <span class="badge" :class="item.type">{{ item.type }}</span>
        <span class="status" :class="item.status">{{ item.status }}</span>
      </div>
      <p class="muted small">
        {{ item.poster_name }} · {{ item.category }} · {{ item.location }} ·
        {{ item.claim_count }} claim<span v-if="item.claim_count != 1">s</span> · {{ formatDate(item.date_reported) }}
      </p>
    </div>
    <div class="dash-actions">
      <button v-if="item.status !== 'resolved'" class="btn btn-sm" @click="setStatus(item, 'resolved')">Mark resolved</button>
      <button v-else class="btn btn-sm" @click="setStatus(item, 'open')">Reopen</button>
      <button class="btn btn-danger btn-sm" @click="removeItem(item)">Delete</button>
    </div>
  </div>
</template>

<style scoped>
.dash-head{ margin-bottom:18px; }
.dash-head h1{ margin-bottom:6px; }

.stat-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:16px; margin-bottom:18px; }
.stat-card{ background:var(--card); border:1px solid var(--line); border-radius:var(--r); padding:18px 20px;
  box-shadow:var(--shadow-sm); display:flex; flex-direction:column; gap:4px; }
.stat-label{ font-size:.72rem; text-transform:uppercase; letter-spacing:.7px; font-weight:800; color:var(--ink-2); }
.stat-value{ font-family:var(--font-display); font-size:2rem; font-weight:600; color:var(--ink); line-height:1.1; }
.stat-sub{ font-size:.82rem; color:var(--ink-2); }

.break-grid{ display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:26px; }
.break-grid h3{ margin-top:0; }
.bar-row{ display:flex; align-items:center; justify-content:space-between; padding:8px 0;
  border-top:1px solid var(--line); }
.bar-row:first-of-type{ border-top:none; }
.bar-k{ color:var(--ink); font-size:.92rem; }
.bar-v{ font-weight:800; color:var(--brand-700); }

.mod-title{ margin:6px 0 2px; }
.dash-row{ align-items:center; }
.dash-info{ min-width:0; flex:1; }
.dash-title-row{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:4px; }
.dash-title{ font-weight:700; font-size:1.04rem; color:var(--ink); }
a.dash-title:hover{ color:var(--brand); }
.dash-actions{ display:flex; gap:8px; flex-shrink:0; }
.small{ font-size:.84rem; }

@media (max-width:680px){
  .break-grid{ grid-template-columns:1fr; }
}
@media (max-width:560px){
  .dash-row{ flex-direction:column; align-items:stretch; }
  .dash-actions{ justify-content:flex-end; }
}
</style>
