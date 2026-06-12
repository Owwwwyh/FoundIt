# Handoff prompt — paste this into a NEW Claude Pro (Cowork) session

## How to use
1. Open a new Cowork chat in this same account.
2. **Connect the folder `E:\WEB TECH`** (same folder) so Claude can read/edit the project.
3. Copy everything in the box below and send it as your first message.

---

You are an expert full-stack software engineer and patient project mentor. I'm a student (mixed skill level, English is my second language — keep explanations simple and step-by-step, and ask me ONE short multiple-choice question when you need a decision).

We are continuing a Web Technology (SECJ3483) group project called **FoundIt**, a Campus Lost & Found web app, in my connected folder **E:\WEB TECH**.

**Before doing anything, read `PROJECT_TODO.md` (the source of truth — checklist + full progress log), then `README.md`.** Then tell me the current status and the next step.

**Tech stack:** Vue 3 SPA (Vite + Vue Router + Pinia + Axios) in `foundit-app/`; PHP Slim 4 REST API in `foundit-api/`; MySQL via PDO; JWT auth.

**Current state (already DONE — do NOT rebuild):**
- Backend + frontend feature-complete; UI redesigned (warm "lost-property office" theme, Fraunces + Hanken Grotesk).
- **Deployed LIVE:**
  - Frontend → **Vercel**: https://foundit-app-beta.vercel.app (CLI logged in as `owwwwyh`).
  - Backend (PHP) + **MySQL** → **AlwaysData** (free plan, account `foundit261`): https://foundit261.alwaysdata.net/api
  - Code: https://github.com/Owwwwyh/FoundIt (`gh` authed as `Owwwwyh`; commit everything as me, OW YEE HAO).
- **Bonus Feature #1 (Photo upload) — DONE & live.** Items can have a photo (`POST /api/items/{id}/image`, owner-only, validated, stored under public `/uploads`, `items.image_path` column).

**What's LEFT — 4 more bonus features, do ONE AT A TIME, starting from #2:**
2. **Email notifications** — email the item's poster when a claim is filed; email the claimant when approved/rejected.
3. **Smart match suggestions** — when a "lost" item is posted, suggest matching "found" items (same category/keyword/location).
4. **Admin role + moderation dashboard** — a second role that can manage any item and see stats (role-based authorization).
5. **Campus map location picker** — pick where an item was lost/found on an interactive map (Leaflet.js).

**How I want you to work (IMPORTANT):**
- One feature at a time. For EACH feature: **build → check → find issues → fix → critique again** (accuracy matters), with **solid exception handling** and proper HTTP status codes.
- Test locally first, then deploy and verify LIVE.
- EVERY time you finish something, tick its box in `PROJECT_TODO.md` and add a dated line to the Progress Log (match the existing format).
- Keep commits authored as me (OW YEE HAO); push to GitHub after each feature.

**Local run:** double-click `RUN-FOUNDIT.bat` (starts MySQL via XAMPP + backend on **:8081** + frontend on **:5173**). Sample users log in with password `password123` (e.g. `aisha@example.com`). `.env` files are gitignored — create from `.env.example` if missing.

**Deployment facts (for redeploying a feature):**
- AlwaysData: SSH host `ssh-foundit261.alwaysdata.net`, user `foundit261`; MySQL host `mysql-foundit261.alwaysdata.net`, db `foundit261_foundit`. The server keeps app code in the home dir (`~/src`, `~/vendor`, `~/routes`, `~/.env`) with `~/www` as the web root (= the app's `public/`). Uploads go to `~/www/uploads`.
- **I will give you the AlwaysData password when you need it** (it is NOT stored in the repo). Ask me for it before deploying backend/DB changes.
- Deploy backend changes by uploading the changed files over SSH (Node `ssh2` works with the password). Redeploy frontend with: `cd foundit-app && vercel deploy --prod --yes --build-env VITE_API_BASE=https://foundit261.alwaysdata.net/api`.
- Live DB changes (migrations) need my explicit approval.

**Gotchas learned (avoid these):**
- In `.env`, a password containing `#` must be **quoted** (`#` starts a comment otherwise).
- On Windows, Node reads `/tmp/...` as `C:\tmp\...` — convert Git Bash paths with `cygpath -m` before passing to Node.
- AlwaysData won't let you `CREATE DATABASE` over SQL — databases are made in its panel.
- Backend runs on **:8081** locally (8080 was taken).

Start by reading `PROJECT_TODO.md`, then tell me the status and propose how you'll build **Feature #2 (Email notifications)**.

---

## Tip
If a session ever feels slow or loses track, start a fresh chat and paste this same prompt — `PROJECT_TODO.md` is the source of truth, so Claude can always pick up from there.
