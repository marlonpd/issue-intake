# Issue Intake

Laravel app for support issues. Each issue gets a summary and a next action from a background job.

## Run with Docker

```bash
git clone https://github.com/marlonpd/issue-intake.git
cd issue-intake
docker compose up -d --wait
docker compose exec app php artisan db:seed
```

Open http://localhost:8000.

This starts the web server, the queue worker and the scheduler. Migrations run on start.

If your user ID is not 1000:

```bash
HOST_UID=$(id -u) HOST_GID=$(id -g) docker compose up -d --wait
```

## Run without Docker

Needs PHP 8.3 with `pdo_sqlite`, and Composer.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

Run each in its own terminal:

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
```

## Database

```bash
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed
```

With Docker, add `docker compose exec app` before each command.

## Tests

```bash
docker compose exec app php artisan test
```

## API

| Method | URL                         |
|--------|-----------------------------|
| GET    | `/api/issues`               |
| POST   | `/api/issues`               |
| GET    | `/api/issues/{id}`          |
| PATCH  | `/api/issues/{id}`          |
| POST   | `/api/issues/{id}/comments` |

Values:

- `priority`: `low`, `medium`, `high`
- `status`: `open`, `in_progress`, `resolved`
- `category`: `billing`, `technical`, `account`, `feature_request`, `other`

Create:

```bash
curl -X POST http://localhost:8000/api/issues \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"title": "Charged twice", "description": "Customer was charged twice and wants a refund.", "priority": "high", "category": "billing"}'
```

Returns `201` with `summary_status: pending`. The worker sets it to `ready`.

View:

```bash
curl http://localhost:8000/api/issues/1 -H "Accept: application/json"
```

List with filters:

```bash
curl "http://localhost:8000/api/issues?status=open&priority=high&category=billing" -H "Accept: application/json"
```

Also takes `sort` (`created_at`, `updated_at`, `priority`), `direction` (`asc`, `desc`), `per_page` and `page`.

Update:

```bash
curl -X PATCH http://localhost:8000/api/issues/1 \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"status": "in_progress"}'
```

Comment:

```bash
curl -X POST http://localhost:8000/api/issues/1/comments \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"author_name": "Anna", "body": "Refund sent."}'
```

Errors:

```json
{"message": "The title field is required.", "errors": {"title": ["The title field is required."]}}
```

`400` bad JSON, `404` not found, `422` invalid input, `500` server error. `errors` is only on `422`.

## Summaries

- Created on issue create, in a queued job.
- Regenerated when title, description, priority or category changes. Not on status change.
- Rules-based: priority + category + first sentence for the summary, keyword or category rules for the next action.
- Job retries 3 times. After that, `summary_status` is `failed`.

```bash
php artisan queue:failed
php artisan queue:retry all
```

## needs_attention

`true` when the issue is not resolved and is high priority or older than 3 days (`ISSUE_OVERDUE_DAYS`).

Updated on every save, and every hour by `php artisan issues:flag-overdue`.

## Web UI

`/issues`. List, filter, view, create, edit, comment.

## Architecture & Decisions

- **SQLite**: no setup. Change `.env` to use MySQL or PostgreSQL.
- **Database queue**: no Redis needed, failed jobs are kept in `failed_jobs`.
- **Rules engine**: free, predictable, easy to test. It sits behind `SummaryGenerator`. To add an LLM, write a class for that interface and add it to `AppServiceProvider`. Pick it with `SUMMARY_DRIVER`.
- **Jobs dispatched from model events**: API, UI and seeder all behave the same.
- **needs_attention is a column**: can be filtered and indexed.
- **No N+1**: comments are eager loaded and lazy loading is off outside production. Tests check the query count.
- **No auth**: not required. Next step would be Sanctum tokens.

## AI usage

Used Claude Code to scaffold the project and write first drafts of the code, tests and this README. I planned the steps, chose Docker, the web UI and the `needs_attention` rule, reviewed every change, ran the tests after each step and fixed what was wrong.
