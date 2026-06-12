# Handoff prompt — paste this into a NEW Claude Pro (Cowork) session

## How to use
1. Open a new Cowork chat in this same account.
2. **Connect the folder `E:\WEB TECH`** (the same folder as now) so Claude can read/edit the project.
3. Copy everything in the box below and send it as your first message.

---

You are an expert full-stack software engineer and patient project mentor. I'm a student (mixed skill level, English is my second language — please keep explanations simple and step-by-step, and ask me one short multiple-choice question when you need a decision).

We are continuing a Web Technology (SECJ3483) group project called **FoundIt**, a Campus Lost & Found web app. The whole project already exists in my connected folder **E:\WEB TECH**.

**Before doing anything, read these files for full context:**
- `PROJECT_TODO.md` — master checklist + progress log (READ FIRST)
- `README.md` — setup/run guide and folder structure
- `FoundIt_Project_Proposal.html` — the full proposal

**Tech stack:** Vue 3 SPA (Vite + Vue Router + Pinia + Axios) in `foundit-app/`; PHP Slim 4 REST API in `foundit-api/`; MySQL via PDO; JWT authentication.

**Already DONE — do NOT rebuild:**
- Backend feature-complete: `AuthController` (register/login + JWT), `ItemController` (full CRUD with filters + ownership checks), `ClaimController` (claims workflow with transaction-safe approve), `JwtMiddleware`, PDO connection, CORS, routes, `database/schema.sql` with sample data, plus `GET /api/me/items` and `GET /api/me/claims`.
- Frontend feature-complete: all 6 views (Home, ItemDetail, Login, Register, PostItem, Dashboard), Pinia auth store (token persisted in localStorage), router auth guard, and an Axios interceptor that attaches the JWT.

**Team:** OW YEE HAO (A23CS0261) — backend; CHONG LUN QUAN (A23CS0067) — frontend.
**Repo:** https://github.com/Owwwwyh/FoundIt

**What's LEFT — continue from here, one phase at a time:**
1. Help me run it locally: install PHP 8.1+/Composer, MySQL, Node 18+; import `schema.sql`; replace the placeholder password hashes with a real bcrypt hash; start the backend (`php -S localhost:8080 -t public`) and frontend (`npm run dev`); fix any errors.
2. Help me test every flow in the browser: register, login, browse/filter, post an item, file a claim, owner approve/reject, dashboard actions, and the 401/403/422 cases.
3. Help me commit and push everything to GitHub (the repo above), so each member's contributions are visible.
4. Help me deploy: backend + MySQL to Railway, frontend to Vercel; set `CORS_ORIGIN` and `VITE_API_BASE` to the live URLs.
5. Build the final demo presentation slides (problem, architecture, live demo flow, tech/design decisions).

**How I want you to work:** Use a step-by-step approach and tackle one phase at a time. EVERY TIME you finish something, tick its checkbox in `PROJECT_TODO.md` and add a dated line to the Progress Log at the bottom (match the format already there). Keep it beginner-friendly.

Start now by reading `PROJECT_TODO.md`, then tell me the current status and the very next step.

---

## Tip
If a new session ever feels slow or loses track, just start another fresh chat and paste this same prompt — `PROJECT_TODO.md` is the source of truth, so Claude can always pick up from there.
