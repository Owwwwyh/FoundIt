<script setup>
import { ref, onMounted } from 'vue'
import http, { imageUrl } from '../api/http'

const items = ref([])
const loading = ref(false)
const error = ref('')
const filters = ref({ type: '', category: '', search: '' })
const categories = ['Electronics', 'Documents', 'Keys', 'Clothing', 'Other']

async function loadItems() {
  loading.value = true
  error.value = ''
  try {
    const params = {}
    if (filters.value.type) params.type = filters.value.type
    if (filters.value.category) params.category = filters.value.category
    if (filters.value.search) params.search = filters.value.search
    const { data } = await http.get('/items', { params })   // async GET to the REST API
    items.value = data.items || []
  } catch (e) {
    error.value = 'Could not load items.'
  } finally {
    loading.value = false
  }
}

function reset() {
  filters.value = { type: '', category: '', search: '' }
  loadItems()
}

// presentation-only helper
function formatDate(d) {
  if (!d) return ''
  const dt = new Date(d)
  if (isNaN(dt)) return d
  return dt.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(loadItems)
</script>

<template>
  <!-- Hero -->
  <section class="hero">
    <div class="hero-copy">
      <p class="eyebrow">Campus Lost &amp; Found</p>
      <h1>Lost something on campus?<br /><span class="hl">FoundIt</span> brings it home.</h1>
      <p class="hero-sub">
        Search everything reported across campus, post what you've lost or found,
        and reclaim it through a simple, verified claim.
      </p>
    </div>
    <router-link class="btn btn-primary btn-lg" to="/post">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Report an item
    </router-link>
    <svg class="hero-deco" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M12 21s7-5.686 7-11a7 7 0 1 0-14 0c0 5.314 7 11 7 11Z" stroke="currentColor" stroke-width="1.4"/>
      <circle cx="12" cy="10" r="2.6" stroke="currentColor" stroke-width="1.4"/>
    </svg>
  </section>

  <!-- Filter bar -->
  <form class="filters" @submit.prevent="loadItems">
    <div class="search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2" stroke-linecap="round"/></svg>
      <input v-model="filters.search" placeholder="Search title or description…" />
    </div>
    <div class="seg" role="group" aria-label="Filter by type">
      <button type="button" :class="{ active: filters.type === '' }" @click="filters.type = ''; loadItems()">All</button>
      <button type="button" :class="{ active: filters.type === 'lost' }" @click="filters.type = 'lost'; loadItems()">Lost</button>
      <button type="button" :class="{ active: filters.type === 'found' }" @click="filters.type = 'found'; loadItems()">Found</button>
    </div>
    <select v-model="filters.category" @change="loadItems">
      <option value="">All categories</option>
      <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Search</button>
    <button class="btn btn-ghost btn-sm" type="button" @click="reset">Reset</button>
  </form>

  <!-- Loading skeletons -->
  <div v-if="loading" class="grid">
    <div v-for="n in 6" :key="n" class="skel">
      <div class="skel-line" style="width:40%"></div>
      <div class="skel-line" style="width:80%; height:18px"></div>
      <div class="skel-line" style="width:60%"></div>
      <div class="skel-line" style="width:35%; margin-top:18px"></div>
    </div>
  </div>

  <p v-else-if="error" class="err">{{ error }}</p>

  <!-- Empty state -->
  <div v-else-if="items.length === 0" class="empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/></svg>
    <h3>Nothing here yet</h3>
    <p class="muted">No items match your search. Try clearing the filters — or be the first to report one.</p>
    <router-link class="btn btn-primary" to="/post">Report an item</router-link>
  </div>

  <!-- Results -->
  <template v-else>
    <p class="results-head muted">{{ items.length }} item<span v-if="items.length !== 1">s</span> reported</p>
    <div class="grid">
      <router-link v-for="item in items" :key="item.id" :to="`/items/${item.id}`" class="card">
        <div v-if="item.image_path" class="card-photo">
          <img :src="imageUrl(item.image_path)" :alt="item.title" loading="lazy" />
        </div>
        <div class="card-top">
          <span class="badge" :class="item.type">{{ item.type }}</span>
          <span class="status" :class="item.status">{{ item.status }}</span>
        </div>
        <h3>{{ item.title }}</h3>
        <p class="meta">{{ item.category }} · {{ item.location }}</p>
        <div class="date">
          <span>{{ formatDate(item.date_reported) }}</span>
          <span class="go">View →</span>
        </div>
      </router-link>
    </div>
  </template>
</template>

<style scoped>
.hero{ position:relative; display:flex; justify-content:space-between; align-items:flex-end; gap:26px; flex-wrap:wrap;
  background:linear-gradient(135deg, #FFFDF8 0%, #F4ECDC 100%); border:1px solid var(--line);
  border-radius:24px; padding:40px 38px; box-shadow:var(--shadow-sm); overflow:hidden; }
.hero-copy{ position:relative; z-index:1; }
.eyebrow{ text-transform:uppercase; letter-spacing:2.4px; font-size:.72rem; font-weight:800; color:var(--accent); margin:0 0 12px; }
.hero h1{ margin:0; max-width:20ch; }
.hero .hl{ color:var(--brand); font-style:italic; }
.hero-sub{ color:var(--ink-2); max-width:48ch; margin:16px 0 0; font-size:1.04rem; }
.btn-lg{ padding:14px 24px; font-size:1rem; border-radius:14px; position:relative; z-index:1; white-space:nowrap; }
.hero-deco{ position:absolute; right:-30px; bottom:-46px; width:230px; height:230px; color:var(--brand); opacity:.06; pointer-events:none; }

.seg{ display:inline-flex; background:var(--paper); border:1px solid var(--line-2); border-radius:10px; padding:3px; }
.seg button{ border:none; background:transparent; padding:9px 16px; border-radius:8px; font-family:var(--font-body);
  font-weight:600; font-size:.88rem; color:var(--ink-2); cursor:pointer; transition:all .15s; }
.seg button:hover{ color:var(--ink); }
.seg button.active{ background:var(--brand); color:#fff; box-shadow:var(--shadow-sm); }

.results-head{ margin:0 0 14px; font-weight:600; font-size:.9rem; }

.card-photo{ margin:-2px 0 12px; border-radius:10px; overflow:hidden; height:150px; background:var(--paper-2); }
.card-photo img{ width:100%; height:100%; object-fit:cover; display:block; }

.skel{ background:var(--card); border:1px solid var(--line); border-radius:var(--r); padding:18px; box-shadow:var(--shadow-sm); }
.skel-line{ height:12px; border-radius:6px; margin:9px 0;
  background:linear-gradient(90deg, var(--paper-2) 25%, #ECE0CB 50%, var(--paper-2) 75%);
  background-size:200% 100%; animation:shimmer 1.4s infinite; }
@keyframes shimmer{ from{ background-position:200% 0; } to{ background-position:-200% 0; } }

.empty{ text-align:center; padding:64px 20px; }
.empty svg{ width:60px; height:60px; color:var(--line-2); margin-bottom:6px; }
.empty h3{ margin:6px 0; font-size:1.4rem; }
.empty .muted{ max-width:38ch; margin:6px auto 20px; }

@media (max-width:560px){
  .hero{ padding:30px 24px; }
  .btn-lg{ width:100%; }
}
</style>
