# CookEase

> AI-assisted Malaysian recipe & meal-planning web application.
> Built with **Laravel 12**, **Tailwind CSS**, **Alpine.js**, **MySQL**, and the **Groq LLM API**.

CookEase helps users decide *what to cook today* based on the ingredients they already have, their BMI / health goal, and personal preferences (cooking time, budget, dietary filters). It generates 12 contextual Malaysian recipes per request via the **Groq Llama-4-Scout** model, fetches food images from **Pixabay**, and lets users favourite recipes, schedule them onto a weekly meal-plan calendar, and leave reviews.

---

## Table of Contents

- [Prerequisites](#prerequisites)
- [Required API Keys](#required-api-keys)
- [Setup Guide A — Laragon / XAMPP (Windows)](#setup-guide-a--laragon--xampp-windows)
- [Setup Guide B — Docker (any OS)](#setup-guide-b--docker-any-os)
- [Verifying the Installation](#verifying-the-installation)
- [Common Commands](#common-commands)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before starting, make sure you have:

| Requirement | Version | Notes |
|-------------|---------|-------|
| **PHP** | 8.2+ | With `pdo_mysql`, `mbstring`, `zip`, `bcmath`, `gd` extensions enabled (Laragon/XAMPP only — Docker bundles these) |
| **Composer** | 2.x | PHP package manager |
| **Node.js** | 18+ (20 recommended) | For Vite asset bundling |
| **npm** | 9+ | Ships with Node |
| **MySQL** | 8.x | Bundled in Laragon/XAMPP, or run via Docker |
| **Git** | latest | To clone the repo |

---

## Required API Keys

CookEase needs three external service keys. Sign up and obtain them before running the app:

| Service | Used For | Where to get it | `.env` variable name |
|---------|----------|-----------------|----------------------|
| **Groq** | AI recipe generation (Llama-4-Scout model) | https://console.groq.com — create an API key | `GROQ_API_KEY` |
| **Pixabay** | Food images for generated recipes | https://pixabay.com/api/docs/ — register and copy your key | `PIXABAY_API_KEY` |
| **Google OAuth** | "Sign in with Google" | Google Cloud Console → APIs & Services → Credentials → OAuth client ID. Set the redirect URI to `http://cookease.test/auth/google/callback` (Laragon) or `http://localhost:8000/auth/google/callback` (Docker / `artisan serve`) | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` |

> ⚠️ **Heads-up on `.env.example`.** The shipped `.env.example` file contains a `PEXELS_API_KEY` line — that is a leftover from an earlier image provider and **the code does not read it**. The application actually looks for **`PIXABAY_API_KEY`** (`config/services.php` → `services.pixabay.key`, used in `IngredientController@getImageFromPixabay`). After copying `.env.example` to `.env`, **add a `PIXABAY_API_KEY=…` line yourself** — you can leave or delete the `PEXELS_API_KEY` line; it's unused.

> ⚠️ **`.env.example` ships with production defaults.** It sets `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL=https://cookease.xyz`. For local development you must flip these to `APP_ENV=local`, `APP_DEBUG=true`, and an `APP_URL` that matches your local host (see each setup guide below).

---

## Setup Guide A — Laragon / XAMPP (Windows)

This is the recommended path for local development on Windows. Steps are written for **Laragon** (which auto-creates pretty `.test` domains); the XAMPP differences are noted inline.

### 1. Clone the repository

Place the project under your web root:

```powershell
# Laragon — defaults to C:\laragon\www
cd C:\laragon\www
git clone https://github.com/<your-org>/cookease.git
cd cookease

# XAMPP — defaults to C:\xampp\htdocs
# cd C:\xampp\htdocs
# git clone https://github.com/<your-org>/cookease.git
# cd cookease
```

### 2. Install PHP and JS dependencies

```powershell
composer install
npm install
```

### 3. Create the `.env` file

Copy the example file and generate the application key:

```powershell
copy .env.example .env
php artisan key:generate
```

Open `.env` in your editor and **replace** the placeholder values that ship with `.env.example` with your local values. The settings that matter for local dev:

```ini
APP_NAME=CookEase
APP_ENV=local                         # change from production
APP_DEBUG=true                        # change from false
APP_URL=http://cookease.test          # XAMPP: http://localhost/cookease/public

# Replace the placeholder DB_HOST=your-db-host etc. with these:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cookease
DB_USERNAME=root
DB_PASSWORD=                          # Laragon default is blank; XAMPP default is blank too

GROQ_API_KEY=gsk_your_groq_key_here

# IMPORTANT: add this line manually — it isn't in .env.example
PIXABAY_API_KEY=your_pixabay_key_here

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://cookease.test/auth/google/callback
```

### 4. Create the database

**Laragon:** open the **HeidiSQL** or **phpMyAdmin** shortcut from the Laragon menu, then create a new database named `cookease` (UTF-8 / `utf8mb4_unicode_ci`).

**XAMPP:** open http://localhost/phpmyadmin, click **New**, and create the `cookease` database.

Or via command line:

```powershell
mysql -u root -e "CREATE DATABASE cookease CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Run migrations and seed the data

```powershell
php artisan migrate
php artisan db:seed --class=RecipeSeeder
php artisan db:seed --class=IngredientSeeder
```

### 6. Link storage and start the dev stack

```powershell
php artisan storage:link

# One-shot dev mode — runs server + queue + Vite together
composer dev
```

Or run them in separate terminals:

```powershell
php artisan serve        # http://localhost:8000
npm run dev              # Vite asset watcher
```

**Laragon shortcut:** with Laragon's auto-virtual-hosts enabled, the app is also reachable at **http://cookease.test** without `php artisan serve`.

### 7. Open the app

Navigate to:

- **Laragon:** http://cookease.test
- **XAMPP:** http://localhost/cookease/public
- **`php artisan serve`:** http://localhost:8000

You should see the login page. Register a new account to begin.

---

## Setup Guide B — Docker (any OS)

The repo ships with a `Dockerfile` that bundles PHP 8.2-FPM, all required extensions, Composer, Node, and the Vite build. This guide assumes **Docker Desktop** (Windows/macOS) or **Docker Engine** (Linux).

### 1. Clone the repository

```bash
git clone https://github.com/<your-org>/cookease.git
cd cookease
```

### 2. Create the `.env` file

```bash
cp .env.example .env
```

Edit `.env` so the database host points at the Docker MySQL service (we'll define it as `db` below). Override `.env.example`'s production defaults:

```ini
APP_NAME=CookEase
APP_ENV=local                         # change from production
APP_DEBUG=true                        # change from false
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db                            # service name in docker-compose.yml
DB_PORT=3306
DB_DATABASE=cookease
DB_USERNAME=cookease
DB_PASSWORD=cookease_secret

GROQ_API_KEY=gsk_your_groq_key_here

# IMPORTANT: add this line manually — it isn't in .env.example
PIXABAY_API_KEY=your_pixabay_key_here

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### 3. Create a `docker-compose.yml`

The repo provides only a `Dockerfile`. Drop this `docker-compose.yml` next to it for a one-command stack with MySQL:

```yaml
services:
  app:
    build: .
    image: cookease:local
    container_name: cookease-app
    ports:
      - "8000:8000"
    environment:
      - DB_HOST=db
    env_file:
      - .env
    depends_on:
      db:
        condition: service_healthy
    volumes:
      - ./storage:/var/www/storage

  db:
    image: mysql:8
    container_name: cookease-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: cookease
      MYSQL_USER: cookease
      MYSQL_PASSWORD: cookease_secret
      MYSQL_ROOT_PASSWORD: root_secret
    ports:
      - "3307:3306"                   # 3307 on host so it doesn't clash with Laragon/XAMPP MySQL
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      timeout: 3s
      retries: 10
    volumes:
      - cookease_db:/var/lib/mysql

volumes:
  cookease_db:
```

### 4. Build and start the stack

```bash
docker compose up -d --build
```

This will:

1. Build the CookEase image from the `Dockerfile` (installs Composer + npm deps, compiles Vite assets).
2. Start the MySQL 8 container with persistent volume.
3. Run the container's `CMD`, which automatically:
   - Clears Laravel caches.
   - Runs `php artisan migrate --force`.
   - Seeds `RecipeSeeder` and `IngredientSeeder`.
   - Links `storage`.
   - Re-caches config / routes / views.
   - Starts `php artisan serve --host=0.0.0.0 --port=8000`.

### 5. Generate the app key (first time only)

```bash
docker exec cookease-app php artisan key:generate
docker compose restart app
```

### 6. Open the app

http://localhost:8000

---

## Verifying the Installation

Whichever path you chose, confirm the install with these checks:

```bash
# 1. Migrations applied
php artisan migrate:status                          # or: docker exec cookease-app php artisan migrate:status

# 2. Seeded recipes are present
php artisan tinker
>>> \App\Models\Recipe::count()                     # should return > 0

# 3. Run the test suite
php artisan test                                    # or: docker exec cookease-app php artisan test
```

Then in the browser:

1. **Register** a new account at `/register` (or use Google OAuth).
2. Submit your **BMI** at `/bmi/form`.
3. Pick a **health goal** at `/health-goals`.
4. Open `/generate`, add a few ingredients, and submit. You should get 12 AI-generated recipes — if so, your **Groq + Pixabay** keys are working.

---

## Common Commands

| Action | Laragon / XAMPP | Docker |
|--------|-----------------|--------|
| Start dev stack | `composer dev` | `docker compose up -d` |
| Stop dev stack | `Ctrl+C` | `docker compose down` |
| Run migrations | `php artisan migrate` | `docker exec cookease-app php artisan migrate` |
| Fresh DB + seed | `php artisan migrate:fresh --seed` | `docker exec cookease-app php artisan migrate:fresh --seed` |
| Build assets for prod | `npm run build` | (already done in image build) |
| Run tests | `php artisan test` | `docker exec cookease-app php artisan test` |
| Tail logs | `php artisan pail` | `docker compose logs -f app` |
| Clear all caches | `php artisan optimize:clear` | `docker exec cookease-app php artisan optimize:clear` |

---

## Troubleshooting

**"SQLSTATE[HY000] [2002] No such file or directory" / connection refused.**
MySQL isn't reachable. On Laragon/XAMPP, confirm MySQL is running in the panel. On Docker, run `docker compose ps` and check the `db` service is `healthy`. Make sure `DB_HOST` in `.env` matches your setup (`127.0.0.1` for Laragon/XAMPP, `db` for Docker).

**"Class 'PDO' not found" or missing extension errors.**
Your PHP install is missing `pdo_mysql`. Enable it in `php.ini` (Laragon: right-click tray → PHP → Extensions; XAMPP: edit `C:\xampp\php\php.ini`). Docker users won't see this — the extensions are baked into the image.

**Vite assets not loading / blank styling.**
Run `npm run dev` (Laragon/XAMPP) so Vite serves the dev bundle. If you only need the compiled bundle, run `npm run build` once. In Docker, the assets are pre-built during image build — rebuild the image if you change frontend code: `docker compose up -d --build`.

**`/generate` returns "Failed to generate recipes."**
Check `storage/logs/laravel.log`. Almost always a missing/invalid `GROQ_API_KEY` in `.env`, or your Groq account has hit its free-tier rate limit.

**Google OAuth redirect mismatch.**
The redirect URI in your Google Cloud Console must **exactly** match `GOOGLE_REDIRECT_URI` in `.env`, including scheme and trailing path. For Laragon use `http://cookease.test/auth/google/callback`; for Docker use `http://localhost:8000/auth/google/callback`.

**Permission errors on `storage/` or `bootstrap/cache/`.**
On Linux/macOS with Docker: `chmod -R 775 storage bootstrap/cache`. On Windows this is rarely an issue.

**Migrations need to be re-run after schema changes.**
The repo also exposes a `/run-migrations` web route as a fallback for hosts without shell access. Visit that URL once authenticated to apply pending migrations. Remove or guard this route before deploying to production.

---

## Project Documentation

For architecture, database schema, application flow, and DevOps details, see [`CLAUDE.md`](./CLAUDE.md).

## License

CookEase is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
