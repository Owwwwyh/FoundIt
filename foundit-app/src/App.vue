<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from './store/auth'
import { useRouter, useRoute } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const menuOpen = ref(false)
const initial = computed(() => (auth.user?.name || '?').trim().charAt(0).toUpperCase())
function logout() { menuOpen.value = false; auth.logout(); router.push('/') }
// Close the mobile menu whenever the route changes
watch(() => route.fullPath, () => { menuOpen.value = false })
</script>

<template>
  <!-- Ambient background -->
  <div class="app-bg" aria-hidden="true">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="grain"></div>
  </div>

  <header class="nav">
    <div class="nav-inner">
      <router-link to="/" class="brand" @click="menuOpen = false">
        <span class="brand-mark">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 21s7-5.686 7-11a7 7 0 1 0-14 0c0 5.314 7 11 7 11Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            <circle cx="12" cy="10" r="2.6" fill="currentColor"/>
          </svg>
        </span>
        <span class="brand-text">Found<span class="brand-accent">It</span></span>
      </router-link>

      <button class="nav-toggle" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen ? 'true' : 'false'" aria-label="Toggle navigation menu">
        <svg v-if="!menuOpen" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        <svg v-else viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>

      <nav class="nav-links" :class="{ open: menuOpen }">
        <router-link to="/" @click="menuOpen = false">Browse</router-link>
        <router-link v-if="auth.isLoggedIn" to="/post" @click="menuOpen = false">Report Item</router-link>
        <router-link v-if="auth.isLoggedIn" to="/dashboard" @click="menuOpen = false">Dashboard</router-link>
        <router-link v-if="auth.isAdmin" to="/admin" class="nav-admin" @click="menuOpen = false">Admin</router-link>
        <router-link v-if="!auth.isLoggedIn" to="/login" class="nav-cta" @click="menuOpen = false">Log in</router-link>
        <span v-else class="navuser">
          <span class="who"><span class="avatar">{{ initial }}</span><span class="who-name">{{ auth.user?.name }}</span></span>
          <a href="#" class="logout" @click.prevent="logout">Logout</a>
        </span>
      </nav>
    </div>
  </header>

  <main class="container">
    <router-view />
  </main>

  <footer class="site-foot">
    <span>FoundIt — Campus Lost &amp; Found</span>
    <span class="dot">·</span>
    <span>SECJ3483 Web Technology</span>
  </footer>
</template>

<style>
/* ============================================================
   FoundIt — design system  (warm "lost-property office" theme)
   Display: Fraunces · Body: Hanken Grotesk
   ============================================================ */
:root{
  --paper:#FAF6EE; --paper-2:#F1E8D8; --card:#FFFCF6; --sunk:#F4ECDD;
  --ink:#241F1A; --ink-2:#6E6456; --line:#E8DDC9; --line-2:#D9CCB3;
  --brand:#1C6B5E; --brand-700:#14534A; --brand-100:#DCEFE3;
  --accent:#DD8E2E; --accent-100:#F7E7CD;
  --lost:#C0503F; --lost-bg:#F7E2DA;
  --found:#2E8B6B; --found-bg:#DCEFE3;
  --danger:#BF4636;
  --r:16px; --r-sm:11px;
  --shadow:0 1px 2px rgba(50,38,18,.05), 0 16px 34px -16px rgba(50,38,18,.24);
  --shadow-sm:0 1px 2px rgba(50,38,18,.05), 0 6px 16px -10px rgba(50,38,18,.18);
  --font-display:"Fraunces", Georgia, "Times New Roman", serif;
  --font-body:"Hanken Grotesk", "Segoe UI", system-ui, -apple-system, sans-serif;
}

*{ box-sizing:border-box; }
html{ -webkit-text-size-adjust:100%; scroll-behavior:smooth; }
body{
  margin:0; font-family:var(--font-body); color:var(--ink); background:var(--paper);
  font-size:16px; line-height:1.6; letter-spacing:.1px;
  -webkit-font-smoothing:antialiased; text-rendering:optimizeLegibility;
}
#app{ min-height:100vh; display:flex; flex-direction:column; }
a{ color:var(--brand); text-decoration:none; transition:color .15s ease; }
a:hover{ color:var(--brand-700); }
h1,h2,h3{ font-family:var(--font-display); color:var(--ink); font-weight:600; line-height:1.12; letter-spacing:-.4px; font-optical-sizing:auto; }
h1{ font-size:clamp(1.95rem, 1.2rem + 2.6vw, 2.7rem); margin:0 0 .35em; }
h2{ font-size:1.55rem; margin:0 0 .4em; }
h3{ font-size:1.16rem; margin:.2em 0; }
p{ margin:.55em 0; }
::selection{ background:var(--accent-100); color:var(--ink); }
:focus-visible{ outline:2px solid var(--accent); outline-offset:2px; border-radius:4px; }

