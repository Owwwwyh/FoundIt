# FoundIt — Master Build Plan & Progress Log

**Repo:** https://github.com/Owwwwyh/FoundIt

## Team
- **OW YEE HAO** (A23CS0261) — Backend (`foundit-api`)
- **CHONG LUN QUAN** (A23CS0067) — Frontend (`foundit-app`)
- **Shared:** database, deployment, README, proposal, slides, testing.

> The brief expects 4 members per group. If you have two more teammates, add their names here and we'll split the workload further (the proposal still has [Member 3] / [Member 4] slots). If it's just the two of you, the split above (one backend, one frontend) is the practical plan.

---

## How to build this using Claude (step by step)

1. **Push to GitHub first** (see commands at the bottom). Commit after every working piece so contributions are visible — the rubric checks this.
2. **Get it running locally** — follow `README.md` (import database, `composer install`, `npm install`, start both servers).
3. **Pick the next unchecked `[ ]` task** below, in order.
4. **Ask Claude to do it.** Example prompts:
   - *"Claude, implement register + login in `foundit-api/src/Controllers/AuthController.php`."*
   - *"Claude, write the items CRUD queries in `ItemController.php`."*
   - *"Claude, build `Register.vue` like `Login.vue`."*
   - *"Claude, build the report form in `PostItem.vue`."*
5. **Claude will:** write the code into the file in `E:\WEB TECH`, tick the box here, and add a dated line to the **Progress Log** at the bottom (this is the "remark what was done" record you asked for).
6. **You test it** (use the Demo Testing Checklist in `README.md`), then **commit & push**.
7. Repeat. Work in order — each step builds on the last.

**Legend:** `[x]` done · `[ ]` to do · `[~]` in progress

---

## Master checklist (whole system)

### Phase 0 — Setup
- [x] Project idea, schema, API design, proposal *(done with Claude)*
- [x] Skeleton repo wired (backend + frontend) in `E:\WEB TECH`
- [x] Proposal updated with GitHub link + member names
- [x] Create GitHub repo & push the folder — *both*
- [x] Install tools: PHP 8.1+, Composer, MySQL, Node 18+ — *both*
- [x] Import `database/schema.sql` and replace placeholder password hashes — *OW*
- [x] `composer install` (backend) and `npm install` (frontend) — *respective*
- [x] Confirm both servers run locally — *both*

### Phase 1 — Backend `foundit-api` — *OW YEE HAO*
- [x] `AuthController::register` — validate, check duplicate email (409), hash password, insert (201/422)
- [x] `AuthController::login` — verify password, issue JWT (200/401)
- [x] `ItemController::index` — list items + filters (`type`, `category`, `search`)
- [x] `ItemController::show` — one item, 404 if missing
- [x] `ItemController::store` — create item, validate (201/422)
- [x] `ItemController::update` — edit/mark resolved, ownership check (200/403/404)
- [x] `ItemController::destroy` — delete, ownership check (204/403)
- [x] `ClaimController::index` — list claims on my item (owner-only)
- [x] `ClaimController::store` — file a claim (201/422)
- [x] `ClaimController::update` — approve/reject (owner-only, 200/403)
- [x] `ClaimController::destroy` — withdraw my claim (204/403)
- [x] Test every endpoint with Postman or curl

### Phase 2 — Frontend `foundit-app` — *CHONG LUN QUAN*
- [x] `Register.vue` — form + validation, call `POST /api/register`
- [x] `Home.vue` — polish into item cards, wire the filters
- [x] `ItemDetail.vue` — fetch item, claim form, owner approve/reject panel
- [x] `PostItem.vue` — report form + client-side validation
- [x] `Dashboard.vue` — "My Items" + "My Claims" tabs
- [x] Verify route guard + token attach work end-to-end (test in browser)
- [x] Basic responsive styling across pages

### Phase 3 — Integration & deployment — *both*
- [x] Connect frontend to backend (fix CORS, set env URLs)
- [x] Deploy backend + MySQL to ~~Railway~~ **AlwaysData** *(Railway dropped its free tier; AlwaysData is free + real MySQL + HTTPS + SSH)*
- [x] Deploy frontend to Vercel
- [x] Seed the live database
- [x] Point `CORS_ORIGIN` and `VITE_API_BASE` to the live URLs

