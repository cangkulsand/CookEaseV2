# Contributing to CookEase

Welcome! This is the **developer workflow** — what to do every time you want to change code.

> **The rule:** never push directly to `main`. Always branch → push → open a Pull Request. The head programmer reviews and merges.

---

## One-Time Setup (do this once)

1. **Install Docker Desktop** — https://www.docker.com/products/docker-desktop/ — and make sure it's **running** (whale icon in the system tray says "running").

2. **Clone the repo** (somewhere convenient on your machine):

   ```powershell
   git clone https://github.com/<owner>/cookease.git
   cd cookease
   ```

3. **Create your `.env` file** from the example and ask the head programmer for the API keys:

   ```powershell
   copy .env.example .env
   ```

   Then open `.env` and fill in `GROQ_API_KEY`, `PIXABAY_API_KEY`, and Google OAuth keys.

4. **Start the stack**:

   ```powershell
   docker compose up -d --build
   ```

   First run takes ~5 minutes. When done, open **http://localhost:8000** — you should see the login page.

---

## Daily Workflow (do this every time you want to make a change)

### Step 1 — Pull latest from main

```powershell
git checkout main
git pull origin main
```

### Step 2 — Create a branch for your work

Use a clear branch name like `feature/<what>` or `fix/<what>`:

```powershell
git checkout -b feature/add-recipe-filter
```

### Step 3 — Edit code

Make your changes. The app is live-reloaded into the container, so edits in VS Code show up instantly in the browser. No restart needed.

To run things inside the container:

```powershell
docker compose exec app php artisan migrate         # run a migration
docker compose exec app vendor/bin/pest             # run tests
docker compose exec app php artisan tinker          # open Tinker REPL
```

### Step 4 — Commit and push

```powershell
git add .
git commit -m "Add ingredient-based recipe filter"
git push -u origin feature/add-recipe-filter
```

The push output will print a link to open a Pull Request. **Click it.**

### Step 5 — Open the Pull Request

On the GitHub PR page:

- **Title:** short, clear (e.g. "Add ingredient-based recipe filter")
- **Description:** what you changed and why. Bullet points are fine.
- Click **Create pull request**.

### Step 6 — Watch the automation

Within ~1–3 minutes of opening the PR, three things happen automatically:

| Check | What it is | What you do if it fails |
|-------|-----------|------------------------|
| **CI / Lint + Unit + Feature Tests** | Runs your tests and code style check | Read the log, fix locally, commit + push again |
| **CodeRabbit** | AI reads your diff and leaves review comments | Read each comment — reply or fix what's valid |
| **Review required** | Head programmer (or teammate) must approve | Wait for approval — ping them on chat if needed |

### Step 7 — Address any feedback

If CodeRabbit or the reviewer asks for changes:

1. Edit the files locally
2. `git add .` → `git commit -m "Address review feedback"` → `git push`
3. The PR auto-updates. CI and CodeRabbit re-run.

### Step 8 — Wait for merge

Once you have ✅ green checks and ✅ approval, the **head programmer merges** the PR into `main`. You don't merge it yourself.

### Step 9 — Clean up

```powershell
git checkout main
git pull origin main
git branch -d feature/add-recipe-filter      # delete local branch
```

The remote branch is auto-deleted by GitHub after merge (if that setting is on).

---

## Branch Naming Convention

| Prefix | When to use | Example |
|--------|-------------|---------|
| `feature/` | A new feature | `feature/recipe-export-pdf` |
| `fix/` | A bug fix | `fix/login-redirect-loop` |
| `chore/` | Maintenance, no behaviour change | `chore/update-dependencies` |
| `docs/` | Documentation only | `docs/update-readme` |

Keep branch names lowercase, use hyphens (not spaces or underscores), and keep them short.

---

## Commit Message Style

One-line, present tense, no period at the end:

✅ `Add ingredient filter to generate form`
✅ `Fix BMI calculation rounding error`
✅ `Update README with Docker instructions`

❌ `fixed stuff`
❌ `WIP`
❌ `Added new feature for filtering ingredients on the generate page.`

If you need to explain *why*, put it in the PR description, not the commit message.

---

## Common Commands Cheat Sheet

```powershell
# Stack management
docker compose up -d                  # start everything
docker compose down                   # stop everything
docker compose ps                     # see what's running
docker compose logs -f app            # follow app logs

# Inside the app container
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec app vendor/bin/pest
docker compose exec app vendor/bin/pint

# Git workflow
git checkout main && git pull         # sync with latest main
git checkout -b feature/<name>        # new branch
git add . && git commit -m "..."      # commit
git push -u origin feature/<name>     # push and create tracking
```

---

## If Something Breaks

| Symptom | Likely fix |
|---------|------------|
| `open //./pipe/dockerDesktopLinuxEngine` error | Docker Desktop isn't running — launch it from Start menu |
| Tests fail on a fresh clone | `docker compose exec app php artisan config:clear` |
| Vite assets not loading | `docker compose up -d --build` to rebuild the image |
| Can't push to `main` directly | That's by design — make a branch, push to it, open a PR |
| PR shows "Review required" | Wait for the head programmer to review and approve |

For anything else, check [`README.md`](./README.md) or ask the head programmer.

---

## Summary — The Loop

```
git pull origin main
  ↓
git checkout -b feature/<name>
  ↓
edit code
  ↓
docker compose exec app vendor/bin/pest      ← run tests locally first
  ↓
git add . && git commit -m "..."
  ↓
git push -u origin feature/<name>
  ↓
Open PR on GitHub
  ↓
CI runs + CodeRabbit reviews (~2 min)
  ↓
Head programmer approves and merges
  ↓
git checkout main && git pull
```

That's it. Welcome to the team. 🚀
