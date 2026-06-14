<script setup>
// ----------------------------------------------------------------------
// LocationMap — a small Leaflet wrapper used in two modes:
//   • editable  : click the map (or drag the pin) to pick where an item was
//                 lost/found. Emits update:modelValue with { lat, lng }.
//   • read-only : show a marker for an item, plus optional faint markers for
//                 the AI location hints.
// Tiles come from OpenStreetMap; no API key required.
// ----------------------------------------------------------------------
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import L from 'leaflet'

// Fix Leaflet's default marker icon paths under a Vite bundle.
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'
L.Marker.prototype.options.icon = L.icon({
  iconUrl, iconRetinaUrl, shadowUrl,
  iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
})

const props = defineProps({
  modelValue: { type: Object, default: null },   // { lat, lng } | null
  editable: { type: Boolean, default: false },
  height: { type: String, default: '320px' },
  hints: { type: Array, default: () => [] }       // [{ location, latitude, longitude, score }]
})
const emit = defineEmits(['update:modelValue'])

// Default view: UTM Skudai campus.
const CAMPUS = [1.5587, 103.6376]

const mapEl = ref(null)
let map = null
let marker = null
let hintLayer = null

function setMarker(lat, lng) {
  if (!map) return
  if (!marker) {
    marker = L.marker([lat, lng], { draggable: props.editable }).addTo(map)
    if (props.editable) {
      marker.on('dragend', () => {
        const p = marker.getLatLng()
        emit('update:modelValue', { lat: +p.lat.toFixed(7), lng: +p.lng.toFixed(7) })
      })
    }
  } else {
    marker.setLatLng([lat, lng])
  }
}

function clearMarker() {
  if (marker) { map.removeLayer(marker); marker = null }
}

function drawHints() {
  if (!map) return
  if (hintLayer) { map.removeLayer(hintLayer); hintLayer = null }
  const valid = (props.hints || []).filter(h => h.latitude != null && h.longitude != null)
  if (!valid.length) return
  hintLayer = L.layerGroup().addTo(map)
  valid.forEach((h, i) => {
    L.circleMarker([h.latitude, h.longitude], {
      radius: 9, color: '#DD8E2E', weight: 2, fillColor: '#DD8E2E',
      fillOpacity: i === 0 ? 0.55 : 0.3
    }).bindTooltip(`${h.location}${h.score != null ? ` · ${h.score}%` : ''}`, { direction: 'top' })
      .addTo(hintLayer)
  })
}

onMounted(() => {
  const start = props.modelValue?.lat != null ? [props.modelValue.lat, props.modelValue.lng] : CAMPUS
  map = L.map(mapEl.value, { scrollWheelZoom: props.editable }).setView(start, 16)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  if (props.modelValue?.lat != null) setMarker(props.modelValue.lat, props.modelValue.lng)
  drawHints()

  if (props.editable) {
    map.on('click', (e) => {
      const lat = +e.latlng.lat.toFixed(7)
      const lng = +e.latlng.lng.toFixed(7)
      setMarker(lat, lng)
      emit('update:modelValue', { lat, lng })
    })
  }

  // Leaflet needs a nudge when it mounts inside a freshly-shown container.
  setTimeout(() => map && map.invalidateSize(), 60)
})

watch(() => props.modelValue, (v) => {
  if (!map) return
  if (v?.lat != null) { setMarker(v.lat, v.lng); map.panTo([v.lat, v.lng]) }
  else clearMarker()
})

watch(() => props.hints, drawHints, { deep: true })

onBeforeUnmount(() => { if (map) { map.remove(); map = null } })
</script>

<template>
  <div ref="mapEl" class="leaflet-host" :style="{ height }"></div>
</template>

<style scoped>
.leaflet-host{ width:100%; border-radius:14px; border:1px solid var(--line);
  box-shadow:var(--shadow-sm); overflow:hidden; z-index:0; }
</style>
