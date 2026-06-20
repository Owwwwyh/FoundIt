# FoundIt — Demo Day Run Sheet & Q&A Prep
**SECJ3483 Web Technology · Final Demo (5%) · Week 15 (22–28 June 2026)**

- **Live app:** https://foundit-app-beta.vercel.app
- **Live API:** https://foundit261.alwaysdata.net/api
- **Repo:** https://github.com/Owwwwyh/FoundIt
- **User login:** `aisha@example.com` / `password123`
- **Admin login:** `admin@example.com` / `password123`
- **Team:** OW YEE HAO (A23CS0261, backend) · CHONG LUN QUAN (A23CS0067, frontend)

> **Golden rule:** the demo is scored on *what you show + how you explain it*. Every click below is tied to a rubric line. Narrate the rubric word as you do it ("this is our **PUT** request… here's the **422** validation… this route is **JWT-protected**").

---

## 0. Before you present (5 min setup)
- [ ] Open the **live URL** in a clean browser tab (not localhost — the rubric rewards a deployed app).
- [ ] Open a 2nd tab with the **API** (`/api/items`) to show raw JSON.
- [ ] Open the **GitHub repo** → Insights → Contributors (proves both members committed).
- [ ] Have **DevTools → Network** ready (F12) to show real async requests + status codes.
- [ ] Log out so you can demo a clean login.
- [ ] Optional: have Postman/curl ready for the 401/403/422 status-code proofs.
- [ ] Decide who drives: **CHONG** drives the UI (frontend), **OW** drives the API/DevTools/DB (backend). Both must speak.

---

## 1. Live demo script (~8 min) — every step maps to a rubric criterion

### A. SPA + navigation *(→ "SPA frontend quality" 15%)* — CHONG
1. Land on **Home**. Point out: "Single Page App — Vue Router, no full page reloads."
2. Click between **Home → Item detail → Dashboard → back**. Note the URL changes but the page never white-flashes (SPA).
3. Resize the window / open phone view → show the **responsive** layout + hamburger nav.

### B. Async data + filtering *(→ async interaction / SPA)* — CHONG
4. Open **DevTools → Network**. Use the **Lost/Found toggle** and **search box**.
5. Show the **`GET /api/items?...` XHR** firing (Axios) and the cards updating without reload. "This is asynchronous communication via Axios."

### C. Auth + JWT *(→ "JWT and protected access" 15%)* — CHONG → OW
6. While logged out, click **Post item** → you're **redirected to /login** (route guard).
7. Log in as `aisha@example.com`. In Network, show **`POST /api/login` → 200** returning a **JWT token**.
8. (OW) In DevTools → Application → Local Storage, show the stored token. "Every protected request sends `Authorization: Bearer <token>`; the server validates it in `JwtMiddleware`."

### D. CRUD — Create / Read / Update / Delete *(→ "CRUD completeness" 20% — the heaviest line)* — CHONG drives, OW narrates status codes
9. **CREATE (POST 201):** Post a new item. Fill the form, **drop a pin on the campus map**, optionally attach a photo → submit. Show **`POST /api/items` → 201**.
10. **READ (GET 200):** Open the new item's detail page → `GET /api/items/{id} → 200`.
11. **UPDATE (PUT 200):** Edit the item (change description / mark resolved) → `PUT /api/items/{id} → 200`.
12. **DELETE (204):** Delete a throwaway item → `DELETE /api/items/{id} → 204 No Content`.
   > Say out loud: "That's all four — GET, POST, PUT, DELETE — working against the live API."

### E. Relationships + claims workflow *(→ "Database design and use" 10%)* — CHONG
13. Open someone else's item → **File a claim** (proof message).
14. Log in as the **owner** (`ben@` or whoever) → **approve** the claim. Note it auto-resolves the item and rejects sibling claims (transaction-safe). "This uses our 3 related tables: `users → items → claims`."

### F. Validation + security *(→ "Validation and security practice" 10%)* — OW
15. Submit a form with bad input → show the **422** with per-field messages (validated on **both** client and server).
16. (Quick API proofs — curl or Postman):
    - `POST /api/items` with **no token → 401**
    - Edit someone else's item → **403**
    - `GET /api/items/9999 → 404`
   > "Passwords are bcrypt-hashed, all SQL uses PDO prepared statements (SQL-injection safe), and errors return correct HTTP codes."

