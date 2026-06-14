<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import http from '../api/http'
import LocationMap from '../components/LocationMap.vue'

const router = useRouter()
const form = ref({
  title: '', category: '', type: '', location: '', description: '',
  date_reported: new Date().toISOString().slice(0, 10)
})

// The point chosen on the campus map ({ lat, lng } | null).
const point = ref(null)
function clearPoint() { point.value = null }
const categories = ['Electronics', 'Documents', 'Keys', 'Clothing', 'Other']
const errors = ref({})
const serverError = ref('')
const loading = ref(false)

const imageFile = ref(null)
const imagePreview = ref('')

function onFile(e) {
  const f = e.target.files && e.target.files[0]
  errors.value = { ...errors.value, image: '' }
  if (!f) { imageFile.value = null; imagePreview.value = ''; return }
  if (!f.type.startsWith('image/')) {
    errors.value = { ...errors.value, image: 'Please choose an image file.' }
    imageFile.value = null; imagePreview.value = ''; e.target.value = ''
    return
  }
  if (f.size > 2 * 1024 * 1024) {
    errors.value = { ...errors.value, image: 'Image must be 2 MB or smaller.' }
    imageFile.value = null; imagePreview.value = ''; e.target.value = ''
    return
  }
  imageFile.value = f
  imagePreview.value = URL.createObjectURL(f)
}

function clearImage() {
  imageFile.value = null
  imagePreview.value = ''
  errors.value = { ...errors.value, image: '' }
}

function validate() {
  const e = {}
  if (!form.value.title.trim()) e.title = 'Title is required.'
  if (!form.value.category) e.category = 'Please choose a category.'
  if (!form.value.type) e.type = 'Please choose lost or found.'
  if (!form.value.location.trim()) e.location = 'Location is required.'
  errors.value = e
  return Object.keys(e).length === 0
}