**🌐 Live URLs:** frontend → https://foundit-app-beta.vercel.app · backend → https://foundit261.alwaysdata.net/api

### Bonus Features — *OW YEE HAO*
- [x] **#1 Photo upload** — upload an image per item (JPG/PNG/WEBP/GIF, 2 MB, owner-only)
- [x] **#2 Email notifications** — email the poster when a claim is filed; email the claimant when approved/rejected
- [x] **#3 Smart match suggestions** — when a "lost" item is posted, suggest matching "found" items
- [ ] **#4 Admin role + moderation dashboard** — second role with role-based auth + stats
- [ ] **#5 Campus map location picker** — Leaflet.js interactive map to pick lost/found location

### Phase 4 — Finish — *both*
- [ ] Run the full Demo Testing Checklist (`README.md`)
- [ ] Export proposal to PDF for submission
- [ ] Finalize README (live URL, setup notes)
- [ ] Build demo presentation slides
- [ ] Rehearse demo + prepare for lecturer Q&A
- [ ] Complete peer-evaluation form

---

## Push to GitHub (one-time)
```bash
cd "E:\WEB TECH"
git init
git add .
git commit -m "Initial scaffold: FoundIt backend + frontend + proposal"
git branch -M main
git remote add origin https://github.com/Owwwwyh/FoundIt.git
git push -u origin main
```
After that, each member commits their own work so contributions show up per person.

---

## Progress log
*(Newest at the bottom. Claude adds a line here each time it completes something.)*

