# A8 — Pull & Run the CookEase Image (for team members)

Each team member (everyone **except** the image creator) runs the **published
Docker Hub image** — no repo clone, no `docker build`. This proves the image is
portable.

## Prerequisites
- Docker Desktop installed and **running**.

## Steps

**1. Pull the image** (this is the screenshot showing image layers downloading):
```bash
docker pull sharhanarean/cookeasev2:latest
```

**2. Run it with a database** (from inside this `a8/` folder):
```bash
docker compose up
```
This starts the app container + a MySQL container, runs migrations & seeders
automatically, then serves the app.

**3. Open the app** (this is the "running in browser" screenshot):
```
http://localhost:8000
```
You should see the CookEase login page.

**4. Stop / clean up:**
```bash
# Ctrl+C to stop, then:
docker compose down -v
```

## What to screenshot for A8
1. The `docker pull` output showing the image layers being downloaded **on your own machine**.
2. The CookEase app open in your browser at `http://localhost:8000`.

## Required environment variables
The container is configured entirely through `docker-compose.yml`
(`environment:` block) — `APP_KEY`, the `DB_*` settings, and `MAIL_MAILER=log`.
No `.env` file is needed; the demo `APP_KEY` is safe to share.

## Notes
- The image contains **only the app**. MySQL is provided here as a second
  container — that is why running the bare image alone (`docker run ...`) shows
  no database. Use `docker compose up` from this folder instead.
- First run is slower (pulls MySQL + waits for it to become healthy before the
  app boots).
