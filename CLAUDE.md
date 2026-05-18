# CookEase — Project Documentation (CLAUDE.md)

> AI-assisted Malaysian recipe & meal-planning web application.
> Built with Laravel 12, Tailwind CSS, Alpine.js, MySQL, and the Groq LLM API.

---

## 1. System About

**CookEase** is a web-based smart cooking assistant designed to help users (especially in Malaysia) decide *what to cook today* based on the ingredients they already have at home, their health profile (BMI, health goals), and personal preferences (cooking time, budget, dietary filters).

The system integrates an **AI recipe generator** (Groq LLM via the Llama 4 Scout model) that returns 12 contextual Malaysian recipes per request, complete with ingredients, instructions, calorie estimates, grocery lists, and supporting food images fetched from the **Pixabay API**.

Users can save favourite recipes, schedule them onto a weekly **meal plan calendar**, leave **reviews and ratings**, and receive **notifications** for upcoming meals. Authentication supports both standard email/password and **Google OAuth** via Laravel Socialite.

### Core problem solved
- Reduces food waste by suggesting recipes from existing ingredients.
- Personalises recipe choice using BMI category and health goals.
- Provides one-stop meal planning — discover, plan, cook, review.

### Project root
- Path: `C:\laragon\www\cookease`
- Framework: Laravel 12 (PHP 8.2+)
- Local stack: Laragon (Apache/Nginx + MySQL + PHP)

---

## 2. Description of the Proposed Project

### i. Requirements Specification

#### Functional Requirements (FR)