async function submit() {
  serverError.value = ''
  if (!validate()) return
  loading.value = true
  try {
    // JWT is attached automatically by the axios interceptor
    const payload = {
      ...form.value,
      latitude: point.value ? point.value.lat : null,
      longitude: point.value ? point.value.lng : null
    }
    const { data } = await http.post('/items', payload)
    const id = data.item.id

    // If a photo was chosen, upload it to the new item (best-effort)
    if (imageFile.value) {
      try {
        const fd = new FormData()
        fd.append('image', imageFile.value)
        await http.post(`/items/${id}/image`, fd)
      } catch (e) {
        // The item is already created; don't lose it if only the photo failed
        console.warn('Photo upload failed:', e.response?.data || e.message)
      }
    }
    router.push(`/items/${id}`)
  } catch (err) {
    if (err.response?.data?.errors) errors.value = err.response.data.errors
    else serverError.value = 'Could not save the item.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="post-wrap">
    <div class="post-head">
      <h1>Report an item</h1>
      <p class="muted">Lost or found something on campus? Add the details so the right person can find it.</p>
    </div>

    <form class="panel form-card" @submit.prevent="submit">
      <div class="field">
        <label>Title</label>
        <input v-model="form.title" placeholder="e.g. Black water bottle" />
        <p v-if="errors.title" class="err">{{ errors.title }}</p>
      </div>

      <div class="field">
        <label>Type</label>
        <div class="type-chips">
          <label class="chip" :class="{ sel: form.type === 'lost' }">
            <input type="radio" value="lost" v-model="form.type" />
            <span class="chip-dot lost"></span> Lost
          </label>
          <label class="chip" :class="{ sel: form.type === 'found' }">
            <input type="radio" value="found" v-model="form.type" />
            <span class="chip-dot found"></span> Found
          </label>
        </div>
        <p v-if="errors.type" class="err">{{ errors.type }}</p>
      </div>

      <div class="grid-2">
        <div class="field">
          <label>Category</label>
          <select v-model="form.category">
            <option value="">Choose…</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
          <p v-if="errors.category" class="err">{{ errors.category }}</p>
        </div>
        <div class="field">
          <label>Date</label>
          <input type="date" v-model="form.date_reported" />
        </div>
      </div>

      <div class="field">
        <label>Location</label>
        <input v-model="form.location" placeholder="Where was it lost / found?" />
        <p v-if="errors.location" class="err">{{ errors.location }}</p>
      </div>

      <div class="field">
        <label>Pin it on the campus map <span class="opt">(optional)</span></label>
        <p class="map-hint muted small">Tap the map to drop a pin where the item was lost or found — drag it to fine-tune.</p>
        <LocationMap v-model="point" :editable="true" height="300px" />
        <div class="map-meta">
          <span v-if="point" class="coords">📍 {{ point.lat.toFixed(5) }}, {{ point.lng.toFixed(5) }}</span>
          <span v-else class="muted small">No pin placed yet.</span>
          <button v-if="point" type="button" class="btn btn-ghost btn-sm" @click="clearPoint">Clear pin</button>
        </div>
      </div>

      <div class="field">
        <label>Description <span class="opt">(optional)</span></label>
        <textarea v-model="form.description" rows="3" placeholder="Any extra detail that helps identify it…"></textarea>
      </div>

      <div class="field">
        <label>Photo <span class="opt">(optional · JPG/PNG/WEBP/GIF, max 2 MB)</span></label>
        <input type="file" accept="image/*" class="file-input" @change="onFile" />
        <p v-if="errors.image" class="err">{{ errors.image }}</p>
        <div v-if="imagePreview" class="img-preview">
          <img :src="imagePreview" alt="Selected photo preview" />
          <button type="button" class="btn btn-ghost btn-sm" @click="clearImage">Remove photo</button>
        </div>
      </div>

      <p v-if="serverError" class="err">{{ serverError }}</p>
      <button class="btn btn-primary btn-block btn-submit" :disabled="loading">{{ loading ? 'Saving…' : 'Submit report' }}</button>
    </form>
  </div>
</template>

<style scoped>
.post-wrap{ max-width:620px; margin:0 auto; }
.post-head{ margin-bottom:22px; }
.post-head h1{ margin-bottom:6px; }
.form-card{ padding:28px; }
.grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.type-chips{ display:flex; gap:12px; }
.chip{ position:relative; flex:1; display:flex; align-items:center; justify-content:center; gap:9px;
  padding:13px; border:1.5px solid var(--line-2); border-radius:12px; cursor:pointer; font-weight:600;
  background:var(--card); transition:all .15s; }
.chip:hover{ border-color:var(--brand); }
.chip.sel{ border-color:var(--brand); background:var(--brand-100); color:var(--brand-700); box-shadow:0 0 0 3px rgba(28,107,94,.1); }
.chip input{ position:absolute; opacity:0; width:0; height:0; }
.chip-dot{ width:10px; height:10px; border-radius:50%; }
.chip-dot.lost{ background:var(--lost); }
.chip-dot.found{ background:var(--found); }
.opt{ color:var(--ink-2); font-weight:400; font-size:.82rem; }
.file-input{ padding:10px 12px; border:1.5px dashed var(--line-2); border-radius:11px; background:var(--paper);
  font-size:.9rem; cursor:pointer; }
.file-input::file-selector-button{ font-family:var(--font-body); font-weight:600; font-size:.85rem; cursor:pointer;
  border:1px solid var(--line-2); background:var(--card); color:var(--ink); padding:7px 12px; border-radius:8px; margin-right:12px; }
.img-preview{ margin-top:12px; display:flex; align-items:flex-end; gap:14px; }
.img-preview img{ width:140px; height:140px; object-fit:contain; background:var(--paper-2); border-radius:12px;
  border:1px solid var(--line); box-shadow:var(--shadow-sm); }
.btn-submit{ margin-top:6px; padding:13px; }
.map-hint{ margin:0 0 10px; }
.map-meta{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:10px; }
.coords{ font-weight:600; font-size:.86rem; color:var(--ink); background:var(--paper-2);
  padding:5px 11px; border-radius:9px; }
@media (max-width:520px){ .grid-2{ grid-template-columns:1fr; } }
</style>
