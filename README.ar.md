
# Bahjah Courses API

A RESTful API for managing courses with **role-based access control** and a **student approval workflow**, built with **Laravel 12** and **Laravel Sanctum**.

Submitted as a technical assessment (Junior Backend Developer, Laravel).

## Features

- Token authentication with Laravel Sanctum: register, login, logout
- **Roles:** `admin` and `student` (no role selection during registration)
- **Student approval workflow:** new users register as `student` / `pending` and cannot access courses until an admin approves them
- **Admin-only course management:** only admins can create, update, or delete courses
- **Student read access:** approved students can list and view **published courses only**
- List endpoint with search by title, pagination, newest-first sorting, and an optional `published` filter
- API Resources for every response (models are never returned directly)
- One unified JSON response format for success and errors (401, 403, 404, 405, 422, 429, 500)
- Form Requests for validation, and a Service layer for business logic
- Rate limiting on `register` and `login` (10 requests per minute per IP)
- Database seeder: 1 admin, 2 students (pending + approved), and 10 sample courses
- No starter kit (no Breeze, Jetstream or Laravel UI)

## Requirements

- PHP 8.2 or higher with the extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`
- Composer 2
- MySQL 8 or MariaDB 10.6+
- The `pdo_sqlite` extension, only needed to run the tests (see [Tests](#tests))

> **Why Laravel 12?** The development environment runs PHP 8.2, and Laravel 13 requires PHP 8.3 or higher. Laravel 12 is the newest release that supports PHP 8.2, and it covers everything this task needs.

## Installation

```bash
git clone https://github.com/amatallahfatma123-gif/bahjah-courses-api.git
cd bahjah-courses-api
composer install
cp .env.example .env
php artisan key:generate
