# Bahjah Courses API

A RESTful API for managing courses, built with **Laravel 12** and **Laravel Sanctum**.
Submitted as a technical assessment (Junior Backend Developer, Laravel).

## Features

- Token authentication with Laravel Sanctum: register, login, logout
- Courses CRUD (create, list, show, update, delete), all protected by `auth:sanctum`
- List endpoint with search by title, pagination, newest-first sorting and an optional `published` filter
- API Resources for every response (models are never returned directly)
- One unified JSON response format for success and errors (422, 401, 404, 405, 429, 500)
- Form Requests for validation, and a Service layer for business logic
- Rate limiting on `register` and `login` (10 requests per minute per IP)
- Database seeder: 10 sample courses and one test user
- No starter kit (no Breeze, Jetstream or Laravel UI)

## Requirements

- PHP 8.2 or higher with the extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`
- Composer 2
- MySQL 8 or MariaDB 10.6+

> **Why Laravel 12?** The development environment runs PHP 8.2, and Laravel 13 requires PHP 8.3 or higher. Laravel 12 is the newest release that supports PHP 8.2, and it covers everything this task needs.

## Installation

```bash
git clone <repository-url>
cd bahjah-courses-api
composer install
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell use `Copy-Item .env.example .env` instead of `cp`.

## Database setup

1. Create the database:

   ```sql
   CREATE DATABASE bahjah_courses CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Check the connection values in `.env` (the defaults in `.env.example` are shown). Change the username and password to match your MySQL server:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bahjah_courses
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. Run the migrations and the seeders:

   ```bash
   php artisan migrate --seed
   ```

   To rebuild everything from scratch:

   ```bash
   php artisan migrate:fresh --seed
   ```

   The seeders are not idempotent. Running `php artisan db:seed` twice on the same database adds the courses again and fails on the duplicate test user, so use `migrate:fresh --seed` to reset.

   The `cache` table created by the migrations is also used by the rate limiter.

4. Start the server:

   ```bash
   php artisan serve
   ```

   The API is available at `http://127.0.0.1:8000/api`.

## Test data

| Item | Value |
|---|---|
| Test user email | `test@example.com` |
| Test user password | `password` |
| Courses | 10 sample courses: 8 published and 2 unpublished. At least 6 titles contain "Laravel", so `?search=Laravel` always returns results |

## Authentication

Send the token returned by `register` or `login` on every protected request:

```
Authorization: Bearer <token>
Accept: application/json
```

Only `register` and `login` are public. Every courses endpoint and `logout` require a valid token.

## API endpoints

Base URL: `http://127.0.0.1:8000/api`

| Method | Endpoint | Auth | Description | Success |
|---|---|---|---|---|
| POST | `/register` | No | Create an account and receive a token | 201 |
| POST | `/login` | No | Log in and receive a token | 200 |
| POST | `/logout` | Yes | Delete the current access token | 200 |
| GET | `/courses` | Yes | List courses (search, pagination, filter) | 200 |
| POST | `/courses` | Yes | Create a course | 201 |
| GET | `/courses/{id}` | Yes | Show one course | 200 |
| PUT / PATCH | `/courses/{id}` | Yes | Update a course (partial updates are allowed) | 200 |
| DELETE | `/courses/{id}` | Yes | Delete a course | 200 |

### Request bodies

| Endpoint | Fields |
|---|---|
| `POST /register` | `name` (required, max 255), `email` (required, valid, unique), `password` (required, min 8) |
| `POST /login` | `email` (required), `password` (required) |
| `POST /courses` | `title` (required, max 255), `description` (optional), `price` (optional, 0 to 999999.99, default 0), `is_published` (optional boolean, default false) |
| `PUT / PATCH /courses/{id}` | Same fields as create, all optional; only the fields you send are changed |

### Query parameters for `GET /courses`

| Parameter | Description | Example |
|---|---|---|
| `page` | Page number | `?page=2` |
| `per_page` | Items per page (1 to 100, default 5) | `?per_page=10` |
| `search` | Case-insensitive match on the course title | `?search=Laravel` |
| `published` | `true` or `1` returns published courses, `false` or `0` returns unpublished ones | `?published=true` |

Results are always sorted by creation date, newest first (ties are broken by `id` so pagination stays stable). The parameters can be combined: `GET /api/courses?published=true&search=Laravel&page=1`.

## Response format

Every response has the same three keys.

Success:

```json
{
  "success": true,
  "message": "Course created successfully",
  "data": { }
}
```

Error:

```json
{
  "success": false,
  "message": "Validation Error",
  "data": null
}
```

Validation errors (422) also include an `errors` object so the client can see which field failed:

```json
{
  "success": false,
  "message": "Validation Error",
  "data": null,
  "errors": {
    "title": ["The title field is required."]
  }
}
```

Paginated list (`GET /courses`):

```json
{
  "success": true,
  "message": "Courses retrieved successfully",
  "data": {
    "items": [
      {
        "id": 10,
        "title": "Laravel for Beginners",
        "description": "Start building web applications with Laravel.",
        "price": 49.99,
        "is_published": true,
        "created_at": "2026-10-21T09:30:00.000000Z",
        "updated_at": "2026-10-21T09:30:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 5,
      "total": 10,
      "last_page": 2
    }
  }
}
```

Login and register return `data.user`, `data.token` and `data.token_type` (`Bearer`).

### Error handling

| Status | When | `message` |
|---|---|---|
| 401 | Missing, invalid or revoked token, or wrong login credentials | `Unauthenticated` or `Invalid credentials` |
| 404 | Unknown course or unknown route | `Resource not found` |
| 405 | Wrong HTTP method for a route | `Method Not Allowed` |
| 422 | Validation failed | `Validation Error` |
| 429 | Too many requests on `register` or `login` | `Too Many Requests` |
| 500 | Unexpected server error (details are logged, never returned) | `Server Error` |

All of them are returned as JSON in the same format, even when the request has no `Accept: application/json` header.

## Rate limiting

`register` and `login` share a limit of **10 requests per minute per IP address**. The 11th request in a minute returns `429` with a `Retry-After` header. Wait for that many seconds before trying again.

## Postman

Import `postman_collection.json` into Postman.

- The collection variable `base_url` defaults to `http://127.0.0.1:8000/api`.
- Running **Login** or **Register** stores the token in the `token` variable automatically, and all other requests use it.
- Running **Create course** stores the new id in `course_id`, which the show, update and delete requests use.
- Register uses a random email on each run, so you can send it repeatedly (keep the rate limit in mind).

## Project structure

```
app/
  Exceptions/ApiExceptionRenderer.php    Unified JSON error handling
  Http/
    Controllers/Api/                     AuthController, CourseController
    Requests/                            Form Requests (Auth, Course)
    Resources/                           UserResource, CourseResource
  Models/                                User, Course (search, published, latest-first scopes)
  Services/                              AuthService, CourseService
  Support/ApiResponse.php                Unified response builder
database/
  factories/                             UserFactory, CourseFactory
  migrations/                            users, cache, jobs, personal_access_tokens, courses
  seeders/                               DatabaseSeeder, CourseSeeder
routes/api.php                           API routes
```

## Code style

The project follows Laravel's Pint preset and uses `declare(strict_types=1)` in all application files.

```bash
vendor/bin/pint --test
```
