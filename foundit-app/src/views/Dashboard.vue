<script setup>
import { ref, onMounted } from 'vue'
import http from '../api/http'

const tab = ref('items')
const myItems = ref([])
const myClaims = ref([])
const loading = ref(false)

async function loadAll() {
  loading.value = true
  try {
    const [a, b] = await Promise.all([http.get('/me/items'), http.get('/me/claims')])
    myItems.value = a.data.items || []
    myClaims.value = b.data.claims || []
  } catch (e) { /* ignore */ } finally {
    loading.value = false
  }
}

async function resolveItem(item) {
  try {
    // send the existing fields back with status = resolved (PUT replaces the row)
    await http.put(`/items/${item.id}`, { ...item, status: 'resolved' })
    await loadAll()
  } catch (e) { alert('Could not update the item.') }
}

async function deleteItem(item) {
  if (!confirm('Delete this item? This cannot be undone.')) return
  try { await http.delete(`/items/${item.id}`); await loadAll() }
  catch (e) { alert('Could not delete the item.') }
}

async function withdraw(claim) {
  if (!confirm('Withdraw this claim?')) return
  try { await http.delete(`/claims/${claim.id}`); await loadAll() }
  catch (e) { alert('Could not withdraw the claim.') }
}

onMounted(loadAll)
</script>

<template>
  <div class="dash-head">
    <h1>My dashboard</h1>
    <p class="muted">Manage the items you've posted and the claims you've filed.</p>
  </div>

  <div class="tabs">
    <button :class="['tab', tab === 'items' && 'active']" @click="tab = 'items'">
      My Items <span class="tab-count">{{ myItems.length }}</span>
    </button>
    <button :class="['tab', tab === 'claims' && 'active']" @click="tab = 'claims'">
      My Claims <span class="tab-count">{{ myClaims.length }}</span>
    </button>
  </div>

  <p v-if="loading" class="muted">Loading…</p>

  <!-- My Items -->
  <div v-else-if="tab === 'items'">
    <div v-if="myItems.length === 0" class="dash-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18" stroke-linecap="round"/></svg>
      <h3>No items yet</h3>
      <p class="muted">You haven't posted anything. Report a lost or found item to get started.</p>
      <router-link class="btn btn-primary" to="/post">Report an item</router-link>
    </div>
    <div v-for="item in myItems" :key="item.id" class="row dash-row">
      <div class="dash-info">
        <div class="dash-title-row">
          <router-link :to="`/items/${item.id}`" class="dash-title">{{ item.title }}</router-link>
          <span class="badge" :class="item.type">{{ item.type }}</span>
          <span class="status" :class="item.status">{{ item.status }}</span>
        </div>
        <p class="muted small">{{ item.claim_count }} claim<span v-if="item.claim_count != 1">s</span> · {{ item.category }} · {{ item.location }}</p>
      </div>
      <div class="dash-actions">
        <button v-if="item.status !== 'resolved'" class="btn btn-sm" @click="resolveItem(item)">Mark resolved</button>
        <button class="btn btn-danger btn-sm" @click="deleteItem(item)">Delete</button>
      </div>
    </div>
  </div>

  <!-- My Claims -->
  <div v-else>
    <div v-if="myClaims.length === 0" class="dash-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9"/></svg>
      <h3>No claims yet</h3>
      <p class="muted">When you claim an item, you can track its status here.</p>
      <router-link class="btn btn-primary" to="/">Browse items</router-link>
    </div>
    <div v-for="c in myClaims" :key="c.id" class="row dash-row">
      <div class="dash-info">
        <div class="dash-title-row">
          <strong class="dash-title">{{ c.item_title }}</strong>
          <span class="badge" :class="c.item_type">{{ c.item_type }}</span>
          <span class="status" :class="c.status">{{ c.status }}</span>
        </div>
        <p class="muted claim-quote">"{{ c.message }}"</p>
      </div>
      <div class="dash-actions">
        <button v-if="c.status === 'pending'" class="btn btn-danger btn-sm" @click="withdraw(c)">Withdraw</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dash-head{ margin-bottom:6px; }
.dash-head h1{ margin-bottom:6px; }
.tab-count{ background:var(--paper-2); color:var(--ink-2); font-size:.72rem; font-weight:800; padding:1px 8px; border-radius:999px; margin-left:6px; }
.tab.active .tab-count{ background:var(--brand-100); color:var(--brand-700); }
.dash-row{ align-items:center; }
.dash-info{ min-width:0; }
.dash-title-row{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:4px; }
.dash-title{ font-weight:700; font-size:1.06rem; color:var(--ink); }
a.dash-title:hover{ color:var(--brand); }
.dash-actions{ display:flex; gap:8px; flex-shrink:0; }
.small{ font-size:.84rem; }
.claim-quote{ font-style:italic; margin:4px 0 0; line-height:1.5; }
.dash-empty{ text-align:center; padding:54px 22px; background:var(--card); border:1px dashed var(--line-2); border-radius:var(--r); }
.dash-empty svg{ width:50px; height:50px; color:var(--line-2); }
.dash-empty h3{ margin:12px 0 4px; }
.dash-empty .muted{ max-width:38ch; margin:4px auto 18px; }
@media (max-width:560px){
  .dash-row{ flex-direction:column; align-items:stretch; }
  .dash-actions{ justify-content:flex-end; }
}
</style>
