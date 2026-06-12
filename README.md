# FoundIt — Campus Lost & Found Hub

A full-stack single-page web app for SECJ3483 Web Technology.
**Stack:** Vue.js SPA · PHP Slim REST API · MySQL (PDO) · JWT auth.

## 🌐 Live demo
- **App:** https://foundit-app-beta.vercel.app
- **API:** https://foundit261.alwaysdata.net/api
- **Login:** `aisha@example.com` / `password123` (sample users)

Frontend hosted on **Vercel** · backend + **MySQL** on **AlwaysData**.

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
│   │   ├── Middleware/JwtMiddleware.php  # verifies JWT (wired)
│   │   └── Controllers/            # AuthController(M1), ItemController(M2), ClaimController(M2)
│   └── database/schema.sql         # tables + sample data
└── foundit-app/                    # Vue 3 frontend (Vite)
    ├── package.json
    ├── vite.config.js
    ├── index.html
    ├── .env.example
    └── src/
        ├── main.js · App.vue       # bootstrap + navbar (wired)
        ├── api/http.js             # axios + JWT interceptor (wired)
        ├── store/auth.js           # auth/token state (wired)
        ├── router/index.js         # routes + auth guard (wired)
        └── views/                  # Home, Login (examples) + Register, ItemDetail, PostItem, Dashboard (stubs)
```

The "wired" files already work. Each member fills the `TODO (M1)…(M4)` markers.

## Setup & run

> **Quick start (Windows):** once the one-time setup below is done, just double-click
> **`RUN-FOUNDIT.bat`** in this folder — it starts MySQL, the backend (`:8081`), and the
> frontend (`:5173`) in their own windows and opens the app. Log in with `aisha@example.com` / `password123`.

### 1. Database
```bash
mysql -u root -p < foundit-api/database/schema.sql
```
The sample users are seeded with a real bcrypt hash, so you can log in straight away with the
password **`password123`** (e.g. `aisha@example.com`, `ben@example.com`, `citra@example.com`).

### 2. Backend
```bash
cd foundit-api
composer install
copy .env.example .env       # (Windows)  — then edit DB creds + JWT_SECRET
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
6. Deploy: backend + MySQL to Railway, frontend to Vercel.

## Demo testing checklist
- [ ] Register → log in → token stored
- [ ] Open `/post` while logged out → redirected to `/login`
- [ ] Create / edit / delete an item
- [ ] File a claim; owner approves/rejects
- [ ] `POST /api/items` with no token → **401**
- [ ] Edit someone else's item → **403**
- [ ] Invalid form → **422** with messages
- [ ] Works at the live public URL
