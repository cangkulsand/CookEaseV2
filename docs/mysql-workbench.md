# Connecting MySQL Workbench to the Docker MySQL

A short guide for browsing the CookEase database with **MySQL Workbench** while
the app runs in Docker.

---

## Prerequisites

- **MySQL Workbench** installed on your machine — https://dev.mysql.com/downloads/workbench/
- The Docker stack is running:
  ```powershell
  docker compose up -d
  ```
  Confirm with `docker compose ps mysql` — `STATUS` should say `healthy`.

---

## Step 1 — Open the connection dialog

Launch MySQL Workbench. On the home screen, click the **`+`** icon next to
*MySQL Connections*.

---

## Step 2 — Fill in the connection details

| Field | Value | Why |
|-------|-------|-----|
| **Connection Name** | `CookEase Docker` | Any label you want |
| **Connection Method** | `Standard (TCP/IP)` | Default |
| **Hostname** | `127.0.0.1` | The MySQL container exposes port 3306 on your host |
| **Port** | `3306` | Set by `docker-compose.yml` |
| **Username** | `cookease` | App user (read/write on the `cookease` schema) |
| **Default Schema** | `cookease` *(optional)* | Auto-selects the right DB on connect |

Click **Store in Vault…** next to *Password*, paste **`secret`**, click OK.

> Want **root access** (drop tables, manage users, etc.)? Use `root` / `rootsecret` instead.

---

## Step 3 — Test the connection

Click **Test Connection**. You should see:

```
Successfully made the MySQL connection.
```

If you see an authentication error, see [Troubleshooting](#troubleshooting) below.

Click **OK** to save. Double-click the new connection tile to open it.

---

## Step 4 — Browse the schema

On the left sidebar (**SCHEMAS**), expand `cookease` → **Tables**. You'll see all CookEase tables:

- `users`
- `recipes`
- `bmis`
- `meal_plans`
- `favorites`
- `reviews`
- `ingredients`
- `ingredient_usage`
- *(plus Laravel's `migrations`, `sessions`, `cache`, `jobs`, `notifications`)*

Right-click any table → **Select Rows – Limit 1000** to view its data.

---

## Credentials Summary (from `docker-compose.yml`)

| User | Password | Privileges |
|------|----------|------------|
| `cookease` | `secret` | Full access to `cookease` schema only |
| `root` | `rootsecret` | Server-wide admin |

> These are **dev defaults**. Change them in `docker-compose.yml` *and* your `.env` before sharing the setup beyond your laptop or pushing the image to a registry.

---

## Common Tasks

### Reset a user's password manually (no need to use forgot-password)

```sql
UPDATE users
SET password = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj0Zo5wO0VFu'
WHERE email = 'you@example.com';
-- (the hash above is `password`)
```

### Wipe and re-seed the database

Easier from the terminal — but if you must do it from Workbench:

```sql
DROP DATABASE cookease;
CREATE DATABASE cookease CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then in PowerShell:

```powershell
docker compose exec app php artisan migrate --seed
```

### Export a backup

Workbench top menu → **Server** → **Data Export** → pick the `cookease` schema → **Start Export**. You'll get a `.sql` file you can later import via the [`docs/quickstart.md`](./quickstart.md#step-2--import-your-sql-dump-skip-if-starting-fresh) flow.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `Can't connect to MySQL server on 127.0.0.1:3306` | MySQL container isn't running. Run `docker compose up -d mysql`, wait 10s, retry. |
| `Authentication plugin 'caching_sha2_password' cannot be loaded` | Edit the connection → **Advanced** tab → **Others** field, add `default-auth=mysql_native_password` → save → reconnect. |
| `Access denied for user 'cookease'@'localhost'` | Wrong password. Use `secret` (lowercase, no quotes). |
| Connection works but `cookease` schema is empty | Migrations haven't run yet. `docker compose exec app php artisan migrate` |
| Port 3306 conflict — connection times out | Another MySQL is running on your host on the same port. Stop it (`net stop MySQL80` as admin) or change the `ports` mapping in `docker-compose.yml` to e.g. `"3307:3306"` and connect on `3307`. |

---

## Alternative GUIs

If you don't like Workbench, the same credentials work with any MySQL-compatible client:

| Client | Notes |
|--------|-------|
| **TablePlus** | Modern, faster, has a great free tier — https://tableplus.com |
| **DBeaver** | Free, open-source, supports many DB engines — https://dbeaver.io |
| **phpMyAdmin** | Browser-based; you can add it to `docker-compose.yml` as another service |
| **VS Code SQLTools** extension | Stays inside your editor |

Same host (`127.0.0.1`), port (`3306`), and credentials work everywhere.
