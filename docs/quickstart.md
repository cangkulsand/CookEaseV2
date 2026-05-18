# CookEase — Docker Quickstart

> The 5-minute version. Assumes you already have a CookEase `.sql` dump you
> want to import into the Docker MySQL.
>
> For the full DevOps guide (CI, staging, production, multi-stage builds, etc.)
> see [`devops-with-docker.md`](./devops-with-docker.md).

---

## What you need before starting

- **Docker Desktop installed and running** (whale icon in the system tray says "running")
- A `.env` file at the repo root (copy from `.env.example` if missing)
- Your existing database exported as `cookease-backup.sql` in the repo root

---

## Step 1 — Start the MySQL container

```powershell
docker compose up -d mysql
```

Wait ~10 seconds, then confirm it's healthy:

```powershell
docker compose ps mysql
```

`STATUS` should say `healthy`.

---

## Step 2 — Import your `.sql` dump

```powershell
Get-Content .\cookease-backup.sql | docker compose exec -T mysql mysql -uroot -prootsecret
```

Verify the import worked:

```powershell
docker compose exec mysql mysql -ucookease -psecret cookease -e "SHOW TABLES;"
```

You should see all CookEase tables (`users`, `recipes`, `bmis`, `meal_plans`, …).

---

## Step 3 — Start the app container

```powershell
docker compose up -d --build app
docker compose logs -f app
```

First build takes ~5 minutes (Composer + npm). When the log shows
`Server running on http://0.0.0.0:8000`, open:

**→ http://localhost:8000**

You should land on the CookEase login page and be able to log in with your
existing user accounts from the imported database.

---

## Done

Everyday commands:

```powershell
docker compose ps                # what's running
docker compose logs -f app       # tail app logs
docker compose stop              # stop everything (keeps data)
docker compose start             # start it again
docker compose down              # remove containers (keeps data volume)
docker compose down -v           # WIPE data — clean slate
```

If something goes wrong, see the **Troubleshooting** table in
[`devops-with-docker.md`](./devops-with-docker.md#12-troubleshooting).