/* ---------- ambient background ---------- */
.app-bg{ position:fixed; inset:0; z-index:-1; overflow:hidden; pointer-events:none;
  background:
    radial-gradient(1100px 620px at 108% -8%, #F0E8D8 0%, transparent 58%),
    radial-gradient(900px 520px at -12% 104%, #EEEDE0 0%, transparent 55%); }
.blob{ position:absolute; border-radius:50%; filter:blur(80px); }
.blob-1{ width:440px; height:440px; background:var(--brand); top:-150px; right:-90px; opacity:.13; }
.blob-2{ width:400px; height:400px; background:var(--accent); bottom:-170px; left:-110px; opacity:.12; }
.grain{ position:absolute; inset:0; opacity:.04; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E"); }

/* ---------- navbar ---------- */
.nav{ position:sticky; top:0; z-index:50; background:rgba(250,246,238,.8);
  backdrop-filter:blur(14px) saturate(1.25); -webkit-backdrop-filter:blur(14px) saturate(1.25);
  border-bottom:1px solid var(--line); }
.nav-inner{ width:100%; max-width:1080px; margin:0 auto; padding:13px 22px;
  display:flex; align-items:center; justify-content:space-between; gap:16px; }
.brand{ display:inline-flex; align-items:center; gap:11px; font-family:var(--font-display);
  font-weight:600; font-size:1.42rem; color:var(--ink); letter-spacing:-.5px; }
.brand:hover{ color:var(--ink); }
.brand-mark{ display:grid; place-items:center; width:36px; height:36px; border-radius:11px;
  background:var(--brand); color:#fff; box-shadow:var(--shadow-sm); }
.brand-mark svg{ width:19px; height:19px; }
.brand-accent{ color:var(--accent); }
.nav-links{ display:flex; align-items:center; gap:4px; }
.nav-links a{ color:var(--ink-2); font-weight:600; font-size:.94rem; padding:8px 13px; border-radius:10px; }
.nav-links a:hover{ color:var(--ink); background:var(--paper-2); }
.nav-links a.router-link-exact-active{ color:var(--brand); }
.nav-cta{ background:var(--ink); color:#fff !important; }
.nav-cta:hover{ background:var(--brand) !important; color:#fff !important; }
.nav-admin{ color:var(--accent) !important; }
.nav-admin:hover{ color:#A8631A !important; background:var(--accent-100); }
.nav-admin.router-link-exact-active{ color:#A8631A !important; }
.navuser{ display:inline-flex; align-items:center; gap:6px; margin-left:8px;
  padding-left:12px; border-left:1px solid var(--line); }
.who{ display:inline-flex; align-items:center; gap:8px; font-weight:600; color:var(--ink); font-size:.92rem; }
.avatar{ width:30px; height:30px; border-radius:50%; background:var(--accent-100); color:#A8631A;
  display:grid; place-items:center; font-weight:800; font-size:.85rem; }
.logout{ color:var(--ink-2); font-weight:600; font-size:.9rem; padding:7px 11px; border-radius:9px; }
.logout:hover{ color:var(--danger); background:var(--lost-bg); }
.nav-toggle{ display:none; align-items:center; justify-content:center; width:42px; height:42px;
  border:1px solid var(--line-2); border-radius:11px; background:var(--card); color:var(--ink); cursor:pointer; }
.nav-toggle:hover{ background:var(--paper-2); }

/* ---------- layout ---------- */
.container{ width:100%; max-width:1080px; margin:0 auto; padding:40px 22px 64px; flex:1 0 auto; }
.site-foot{ flex-shrink:0; border-top:1px solid var(--line); color:var(--ink-2); font-size:.85rem;
  text-align:center; padding:22px; display:flex; gap:9px; justify-content:center; align-items:center;
  background:rgba(255,253,247,.55); }
.site-foot .dot{ opacity:.45; }
.page-head{ display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:6px; }

/* ---------- helpers ---------- */
.muted{ color:var(--ink-2); }
.err{ color:var(--danger); font-size:.88rem; margin:6px 0; }
.ok{ color:#1d6b4e; font-size:.92rem; margin:10px 0; background:var(--found-bg);
  border:1px solid #BFE3CF; padding:11px 14px; border-radius:11px; }

/* ---------- buttons ---------- */
.btn{ display:inline-flex; align-items:center; justify-content:center; gap:7px;
  font-family:var(--font-body); font-weight:600; font-size:.92rem; line-height:1;
  padding:11px 17px; border-radius:11px; border:1px solid var(--line-2); background:var(--card);
  color:var(--ink); cursor:pointer; transition:transform .12s ease, background .15s, box-shadow .15s, border-color .15s; }
.btn:hover{ background:var(--paper-2); transform:translateY(-1px); }
.btn:active{ transform:translateY(0); }
.btn-primary{ background:var(--brand); border-color:var(--brand); color:#fff; box-shadow:var(--shadow-sm); }
.btn-primary:hover{ background:var(--brand-700); border-color:var(--brand-700); color:#fff; }
.btn-danger{ background:transparent; border-color:var(--lost); color:var(--lost); }
.btn-danger:hover{ background:var(--lost-bg); color:var(--lost); }
.btn-ghost{ background:transparent; border-color:transparent; color:var(--ink-2); }
.btn-ghost:hover{ background:var(--paper-2); color:var(--ink); }
.btn-sm{ padding:8px 13px; font-size:.83rem; border-radius:9px; }
.btn-block{ width:100%; }
.btn:disabled{ opacity:.55; cursor:default; transform:none; box-shadow:none; }

/* ---------- form controls ---------- */
input, select, textarea{ font-family:var(--font-body); color:var(--ink); }
input::placeholder, textarea::placeholder{ color:#A99E8C; }
input:focus, select:focus, textarea:focus{ outline:none; border-color:var(--brand);
  box-shadow:0 0 0 3px rgba(28,107,94,.16); }
.form{ max-width:480px; }
.field{ margin-bottom:16px; display:flex; flex-direction:column; }
.field > label{ font-weight:600; font-size:.86rem; margin-bottom:7px; color:var(--ink); }
.field input, .field select, .field textarea{ padding:12px 14px; border:1px solid var(--line-2);
  border-radius:11px; font-size:.96rem; background:var(--card); transition:border-color .15s, box-shadow .15s; }
.field textarea{ resize:vertical; min-height:92px; line-height:1.55; }
.radios{ display:flex; gap:10px; }
.radios > label{ font-weight:500; }

/* ---------- filter bar ---------- */
.filters{ display:flex; flex-wrap:wrap; gap:10px; align-items:center; background:var(--card);
  border:1px solid var(--line); border-radius:var(--r); padding:12px; box-shadow:var(--shadow-sm); margin:20px 0 24px; }
.filters .search{ flex:1 1 220px; min-width:200px; position:relative; display:flex; align-items:center; }
.filters .search svg{ position:absolute; left:13px; width:17px; height:17px; color:var(--ink-2); pointer-events:none; }
.filters input, .filters select{ font-size:.92rem; padding:11px 13px; border:1px solid var(--line-2);
  border-radius:10px; background:var(--paper); }
.filters .search input{ width:100%; padding-left:39px; }
.filters select{ cursor:pointer; }

/* ---------- item cards ---------- */
.grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:18px; }
.card{ display:flex; flex-direction:column; background:var(--card); border:1px solid var(--line);
  border-radius:var(--r); padding:18px; color:var(--ink); box-shadow:var(--shadow-sm);
  transition:transform .16s ease, box-shadow .16s ease, border-color .16s; position:relative; overflow:hidden; }
.card::before{ content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--brand);
  opacity:0; transition:opacity .16s ease; }
.card:hover{ transform:translateY(-3px); box-shadow:var(--shadow); border-color:var(--line-2); }
.card:hover::before{ opacity:1; }
.card-top{ display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:12px; }
.card h3{ margin:0 0 6px; font-size:1.18rem; }
.card .meta{ color:var(--ink-2); font-size:.9rem; margin:0; }
.card .date{ color:var(--ink-2); font-size:.78rem; margin-top:auto; padding-top:14px;
  display:flex; align-items:center; justify-content:space-between; }
.card .go{ color:var(--brand); font-weight:700; }

/* ---------- badges & status ---------- */
.badge{ display:inline-flex; align-items:center; gap:6px; font-size:.68rem; font-weight:700;
  text-transform:uppercase; letter-spacing:.7px; padding:4px 11px; border-radius:999px; white-space:nowrap; }
.badge::before{ content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
.badge.lost{ background:var(--lost-bg); color:var(--lost); }
.badge.found{ background:var(--found-bg); color:var(--found); }
.status{ font-size:.74rem; font-weight:600; color:var(--ink-2); text-transform:capitalize;
  display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
.status::before{ content:""; width:7px; height:7px; border-radius:50%; background:currentColor; opacity:.55; }
.status.open::before{ background:var(--brand); opacity:1; }
.status.claimed::before{ background:var(--accent); opacity:1; }
.status.resolved::before{ background:var(--found); opacity:1; }
.status.pending::before{ background:var(--accent); opacity:1; }
.status.approved::before{ background:var(--found); opacity:1; }
.status.rejected::before{ background:var(--lost); opacity:1; }

/* ---------- panel / rows / claims / tabs ---------- */
.panel{ background:var(--card); border:1px solid var(--line); border-radius:var(--r);
  padding:22px; box-shadow:var(--shadow-sm); }
.panel h3{ margin-top:0; }
.panel textarea{ width:100%; padding:12px 14px; border:1px solid var(--line-2); border-radius:11px;
  background:var(--paper); font-size:.95rem; resize:vertical; min-height:84px; }
.row{ display:flex; justify-content:space-between; align-items:center; gap:14px; background:var(--card);
  border:1px solid var(--line); border-radius:var(--r); padding:16px 18px; margin-bottom:12px;
  box-shadow:var(--shadow-sm); transition:box-shadow .15s, transform .15s; }
.row:hover{ box-shadow:var(--shadow); transform:translateY(-1px); }
.claim{ border-top:1px solid var(--line); padding:16px 0; }
.claim:last-child{ padding-bottom:0; }
.tabs{ display:inline-flex; gap:4px; background:var(--paper-2); padding:5px; border-radius:13px;
  margin:16px 0 24px; border:1px solid var(--line); }
.tab{ border:none; background:transparent; padding:9px 19px; border-radius:9px; cursor:pointer;
  font-family:var(--font-body); font-weight:600; font-size:.92rem; color:var(--ink-2); transition:all .15s; }
.tab:hover{ color:var(--ink); }
.tab.active{ background:var(--card); color:var(--brand); box-shadow:var(--shadow-sm); }

/* ---------- motion ---------- */
@keyframes rise{ from{ opacity:0; transform:translateY(12px); } to{ opacity:1; transform:none; } }
.fade-enter-active{ transition:opacity .26s ease, transform .26s ease; }
.fade-leave-active{ transition:opacity .14s ease; }
.fade-enter-from{ opacity:0; transform:translateY(8px); }
.fade-leave-to{ opacity:0; }
.grid .card{ animation:rise .42s ease both; }
.grid .card:nth-child(2){ animation-delay:.04s; }
.grid .card:nth-child(3){ animation-delay:.08s; }
.grid .card:nth-child(4){ animation-delay:.12s; }
.grid .card:nth-child(5){ animation-delay:.16s; }
.grid .card:nth-child(6){ animation-delay:.2s; }
.grid .card:nth-child(7){ animation-delay:.24s; }
.grid .card:nth-child(8){ animation-delay:.28s; }
@media (prefers-reduced-motion: reduce){ *{ animation:none !important; transition:none !important; } }

/* ---------- responsive ---------- */
@media (max-width:640px){
  .nav-inner{ padding:11px 16px; }
  .brand{ font-size:1.28rem; }
  .nav-toggle{ display:inline-flex; }
  /* nav links collapse into a dropdown panel under the bar */
  .nav-links{ position:absolute; top:100%; left:0; right:0; flex-direction:column; align-items:stretch;
    gap:3px; background:var(--card); border-bottom:1px solid var(--line); box-shadow:var(--shadow);
    padding:10px 16px 16px; display:none; }
  .nav-links.open{ display:flex; }
  .nav-links a{ padding:12px 13px; font-size:1rem; border-radius:10px; }
  .nav-cta{ text-align:center; margin-top:2px; }
  .who-name{ display:inline; }
  .navuser{ margin-left:0; padding:10px 4px 2px; border-left:none; border-top:1px solid var(--line);
    margin-top:6px; justify-content:space-between; width:100%; }
  .container{ padding:26px 16px 48px; }
}
</style>
