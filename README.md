# FoundIt — Campus Lost & Found Hub

A full-stack single-page web app for SECJ3483 Web Technology.
**Stack:** Vue.js SPA · PHP Slim REST API · MySQL (PDO) · JWT auth.

## 🌐 Live demo
- **App:** https://foundit-app-beta.vercel.app
- **API:** https://foundit261.alwaysdata.net/api
- **User login:** `aisha@example.com` / `password123` (sample users)
- **Admin login:** `admin@example.com` / `password123` (moderation dashboard)

Frontend hosted on **Vercel** · backend + **MySQL** on **AlwaysData**.

## ✨ Features

**Core**
- 🔐 **Accounts & auth** — register / log in, JWT-protected routes, bcrypt-hashed passwords
- 🔑 **Forgot password** — request a reset link by email, then set a new password via a secure, single-use, 30-minute link
- 📋 **Post lost & found items** — title, category, location, date, description
- 🔎 **Browse & filter** — keyword search + filter by type (lost/found), category and status
- 🙋 **Claims workflow** — file a claim with proof; the poster approves/rejects. Approving resolves the item and auto-rejects the other pending claims (transaction-safe)
- 📊 **Dashboard** — manage the items you posted and track the claims you filed

**Bonus**
- 📷 **Photo upload** — attach a photo per item (JPG/PNG/WEBP/GIF, 2 MB; real image-type validation, owner-only)
- 📧 **Email notifications** — poster is emailed when a claim is filed; claimant is emailed when it's approved/rejected (PHPMailer + SMTP — set `MAIL_*` in `.env`; gracefully skipped if unconfigured)
- 🤝 **Smart match suggestions** — opening a *lost* item suggests likely matching *found* items (and vice versa), ranked by category, shared keywords, location and date

**Advanced add-ons**
- 🛡️ **Admin role + moderation dashboard** — a second role beyond normal users. Admins get a `/admin` dashboard with campus-wide stats (totals, **resolved rate**, items by status/category, claim counts) and can moderate **any** item (force status / delete). Enforced server-side with role-based JWT + `AdminMiddleware`, on top of the existing per-owner checks.
- 🗺️ **Campus map location picker** — pin where an item was lost/found on an interactive **Leaflet** + OpenStreetMap map when reporting it; the point (and AI-suggested places) are shown on the item page. Ties the app directly to "campus."
- 🤖 **AI location hints** — a **local probabilistic scorer** ranks the places a user has recently been to and suggests where an item is likely to turn up. It blends four signals: *temporal proximity* to the report date, *visit frequency*, *category/keyword affinity*, and optional *distance clustering* (Haversine) when map points exist. Results are stored on the item as `ai_location_hints`. If `OPENAI_API_KEY` is set it adds a short natural-language summary, with a **graceful offline fallback** when it isn't.

## API endpoints

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/api/register` | – | Create an account |
| POST | `/api/login` | – | Log in, returns a JWT |
| POST | `/api/forgot-password` | – | Email a password-reset link |
| POST | `/api/reset-password` | – | Set a new password using the reset token |
| GET | `/api/items` | – | List items (`?type=`, `?category=`, `?status=`, `?search=`) |
| GET | `/api/items/{id}` | – | One item |
| GET | `/api/items/{id}/matches` | – | Smart match suggestions *(bonus #3)* |
| POST | `/api/items` | JWT | Create an item (accepts `latitude`/`longitude` map point) |
| PUT | `/api/items/{id}` | JWT (owner) | Edit / mark resolved |
| DELETE | `/api/items/{id}` | JWT (owner) | Delete an item |
| POST | `/api/items/{id}/image` | JWT (owner) | Upload / replace the photo *(bonus #1)* |
| POST | `/api/items/{id}/ai-hints` | JWT (owner) | Re-run the AI location scorer *(advanced)* |
| GET | `/api/items/{id}/claims` | JWT (owner) | List claims on my item |
| POST | `/api/items/{id}/claims` | JWT | File a claim *(emails the poster — bonus #2)* |
| PUT | `/api/claims/{id}` | JWT (owner) | Approve / reject *(emails the claimant — bonus #2)* |
| DELETE | `/api/claims/{id}` | JWT (claimant) | Withdraw my claim |
| GET | `/api/me/items` | JWT | Items I posted |
| GET | `/api/me/claims` | JWT | Claims I filed |
| GET | `/api/admin/stats` | JWT (admin) | Moderation stats — totals, resolved rate *(advanced)* |
| GET | `/api/admin/items` | JWT (admin) | List every item (`?type=`, `?status=`, `?search=`) *(advanced)* |
| PUT | `/api/admin/items/{id}` | JWT (admin) | Force an item's status *(advanced)* |
| DELETE | `/api/admin/items/{id}` | JWT (admin) | Remove any item *(advanced)* |
| GET | `/api/admin/users` | JWT (admin) | List accounts *(advanced)* |

## What's in this folder

```
WEB TECH/
├── FoundIt_Project_Proposal.html   # submission-ready proposal (open in browser, Print > Save as PDF)
├── README.md                       # this file
├── PROJECT_TODO.md                 # master build checklist + progress log (start here)
├── foundit-api/                    # PHP Slim backend
│   ├── composer.json
│   ├── .env.example
│   ├── public/index.php            # bootstrap (wired)
│   ├── routes/api.php              # all routes / API contract (wired)
│   ├── src/
│   │   ├── Database.php            # PDO connection (wired)
│   │   ├── Middleware/            # JwtMiddleware + AdminMiddleware (role-based authz)
│   │   ├── Services/             # MailService + AiLocationService (local AI scorer)
│   │   └── Controllers/            # AuthController, ItemController, ClaimController, AdminController
│   └── database/
│       ├── schema.sql             # tables + sample data (incl. admin + map/AI columns)
│       └── migration_features.sql # additive migration for existing databases
└── foundit-app/                    # Vue 3 frontend (Vite)
    ├── package.json
    ├── vite.config.js
    ├── index.html
    ├── .env.example
    └── src/
        ├── main.js · App.vue       # bootstrap + navbar (wired)
        ├── api/http.js             # axios + JWT interceptor (wired)
        ├── store/auth.js           # auth/token/role state (wired)
        ├── router/index.js         # routes + auth/admin guards (wired)
        ├── components/LocationMap.vue  # Leaflet map picker / viewer
        └── views/                  # Home, Login, Register, ItemDetail, PostItem, Dashboard, Admin
