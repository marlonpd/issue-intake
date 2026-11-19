# Issue Intake

A small Laravel app for logging support issues. Each issue gets a short summary and a suggested next action, generated in a background job. Team members can comment on issues.

There is a JSON API and a simple web UI.

Stack: Laravel 13, PHP 8.3, SQLite, database queue.

## Running with Docker (recommended)

You only need Docker.

```bash
git clone https://github.com/marlonpd/issue-intake.git
cd issue-intake
docker compose up -d --wait
docker compose exec app php artisan db:seed
```

Open http://localhost:8000.

`docker compose up` starts three containers:

| Service     | What it does                                       |
|-------------|----------------------------------------------------|
| `app`       | Web server on port 8000                            |
| `worker`    | Queue worker (`php artisan queue:work`)            |
| `scheduler` | Runs scheduled tasks (`php artisan schedule:work`) |

On the first start, the `app` container installs Composer packages, creates `.env` and the SQLite file, and runs the migrations. `--wait` returns once that is done.

The containers run as user 1000 so the files they create belong to you. If your user ID is different, start them with:

```bash
HOST_UID=$(id -u) HOST_GID=$(id -g) docker compose up -d --wait
```

## Running without Docker

You need PHP 8.3 with the `pdo_sqlite` extension, and Composer.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

Then start these in three terminals:

```bash
php artisan serve        # web server
php artisan queue:work   # processes summary jobs
php artisan schedule:work  # runs the hourly overdue check
```

Without the queue worker, new issues stay at `summary_status: pending`.

## Database

```bash
php artisan migrate            # create tables
php artisan db:seed            # add 7 sample issues with comments
php artisan migrate:fresh --seed   # reset everything
```

With Docker, put `docker compose exec app` in front of each command.

The seeder adds issues that cover every priority, category and status. Seeding also queues a summary job for each issue, so the worker fills in their summaries.

## Tests

```bash
docker compose exec app php artisan test
# or, without Docker:
php artisan test
```

Tests use an in-memory SQLite database, so they don't touch your data.

## API

All endpoints are under `/api` and return JSON.

| Method | URL                         | Description                    |
|--------|-----------------------------|--------------------------------|
| GET    | `/api/issues`               | List issues (filter, sort, paginate) |
| POST   | `/api/issues`               | Create an issue                |
| GET    | `/api/issues/{id}`          | Show an issue with its comments |
| PATCH  | `/api/issues/{id}`          | Update an issue                |
| POST   | `/api/issues/{id}/comments` | Add a comment                  |

Allowed values:

- `priority`: `low`, `medium`, `high`
- `status`: `open`, `in_progress`, `resolved` (defaults to `open`)
- `category`: `billing`, `technical`, `account`, `feature_request`, `other`

### Create an issue

```bash
curl -X POST http://localhost:8000/api/issues \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{
    "title": "Customer charged twice",
    "description": "The customer sees two charges for the same order and wants a refund.",
    "priority": "high",
    "category": "billing"
  }'
```

Returns `201`. The summary is not ready yet:

```json
{
  "data": {
    "id": 8,
    "title": "Customer charged twice",
    "description": "The customer sees two charges for the same order and wants a refund.",
    "priority": "high",
    "category": "billing",
    "status": "open",
    "needs_attention": true,
    "summary": null,
    "suggested_next_action": null,
    "summary_status": "pending",
    "created_at": "2026-11-19T10:00:00.000000Z",
    "updated_at": "2026-11-19T10:00:00.000000Z"
  }
}
```

### Show an issue

```bash
curl http://localhost:8000/api/issues/8 -H "Accept: application/json"
```

After the worker runs, `summary_status` is `ready`:

```json
"summary": "High priority billing issue: The customer sees two charges for the same order and wants a refund.",
"suggested_next_action": "Check the payment provider for duplicate charges and refund the customer if confirmed.",
"summary_status": "ready",
"comments": []
```

### List and filter

```bash
curl "http://localhost:8000/api/issues?status=open&priority=high&category=billing" -H "Accept: application/json"
```

Filters can be combined. Other query options:

- `sort`: `created_at` (default), `updated_at`, `priority`
- `direction`: `desc` (default), `asc`
- `per_page`: 1 to 100 (default 15)
- `page`

### Update an issue

Send only the fields you want to change.

```bash
curl -X PATCH http://localhost:8000/api/issues/8 \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"status": "in_progress"}'
```

### Add a comment

```bash
curl -X POST http://localhost:8000/api/issues/8/comments \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"author_name": "Anna Reyes", "body": "Refund sent."}'
```

### Errors

Every error has a `message`. Validation errors also include `errors`.

```json
{
  "message": "The title field is required. (and 1 more error)",
  "errors": {
    "title": ["The title field is required."],
    "priority": ["The selected priority is invalid."]
  }
}
```

| Status | When                                         |
|--------|----------------------------------------------|
| 400    | The request body is not valid JSON           |
| 404    | The issue or URL does not exist              |
| 422    | Validation failed                            |
| 500    | Unexpected error (details go to the log only) |