| # | Requirement | Implemented in |
|---|-------------|----------------|
| FR1 | **User Registration & Authentication.** Users must be able to register with name, email, and password, log in, log out, and optionally authenticate via Google OAuth. | `Auth/*Controller`, `SocialAuthController`, `routes/auth.php` |
| FR2 | **Health Profile Management (BMI & Health Goals).** Users can submit age, gender, height, and weight; the system computes BMI and BMI category, and lets users select a health goal (lose / maintain / gain weight). | `BMIController`, `HealthGoalController`, `bmis` & `health_goals` tables |
| FR3 | **AI-Driven Recipe Generation.** Users submit a list of ingredients (with optional filters: cooking time, budget, dietary preferences). The system calls the Groq LLM API and returns 12 personalised Malaysian recipes that respect the user's BMI, health goal, and filters. | `IngredientController@process` |
| FR4 | **Recipe Browsing, Favouriting & Reviews.** Users can browse stored recipes, save/unsave them as favourites, and submit a 1–5 star rating with a written comment. | `RecipeController`, `FavoriteController`, `ReviewController` |
| FR5 | **Meal Plan Scheduling.** Users can attach saved/generated recipes to specific dates and meal types (breakfast, lunch, dinner, others), edit or delete planned meals, and view a weekly calendar. Past-dated plans are auto-pruned. | `MealPlanController`, `meal_plans` table |
| FR6 | **Notification System.** The application generates database-stored notifications (e.g. for today's meal plans) which can be marked as read by the user. | `NotificationController`, `notifications` table |
| FR7 | **Personalised Dashboard.** Authenticated users see a dashboard summarising their BMI, today's calorie intake, saved recipe count, recipes generated this week, weekly meal plan count, today's meals, recent favourites, recommended recipes, and AI-generated daily cooking tips. | `DashboardController@index` |
| FR8 | **Ingredient Usage Tracking.** Every ingredient submitted for generation is logged so that the UI can surface "Recently Used" and "Frequently Used" ingredient suggestions per user. | `IngredientController`, `ingredient_usage` table |

#### Non-Functional Requirements (NFR)

| # | Requirement | Notes |
|---|-------------|-------|
| NFR1 | **Performance / Responsiveness.** Pages must render under 3 seconds on broadband; AI recipe generation is asynchronous-feeling via session storage and a single redirect to the result page. Vite is used to build optimised JS/CSS bundles. |
| NFR2 | **Security.** Passwords are hashed via Laravel's `bcrypt` cast. CSRF protection is enabled on all POST/PUT/DELETE forms. Sessions and authentication are middleware-protected (`auth`, `verified`). API keys (Groq, Pixabay, Google OAuth) are stored only in `.env` and never committed. |
| NFR3 | **Usability / Accessibility.** Mobile-responsive UI built with Tailwind CSS; Alpine.js progressive enhancement; Tagify for ingredient input; Flatpickr/Pikaday date pickers; emoji-friendly category labels for quick visual scanning. |
| NFR4 | **Reliability / Fault Tolerance.** External AI calls are wrapped in try/catch with user-friendly error messages and Laravel `Log` entries; image fetches gracefully fall back to a local placeholder if Pixabay returns no result. |
| NFR5 | **Maintainability / Portability.** Clean MVC separation, PSR-4 autoloading, database-agnostic Eloquent models, environment-driven config, Dockerfile for containerised deployment. |

#### User Roles (minimum 3)

1. **Guest (Unauthenticated Visitor)**
   - Can view the landing/login/registration pages only.
   - Redirected to `/login` from the root route.
   - Cannot generate recipes, plan meals, or access the dashboard.

2. **Registered User (Standard Member)**
   - Has full access to all core features: dashboard, BMI form, health goals, recipe generation, browsing, favouriting, meal planning, reviews, and notifications.
   - Owns their own data (BMI history, favourites, meal plans, reviews) — enforced via `user_id` foreign keys with `onDelete('cascade')`.

3. **Administrator / Maintainer (System Operator)**
   - Operates the deployment: manages `.env` secrets (Groq, Pixabay, Google OAuth keys), runs migrations (`/run-migrations` route or `php artisan migrate`), monitors logs (`storage/logs/laravel.log`), and seeds the ingredient master list.
   - Currently implemented at the infrastructure level rather than as a distinct in-app role; future versions can promote this to an `is_admin` flag with a moderation panel for recipe content and review takedowns.

> *(Optional 4th role envisioned for future scope: **Nutritionist / Content Curator** — could review user-submitted recipes and validate AI output for dietary correctness.)*

---

### ii. System Architecture

#### Architecture Diagram

```
+-------------------------------------------------------------+
|                         CLIENT TIER                          |
|   Browser (Desktop / Mobile)                                 |
|   - Blade templates rendered server-side                     |
|   - Tailwind CSS, Alpine.js, Tagify, Flatpickr/Pikaday       |
|   - Vite-bundled JS/CSS assets                               |
+----------------------------+--------------------------------+
                             | HTTPS (forms, JSON over fetch/axios)
                             v
+-------------------------------------------------------------+
|                       APPLICATION TIER                       |
|                  Laravel 12 (PHP 8.2+)                       |
|                                                              |
|   Routing (routes/web.php, routes/auth.php)                  |
|         |                                                    |
|         v                                                    |
|   Middleware  -->  auth, verified, csrf, web                 |
|         |                                                    |
|         v                                                    |
|   Controllers                                                |
|   - DashboardController     - RecipeController               |
|   - BMIController           - FavoriteController             |
|   - HealthGoalController    - MealPlanController             |
|   - IngredientController    - ReviewController               |
|   - GenerateController      - NotificationController         |
|   - ProfileController       - SocialAuthController           |
|         |                                                    |
|         v                                                    |
|   Eloquent Models                                            |
|   User, Bmi, HealthGoal, Recipe, Favorite, MealPlan, Review  |
+--------+--------------------+-------------------+------------+
         |                    |                   |
         v                    v                   v
+----------------+   +-----------------+   +------------------+
|   DATA TIER    |   |  AI / EXTERNAL  |   |   AUTH PROVIDER  |
|   MySQL        |   |  Groq API       |   |   Google OAuth   |
|   (Laragon)    |   |  (Llama 4 Scout)|   |   (Socialite)    |
|                |   |  Pixabay API    |   |                  |
+----------------+   +-----------------+   +------------------+
```

#### Architectural Pattern

CookEase follows a classic **3-tier MVC architecture**:

1. **Presentation Tier (Client)** — Server-rendered Blade templates enhanced with Tailwind CSS for layout and Alpine.js for lightweight interactivity. Vite compiles assets. The client communicates with the server via standard HTTP form submissions and a small number of JSON endpoints (e.g. `/api/ingredients`).

2. **Application Tier (Server)** — Laravel 12 routes incoming requests through middleware (auth, CSRF, session) into Controllers. Controllers orchestrate Eloquent Models, session state (for transient AI-generated recipes), and outbound HTTP calls to the Groq LLM and Pixabay APIs. Authentication is provided by Laravel Breeze for email/password and Laravel Socialite for Google OAuth.

3. **Data Tier (Persistence + External Services)** — A MySQL database (managed locally by Laragon) stores all persistent records. Two external services act as data sources: **Groq** (for AI-generated recipes and cooking tips) and **Pixabay** (for stock food imagery).

#### Technologies Used

| Layer | Technology |
|-------|-----------|
| **Backend Framework** | Laravel 12 (PHP 8.2+) |
| **Auth Scaffolding** | Laravel Breeze, Laravel Socialite (Google) |
| **Frontend Templating** | Blade |
| **CSS Framework** | Tailwind CSS 3 + `@tailwindcss/forms` |
| **JS Runtime / UI** | Alpine.js, Axios |
| **Asset Bundler** | Vite 6 + `laravel-vite-plugin` |
| **UI Plugins** | Tagify (ingredient chips), Flatpickr / Pikaday (date pickers) |
| **Database** | MySQL (via Laragon, PDO MySQL extension) |
| **AI Service** | Groq API — model `meta-llama/llama-4-scout-17b-16e-instruct` |
| **Image Service** | Pixabay API |
| **Testing** | Pest 3, PHPUnit, Mockery |
| **Dev Tooling** | Laravel Pint, Laravel Pail, Laravel Sail, Concurrently |
| **Containerisation** | Docker (Dockerfile present) |

---

## 3. Application Flow

### 3.1 High-Level User Flow

```
[ Visitor ] ──► /login ──► (register or Google OAuth)
                  │
                  ▼
            [ Authenticated User ]
                  │
   ┌──────────────┼─────────────────────────────────┐
   ▼              ▼                                 ▼
Dashboard     BMI / Health Goal Setup       Generate Recipes
   │              │                                 │
   │              ▼                                 ▼
   │        Health profile saved            Submit ingredients + filters
   │              │                                 │
   │              └────────────┐                    ▼
   │                           │           Groq LLM returns 12 recipes
   │                           │                    │
   │                           ▼                    ▼
   └─────► Browse Recipes ◄── Favourites ◄──── View Result Grid
                  │                                 │
                  ▼                                 ▼
            Recipe Detail ──► Add to Meal Plan ──► Notifications
                  │                                 │
                  ▼                                 ▼
            Submit Review                 Mark notification as read
```

### 3.2 Recipe Generation Flow (Detailed)

1. User opens **`/generate`** (`GenerateController@showForm`) and sees recently/frequently used ingredients.
2. User adds ingredients via **Tagify**, picks optional filters (cuisine, diet), cooking time, budget, and submits.
3. **`IngredientController@process`** validates input, normalises ingredient names, inserts any new ones into `ingredients`, and logs each into `ingredient_usage` for that user.
4. The controller assembles a prompt that includes the user's **BMI value + category** and **health goal**, then calls the **Groq Chat Completions API**.
5. The JSON array of 12 recipes is parsed, each is enriched with a Pixabay image URL, and the full set is stored in the **session** (not the DB) under `generated_recipes`.
6. The user is redirected to **`/generate-result`** which renders the recipe cards.
7. When the user opens a specific recipe (`/recipe-detail/{index}?from=session`), the view shows full ingredients/instructions/grocery list, with buttons to **save**, **plan**, or **review** — each of those actions persists the recipe into the `recipes` table on first interaction.

### 3.3 Meal Planning Flow

1. From a recipe detail page, the user clicks **Add to Meal Plan**, picks a date and meal type.
2. `MealPlanController@store` (or `storeFromGenerated`) creates a `meal_plans` row.
3. The dashboard and `/meal-plan` calendar view query `meal_plans` for the current week.
4. On any visit to a recipe detail page, **past-dated meal plans are pruned automatically** for that user.
5. A scheduled or manual hit to `/send-meal-notifications` (`NotificationController`) generates database notifications for today's planned meals.

---

## 4. Database

### 4.1 Schema Overview

| Table | Purpose | Key columns |
|-------|---------|-------------|
| `users` | Auth + identity | `id`, `name`, `email` (unique), `password` (nullable, hashed), `email_verified_at`, `remember_token` |
| `password_reset_tokens` | Password reset flow | `email` (PK), `token`, `created_at` |
| `sessions` | Laravel session driver = database | `id` (PK), `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity` |
| `cache`, `cache_locks` | Cache driver tables | standard Laravel cache schema |
| `jobs`, `job_batches`, `failed_jobs` | Queue driver tables | standard Laravel queue schema |
| `bmis` | User health metrics + computed BMI | `user_id` (FK→users, cascade), `age`, `gender` (`male`/`female`), `height`, `weight`, `bmi_value`, `calorie_target` |
| `health_goals` | One per user | `user_id`, `goal` (e.g. `lose_weight`, `gain_weight`, `maintain_weight`) |
| `ingredients` | Master list of ingredient names | `id`, `name` |
| `ingredient_usage` | Per-user usage log for "recent / frequent" UI | `user_id`, `ingredient_id`, `used_at` |
| `recipes` | Persistent recipe records | `name`, `description`, `duration`, `servings`, `difficulty` (`easy`/`medium`/`hard`), `calories`, `image`, `ingredients` (JSON, nullable), `instructions` (JSON), `grocery_lists` (JSON, nullable) |
| `favorites` | M:N pivot user ↔ recipe | `user_id`, `recipe_id` (both cascade) |
| `meal_plans` | Scheduled meals per day/type | `user_id`, `recipe_id`, `date`, `meal_type` (`breakfast`/`lunch`/`dinner`/`others`) |
| `reviews` | User reviews on recipes | `user_id`, `recipe_id`, `rating` (1–5), `comment` |
| `notifications` | Laravel native notifications | UUID `id`, `type`, polymorphic `notifiable_type`+`notifiable_id`, `data` (JSON text), `read_at` |

### 4.2 Entity Relationships (ERD-Lite)

```
                                     users (1)
                                       │
        ┌──────────┬──────────┬────────┼────────┬───────────┬───────────┬────────────────┐
        │          │          │        │        │           │           │                │
        ▼          ▼          ▼        ▼        ▼           ▼           ▼                ▼
      bmis    health_goals  favorites  meal_plans  reviews  ingredient_usage  notifications  sessions
       1:1       1:1         M:N         1:N       1:N           1:N            1:N (poly)    1:N
                              │           │         │             │
                              ▼           ▼         ▼             ▼
                                    recipes (1)            ingredients (1)
                                       │                        │
                                       └─ 1:N ───── reviews     │
                                       └─ 1:N ──── meal_plans   │
                                       └─ M:N ──── favorites    │
                                                                │
                                                          ingredient_usage
```

Cardinalities:
- `User 1—1 Bmi` (latest record retrieved via `latestOfMany()`)
- `User 1—1 HealthGoal`
- `User 1—N MealPlan`, `Review`, `IngredientUsage`
- `User M—N Recipe` via `favorites`
- `Recipe 1—N MealPlan`, `Review`
- `Ingredient 1—N IngredientUsage`

### 4.3 Notable Schema Changes (Migration History)

- `2025_05_07_064059` — `users.password` made nullable (to allow Google OAuth-only accounts).
- `2025_05_25_114038` — `recipes.instructions` column type changed.
- `2025_05_27_110900` — `bmis.calorie_target` added.
- `2025_05_28` — `recipes.ingredients` and `recipes.grocery_lists` made nullable.
- `2025_05_31` — `reviews.comment` added; obsolete `review` column dropped.
- `2025_06_22` — `meal_plans.meal_type` enum widened to include `others`.

---

## 5. Project Structure (Quick Reference)

```
cookease/
├── app/
│   ├── Http/Controllers/   # All feature controllers (see §2.i FR mapping)
│   └── Models/             # User, Bmi, HealthGoal, Recipe, Favorite, MealPlan, Review
├── config/services.php     # Groq, Pixabay, Google OAuth keys (env-driven)
├── database/migrations/    # 20 migration files (auth + feature schema)
├── resources/views/        # Blade templates: dashboard, generate, recipe-detail, meal_plan, etc.
├── routes/
│   ├── web.php             # Main app routes (auth-protected group)
│   └── auth.php            # Breeze-generated auth routes
├── public/                 # Front controller, compiled assets, placeholder images
├── Dockerfile              # Container build recipe
├── vite.config.js          # Asset bundling config
└── tailwind.config.js      # Theme + content paths
```

### Required Environment Variables (`.env`)

```
APP_KEY=...
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=cookease
DB_USERNAME=root
DB_PASSWORD=

GROQ_API_KEY=...
PIXABAY_API_KEY=...

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://cookease.test/auth/google/callback
```

---

## 6. Local Development Commands

```bash
# Install deps
composer install
npm install

# Set up DB
php artisan migrate

# Run dev stack (server + queue + vite concurrently)
composer dev

# Standalone
php artisan serve
npm run dev

# Tests
php artisan test
```

---

## 7. Notes for AI Coding Assistants

- **Never commit** `.env` or any file containing the Groq / Pixabay / Google secrets.
- Recipe generation is **session-backed first**, then **DB-persisted on first user action** (favourite, plan, or review). When changing the generation flow, preserve this two-phase pattern — both the `?from=session` and the persistent `Recipe::find` paths in `routes/web.php` must keep working.
- BMI is **computed on the fly** via `Bmi::getBmiAttribute()` (height in cm, weight in kg). Do not duplicate the formula; reuse the accessor.
- Past meal plans are auto-pruned whenever a user opens a recipe detail page — keep this side-effect intact unless replacing it with a scheduled job.
- The `/run-migrations` web route exists for deployments without shell access; it is useful but should be removed or guarded in production.

---

## 8. The Adoption of DevOps

This section describes how CookEase is operated and shipped from the point of view of the DevOps engineer. It maps directly to the project's existing toolchain: Laravel 12, Vite, MySQL, and the existing `Dockerfile`.

### i. DevOps Modelling and Tools

#### DevOps Model Used

The project will transition from **manual development and deployment** to a **Continuous Integration and Continuous Delivery (CI/CD) Model** paired with a **Main Branch, Feature Branching, and Hotfix Branch** strategy. This model ensures that the four-person team can collaborate concurrently without overwriting each other's code, and it prioritises **automated testing** to guarantee that critical logic — such as recipe generation and health-goal calculations — remains intact during updates.

**Branching strategy (3 branch types):**
- **`main`** — the always-deployable trunk. Every commit on `main` is a candidate release; direct pushes are forbidden, all changes arrive via PR after a green pipeline.
- **`feature/*`** — short-lived branches for new functionality (e.g. `feature/meal-plan-export`, `feature/bmi-recalc`). Cut from `main`, merged back into `main` via PR, then deleted.
- **`hotfix/*`** — short-lived branches for urgent production fixes (e.g. `hotfix/groq-timeout`, `hotfix/login-csrf`). Cut from `main` at the currently-deployed SHA, merged back to `main`, fast-tracked through the pipeline, and deployed straight to production after the manual approval gate.

**Team composition (4 members):**
- **1 Lead Developer** — owns architecture, reviews and approves all PRs, manages release gates, authorises hotfix deployments.
- **3 Developers** — implement features on isolated feature branches, raise PRs, and respond to review feedback. Any team member may open a hotfix branch when an incident demands it.

**Why CI/CD + Feature Branching for CookEase:**
- Each developer works on a dedicated branch (e.g. `feature/meal-plan-export`, `feature/bmi-recalc`), eliminating merge conflicts on shared files like `routes/web.php` and the recipe controllers.
- Every push triggers an automated pipeline, so regressions in the AI prompt assembly, BMI accessor, or session-to-DB recipe persistence are caught before the change reaches `main`.
- Continuous Delivery means a green build on `main` produces a release-ready Docker image, gated by a manual approval before it goes to production users.

**Eight-phase DevOps loop applied to CookEase:**

```
   Plan ─► Code ─► Build ─► Test ─► Release ─► Deploy ─► Operate ─► Monitor
     ▲                                                                 │
     └─────────────────────────── Feedback ◄───────────────────────────┘
```

Key principles applied:
- **Feature Branching** — every change starts on a short-lived branch off `main`; `main` is always deployable.
- **Everything as code** — application code, infrastructure (Dockerfile, Compose, Terraform), pipeline definitions (`Jenkinsfile`), and database schema (Laravel migrations) all live in Git.
- **Shift-left quality** — static analysis (SonarQube), formatter, and unit/integration tests run *before* merge.
- **Immutable artefacts** — each commit on `main` produces a single tagged **Docker** image that is promoted unchanged through staging → production.
- **Automated rollback** — keep the previous image tag; `docker compose pull && up -d` reverts within seconds.

#### Toolchain (DevOps + Code Quality, merged)

| Category | Tool | Simple explanation |
|----------|------|--------------------|
| **Version Control** | **Git + GitHub** | Stores all code; webhook triggers Jenkins on every push. |
| **Branching Strategy** | **`main` + `feature/*` + `hotfix/*`** | `main` is always deployable; `feature/*` for new work; `hotfix/*` for urgent prod fixes. |
| **CI/CD Server** | **Jenkins** | Runs the four-stage pipeline defined in the repo's `Jenkinsfile`. |
| **Build / Dependencies** | **Composer** (PHP) + **npm** (JS/Vite) | Composer fetches PHP packages; npm fetches JS packages; Vite builds the front-end bundle. |
| **Container Runtime** | **Docker** + **Docker Compose** | Same image runs everywhere — laptop, CI, staging, production. No environment drift. |
| **Artifact Repository** | **Sonatype Nexus** | Stores every built Docker image as a versioned, deployable artifact. |
| **Infrastructure as Code** | **Terraform** | Defines servers, network, DNS, MySQL as `*.tf` files in Git. Reproducible environments. |
| **Configuration / Secrets** | **Docker Compose** + **Jenkins Credentials Store** | Compose templates the running stack; secrets injected at deploy time, never in source. |
| **Code Style — PHP** | **Laravel Pint** | Auto-formats PHP to PSR-12; pipeline fails if anyone forgets to run it. |
| **Static Analysis — PHP** | **Larastan** (PHPStan) | Catches type errors, undefined methods, and broken Eloquent relations before runtime. |
| **Lint — JS / CSS** | **ESLint** + **Prettier** | Catches JS bugs and enforces consistent formatting on Alpine.js and Blade scripts. |
| **Quality Gate (aggregator)** | **SonarQube** | Single dashboard scoring bugs, smells, security hotspots, coverage; blocks merges on regression. |
| **Testing** | **Pest 3** + **PHPUnit** + **Mockery** | Unit + integration + feature tests for controllers, models, and HTTP flows. |
| **Coverage** | **PCOV** + **Codecov** | Measures % of code hit by tests; PR comment shows delta vs. `main`. |
| **Security — Dependencies** | **`composer audit`** + **`npm audit`** + **Dependabot** | Flags CVEs in PHP and JS packages. |
| **Security — Container** | **Trivy** | Scans the built Docker image for known CVEs; fails on HIGH/CRITICAL. |
| **Pre-commit Guard** | **Husky** | Runs Pint and a quick test filter locally before the commit lands. |
| **Notifications** | **Slack** + **Email** | Pipeline outcome posted to `#cookease-ci` and emailed to the Lead Developer. |
| **Monitoring — Errors** | **Sentry** | Captures PHP exceptions and JS errors (Groq timeouts, JSON decode failures). |
| **Monitoring — Metrics** | **Prometheus + Grafana** | Tracks CPU/RAM, MySQL queries, queue depth. |
| **Monitoring — Logs** | **Grafana Loki** | Centralises `laravel.log` + Nginx access logs. |
| **Monitoring — Uptime** | **UptimeRobot** | Pings `/login`; alerts on-call if the app is unreachable. |

---

### ii. Continuous Integration (CI)

#### Setup

Continuous Integration (CI) begins on the developers' local machines using **Docker**.

**Isolated Local Development.** To prevent data collisions, each developer runs a local **MySQL container** alongside the application container, allowing them to safely test all functions in isolation without touching shared state.

**Integration and Testing of Code Changes.** When a developer completes a feature, they commit the code to a `feature/*` branch and push it to **GitHub**. Every push and every pull request targeting `main` triggers the **Jenkins pipeline** via a GitHub webhook. The pipeline runs the four stages below: it builds the application (**Composer** + **npm** + **Vite**), executes **unit and integration tests with Pest 3 / PHPUnit** against an ephemeral MySQL service container, and then runs **SonarQube** static analysis to flag bugs, code smells, and security hotspots. Failure at any stage blocks the merge.

The pipeline is defined in a `Jenkinsfile` at the repository root and runs inside a Docker agent so the build environment matches production exactly.

#### Four-Stage Jenkins Pipeline

```
┌────────────────┐   ┌────────────────┐   ┌─────────────────────┐   ┌──────────────────┐
│  STAGE 01      │   │  STAGE 02      │   │  STAGE 03           │   │  STAGE 04        │
│  Code Commit   │──►│  Automated     │──►│  Testing &          │──►│  Report &        │
│                │   │  Build         │   │  Analysis           │   │  Notify          │
└────────────────┘   └────────────────┘   └─────────────────────┘   └──────────────────┘
```

| Stage | What Happens | Tools |
|-------|-------------|-------|
| **01 — Code Commit** | Developers push changes to Git on their feature branch. The push fires a GitHub webhook that automatically triggers the Jenkins pipeline. No manual intervention required. | **Git + GitHub webhook + Jenkins** |
| **02 — Automated Build** | Jenkins resolves dependencies (**Composer** fetches PHP packages, **npm** fetches JS packages), builds the Vite asset bundle, runs `php artisan optimize`, and packages everything into a versioned Docker image artifact. A `composer ci` script in `composer.json` orchestrates the multi-step sequence so the pipeline runs a single command. | **Jenkins, Composer, npm, Docker** |
| **03 — Testing & Analysis** | Execution of **unit and integration tests** (Pest 3 / PHPUnit against an ephemeral MySQL service container) and **SonarQube static code analysis** (bugs, code smells, security hotspots, coverage trend, quality gate). Failure on either side blocks the pipeline. | **Pest 3, PHPUnit, SonarQube** |
| **04 — Report & Notify** | Feedback is sent to the team via **Slack** and **Email** with build status, test results, coverage delta, and the SonarQube quality-gate verdict. The Lead Developer reviews failures immediately; passing builds are surfaced in the PR check list. | **Jenkins Slack plugin, Email Extension plugin** |

#### How Code Changes Are Integrated and Tested

1. Developer creates a short-lived feature branch from `main`: `git switch -c feature/meal-plan-export`.
2. Local pre-commit hook runs **Laravel Pint** for formatting and a focused `vendor/bin/pest --filter=<scope>` (optional but recommended).
3. Developer pushes the branch and opens a **Pull Request** against `main`. The push triggers the four-stage Jenkins pipeline above.
4. **Branch protection** on `main` requires:
   - All four pipeline stages green.
   - SonarQube **Quality Gate = Passed** (no new bugs, no critical security hotspots, coverage ≥ 70%).
   - At least **1 approving review** from the Lead Developer (CODEOWNER).
   - The branch up-to-date with `main`.
5. On merge, the **CD pipeline** takes over (see §iii) and produces a release-ready Docker image.

#### Sample `Jenkinsfile` skeleton

```groovy
pipeline {
    agent { docker { image 'php:8.2-cli' } }

    triggers { githubPush() }

    environment {
        SONAR_TOKEN = credentials('sonarqube-token')
        SLACK_CHANNEL = '#cookease-ci'
    }

    stages {
        stage('01 — Code Commit') {
            steps {
                checkout scm
                echo "Triggered by push to ${env.BRANCH_NAME} @ ${env.GIT_COMMIT}"
            }
        }

        stage('02 — Automated Build') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                sh 'npm ci && npm run build'              // Vite asset bundle
                sh 'php artisan config:cache && php artisan route:cache'
                sh 'docker build -t cookease:${GIT_COMMIT} .'
            }
        }

        stage('03 — Testing & Analysis') {
            parallel {
                stage('Unit + Integration Tests') {
                    steps {
                        sh 'cp .env.example .env && php artisan key:generate'
                        sh 'php artisan migrate --force'
                        sh 'vendor/bin/pest --coverage --min=70'
                    }
                }
                stage('SonarQube Static Analysis') {
                    steps {
                        withSonarQubeEnv('SonarQube') {
                            sh 'sonar-scanner -Dsonar.login=$SONAR_TOKEN'
                        }
                        timeout(time: 5, unit: 'MINUTES') {
                            waitForQualityGate abortPipeline: true
                        }
                    }
                }
            }
        }

        stage('04 — Report & Notify') {
            steps {
                slackSend channel: env.SLACK_CHANNEL,
                          message: "Build #${env.BUILD_NUMBER} on ${env.BRANCH_NAME}: ${currentBuild.currentResult}"
                emailext to: 'lead@cookease.app',
                         subject: "CookEase CI ${currentBuild.currentResult} — ${env.BRANCH_NAME}",
                         body:    "See ${env.BUILD_URL} for details."
            }
        }
    }

    post {
        failure {
            slackSend channel: env.SLACK_CHANNEL, color: 'danger',
                      message: "❌ Pipeline FAILED on ${env.BRANCH_NAME} — investigate immediately."
        }
    }
}
```

---

### iii. Continuous Delivery (CD)

#### The Delivery Pipeline

Once a Pull Request (PR) passes all CI stages, the **Lead Developer** reviews the PR. Upon approval, the code is merged into `main`, which automatically triggers the delivery pipeline — the build is packaged as an immutable **Docker image**, pushed to **Nexus**, and **deployed to staging**.

The pipeline then **pauses for manual approval**: instead of pushing straight to live users, the Lead Developer clicks **"Promote"** in Jenkins after verifying the staging environment. Only then does the pipeline deploy the **same image** to production. This human gate exists because AI prompt outputs and recipe data can have subtle regressions that benefit from a real-world sanity check before reaching end users.

```
PR merged to main
      │
      ▼
┌──────────────────┐
│ Build Docker img │  ───►  push to nexus.cookease.app/cookease:<git-sha>
└────────┬─────────┘                                │
         │                                          │
         ▼                                          ▼
┌──────────────────┐                       also tag :staging
│ Deploy → STAGING │  ◄─── auto, every merge to main
└────────┬─────────┘
         │  smoke tests + manual QA + health checks
         ▼
┌──────────────────┐
│ Manual Approval  │  ◄─── Jenkins "Proceed" input gate (Lead Developer)
└────────┬─────────┘
         ▼
┌──────────────────┐
│ Deploy → PROD    │  ───►  retag :production, run migrations, swap container
└──────────────────┘
```

#### Why Docker is central to CD

- **Immutable artefact**: each green build produces one Docker image identified by the Git SHA. The same image is promoted unchanged from staging → production, so what was tested is exactly what users get.
- **Environment parity**: the project's existing `Dockerfile` (PHP-FPM 8.2 with `pdo_mysql`, `mbstring`, `zip`, `bcmath`, `gd`) runs identically on a developer laptop, on the Jenkins build agent, on staging, and in production.
- **Atomic rollback**: because every image is tagged and retained, reverting to a previous release is a single `docker compose pull cookease:<previous-sha> && docker compose up -d`.
- **Zero-downtime swap**: Compose starts the new container alongside the old one, Nginx switches upstream, and the old container drains and exits without dropping in-flight requests.

#### Environments

| Environment | URL (example) | Purpose | Data | Trigger |
|-------------|---------------|---------|------|---------|
| **Local Dev** | `http://cookease.test` | Day-to-day coding on feature branches | SQLite or local MySQL via Laragon | Manual (`composer dev`) |
| **CI** | ephemeral Jenkins agent | Automated four-stage pipeline | MySQL service container, throwaway | Every push / PR |
| **Staging** | `https://staging.cookease.app` | Pre-prod validation, QA, AI prompt regression | Anonymised copy of prod DB, **separate Groq + Pixabay test keys** | Auto on merge to `main` |
| **Production** | `https://cookease.app` | Live users | Real MySQL, real API keys | **Manual approval** by Lead Developer in Jenkins after staging passes |

#### Deployment mechanics

- A single image (`nexus.cookease.app/cookease:<sha>`) is promoted between environments — never rebuilt.
- The runtime command (already in the project's `Dockerfile`) runs `php artisan migrate --force` and the cache warm-up steps automatically on container start.
- **Zero-downtime swap**: Compose pulls the new image, starts it on a sibling container, Nginx reverse proxy switches upstream, old container drains and exits.
- **Rollback**: `docker compose pull cookease:<previous-sha> && docker compose up -d` — under one minute.
- Migration safety: any migration that drops/renames a column is a **two-step deploy** (add nullable → backfill → drop in next release) so the previous image still works during rollback.

#### Infrastructure as Code (IaC) — Standardising Environments with Terraform

**The Core Concept.** Managing and provisioning infrastructure through machine-readable configuration files instead of manual processes. This ensures **consistency** and **repeatability** across the SRS lifecycle (development → staging → production).

**Why IaC for CookEase.** The 4-person team cannot afford "snowflake" servers — environments where someone once SSH'd in and tweaked a config that nobody else knows about. With **Terraform**, every piece of infrastructure that runs CookEase is defined as code, versioned in Git, and reproducible by any team member with a single command.

**What Terraform provisions for CookEase:**

| Resource | What it defines | File |
|----------|-----------------|------|
| **Compute (VPS / VM)** | The host that runs the Docker stack — CPU/RAM size, OS image, SSH keys, firewall rules. | `infra/compute.tf` |
| **Networking** | VPC, subnets, security groups (open 80/443 inbound, 22 only from Lead's IP). | `infra/network.tf` |
| **DNS** | A-records for `cookease.app` and `staging.cookease.app` pointing at the load balancer. | `infra/dns.tf` |
| **Managed MySQL** | Database instance, version (MySQL 8), automated backups, connection allowlist. | `infra/database.tf` |
| **TLS Certificates** | Let's Encrypt certs via the ACME provider. | `infra/tls.tf` |
| **Object Storage** | Bucket for storing Vite asset uploads and recipe image cache (Pixabay fallbacks). | `infra/storage.tf` |

**Workflow.** Terraform lives in the same repo under `infra/`, gated behind its own pipeline:

```
Developer edits infra/*.tf  ──►  PR opened
                                    │
                                    ▼
                          terraform fmt + terraform validate
                                    │
                                    ▼
                          terraform plan (posted to PR comment)
                                    │
                                    ▼
                          Lead Developer reviews the plan
                                    │
                                    ▼
                          Merge to main  ──►  terraform apply (Jenkins)
```

**Outcome.** Staging and production are byte-for-byte equivalent — same MySQL version, same firewall rules, same DNS topology — so a regression seen in staging will reproduce in production, and vice versa. Spinning up a brand new environment (e.g. for a one-off load test) takes minutes, not days.

---

### iv. Automatic Build and Release

#### Build Process

The build is fully automated by **Jenkins** and produces a single immutable artefact: a Docker image.

```
git push  ──►  CI green  ──►  Jenkins build stage runs:
                                1. checkout @ commit SHA
                                2. composer install --no-dev --optimize-autoloader
                                3. npm ci && npm run build  (Vite asset bundle)
                                4. php artisan config:cache && route:cache
                                5. docker build -t nexus.cookease.app/cookease:<sha>
                                   (uses existing project Dockerfile)
                                6. docker push <both :sha and :main tags>
                                7. trivy image scan (fail on HIGH/CRITICAL)
                                8. cosign sign image (supply-chain integrity)
                                9. Slack + Email notification with build status
```

The Dockerfile already in the repo handles:
- PHP 8.2-FPM base image with all required extensions (`pdo_mysql`, `mbstring`, `zip`, `bcmath`, `gd`).
- Composer install with `--optimize-autoloader --no-dev`.
- `npm install && npm run build` to compile Vite assets.
- Storage permissions and final `php artisan` cache warm-up at container start.

#### Sample release tooling

| Step | Tool | Notes |
|------|------|-------|
| Build orchestration | **Jenkins** | Self-hosted, pipeline as code (`Jenkinsfile`), webhook-triggered, Nexus / Slack / Email plugins. |
| Build automation | **Composer scripts** (`composer.json` → `"scripts"`) | Native to Laravel; chains `composer install`, `npm ci`, `npm run build`, and `php artisan optimize` into a single command (e.g. `composer ci`). |
| Image build | **Docker Buildx** | Multi-arch (amd64/arm64) build cache. |
| Artifact repository / image registry | **Sonatype Nexus** (`nexus.cookease.app`) | Stored credentials in Jenkins; hosts the deployable Docker artifact for every build. |
| Image signing | **Sigstore Cosign** | Verifies provenance at deploy time. |
| Infrastructure provisioning | **Terraform** | Provisions VPS, network, DNS, MySQL, TLS — see §iii IaC. |
| Image scanning | **Trivy** | Fails the pipeline on critical CVEs. |
| Notifications | **Slack + Email** | Pipeline outcomes posted to `#cookease-ci` and emailed to the Lead Developer. |

#### Sample CD `Jenkinsfile` build/release skeleton

```groovy
pipeline {
    agent any
    triggers { githubPush() }   // fires on every merge to main

    environment {
        IMAGE = "nexus.cookease.app/cookease:${env.GIT_COMMIT}"
        REGISTRY_CREDS = credentials('nexus-token')
        STAGING_SSH    = credentials('staging-ssh-key')
        PROD_SSH       = credentials('prod-ssh-key')
    }

    stages {
        stage('Build & Push Image') {
            steps {
                sh 'docker build -t $IMAGE .'
                sh 'echo $REGISTRY_CREDS_PSW | docker login nexus.cookease.app -u $REGISTRY_CREDS_USR --password-stdin'
                sh 'docker push $IMAGE'
                sh 'trivy image --severity HIGH,CRITICAL --exit-code 1 $IMAGE'
                sh 'cosign sign --yes $IMAGE'
            }
        }

        stage('Deploy → Staging') {
            steps {
                sshagent(['staging-ssh-key']) {
                    sh '''
                        ssh deploy@staging.cookease.app \
                            "cd /opt/cookease && \
                             IMAGE_TAG=${GIT_COMMIT} docker compose pull && \
                             docker compose up -d && \
                             docker exec cookease-app php artisan migrate --force"
                    '''
                }
            }
        }

        stage('Manual Approval') {
            steps {
                input message: 'Promote this build to production?',
                      submitter: 'lead-programmer'
            }
        }

        stage('Deploy → Production') {
            steps {
                sshagent(['prod-ssh-key']) {
                    sh '''
                        ssh deploy@cookease.app \
                            "cd /opt/cookease && \
                             IMAGE_TAG=${GIT_COMMIT} docker compose pull && \
                             docker compose up -d && \
                             docker exec cookease-app php artisan migrate --force"
                    '''
                }
            }
        }
    }

    post {
        success { slackSend channel: '#cookease-ci', color: 'good',    message: "✅ Released ${env.IMAGE}" }
        failure { slackSend channel: '#cookease-ci', color: 'danger',  message: "❌ Release FAILED for ${env.IMAGE}" }
    }
}
```

---

### v. Pipeline Planning

#### Full DevOps Pipeline Diagram

```
┌──────────────┐
│   Developer  │
│  Local Dev   │  pint, pest, composer dev (on a feature branch)
└──────┬───────┘
       │ git push (feature branch)
       ▼
┌──────────────────────────────────────────────────────────────┐
│                     GitHub (origin)                           │
│   ┌───────────────────────────────────────────────────────┐   │
│   │  Pull Request opened against main                     │   │
│   └────────────────────────┬──────────────────────────────┘   │
└────────────────────────────┼──────────────────────────────────┘
                             │ webhook triggers Jenkins
                             ▼
┌──────────────────────────────────────────────────────────────┐
│                  CI Pipeline (Jenkinsfile)                    │
│                                                               │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌─────────┐ │
│  │ STAGE 01   │─►│ STAGE 02   │─►│ STAGE 03   │─►│ STAGE 04│ │
│  │ Code Commit│  │ Auto Build │  │ Test +     │  │ Report &│ │
│  │ (Git push) │  │ (Composer +│  │ SonarQube  │  │ Notify  │ │
│  │            │  │  npm +     │  │ analysis   │  │ (Slack +│ │
│  │            │  │  Vite +    │  │ (Pest +    │  │ Email)  │ │
│  │            │  │  Docker)   │  │  MySQL svc)│  │         │ │
│  └────────────┘  └────────────┘  └────────────┘  └─────────┘ │
│                          │                                    │
│                          ▼                                    │
│  All green? ──► Lead Developer approves PR ──► merge to main │
└──────────────────────────────────────────────────────────────┘
                             │ merge to main
                             ▼
┌──────────────────────────────────────────────────────────────┐
│                  CD Pipeline (Jenkinsfile)                    │
│                                                               │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐  │
│  │ Build Docker │─►│ Push to Nexus│─►│ Trivy + Cosign sign│  │
│  └──────────────┘  └──────────────┘  └─────────┬──────────┘  │
│                                                │              │
│                                                ▼              │
│             ┌────────────────────┐                            │
│             │  Deploy to STAGING │ (auto, SSH + compose pull) │
│             └─────────┬──────────┘                            │
│                       │                                       │
│                       ▼ smoke tests, /login health, AI ping   │
│             ┌────────────────────┐                            │
│             │  Manual Approval   │ (Jenkins input gate —      │
│             └─────────┬──────────┘  Lead Developer)          │
│                       ▼                                       │
│             ┌────────────────────┐                            │
│             │  Deploy to PROD    │  retag :production         │
│             │  + run migrate     │  zero-downtime container   │
│             └─────────┬──────────┘  swap                      │
└───────────────────────┼──────────────────────────────────────┘
                        │
                        │  (infrastructure provisioned by
                        │   Terraform — see §iii IaC)
                        ▼
┌──────────────────────────────────────────────────────────────┐
│                       Operate & Monitor                       │
│                                                               │
│   Sentry (errors)  •  Prometheus + Grafana (metrics)          │
│   Loki / Logtail (logs)  •  UptimeRobot (synthetic)           │
│                                                               │
│           Alerts ──► PagerDuty / Slack #cookease-ops          │
│                          │                                    │
│                          ▼                                    │
│                  Feedback to Plan phase                       │
└──────────────────────────────────────────────────────────────┘
```

#### Pipeline Steps — Build, Test, Deploy

| Phase | Step | Tool | Pass / Fail Criterion |
|-------|------|------|----------------------|
| **Source** | Checkout commit | Jenkins `checkout scm` | Repo cloned at SHA |
| **Build (deps)** | PHP deps | Composer | `composer install --no-dev --optimize-autoloader` exits 0 |
|  | JS deps + bundle | npm + Vite | `npm ci && npm run build` succeeds |
|  | Laravel optimisation | `php artisan` | `config:cache` + `route:cache` exit 0 |
| **Test — Static** | Code formatting | Laravel Pint | No drift vs. preset |
|  | Static analysis + Quality Gate | **SonarQube** | Gate = Passed (no new bugs / smells / hotspots) |
| **Test — Dynamic** | Migrations apply | `php artisan migrate` against MySQL service | Exit 0 |
|  | Unit + Integration tests | Pest 3 / PHPUnit | 100% pass, ≥70% coverage |
| **Test — Security** | Dep audit | `composer audit`, `npm audit` | No HIGH/CRITICAL |
|  | Image scan | Trivy | No HIGH/CRITICAL |
| **Build (image)** | Docker build | Buildx + project `Dockerfile` | Image produced |
|  | Push image | `docker push` to Nexus | Both `:sha` and `:staging` tags stored as deployable artifact |
|  | Sign image | Cosign | Signature verified |
| **Notify** | Pipeline outcome | **Slack + Email** (Jenkins plugins) | Message delivered to `#cookease-ci` and Lead's inbox |
| **Provision** | Infrastructure | **Terraform** | `terraform apply` clean diff |
| **Deploy — Staging** | SSH + compose pull/up | Jenkins `sshagent` | Container healthy |
|  | Migrate | `artisan migrate --force` | Exit 0 |
|  | Smoke test | curl `/login`, `/dashboard` | HTTP 200 / 302 |
| **Approval** | Reviewer click | Jenkins `input` step | Approved by Lead Developer |
| **Deploy — Prod** | SSH + compose pull/up | Jenkins `sshagent` | Container healthy |
|  | Migrate | `artisan migrate --force` | Exit 0 |
|  | Post-deploy verify | UptimeRobot probe + Sentry "no new error spike" | Quiet for 10 min |
| **Operate** | Continuous monitoring | Sentry / Prometheus / Loki | Alerts fire to Slack/PagerDuty |

---

### vi. Code Quality

> Code-quality **tools** are merged into the unified DevOps toolchain in §8.i (Pint, Larastan, ESLint, SonarQube, Pest, Trivy, etc.). This section covers the **policies** that govern how those tools are used.

#### Code Review Policy

1. **No direct pushes to `main`.** Branch protection enforced on GitHub.
2. Every change must arrive via **Pull Request**.
3. PR template requires:
   - Linked issue / ticket.
   - "What changed and why" section.
   - Manual test plan or note that automated tests cover it.
   - Migration impact (if any) and rollback plan.
4. **Minimum 1 approving review** from a `CODEOWNERS`-listed engineer; **2 reviewers** required for migrations, auth, or any change touching Groq/Pixabay credentials handling.
5. PR must show **all four Jenkins pipeline stages green** and **no SonarQube Quality Gate regressions** before merge.
6. Reviewer focus areas (review checklist):
   - Functional correctness & edge cases.
   - Security (no leaked secrets, validated input, CSRF intact, cascade FK behaviour).
   - Performance (N+1 queries, missing eager loads — common risk in `DashboardController`).
   - Tests added/updated.
   - Backwards-compat for the two-phase recipe persistence (session ↔ DB).
7. **Squash-merge** is the default merge style — keeps `main` history linear; commit body becomes part of the changelog.

#### Testing Policy

1. **Pyramid**: many unit tests, fewer feature/HTTP tests, a small handful of end-to-end smoke tests (curl on staging).
2. **Coverage floor**: ≥70% line coverage on `app/`, enforced by `vendor/bin/pest --min=70`. PRs that decrease coverage by >2% are blocked.
3. **Mandatory tests**:
   - Every new controller action has at least one feature test (HTTP request + DB assertion).
   - Every new Eloquent relationship has a unit test verifying the relation type.
   - Every new migration that changes existing columns has a test for the migrated state.
4. **External dependencies are faked**: Groq and Pixabay calls are intercepted with `Http::fake()` in tests so CI never hits the real APIs.
5. **Pre-deploy smoke test**: after staging deploy, the pipeline `curl`s `/login` (expects 200) and `/dashboard` (expects 302 redirect when unauthenticated) before opening the production approval gate.
6. **Post-deploy verification window**: 10-minute Sentry / UptimeRobot quiet period before the deploy is considered successful; otherwise auto-rollback to previous image tag.
