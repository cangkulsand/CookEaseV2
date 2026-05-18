# Implementing DevOps with Docker — CookEase

> A practical, step-by-step guide to running CookEase entirely on Docker —
> from local dev through CI, staging, and production. No Laragon, no manual
> PHP/MySQL installs.
>
> Companion to the architecture description in `CLAUDE.md` §8.
> This document focuses on **how to actually do it**, not the theory.

---

## Table of Contents

1. [Why Docker for CookEase](#1-why-docker-for-cookease)
2. [Prerequisites](#2-prerequisites)
3. [Phase 1 — Containerise the App Locally](#3-phase-1--containerise-the-app-locally)
4. [Phase 2 — Local Stack with Docker Compose](#4-phase-2--local-stack-with-docker-compose)
5. [Phase 3 — Production-Grade Dockerfile](#5-phase-3--production-grade-dockerfile)
6. [Phase 4 — CI Pipeline (Build + Test inside Docker)](#6-phase-4--ci-pipeline-build--test-inside-docker)
7. [Phase 5 — Push Images to a Registry (Nexus)](#7-phase-5--push-images-to-a-registry-nexus)
8. [Phase 6 — Deploy to Staging and Production](#8-phase-6--deploy-to-staging-and-production)
9. [Phase 7 — Zero-Downtime Updates and Rollback](#9-phase-7--zero-downtime-updates-and-rollback)
10. [Phase 8 — Operate and Monitor](#10-phase-8--operate-and-monitor)
11. [Daily Developer Workflow](#11-daily-developer-workflow)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. Why Docker for CookEase

CookEase has historically been run on a mix of manually-installed PHP/MySQL
setups with no automated deployment story. Docker solves four concrete problems
for the four-person team:

| Problem today | What Docker gives us |
|---------------|----------------------|
| "Works on my machine" — PHP/MySQL versions drift between laptops | One image, identical bytes everywhere |
| Manual deploys via FTP/SSH copy | Build once, ship the same image to staging then production |
| Hard to test against a clean MySQL | `docker compose up mysql` gives a throwaway DB in seconds |
| Rollback means restoring backups | Re-pull the previous image tag in under 60 s |

The existing `Dockerfile` at the repo root is a starting point — it builds, but it
runs `php artisan serve` (a dev server). The phases below evolve it into a
production-fit pipeline.

---

## 2. Prerequisites

Install once per developer machine:

| Tool | Why | Install |
|------|-----|---------|
| **Docker Desktop** (Win/Mac) or Docker Engine (Linux) | Build & run containers | https://docs.docker.com/get-docker/ |
| **Docker Compose v2** | Multi-container orchestration | Ships with Docker Desktop; on Linux: `apt install docker-compose-plugin` |
| **Git** | Source control | Already required |
| **Make** *(optional)* | Shortcut commands | `choco install make` on Windows |

Verify:

```powershell
docker --version            # Docker version 24.x or newer
docker compose version      # Docker Compose version v2.x
```

### 2.1 Start Docker Desktop *before* running any `docker` command

On Windows/Mac the Docker CLI talks to the engine through a named pipe that only
exists while **Docker Desktop is running**. If you see this error:

```
ERROR: error during connect: Head "http://%2F%2F.%2Fpipe%2FdockerDesktopLinuxEngine/_ping":
open //./pipe/dockerDesktopLinuxEngine: The system cannot find the file specified.
```

it means Docker Desktop is not running. Fix:

```powershell
Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"
```

Wait ~30–60 s for the whale icon in the system tray to settle on
**"Docker Desktop is running"**, then confirm the engine is reachable:

```powershell
docker version              # must print BOTH Client and Server sections
docker info
```

If only the Client section prints, the engine is still starting — give it
another minute. If Docker Desktop refuses to start, see the table at the
bottom of §12 (Troubleshooting) for the two common Windows 11 causes
(WSL 2 update needed, or virtualization disabled in BIOS).

> **Team convention:** add Docker Desktop to Windows startup (Settings →
> Apps → Startup) so you never hit this error mid-workflow.

---

## 3. Phase 1 — Containerise the App Locally

**Goal:** build and run CookEase inside a single container.

### 3.1 Build the image

From the repo root:

```powershell
docker build -t cookease:dev .
```

This uses the existing `Dockerfile`. First build takes ~5 minutes (Composer + npm).
Subsequent builds re-use layers and finish in seconds when only PHP code changed.

### 3.2 Run it

```powershell
docker run --rm -p 8000:8000 --env-file .env cookease:dev
```

> ⚠️ **You will get a `SQLSTATE[HY000] [2002] Connection refused` error here.**
> That's expected. Your `.env` has `DB_HOST=127.0.0.1`, but inside the container
> `127.0.0.1` means *the container itself*, where no MySQL is running.
>
> A standalone `docker run` has no MySQL alongside it. **Skip ahead to Phase 2**
> and use `docker compose up`, which boots a dedicated MySQL container next to
> the app on an internal Docker network. This is the only setup the rest of the
> guide assumes.

Open http://localhost:8000 — once Phase 2 is in place you'll see the CookEase
login page.

> **At this point** you've replaced the "install PHP 8.2 + MySQL + Node manually"
> step. But the app still needs a MySQL it can talk to. That's Phase 2.

---

## 4. Phase 2 — Local Stack with Docker Compose

**Goal:** one command (`docker compose up`) spins up app + MySQL + (optional) Redis.

### 4.1 The `docker-compose.yml` file

A ready-to-use `docker-compose.yml` already lives at the **repo root**. You do
not need to create one — just review the contents and tweak the credentials if
you need to. For reference, here is what's in it:

```yaml
services:
  app:
    build: .
    image: cookease:dev
    container_name: cookease-app
    ports:
      - "8000:8000"
    environment:
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: cookease
      DB_USERNAME: cookease
      DB_PASSWORD: secret
    env_file:
      - .env
    depends_on:
      mysql:
        condition: service_healthy
    volumes:
      - ./storage:/var/www/storage   # keep logs & uploads outside the image
    networks: [cookease]

  mysql:
    image: mysql:8.0
    container_name: cookease-mysql
    environment:
      MYSQL_DATABASE: cookease
      MYSQL_USER: cookease
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: rootsecret
    ports:
      - "3306:3306"                  # MySQL exposed on the standard port
    volumes:
      - cookease-mysql-data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-uroot", "-prootsecret"]
      interval: 5s
      timeout: 3s
      retries: 10
    networks: [cookease]

volumes:
  cookease-mysql-data:

networks:
  cookease:
    driver: bridge
```

> **Conventions baked into this file:**
> - Service names are `app` and `mysql`. Laravel reaches the database with
>   `DB_HOST=mysql` — that hostname only works *inside* the Compose network.
> - The dev credentials (`cookease` / `secret`, root `rootsecret`) are
>   placeholders. Change them in this file *and* any `.env` overrides before
>   sharing the setup beyond your laptop.
> - The named volume `cookease-mysql-data` persists MySQL data across
>   `docker compose down`. Use `down -v` to wipe it.
> - Port `3306` on the host maps straight to the MySQL container, so you can
>   point TablePlus/DBeaver/phpMyAdmin at `127.0.0.1:3306`.
> - `./storage` is bind-mounted into the app container so Laravel logs and any
>   user uploads survive image rebuilds.

### 4.2 Bring the stack up

```powershell
docker compose up -d --build
docker compose logs -f app
```

Migrations and seeders run automatically on container start (see `CMD` in the
Dockerfile). When the log shows `Server running on http://0.0.0.0:8000`, browse
http://localhost:8000.

### 4.3 Importing existing data into the Docker MySQL

By default the app container runs `php artisan migrate` and the recipe/ingredient
seeders on first start, so a **clean install needs no import** — open
http://localhost:8000 and register a new user.

If you already have a CookEase database elsewhere (e.g. from an older dev
environment, a backup, or a colleague's machine) and want to bring its rows
into the Docker MySQL, do this once:

#### 4.3.1 Get a SQL dump file

From wherever your existing MySQL is running, produce a single `.sql` file.

**Option A — `mysqldump` from the host's `mysql` client (if installed):**

```powershell
mysqldump -u root -p --databases cookease `
  --add-drop-database `
  --single-transaction `
  --routines --triggers `
  > cookease-backup.sql
```

**Option B — phpMyAdmin** → select the `cookease` database → **Export** tab →
format **SQL** → **Go**. Save the file as `cookease-backup.sql` in the repo
root.

> Place the file in the repo root (or anywhere you can reference by path).
> Make sure `cookease-backup.sql` is in `.gitignore` — it may contain user data.

#### 4.3.2 Reset the Docker MySQL to an empty state

The first start of the app container ran migrations and seeders. To replace
that data with your dump, wipe the MySQL volume:

```powershell
docker compose down -v          # -v drops the named volume cookease-mysql-data
docker compose up -d mysql      # bring only MySQL back up; app stays down
```

Wait ~10 s for the healthcheck to pass:

```powershell
docker compose ps mysql         # STATUS should show "healthy"
```

#### 4.3.3 Import the dump

Pipe the file into the `mysql` client running inside the container:

```powershell
Get-Content .\cookease-backup.sql | `
  docker compose exec -T mysql mysql -ucookease -psecret cookease
```

> The `-T` flag disables TTY allocation, which is required when piping stdin
> into `docker compose exec` on Windows.

If your dump starts with `CREATE DATABASE` / `USE cookease;` statements, log
in as root instead so it has permission to create the schema:

```powershell
Get-Content .\cookease-backup.sql | `
  docker compose exec -T mysql mysql -uroot -prootsecret
```

#### 4.3.4 Verify

```powershell
docker compose exec mysql mysql -ucookease -psecret cookease -e "SHOW TABLES;"
docker compose exec mysql mysql -ucookease -psecret cookease -e "SELECT COUNT(*) FROM users;"
```

You should see the full CookEase schema and your imported row counts.

#### 4.3.5 Bring the app back up

```powershell
docker compose up -d app
```

The container's startup `php artisan migrate --force` will run on top of your
imported schema. As long as your dump was from a version with the same or older
migrations, this is a no-op — Laravel only applies migrations that aren't yet
in the `migrations` table.

#### Connecting from a GUI (TablePlus / DBeaver / phpMyAdmin)

The MySQL container exposes port **3306** on `localhost`. Use these credentials:

| Field | Value |
|-------|-------|
| Host | `127.0.0.1` |
| Port | `3306` |
| Database | `cookease` |
| Username | `cookease` (or `root`) |
| Password | `secret` (or `rootsecret` for root) |

These are the dev defaults from `docker-compose.yml` (§4.1). Change them in
both that file and your `.env` before sharing the setup beyond your laptop.

### 4.4 Tear down

```powershell
docker compose down              # stop containers, keep DB volume
docker compose down -v           # also wipe the MySQL volume — clean slate
```

> **Convention for the team:** add `docker-compose.yml` to Git. Add
> `docker-compose.override.yml` to `.gitignore` so each developer can keep
> personal tweaks (different host port, extra services) without committing them.

---

## 5. Phase 3 — Production-Grade Dockerfile

The existing `Dockerfile` works for local dev but has three issues for production:

1. **Runs `php artisan serve`** — fine for dev, not for prod load.
2. **One stage** — ships build tools (npm, git) into the runtime image.
3. **Seeders run on every container start** — dangerous in prod.

Create `Dockerfile.prod` alongside the existing one:

```dockerfile
# ---- Stage 1: PHP deps ------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ---- Stage 2: JS bundle -----------------------------------------------------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY public public
RUN npm run build

# ---- Stage 3: Runtime -------------------------------------------------------
FROM php:8.2-fpm-alpine AS runtime

RUN apk add --no-cache nginx supervisor libpng libzip oniguruma libxml2 icu-libs \
 && apk add --no-cache --virtual .build-deps libpng-dev libzip-dev oniguruma-dev libxml2-dev icu-dev \
 && docker-php-ext-install pdo_mysql mbstring zip bcmath gd intl \
 && apk del .build-deps

WORKDIR /var/www

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

COPY docker/nginx.conf       /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
```

### Supporting files (under `docker/`)

**`docker/entrypoint.sh`**

```sh
#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force      # safe — no seeders here

exec "$@"
```

**`docker/nginx.conf`** — minimal Nginx in front of PHP-FPM (omitted for brevity;
standard Laravel + FPM config pointing `root` at `/var/www/public`).

**`docker/supervisord.conf`** — runs nginx + php-fpm in the same container.

Build it:

```powershell
docker build -f Dockerfile.prod -t cookease:prod .
```

> **Two-Dockerfile pattern:** `Dockerfile` (dev convenience, includes seeders)
> stays for `docker compose`. `Dockerfile.prod` is what CI builds and what gets
> deployed. Don't mix the two.

---

## 6. Phase 4 — CI Pipeline (Build + Test inside Docker)

**Goal:** every push runs the four-stage Jenkins pipeline from `CLAUDE.md` §8.ii
using Docker so the build environment is identical for every developer and the CI
agent.

### 6.1 Make Composer scripts call Docker

In `composer.json` add:

```json
"scripts": {
    "ci": [
        "@php artisan config:clear",
        "@php artisan migrate --force",
        "vendor/bin/pest --coverage --min=70",
        "vendor/bin/pint --test"
    ]
}
```

Now `composer ci` is the single command CI runs inside the container.

### 6.2 Jenkinsfile (Docker-based agent)

```groovy
pipeline {
    agent any

    triggers { githubPush() }

    environment {
        IMAGE = "nexus.cookease.app/cookease:${env.GIT_COMMIT}"
    }

    stages {
        stage('01 — Build image') {
            steps {
                sh 'docker build -f Dockerfile.prod -t $IMAGE .'
            }
        }

        stage('02 — Test inside container') {
            steps {
                sh '''
                  docker network create cookease-ci || true
                  docker run -d --name ci-mysql --network cookease-ci \
                    -e MYSQL_DATABASE=cookease \
                    -e MYSQL_ROOT_PASSWORD=ci \
                    mysql:8.0
                  # wait for mysql
                  until docker exec ci-mysql mysqladmin ping -uroot -pci --silent; do sleep 2; done

                  docker run --rm --network cookease-ci \
                    -e DB_HOST=ci-mysql -e DB_USERNAME=root -e DB_PASSWORD=ci \
                    $IMAGE composer ci
                '''
            }
            post {
                always {
                    sh 'docker rm -f ci-mysql || true'
                    sh 'docker network rm cookease-ci || true'
                }
            }
        }

        stage('03 — Static analysis') {
            steps {
                withSonarQubeEnv('SonarQube') {
                    sh 'sonar-scanner'
                }
                timeout(time: 5, unit: 'MINUTES') {
                    waitForQualityGate abortPipeline: true
                }
            }
        }

        stage('04 — Push to registry') {
            when { branch 'main' }
            steps {
                withCredentials([usernamePassword(credentialsId: 'nexus',
                                                  usernameVariable: 'U',
                                                  passwordVariable: 'P')]) {
                    sh 'echo $P | docker login nexus.cookease.app -u $U --password-stdin'
                    sh 'docker push $IMAGE'
                    sh 'docker tag  $IMAGE nexus.cookease.app/cookease:latest'
                    sh 'docker push nexus.cookease.app/cookease:latest'
                }
            }
        }
    }

    post {
        always  { sh 'docker image prune -f' }
        success { slackSend channel: '#cookease-ci', color: 'good',
                            message: "✅ ${env.IMAGE}" }
        failure { slackSend channel: '#cookease-ci', color: 'danger',
                            message: "❌ ${env.IMAGE}" }
    }
}
```

Key DevOps wins from this pipeline:

- The **same image** that runs CI tests is the one that ships to production.
  No "but it built fine on Jenkins" surprises.
- The test MySQL is ephemeral — no shared state between PR builds.
- The image is **tagged by Git SHA**, never overwritten. Every build is auditable.

---

## 7. Phase 5 — Push Images to a Registry (Nexus)

The pipeline above pushes to `nexus.cookease.app/cookease:<sha>`. One-time setup:

1. Install Sonatype Nexus OSS (or use a managed registry: GHCR, AWS ECR, Docker Hub).
2. Create a **Docker (hosted)** repository named `cookease`.
3. Generate a deploy user with `write` permission.
4. Add credentials in Jenkins → **Manage Credentials** as `nexus`
   (username + password kind).

Tag conventions:

| Tag | Meaning | When written |
|-----|---------|--------------|
| `cookease:<git-sha>` | Immutable, one per commit on main | Every successful CI build |
| `cookease:staging`   | Pointer to whatever is currently deployed to staging | After staging deploy |
| `cookease:production` | Pointer to whatever is currently live | After production approval |
| `cookease:latest`     | Convenience — same as latest `staging` | Every push to main |

> Never overwrite `<git-sha>` tags. They are your audit trail and rollback targets.

---

## 8. Phase 6 — Deploy to Staging and Production

### 8.1 Server-side `docker-compose.yml`

On each server (staging and production) place a Compose file at
`/opt/cookease/docker-compose.yml`:

```yaml
services:
  app:
    image: nexus.cookease.app/cookease:${IMAGE_TAG:-latest}
    restart: unless-stopped
    env_file: /opt/cookease/.env
    ports:
      - "80:80"
    depends_on:
      - mysql
    networks: [cookease]
    healthcheck:
      test: ["CMD", "curl", "-fsS", "http://localhost/login"]
      interval: 30s
      timeout: 5s
      retries: 3

  mysql:
    image: mysql:8.0
    restart: unless-stopped
    env_file: /opt/cookease/.env.mysql
    volumes:
      - cookease-mysql:/var/lib/mysql
    networks: [cookease]

volumes:
  cookease-mysql:

networks:
  cookease:
```

### 8.2 Deploy command (run by Jenkins via SSH)

```sh
# Staging — automatic on every main merge
ssh deploy@staging.cookease.app \
  "cd /opt/cookease && \
   IMAGE_TAG=${GIT_COMMIT} docker compose pull && \
   IMAGE_TAG=${GIT_COMMIT} docker compose up -d"

# Production — only after Jenkins manual approval gate
ssh deploy@cookease.app \
  "cd /opt/cookease && \
   IMAGE_TAG=${GIT_COMMIT} docker compose pull && \
   IMAGE_TAG=${GIT_COMMIT} docker compose up -d"
```

The `entrypoint.sh` runs `php artisan migrate --force` on container start, so
schema changes apply automatically. **Migrations must be backward-compatible**
within one release so the previous image still works during rollback —
see CLAUDE.md §8.iii ("two-step deploy" pattern).

### 8.3 Secrets

Secrets live in `/opt/cookease/.env` on the server — never in the image, never
in Git. Recommended file permissions:

```sh
sudo chown root:docker /opt/cookease/.env
sudo chmod 640 /opt/cookease/.env
```

For multi-server setups, promote to **Docker Secrets** or **HashiCorp Vault**.

---

## 9. Phase 7 — Zero-Downtime Updates and Rollback

### Zero-downtime swap (when behind Nginx / a load balancer)

`docker compose up -d` with the new image tag starts the new container, waits for
its healthcheck, then stops the old one. With a reverse proxy in front (Nginx,
Traefik, Caddy) using upstream health checks, in-flight requests drain cleanly.

### Rollback in under 60 seconds

```sh
ssh deploy@cookease.app \
  "cd /opt/cookease && \
   IMAGE_TAG=<previous-sha> docker compose pull && \
   IMAGE_TAG=<previous-sha> docker compose up -d"
```

Keep the **last 5 production tags** retained in Nexus. Anything older can be
pruned by a retention policy.

---

## 10. Phase 8 — Operate and Monitor

Docker doesn't replace monitoring — it makes monitoring *easier* because every
environment is the same shape.

| Concern | Tool | How it plugs into the Docker stack |
|---------|------|-----------------------------------|
| Container logs | **Loki + Promtail** | Promtail tails `/var/lib/docker/containers/*/*.log` and ships to Loki |
| Errors | **Sentry** | `SENTRY_LARAVEL_DSN` in `.env`; package already supported by Laravel |
| Metrics | **Prometheus + Grafana** | Add `cadvisor` service to the prod Compose file for container metrics |
| Uptime | **UptimeRobot** | External HTTP probe on `/login` |
| Image scanning | **Trivy** | `trivy image cookease:<sha>` in the CI pipeline post-build |

Minimum daily-ops commands every team member should know:

```sh
docker compose ps                    # what's running
docker compose logs -f app           # tail app logs
docker compose exec app php artisan tinker   # open Tinker inside the container
docker compose exec app php artisan queue:work   # process the queue
docker stats                         # live CPU/RAM per container
```

---

## 11. Daily Developer Workflow

```
┌──────────────────────────────────────────────────────────────────┐
│  Morning                                                          │
│   git switch -c feature/foo                                       │
│   docker compose up -d           # stack ready in seconds         │
│                                                                   │
│  Coding                                                           │
│   edit files → browser auto-reloads (Vite dev server is bind-     │
│                mounted in docker-compose.override.yml)            │
│   docker compose exec app php artisan migrate                     │
│   docker compose exec app vendor/bin/pest --filter=Bmi            │
│                                                                   │
│  Before pushing                                                   │
│   docker compose exec app composer ci    # same command CI runs   │
│                                                                   │
│  Push & PR                                                        │
│   git push origin feature/foo                                     │
│   → Jenkins builds the same image, runs the same `composer ci`    │
│   → PR shows green check → Lead reviews → merge → auto-deploy     │
│                                                                   │
│  End of day                                                       │
│   docker compose down            # nothing left running           │
└──────────────────────────────────────────────────────────────────┘
```

---

## 12. Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| `open //./pipe/dockerDesktopLinuxEngine: The system cannot find the file specified` | Docker Desktop is not running | Launch Docker Desktop, wait for "running" state, retry (see §2.1) |
| Docker Desktop won't start — "WSL 2 installation is incomplete" | WSL kernel out of date | Admin PowerShell: `wsl --update` then reboot |
| Docker Desktop won't start — "Hardware assisted virtualization not enabled" | VT-x/AMD-V disabled or Windows features off | Enable Virtualization in BIOS; turn on *Virtual Machine Platform* + *Windows Subsystem for Linux* in Optional Features; reboot |
| `SQLSTATE[HY000] [2002] Connection refused` when running standalone `docker run` | `.env` points `DB_HOST` at `127.0.0.1`, which inside the container is the container itself | Use `docker compose up` (§4) — it provides a MySQL container the app can reach by service name (`DB_HOST=mysql`) |
| `Connection refused` to MySQL on container start under Compose | App started before MySQL was ready | Add `depends_on.condition: service_healthy` (already in §4.1) |
| `Class "PDO" not found` | Image missing `pdo_mysql` extension | Already installed in both Dockerfiles; rebuild with `--no-cache` |
| Vite assets 404 in prod | `public/build` not copied into runtime stage | Check Stage 2 → Stage 3 copy in `Dockerfile.prod` |
| `storage/logs/laravel.log: permission denied` | UID mismatch between host and container | Re-run `chown -R www-data:www-data storage` inside the container, or use named volume |
| Migrations fail on deploy | Non-backward-compatible migration | Roll forward with a fix migration; never `migrate:rollback` on prod — restore from backup |
| `docker compose up` hangs on "Pulling" | Not logged into the registry | `docker login nexus.cookease.app` on the host |
| Image size > 800 MB | Build tools leaked into runtime stage | Confirm multi-stage build is being used (`Dockerfile.prod`, not `Dockerfile`) |

---

## Checklist — "Are we DevOps-ready?"

- [ ] Every developer can run `docker compose up` and reach a working app
- [ ] `Dockerfile.prod` builds a < 300 MB image with no `npm` / `composer` in it
- [ ] CI runs `composer ci` inside the same image it will deploy
- [ ] Images are tagged by Git SHA and pushed to Nexus on every main merge
- [ ] Staging auto-deploys; production requires manual Jenkins approval
- [ ] Rollback is documented and rehearsed (drill it once per quarter)
- [ ] Container logs and errors flow into Loki/Sentry
- [ ] `.env` is never in the image and never in Git

Once all eight are ticked, CookEase is running a textbook Docker-based DevOps
pipeline that matches the architecture in `CLAUDE.md` §8.
