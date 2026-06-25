# FoundIt — Campus Lost & Found Hub

> A full-stack single-page web app that helps students report, find, and reclaim
> lost belongings around campus.

[![Docker stack](https://github.com/Owwwwyh/FoundIt/actions/workflows/docker.yml/badge.svg)](https://github.com/Owwwwyh/FoundIt/actions/workflows/docker.yml)
![Vue 3](https://img.shields.io/badge/frontend-Vue%203%20%2B%20Vite-42b883)
![PHP Slim](https://img.shields.io/badge/backend-PHP%20Slim%204-777bb4)
![MySQL](https://img.shields.io/badge/database-MySQL%20%2F%20MariaDB-00758f)
![JWT](https://img.shields.io/badge/auth-JWT-black)

Built for **SECJ3483 Web Technology**.

---

## 🌐 Live demo

| | |
|---|---|
| **App** | https://foundit-app-beta.vercel.app |
| **API** | https://foundit261.alwaysdata.net/api |
| **User login** | `aisha@example.com` / `password123` |
| **Admin login** | `admin@example.com` / `password123` |

Frontend on **Vercel**, backend + **MySQL** on **AlwaysData**, all over HTTPS.

---

## 🏗️ Architecture

```
┌──────────────┐     HTTPS / JSON      ┌──────────────────┐     PDO      ┌─────────┐
│  Vue 3 SPA   │ ───── Axios ────────▶ │  PHP Slim 4 API  │ ──────────▶  │  MySQL  │
│  (Vite)      │ ◀──── JWT ─────────── │  JWT middleware  │              │ 3 tables│
│  Vercel      │                       │  AlwaysData      │              │         │
└──────────────┘                       └──────────────────┘              └─────────┘
```

- **Frontend** — Vue 3 + Vue Router (SPA) + Pinia store, Axios with a JWT interceptor.
- **Backend** — PHP Slim 4 REST API, JSON in/out, `JwtMiddleware` + `AdminMiddleware`.
- **Database** — MySQL via PDO (prepared statements), three related tables:
  `users` → `items` → `claims`.
- **Auth** — bcrypt password hashing, HS256 JWT carrying user id + role.

---

## ✨ Features

**Core**
- 🔐 **Accounts & auth** — register / log in, JWT-protected routes, bcrypt-hashed passwords.
- 🔑 **Forgot password** — emailed reset link, single-use and 30-minute expiry.
- 📋 **Post lost & found items** — title, category, location, date, description.
- 🔎 **Browse & filter** — keyword search + filter by type (lost/found), category, status.
- 🙋 **Claims workflow** — file a claim with proof; the poster approves/rejects. Approving
  resolves the item and auto-rejects the other pending claims (transaction-safe).
- 📊 **Dashboard** — manage your posted items and track the claims you filed.

**Bonus**
- 📷 **Photo upload** — one photo per item (JPG/PNG/WEBP/GIF, 2 MB, real image-type validation, owner-only).
- 📧 **Email notifications** — poster emailed on a new claim; claimant emailed on approve/reject
  (PHPMailer + SMTP; gracefully skipped if `MAIL_*` is unset).
- 🤝 **Smart match suggestions** — opening a *lost* item suggests likely matching *found* items
  (and vice versa), ranked by category, shared keywords, location and date.

**Advanced**
- 🛡️ **Admin role + moderation dashboard** — campus-wide stats (totals, **resolved rate**, items by
  status/category, claim counts), moderation of **any** item, and a **users & their missing items**
  table (last seen location + *time to be found*). Enforced server-side with role-based JWT + `AdminMiddleware`.
- 🏆 **"Most lost" podium** — home page shows a gold/silver/bronze top-3 of the categories students
  lose most, ranked live from the database.
- 🗺️ **Campus map location picker** — pin where an item was lost/found on an interactive **Leaflet** +
  OpenStreetMap map; the point (and AI-suggested places) appear on the item page.
- 🤖 **AI location hints** — a **local probabilistic scorer** ranks likely places an item will turn up,
  blending temporal proximity, visit frequency, category/keyword affinity, and distance clustering
  (Haversine). Optional `OPENAI_API_KEY` adds a natural-language summary, with an offline fallback.

---

## 🚀 Run it locally

### Option A — Docker (one command, recommended) 🐳

The whole stack — database, API, and frontend — runs in containers. No XAMPP, PHP, or Node needed.

```bash
docker compose up --build
```

| What | URL |
|---|---|
| App | http://localhost:8080 |
| API | http://localhost:8081/api/items |
| Database | `localhost:3307` · `root` / `foundit_root_pw` · db `foundit` |

The database **seeds itself** from `schema.sql` on first boot. Stop with `docker compose down`
(add `-v` to wipe the DB). Full details in **[DOCKER.md](DOCKER.md)**.

### Option B — Manual (XAMPP / PHP / Node)

> **Windows quick start:** after the one-time setup, double-click **`RUN-FOUNDIT.bat`** — it starts
> MySQL, the backend (`:8081`) and the frontend (`:5173`), and opens the app.

**1. Database**
```bash
mysql -u root -p < foundit-api/database/schema.sql
```
Sample users are seeded with a real bcrypt hash — log in immediately with `password123`
(`aisha@example.com`, `ben@example.com`, `citra@example.com`, or admin `admin@example.com`).

> Already have a `foundit` database? Run the additive migration instead (safe to re-run):
> ```bash
> mysql -u root -p foundit < foundit-api/database/migration_features.sql
> ```

**2. Backend**
```bash
cd foundit-api
composer install
copy .env.example .env       # edit DB creds + JWT_SECRET (optional: OPENAI_API_KEY)
php -S localhost:8081 -t public
```
Test: http://localhost:8081/api/items returns JSON.

**3. Frontend**
```bash
cd foundit-app
npm install
copy .env.example .env       # VITE_API_BASE = http://localhost:8081/api
npm run dev
```
Open http://localhost:5173. *(CORS error? Make sure `CORS_ORIGIN` in the backend `.env` matches the frontend URL.)*

---

## 🔄 CI/CD & deployment

- **Frontend auto-deploy** — the GitHub repo is connected to **Vercel**; every push to `main`
  automatically builds and deploys the frontend (root directory `foundit-app/`). No manual step.
- **Docker CI** — a [GitHub Actions workflow](.github/workflows/docker.yml) builds the full Docker
  stack on every push, starts it, and asserts the API returns seeded data and the frontend is
  served (the green badge above).
- **Backend** — PHP + MySQL hosted on **AlwaysData** (deployed separately).

---

## 📡 API reference

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/api/register` | – | Create an account |
| POST | `/api/login` | – | Log in, returns a JWT |
| POST | `/api/forgot-password` | – | Email a password-reset link |
| POST | `/api/reset-password` | – | Set a new password using the reset token |
| GET | `/api/items` | – | List items (`?type=`, `?category=`, `?status=`, `?search=`) |
| GET | `/api/items/{id}` | – | One item |
| GET | `/api/items/{id}/matches` | – | Smart match suggestions |
| GET | `/api/lost-leaderboard` | – | Top-3 most-lost categories (home podium) |
| POST | `/api/items` | JWT | Create an item (accepts `latitude`/`longitude` map point) |
| PUT | `/api/items/{id}` | JWT (owner) | Edit / mark resolved |
| DELETE | `/api/items/{id}` | JWT (owner) | Delete an item |
| POST | `/api/items/{id}/image` | JWT (owner) | Upload / replace the photo |
| POST | `/api/items/{id}/ai-hints` | JWT (owner) | Re-run the AI location scorer |
| GET | `/api/items/{id}/claims` | JWT (owner) | List claims on my item |
| POST | `/api/items/{id}/claims` | JWT | File a claim (emails the poster) |
| PUT | `/api/claims/{id}` | JWT (owner) | Approve / reject (emails the claimant) |
| DELETE | `/api/claims/{id}` | JWT (claimant) | Withdraw my claim |
| GET | `/api/me/items` | JWT | Items I posted |
| GET | `/api/me/claims` | JWT | Claims I filed |
| GET | `/api/admin/stats` | JWT (admin) | Moderation stats — totals, resolved rate |
| GET | `/api/admin/items` | JWT (admin) | List every item (`?type=`, `?status=`, `?search=`) |
| GET | `/api/admin/lost-items` | JWT (admin) | Each user's missing items + last location + days missing |
| PUT | `/api/admin/items/{id}` | JWT (admin) | Force an item's status |
| DELETE | `/api/admin/items/{id}` | JWT (admin) | Remove any item |
| GET | `/api/admin/users` | JWT (admin) | List accounts |

**Status codes used:** 200, 201, 204, 400, 401, 403, 404, 409, 422, 500.

---

## 📁 Project structure

```
FoundIt/
├── docker-compose.yml              # full local stack (db + api + web)
├── DOCKER.md                       # Docker run guide
├── .github/workflows/docker.yml    # CI: builds & tests the Docker stack
├── foundit-api/                    # PHP Slim backend
│   ├── Dockerfile · docker/apache.conf
│   ├── composer.json · composer.lock
│   ├── public/index.php            # app bootstrap + CORS + error handling
│   ├── routes/api.php              # the API contract (all routes)
│   ├── src/
│   │   ├── Database.php            # PDO connection
│   │   ├── Middleware/             # JwtMiddleware + AdminMiddleware
│   │   ├── Services/               # MailService + AiLocationService
│   │   └── Controllers/            # Auth, Item, Claim, Admin
│   └── database/
│       ├── schema.sql              # tables + sample data
│       └── migration_features.sql  # additive migration for existing DBs
└── foundit-app/                    # Vue 3 frontend (Vite)
    ├── Dockerfile · nginx.conf
    └── src/
        ├── main.js · App.vue       # bootstrap + navbar
        ├── api/http.js             # axios + JWT interceptor
        ├── store/auth.js           # auth/token/role state
        ├── router/index.js         # routes + auth/admin guards
        ├── components/LocationMap.vue
        └── views/                  # Home, Login, Register, ItemDetail, PostItem, Dashboard, Admin
```

---

## 👥 Team

| Member | Matric | Role |
|---|---|---|
| **OW YEE HAO** | A23CS0261 | Backend — Slim controllers, JWT/Admin middleware, PDO queries, services, deployment |
| **CHONG LUN QUAN** | A23CS0067 | Frontend — Vue views, router/guards, Pinia store, Axios layer, responsive UI |

---

## 🔒 Security notes

bcrypt password hashing · PDO prepared statements (no string-concatenated SQL) · ownership + role
checks · real image-type validation on upload · no email enumeration on forgot-password ·
error details hidden in production (`APP_DEBUG=false`) · CORS restricted to the frontend origin.

<details>
<summary>📋 Demo testing checklist</summary>

- [ ] Register → log in → token stored
- [ ] Forgot password → reset link → set a new password → log in with it
- [ ] Open `/post` while logged out → redirected to `/login`
- [ ] Create / edit / delete an item
- [ ] File a claim; owner approves/rejects
- [ ] `POST /api/items` with no token → **401**
- [ ] Edit someone else's item → **403**
- [ ] Invalid form → **422** with messages
- [ ] Upload a photo → shows on the card and detail page
- [ ] File a claim → poster emailed; approve/reject → claimant emailed (needs SMTP)
- [ ] Open a lost item → "Possible matches" lists likely found items
- [ ] Drop a pin on the campus map → it shows on the item page
- [ ] "AI location hints" lists ranked places with reasons; owner can refresh
- [ ] Home page shows the **"Most lost"** podium (top-3 lost categories)
- [ ] Admin console shows the **users & their missing items** table
- [ ] Normal user opening `/admin` redirects home; `GET /api/admin/stats` → **403**
- [ ] Works at the live public URL

</details>