Strings are trimmed. A field that only contains spaces counts as empty.

## How summaries are generated

1. When an issue is created, `GenerateIssueSummary` is queued and the API returns right away with `summary_status: pending`.
2. The worker runs the job and saves `summary`, `suggested_next_action` and `summary_status: ready`.
3. When `title`, `description`, `priority` or `category` changes, the status goes back to `pending` and the job runs again. The old summary stays until the new one is ready. Changing only `status` does not regenerate it.

The generator is rules-based (`app/Services/Summary/RuleBasedSummaryGenerator.php`):

- **Summary**: priority, category and the first sentence of the description.
- **Next action**: the first matching keyword rule (for example "refund", "password", "slow"). If nothing matches, it uses an action for the category. Feature requests always use the category action.

### Failed jobs

The job tries 3 times, waiting 10 seconds and then 30 seconds between attempts. If it still fails, `summary_status` is set to `failed`, and the job is saved in the `failed_jobs` table. The API keeps working.

```bash
php artisan queue:failed      # list failed jobs
php artisan queue:retry all   # retry them
```

## needs_attention

`needs_attention` is `true` when the issue is **not resolved** and is either:

- `high` priority, or
- overdue: created more than 3 days ago (`ISSUE_OVERDUE_DAYS` in `.env`).

It is recalculated:

- every time an issue is saved (in `Issue::booted()`), and
- every hour by `php artisan issues:flag-overdue`, which catches issues that go overdue without being edited. The `scheduler` container runs this.

## Web UI

- `/issues`: list with filters and sorting. Issues that need attention are highlighted.
- `/issues/{id}`: summary, description and comments, plus a comment form. The page refreshes by itself while the summary is pending.
- `/issues/create` and `/issues/{id}/edit`: the issue form.

The UI uses Blade and one CSS file. It has no JavaScript and no build step. It uses the same models and validation as the API.

## Architecture & Decisions

- **SQLite.** No setup, and enough for this project. All queries are standard Laravel, so switching to MySQL or PostgreSQL only means changing `.env`.
- **Database queue.** No Redis needed. Jobs survive restarts, and Laravel already has a `failed_jobs` table. It polls the database, which is slower than Redis under heavy load, but that's fine here.
- **Rules-based generator (Path B).** It's predictable, free, easy to test and doesn't need an API key. It implements `SummaryGenerator`, and `SUMMARY_DRIVER` in `.env` picks which one is used (`AppServiceProvider`). To add an LLM: write a class that implements `SummaryGenerator` and add one line to the `match` in `AppServiceProvider`. A test (`test_job_uses_the_bound_summary_generator`) already swaps in a fake generator this way.
- **Jobs are queued from model events** (`Issue::booted()`), not from controllers. The API, the web UI and the seeder all get the same behavior without repeating code. The downside is that saving an issue has a side effect, so all of that logic sits in one method.
- **`needs_attention` is a stored column**, not computed on read. That way it can be filtered and indexed. The scheduled command keeps it correct for issues nobody edits.
- **Enums** (`app/Enums`) hold the fixed values for priority, status, category and summary status. Validation, casts, the factories and the UI all use them.
- **No N+1 queries.** The show endpoint loads comments with one extra query, and the list uses `withCount`. Lazy loading is turned off outside production, and two tests check the query count.
- **No authentication.** It wasn't required. Next I would add Sanctum API tokens and use the logged-in user as the comment author.

Things I would add next: an LLM driver, push updates when a summary is ready (instead of polling), and optimistic locking on updates.

## Project layout

```
app/Enums/                    fixed values (priority, status, category, summary status)
app/Http/Controllers/         API controllers
app/Http/Controllers/Web/     web UI controllers
app/Http/Requests/            validation
app/Http/Resources/           JSON output
app/Jobs/GenerateIssueSummary.php
app/Models/                   Issue, Comment
app/Services/Summary/         SummaryGenerator interface + rules-based version
app/Console/Commands/         issues:flag-overdue
bootstrap/app.php             API error format
routes/api.php, routes/web.php, routes/console.php
```

## Troubleshooting

- **Changes don't show up in Docker:** Docker Desktop sometimes keeps an old copy of a changed file. Run `docker compose restart`.
- **Summaries stay `pending`:** the worker isn't running. Check `docker compose logs worker`.

## AI usage

I used Claude Code (Anthropic) while building this project.

- **What I used it for:** setting up the Laravel and Docker project, writing first versions of the models, controllers, job, rules engine, tests and this README, and checking the endpoints with curl and the UI in a browser.
- **What I decided and reviewed:** the step-by-step plan and commit order, running everything in Docker, adding a web UI, and the `needs_attention` rule (resolved issues are never flagged). I read every change before committing, ran the tests after each step, and changed things that didn't fit: the keyword rules, which missed words like "slowly", and Laravel's default seeder setting that turns off model events (which would have skipped `needs_attention` and the summary jobs).
