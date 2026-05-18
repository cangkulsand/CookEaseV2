# CookEase

> AI-assisted Malaysian recipe & meal-planning web application.
> Built with **Laravel 12**, **Tailwind CSS**, **Alpine.js**, **MySQL**, and the **Groq LLM API**.

CookEase helps users decide *what to cook today* based on the ingredients they already have, their BMI / health goal, and personal preferences (cooking time, budget, dietary filters). It generates 12 contextual Malaysian recipes per request via the **Groq Llama-4-Scout** model, fetches food images from **Pixabay**, and lets users favourite recipes, schedule them onto a weekly meal-plan calendar, and leave reviews.

---

## Quickstart

The full project runs on **Docker** — no manual PHP, Node, or MySQL installs needed.

### Prerequisites

- **Docker Desktop** installed and running (whale icon in the system tray says "running")
- A `.env` file at the repo root (copy from `.env.example` if missing) with your API keys filled in — see [Required API Keys](#required-api-keys)
- *(Optional)* Your existing database exported as `cookease-backup.sql` at the repo root

---

### Step 1 — Start the MySQL container

```powershell
docker compose up -d mysql
```

Wait ~10 seconds, then confirm it's healthy:

```powershell
docker compose ps mysql
```

`STATUS` should say `healthy`.

---

### Step 2 — Import your `.sql` dump *(skip if starting fresh)*

```powershell
Get-Content .\cookease-backup.sql | docker compose exec -T mysql mysql -uroot -prootsecret
```

Verify the import worked:

```powershell
docker compose exec mysql mysql -ucookease -psecret cookease -e "SHOW TABLES;"
```

You should see all CookEase tables (`users`, `recipes`, `bmis`, `meal_plans`, …).

> **No dump? No problem.** Skip this step entirely — the app container will run migrations and seed default recipes/ingredients automatically on first start.

---

### Step 3 — Start the app container

```powershell
docker compose up -d --build app
docker compose logs -f app
```

First build takes ~5 minutes (Composer + npm). When the log shows
`Server running on http://0.0.0.0:8000`, open the application using either method below:

- **Browser:**  
  **→ http://localhost:8000**

- **Docker Desktop:**  
  Open Docker Desktop → Containers → select the container → click the exposed **8000** port link.

You should land on the CookEase login page. If you imported a dump, log in with your existing accounts. Otherwise register a new user.

---

## Required API Keys

CookEase needs three external service keys. Sign up and obtain them before starting:

| Service | Used For | Where to get it | `.env` variable |
|---------|----------|-----------------|-----------------|
| **Groq** | AI recipe generation (Llama-4-Scout model) | https://console.groq.com — create an API key | `GROQ_API_KEY` |
| **Pixabay** | Food images for generated recipes | https://pixabay.com/api/docs/ — register and copy your key | `PIXABAY_API_KEY` |
| **Google OAuth** | "Sign in with Google" | Google Cloud Console → APIs & Services → Credentials → OAuth client ID. Set the redirect URI to `http://localhost:8000/auth/google/callback` | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` |

> ⚠️ **`.env.example` heads-up.** It ships with `APP_ENV=production`, `APP_DEBUG=false`, and a leftover `PEXELS_API_KEY` line that the code does not read. For local dev, set `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost:8000`, and **add a `PIXABAY_API_KEY=…` line manually**.

---

## Bundled Services

The Docker stack starts three containers — all running together:

| Service | URL / Port | What it's for |
|---------|------------|---------------|
| **App** (Laravel) | http://localhost:8000 | The CookEase web app |
| **MySQL** | `127.0.0.1:3306` | Database — connect with [MySQL Workbench](./docs/mysql-workbench.md) using user `cookease` / pw `secret` |
| **Mailpit** | http://localhost:8025 | Fake SMTP inbox — every email the app sends (forgot-password, notifications) lands here so you can see it instantly |

For Mailpit to capture mail, your `.env` needs:

```ini
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@cookease.local
```

---

## Everyday Commands

```powershell
docker compose ps                # what's running
docker compose logs -f app       # tail app logs
docker compose stop              # stop everything (keeps data)
docker compose start             # start it again
docker compose down              # remove containers (keeps data volume)
docker compose down -v           # WIPE data — clean slate
```

Run an artisan command inside the app container:

```powershell
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app php artisan tinker
```

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `open //./pipe/dockerDesktopLinuxEngine: The system cannot find the file specified` | Docker Desktop is not running — launch it from the Start menu and wait for "running" |
| `SQLSTATE[HY000] [2002] Connection refused` | App started before MySQL was ready. Check `docker compose ps mysql` shows `healthy`, then `docker compose restart app` |
| `/generate` returns "Failed to generate recipes" | Check `docker compose logs app` — usually a missing/invalid `GROQ_API_KEY` in `.env`, or you've hit the Groq free-tier rate limit |
| Google OAuth redirect mismatch | The redirect URI in Google Cloud Console must **exactly** match `GOOGLE_REDIRECT_URI` in `.env` — `http://localhost:8000/auth/google/callback` |
| Vite assets not loading after frontend change | Rebuild the image: `docker compose up -d --build app` |

For the full troubleshooting list, see [`docs/devops-with-docker.md`](./docs/devops-with-docker.md#12-troubleshooting).

---

## Project Documentation

- [`docs/quickstart.md`](./docs/quickstart.md) — the 3-step Docker quickstart (same as the section above)
- [`docs/mysql-workbench.md`](./docs/mysql-workbench.md) — connect MySQL Workbench (or TablePlus / DBeaver) to the Docker database
- [`docs/devops-with-docker.md`](./docs/devops-with-docker.md) — full DevOps pipeline: CI, staging, production, image registry, zero-downtime deploys
- [`CONTRIBUTING.md`](./CONTRIBUTING.md) — developer workflow: branch, PR, review, merge
- [`CLAUDE.md`](./CLAUDE.md) — architecture, database schema, application flow, and DevOps theory

## License

CookEase is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