- **2026-06-12 — Claude:** Scaffolded full project into `E:\WEB TECH` — Slim backend (auth/JWT middleware/PDO wired, items & claims controllers stubbed), Vue frontend (router + guard, axios+JWT interceptor, auth store, all views), `database/schema.sql` with sample data, README, and the proposal.
- **2026-06-12 — Claude:** Added GitHub link and member names (OW YEE HAO, CHONG LUN QUAN) to the proposal; created this build plan + progress log.
- **2026-06-12 — Claude:** Implemented `AuthController` (OW) — `register` (validation, duplicate-email 409, bcrypt hashing, 201/422) and `login` (password_verify, JWT issuing, 401). Backend auth is now functional.
- **2026-06-12 — Claude:** Implemented full `ItemController` (OW) — filtered `index` (type/category/status/search), `show` (404), `store` (201/422), owner-guarded `update` (200/403/404) and `destroy` (204/403). Items CRUD complete with prepared statements + validation.
- **2026-06-12 — Claude:** Implemented full `ClaimController` (OW) — owner-only `index`, `store` (with guards: no self-claim, no duplicate pending, proof ≥10 chars), transaction-safe `update` (approve resolves item + auto-rejects siblings), claimant `destroy`. **Backend is now feature-complete** (auth + items + claims).
- **2026-06-12 — Claude:** Built the full Vue frontend (CHONG) — Register, Home (cards + filters), ItemDetail (claim form + owner approve/reject), PostItem (report form), Dashboard (My Items / My Claims). Updated auth store to persist the user; added shared styling in App.vue. Added backend `GET /api/me/items` and `GET /api/me/claims` to power the dashboard. **App is now end-to-end** — pending local browser testing.
- **2026-06-12 — Claude:** **API tested end-to-end** (OW) — ran a 22-case curl suite against http://localhost:8081/api covering every status code in the proposal: register 201/409/422, login 200/401, items list+filter 200, show 200/404, create 201 + no-token **401** + invalid **422**, edit-other's-item **403** + own-item 200, claim 201 + self-claim 422 + short-message 422 + duplicate 409, owner-only claim review **403**/200, delete-other's **403** + own 204. All pass. Verified the transaction-safe approve (approving a claim flips the item to `resolved` and auto-rejects sibling claims). Reset the database to pristine sample data afterwards.
- **2026-06-12 — Claude:** Got the app **running locally** (both). Installed Composer (as `foundit-api/composer.phar`); ran `composer install` (backend) and `npm install` (frontend). Found a running MariaDB 10.4 (XAMPP) on 127.0.0.1:3306 with root/no-password — imported `database/schema.sql` (3 users, 4 items, 2 claims) and replaced the placeholder password hashes with a real bcrypt hash (all sample users now log in with **password123**). Created both `.env` files (generated a JWT secret). **Note:** port 8080 was already taken by another local service, so the backend now runs on **:8081** (frontend `.env` updated to match). Verified: `GET /api/items` returns DB JSON (200), login returns a JWT (200) and a bad password returns 401. Backend → http://localhost:8081/api, frontend → http://localhost:5173.
- **2026-06-12 — Claude:** **Redesigned the whole frontend UI** (CHONG) from the raw default styling into a polished, cohesive "lost-property office" theme — warm cream canvas, deep-teal + marigold palette, an editorial serif (Fraunces) paired with a clean grotesque (Hanken Grotesk), a full design-system in `App.vue` (buttons, badges, status pills, inputs, cards, tabs), a sticky translucent navbar with brand mark, ambient background + grain texture, and per-page polish: Home hero + segmented Lost/Found filter + loading skeletons + empty states; two-column Item Detail with claim/owner panels; chip-style report form; centered auth cards (with a demo-login hint); card-based Dashboard. **No app logic changed** — only templates + styles. Verified in-browser via the preview tool (Home, Login render correctly, live data loads, no horizontal overflow). Added one-click launcher scripts (`RUN-FOUNDIT.bat`, `START-MYSQL.bat`, `start-backend.bat`, `start-frontend.bat`) so the servers run persistently for the demo.
- **2026-06-12 — Claude:** **Code review + pushed to GitHub** (OW). Reviewed the whole codebase — backend is solid (input validation, bcrypt, JWT 401, ownership 403, prepared statements, transaction-safe approve; no functional bugs found). Fixes applied: added the missing `foundit-app/.gitignore` + a root `.gitignore` (so `node_modules`, `vendor`, `.env`, `composer.phar`, `.claude/` are never committed); baked a real bcrypt hash into `schema.sql` so sample users log in with `password123` straight after import (removed the fragile manual hash step); made API error detail output env-driven via `APP_DEBUG` (off by default — no stack-trace leaks in production); fixed the `VITE_API_BASE` fallback and the README/`.env.example` port references to `:8081`. Initialized git and pushed **3 logical commits** (docs → backend → frontend, all authored by OW) to https://github.com/Owwwwyh/FoundIt — 38 source files, no dependencies or secrets.
- **2026-06-12 — Claude:** **Deployed the whole app live** (Phase 3, both). Backend + MySQL on **AlwaysData** (free, real MySQL, HTTPS, SSH — chosen because Railway dropped its free tier): created the `foundit261_foundit` database, seeded it via external MySQL, uploaded the Slim app over SSH (code kept above the web root for security, `public/` as docroot), and added a production `.htaccess`. Hit + fixed a real bug: the DB password contains `#`, which `.env` treats as a comment — fixed by quoting it. Frontend on **Vercel** (`vite build`, `VITE_API_BASE` baked to the live API). Set `CORS_ORIGIN` to the Vercel origin and verified the cross-origin header. **Live:** frontend https://foundit-app-beta.vercel.app, backend https://foundit261.alwaysdata.net/api — items, login (JWT), and 401 all verified live over HTTPS.
- **2026-06-12 — Claude:** **Bonus Feature #1 — Photo upload per item.** Backend: `POST /api/items/{id}/image` (JWT, owner-only) — validates the *real* image type (JPG/PNG/WEBP/GIF via `getimagesizefromstring`, not just the extension), 2 MB limit (+ defense-in-depth byte recheck after a self-review), random safe filename under public `/uploads`, replaces the old photo, and deletes the file when the item is deleted; full exception handling (401/403/404/422/500). Added `items.image_path`. Frontend: file picker + live preview + client-side validation in PostItem; photos shown on Home cards, Item Detail (hero), and Dashboard thumbnails (`imageUrl()` helper). Tested locally (200/422/403/401, served as image/png, browser display confirmed) and **verified live** end-to-end (migrated live DB, re-uploaded backend over SSH, redeployed Vercel).
- **2026-06-12 — Claude:** **Bonus Feature #2 — Email notifications.** Backend-only feature using PHPMailer + Gmail SMTP. Created `MailService.php` (`sendClaimFiled` emails the item poster when a new claim is filed; `sendClaimReviewed` emails the claimant when approved/rejected, including auto-rejected claimants). Hooked into `ClaimController` — emails are fire-and-forget (errors logged, never crash the API response). Graceful skip when SMTP is not configured. `.env.example` updated with SMTP placeholders. Tested locally (claim filed → 201 with ~5s SMTP delay, approve → 200 with email sent, no errors). Committed & pushed to GitHub. Deployed to AlwaysData (uploaded files + `composer install` for PHPMailer + SMTP env vars appended to remote `.env`). **Verified live** — filed a claim and approved it on the production API; both endpoints returned correct status codes with email sends completing without error.
- **2026-06-12 — Claude:** **Rechecked Features #1 & #2 on the live site** (OW). Ran an automated 8-point end-to-end test against the production API using a single throwaway item (created → photo uploaded → claimed → approved → deleted — fully reversible, sample data untouched). **All 8 passed:** login/JWT; create item (201); photo upload (200 + `image_path` set); photo actually served as `image/png` over HTTPS; non-image file rejected (422); claim filed (201) and approved (200) with the email hooks firing **without crashing the API**; item auto-resolved on approval; throwaway item cleaned up (GET → 404). **No bugs found — no fixes needed.** Caveat: real-inbox email *delivery* was not re-confirmed because the sample users use `@example.com` placeholder addresses — the integration is correct and non-crashing, but to *show* an email arriving (e.g. on demo day) use an account with a real email. Next: Feature #3 (smart match suggestions).
- **2026-06-12 — Claude:** **Bonus Feature #3 — Smart match suggestions.** When you open a *lost* item, the app now suggests *open* *found* items that look like the same thing (and vice versa). **Backend:** new public `GET /api/items/{id}/matches` (no DB migration — pure read) that pulls the opposite-type open items and ranks them by a simple relevance score: same category (+3), each shared title/description keyword (+2, stop-words & short tokens filtered), overlapping location (+1), reported within a week (+1); only suggestions scoring ≥ 2 are returned (so a coincidental same-week date alone isn't a "match"), top 5, each with human-readable "why matched" reasons. **Frontend:** a styled "Possible matches" card grid on `ItemDetail.vue` (thumbnail/placeholder, type badge, category·location, reason pills) that links straight to each candidate. **Critique fix:** added a `watch(route.params.id)` re-fetch — match cards are the first in-app links from one item page to another, and Vue reuses the component, so `onMounted` alone would not have refreshed the page. **Verified locally** end-to-end: scoring correct in both directions, weak date-only noise filtered, missing item → 404, the UI renders with no console errors, and clicking a match card now correctly loads the new item.
- **2026-06-12 — Claude:** **Full-system recheck + GitHub update** (OW). Did a whole-codebase review (auth, items, claims, photo, email, matches, middleware, frontend views/router/store) — no bugs found. Ran a **46-point automated API suite** against the local backend covering every endpoint + status code (auth 201/409/422/200/401; items read + all filters + 404; items write 401/422/201/403/200/422/404; photo 200/served-as-image/422/403/401; matches 200/opposite-type/404; claims 401/422×2/201/409/404/200/403×2/422/200/resolved/204) — **46/46 passed**, all test data cleaned up so the DB ended pristine. Frontend smoke test in the browser: home renders, **login works end-to-end** (form → API → JWT stored → navbar updates → redirect), dashboard loads, Feature #3 matches render and match-card navigation works — **0 console errors**. Refreshed the **README** (added a Features section with the 3 bonus features, an API-endpoints table, fixed a stale "Railway" reference → AlwaysData, updated the demo checklist). Committed Feature #3 + docs and pushed to GitHub.
- **2026-06-12 — Claude:** **Deployed Feature #3 live** (OW). Uploaded the 2 changed backend files (`ItemController.php`, `routes/api.php`) to AlwaysData over SSH (remote backup taken first; `php -l` clean on remote PHP 8.4; no DB migration, no new dependencies) and redeployed the frontend to Vercel (`--prod`, `VITE_API_BASE` → live API), re-aliased to https://foundit-app-beta.vercel.app. **Verified live:** `GET /api/items/{id}/matches` returns ranked matches (a throwaway lost calculator matched the seeded found Casio at score 10, then deleted); the deployed JS bundle contains the "Possible matches" UI and the `/matches` call. Live DB left at the original seed (items 1–4). **All 3 bonus features (#1 photo, #2 email, #3 smart match) are now live.**