### G. Advanced features (the wow factor) — both
17. **Admin role:** log in as `admin@example.com` → the **Admin** link appears → open the **moderation dashboard** (stats, resolved rate, delete any item). Then show a normal user has **no** Admin link and `/admin` bounces home → "role-based authorization on top of ownership checks."
18. **Campus map:** the pin you dropped earlier now shows on the item page (Leaflet + OpenStreetMap).
19. **AI location hints:** open an item → the **AI location hints** panel ranks likely places with reasons + score bars. "A local probabilistic scorer — temporal, frequency, category, and distance signals."

---

## 2. Architecture explanation (~2 min) *(→ "Presentation and technical explanation" 15%)* — OW
Say this clearly (use the slide):
> "FoundIt is a **Vue 3 SPA** talking to a **PHP Slim REST API** over JSON, with **MySQL via PDO**. The browser stores a **JWT** after login; Axios attaches it to every protected request; `JwtMiddleware` verifies it and `AdminMiddleware` enforces the admin role. Data is three related tables — users, items, claims. The frontend is deployed on **Vercel**, the backend + MySQL on **AlwaysData**, over HTTPS."

---

## 3. Likely lecturer questions & confident answers
*The lecturer can question any member — both of you should be able to answer these.*

**Q: Why Vue / what makes it a SPA?**
A: One HTML shell; Vue Router swaps components client-side without full reloads. State lives in a Pinia store; data is fetched asynchronously with Axios.

**Q: How does JWT actually work here?**
A: On login we verify the bcrypt hash, then sign a JWT (HS256) containing the user id and role with our secret. The client sends it as `Authorization: Bearer …`. `JwtMiddleware` decodes/verifies it (signature + expiry); invalid/missing → 401. The role claim drives `AdminMiddleware` (→ 403 for non-admins).

**Q: Where's your input validation?**
A: Both sides. Vue forms validate before sending (instant feedback); the server re-validates in each controller and returns **422** with per-field messages — because client validation can be bypassed, the server is the source of truth.

**Q: How do you prevent SQL injection?**
A: Every query uses **PDO prepared statements** with bound parameters — user input is never concatenated into SQL. We also disabled emulated prepares.

**Q: What HTTP status codes do you use and when?**
A: 200 OK, 201 Created, 204 No Content (delete), 400 bad request, 401 unauthenticated, 403 forbidden (ownership/role), 404 not found, 409 conflict (duplicate email/claim), 422 validation, 500 server error.

**Q: Show me your three related tables.**
A: `users` (1) → `items` (many) via `items.user_id`; `items` (1) → `claims` (many) via `claims.item_id`; `claims.user_id` → `users`. Foreign keys with `ON DELETE CASCADE`.

**Q: What happens when a claim is approved?**
A: A DB transaction sets the item to `resolved` and auto-rejects the other pending claims, so the data can't end up inconsistent.

**Q: How is the admin different from a user?**
A: A `role` column on `users`. The JWT carries the role; `AdminMiddleware` gates `/api/admin/*`. Admins can moderate **any** item; normal users only their own (ownership 403).

**Q: Which part did *you* build?** (be honest, individual)
A: OW — backend (Slim controllers, JWT/Admin middleware, PDO queries, services, deployment). CHONG — frontend (Vue views, router/guards, Pinia store, Axios layer, responsive UI).

**Q: Any security beyond auth?**
A: Bcrypt passwords, prepared statements, ownership + role checks, real image-type validation on upload, forgot-password doesn't reveal which emails exist (no enumeration), error details hidden in production (`APP_DEBUG=false`), CORS restricted to our frontend origin.

---

## 4. If something fails live (fallbacks)
- **Wi-Fi down / live API slow:** run it **locally** — double-click `RUN-FOUNDIT.bat` (MySQL + backend :8081 + frontend :5173). Same app.
- **A write breaks on the projector:** fall back to the **API tab / Postman** to show the JSON + status code, and the **slide screenshots**.
- **Login fails:** you have 4 seed accounts (aisha/ben/citra + admin), all `password123`.
- Keep a **screen-recording** of a full happy-path run as a last-resort backup.

---

## 5. Final pre-demo checklist
- [ ] Live URL loads on the presentation machine's browser.
- [ ] Both members can log in and each can speak to their half.
- [ ] GitHub Contributors page shows both names.
- [ ] Proposal PDF, slides, README, schema.sql all in the submission folder.
- [ ] Peer evaluation submitted by 30 June 2026.
- [ ] Rehearsed once end-to-end, under ~12 minutes total.
