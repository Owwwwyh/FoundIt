# FoundIt — Run with Docker 🐳

The whole app (database + PHP API + Vue frontend) runs as **one self-contained stack**
with a single command. No XAMPP, no manual MySQL, no PHP/Node install needed —
just Docker.

## Prerequisites
- **Docker Desktop** installed and running. (Windows: it uses the WSL2 backend.)
  Verify with: `docker --version` and `docker compose version`.

## Start it
From the project root (`WEB TECH/`):

```bash
docker compose up --build
```

First run takes a few minutes (it builds the images and seeds the database).
When it's ready, open:

| What | URL |
|---|---|
| **App (frontend)** | http://localhost:8080 |
| **API (raw JSON)** | http://localhost:8081/api/items |
| **Database** | `localhost:3307` · user `root` · pass `foundit_root_pw` · db `foundit` |

**Logins** (seeded from `foundit-api/database/schema.sql`):
- User: `aisha@example.com` / `password123`
- Admin: `admin@example.com` / `password123`

## Stop it
```bash
docker compose down        # stop containers (database is kept)
docker compose down -v      # stop AND wipe the database volume (fresh seed next time)
```

## What's in the stack
| Service | Image / build | Port (host) | Notes |
|---|---|---|---|
| `db`  | `mariadb:10.11` | 3307 → 3306 | `schema.sql` auto-creates + seeds on first boot |
| `api` | `foundit-api/Dockerfile` (PHP 8.2 + Apache) | 8081 → 80 | Slim REST API; uses committed `vendor/` |
| `web` | `foundit-app/Dockerfile` (Node build → nginx) | 8080 → 80 | Vue SPA; API base baked to `http://localhost:8081/api` |

Two named volumes persist data between runs: `foundit_db` (database) and
`foundit_uploads` (uploaded item photos).

## How config works
- The API reads its settings from **`foundit-api/.env.docker`** (copied to `.env`
  inside the image). Key difference from local dev: `DB_HOST=db` (the compose
  service name) instead of `127.0.0.1`.
- The frontend's API URL is set at **build time** via the `VITE_API_BASE` build arg
  in `docker-compose.yml`, so the Dockerized app talks to the Dockerized API — not
  the live AlwaysData backend.

## Notes / gotchas
- Host DB port is **3307** (not 3306) on purpose, so it won't clash with a local
  XAMPP/MySQL already using 3306.
- This stack is **independent** of the live deployment (Vercel + AlwaysData). It's
  for local development and the demo — it doesn't change anything in production.
- If you edit `schema.sql`, run `docker compose down -v` then `up --build` again to
  re-seed from scratch (the DB only seeds when its volume is empty).

## Demo talking point 🎤
> "The entire app is containerised — database, PHP API, and Vue frontend each run in
> their own Docker container, orchestrated by Docker Compose. Anyone can clone the
> repo and run the whole stack with one command, `docker compose up`, with no manual
> setup. The database even seeds itself on first boot."