```

All views and API endpoints are implemented, tested, and deployed live.

## Setup & run

> **Quick start (Windows):** once the one-time setup below is done, just double-click
> **`RUN-FOUNDIT.bat`** in this folder — it starts MySQL, the backend (`:8081`), and the
> frontend (`:5173`) in their own windows and opens the app. Log in with `aisha@example.com` / `password123`.

### 1. Database
```bash
mysql -u root -p < foundit-api/database/schema.sql
```
The sample users are seeded with a real bcrypt hash, so you can log in straight away with the
password **`password123`** (e.g. `aisha@example.com`, `ben@example.com`, `citra@example.com`,
or the admin account `admin@example.com`).

> **Already have a `foundit` database?** Don't drop it — run the additive migration instead,
> which adds the `role`, `latitude`, `longitude` and `ai_location_hints` columns and promotes
> the admin account (safe to re-run):
> ```bash
> mysql -u root -p foundit < foundit-api/database/migration_features.sql
> ```

### 2. Backend
```bash
cd foundit-api
composer install
copy .env.example .env       # (Windows)  — then edit DB creds + JWT_SECRET
                             # (optional) set OPENAI_API_KEY to enrich AI hints
php -S localhost:8081 -t public
```
Test: open http://localhost:8081/api/items — should return JSON.

### 3. Frontend
```bash
cd foundit-app
npm install
copy .env.example .env       # VITE_API_BASE = http://localhost:8081/api
npm run dev
```
Open http://localhost:5173.

> If you see a CORS error, make sure `CORS_ORIGIN` in the backend `.env` exactly matches the frontend URL.

## Who builds what

| Member | Files | Builds |
|---|---|---|
| **M1** Backend Lead | `AuthController.php` | register + login + password hashing + JWT issuing |
| **M2** Backend | `ItemController.php`, `ClaimController.php` | all SQL queries, validation, status codes |
| **M3** Frontend Lead | `Register.vue` (+ owns router/store/App) | auth UI, route guards, token handling |
| **M4** Frontend | `Home.vue`, `ItemDetail.vue`, `PostItem.vue`, `Dashboard.vue` | feature screens, forms, async calls |

## Build order
1. Import the database, fix password hashes.
2. **M1:** finish login → `POST /api/login` returns a token.
3. **M2:** `GET /api/items` returns the seeded items.
4. **M3:** log in from the UI → navbar shows protected links. **← key milestone.**
5. **M2 + M4:** full CRUD + claims workflow.
6. Deploy: backend + MySQL to AlwaysData, frontend to Vercel.

## Demo testing checklist
- [ ] Register → log in → token stored
- [ ] Forgot password → reset link emailed → set a new password → log in with it
- [ ] Open `/post` while logged out → redirected to `/login`
- [ ] Create / edit / delete an item
- [ ] File a claim; owner approves/rejects
- [ ] `POST /api/items` with no token → **401**
- [ ] Edit someone else's item → **403**
- [ ] Invalid form → **422** with messages
- [ ] Upload a photo to an item → shows on the card and detail page *(bonus #1)*
- [ ] File a claim → poster receives an email; approve/reject → claimant receives one *(bonus #2, needs SMTP in `.env`)*
- [ ] Open a lost item → "Possible matches" lists likely found items *(bonus #3)*
- [ ] Report an item → drop a pin on the campus map → it shows on the item page *(advanced: map picker)*
- [ ] Open an item → "AI location hints" lists ranked places with reasons; owner can refresh *(advanced: AI scorer)*
- [ ] Log in as `admin@example.com` → "Admin" link appears → dashboard shows stats and can delete any item *(advanced: admin role)*
- [ ] Log in as a normal user → opening `/admin` redirects home; `GET /api/admin/stats` → **403** *(role-based authorization)*
- [ ] Works at the live public URL
