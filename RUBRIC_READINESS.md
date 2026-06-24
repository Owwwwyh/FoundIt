# FoundIt — Rubric Readiness Self-Check
**SECJ3483 Web Technology Group Project (20%)** · prepared 2026-06-20

This maps every rubric criterion to concrete evidence in the FoundIt project, so you can
walk into the demo knowing exactly what proves each mark.

- **Live app:** https://foundit-app-beta.vercel.app · **API:** https://foundit261.alwaysdata.net/api
- **Repo:** https://github.com/Owwwwyh/FoundIt

---

## A. Required Project Scope (minimum requirements) — all met ✅

| Requirement | Status | Evidence |
|---|---|---|
| Clear problem domain + target users | ✅ | Campus lost & found; users = students/staff (proposal) |
| SPA behaviour using Vue.js | ✅ | Vue 3 + Vue Router, Pinia store, single shell |
| Frontend consumes backend asynchronously | ✅ | Axios (`src/api/http.js`) + JWT interceptor |
| Full REST API: GET, POST, PUT, DELETE | ✅ | Items + claims CRUD (`routes/api.php`) |
| ≥3 related tables with realistic sample data | ✅ | `users` → `items` → `claims` (FKs), seeded |
| ≥1 JWT auth flow + protected access | ✅ | `POST /login` issues JWT; `JwtMiddleware` guards routes |
| Input validation on frontend **and** backend | ✅ | Vue form validation + controller `validate()` → 422 |
| Error handling with proper HTTP status codes | ✅ | 200/201/204/400/401/403/404/409/422/500 |
| Deployed to a live/test server | ✅ | Vercel (frontend) + AlwaysData (PHP + MySQL), HTTPS |

---

## B. Demo Rubric (5%) — criterion-by-criterion

| # | Criterion (weight) | What proves it | Est. |
|---|---|---|---|
| 1 | **SPA frontend quality** (15%) | Vue Router navigation with no reloads; responsive layout + mobile hamburger; cohesive themed UI | Strong |
| 2 | **CRUD completeness** (20%) | Live create (201) / read (200) / update (200) / delete (204) on items, plus claims CRUD | Strong |
| 3 | **REST API implementation** (15%) | Slim routes, JSON request/response, correct status codes, grouped public/protected/admin routes | Strong |
| 4 | **Database design & use** (10%) | 3 related tables with FKs + `ON DELETE CASCADE`; transaction-safe claim approval | Strong |
| 5 | **JWT & protected access** (15%) | Login issues HS256 JWT; `JwtMiddleware` (401) + `AdminMiddleware` (403); route guards | Strong |
| 6 | **Validation & security practice** (10%) | Dual-side validation (422); bcrypt; PDO prepared statements; ownership/role checks; no email enumeration | Strong |
| 7 | **Presentation & technical explanation** (15%) | Slide deck + run sheet + Q&A prep; both members can explain their half | Prep done — **rehearse** |

**Biggest single line = CRUD (20%)** → make sure all four verbs are shown live and named out loud.

---

## C. Deliverables checklist (rubric section 10)

| Deliverable | Status | Location |
|---|---|---|
| Proposal (PDF/Word) | ✅ | `FoundIt_Project_Proposal.pdf` |
| Source code repo + visible contribution history | ✅ | github.com/Owwwwyh/FoundIt |
| Presentation slides | ✅ | `FoundIt_Demo_Slides.pptx` |
| Live URL / hosted environment | ✅ | Vercel + AlwaysData |
| Database schema / export / seed | ✅ | `foundit-api/database/schema.sql` (+ `migration_features.sql`) |
| README with setup instructions | ✅ | `README.md` |
| Demo script / rehearsal prep | ✅ | `DEMO_RUN_SHEET.md` |
| Completed peer evaluation form | ⏳ | **Each member submits individually by 30 June 2026** |

---

## D. Gaps / action items before submission

1. **Rehearse the demo once end-to-end** (< 12 min). Criterion 7 is purely about confident delivery.
2. **Peer evaluation (5%)** — individual + confidential; **due 30 June 2026**. Don't forget it; it's a separate 5%.
3. **Team size:** the brief expects **4 members**; the proposal cover still has `[Member 3]/[Member 4]` placeholders. Either add the real names/matrics, or confirm a 2-person team is acceptable with your lecturer.
4. **Real email for the email-notification demo:** seed users are `@example.com` (sends fire but go nowhere). To *show* an email arriving, log in / register with a real inbox.
5. **Academic integrity:** the lecturer may question any member individually — both must understand all code, not just their half (see the Q&A in the run sheet).

---

## E. Score posture
Core scope and all four demo "hard" criteria (CRUD, REST, DB, JWT) are **implemented, deployed, and live-verified**, plus six extra features (photo, email, smart match, admin role, campus map, AI hints) that go well beyond the minimum. The remaining risk is **delivery** (rehearsal) and **process** (peer eval + team-size note) — not the build.
