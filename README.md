# Jedligram Backend

A RESTful API backend for the **Jedligram** application — a Reddit-like platform with threads, posts, nested comments, voting, and user profiles. Built with **Laravel 12**.

---

## Table of Contents

- [Tech Stack & Packages](#tech-stack--packages)
- [Project Setup](#project-setup)
- [Environment Variables](#environment-variables)
- [API Routes Reference](#api-routes-reference)
  - [Authentication](#authentication)
  - [Users](#users)
  - [Threads](#threads)
  - [Posts](#posts)
  - [Comments](#comments)
  - [Votes](#votes)
- [API Documentation (Scramble)](#api-documentation-scramble)
- [Running Tests](#running-tests)
- [Docker](#docker)

---

## Tech Stack & Packages

### Core
- **PHP ^8.2**
- **Laravel ^12.0**

### Production Packages

| Package | Purpose |
| --- | --- |
| [`laravel/sanctum`](https://github.com/laravel/sanctum) | Token-based API authentication. Issues and validates Bearer tokens for stateless SPA and API authentication. |
| [`laravel/scout`](https://github.com/laravel/scout) | Full-text search abstraction. Provides `?search=` query support on the `GET /api/threads` and `GET /api/users` endpoints. Configured with the `database` driver by default (no external service needed). |
| [`dedoc/scramble`](https://github.com/dedoc/scramble) | Automatic OpenAPI 3.1 documentation generation from code. Serves a live interactive API docs UI at `/docs/api`. No annotations required. |
| [`reliese/laravel`](https://github.com/reliese/laravel) | Model code generator. Used during development to scaffold Eloquent model boilerplate from the database schema. |
| [`staudenmeir/laravel-adjacency-list`](https://github.com/staudenmeir/laravel-adjacency-list) | Recursive tree relationships for Eloquent. Powers the nested comment system (replies to replies) using a `parent_id` self-referential structure with depth tracking. |

### Dev Packages

| Package | Purpose |
| --- | --- |
| [`pestphp/pest`](https://pestphp.com/) | Modern PHP testing framework with an expressive, minimal API. |
| [`pestphp/pest-plugin-laravel`](https://github.com/pestphp/pest-plugin-laravel) | Laravel-specific helpers for Pest (e.g. `actingAs`, `artisan`). |
| [`laravel/pail`](https://github.com/laravel/pail) | Real-time log streaming in the terminal during development. |
| [`laravel/pint`](https://github.com/laravel/pint) | Opinionated PHP code style fixer (PSR-12 based). |
| [`laravel/sail`](https://github.com/laravel/sail) | Docker-based local development environment. |
| [`nunomaduro/collision`](https://github.com/nunomaduro/collision) | Beautiful CLI error reporting for tests and Artisan commands. |

---

## Project Setup

### Prerequisites

- PHP >= 8.2 with extensions: `pdo`, `pdo_mysql` (or `pdo_sqlite`), `mbstring`, `xml`, `zip`
- Composer >= 2
- A database: **SQLite** (default, zero-config) or **MySQL**

### Quick Start

A `composer setup` script handles everything in one command:

```bash
composer setup
```

This script will:
1. Run `composer install`
2. Copy `.env.example` → `.env` (if `.env` doesn't exist yet)
3. Generate the `APP_KEY`
4. Run all database migrations

### Manual Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy and configure the environment file
cp .env.example .env

# 3. Generate the application encryption key
php artisan key:generate

# 4. Run database migrations
php artisan migrate

# 5. (Optional) Seed the database with test data
php artisan db:seed

# 6. Create the storage symlink (required for profile picture serving)
php artisan storage:link
```

### Development Server

```bash
composer dev
```

This starts two concurrent processes:
- **`php artisan serve`** — Laravel dev server on `http://localhost:8000`
- **`php artisan queue:listen`** — Background queue worker

---

## Environment Variables

Copy `.env.example` to `.env` and adjust the values below.

### Application

| Variable | Default | Description |
| --- | --- | --- |
| `APP_NAME` | `Laravel` | Application name used in notifications and the docs UI title. |
| `APP_ENV` | `local` | Environment mode (`local`, `production`). Affects CORS allowed origins and error reporting. |
| `APP_KEY` | *(empty)* | **Required.** 32-character base64 encryption key. Generate with `php artisan key:generate`. |
| `APP_DEBUG` | `true` | Show detailed error pages. **Set to `false` in production.** |
| `APP_URL` | `http://localhost` | Base URL of the application. Used for asset URL generation and CORS. |
| `API_VERSION` | `0.2` | API version string displayed in the Scramble API docs. |

### Frontend & CORS

| Variable | Default | Description |
| --- | --- | --- |
| `FRONTEND_URL` | `http://localhost:3000` | URL of the frontend SPA. In `production` mode, only this origin (and `APP_URL`) are allowed by CORS. In `local` mode all origins are allowed (`*`). |

### Database

| Variable | Default | Description |
| --- | --- | --- |
| `DB_CONNECTION` | `sqlite` | Database driver. Supported: `sqlite`, `mysql`. |
| `DB_HOST` | `127.0.0.1` | *(MySQL only)* Database server host. |
| `DB_PORT` | `3306` | *(MySQL only)* Database server port. |
| `DB_DATABASE` | `laravel` | Database name. For SQLite, this is the path to the `.sqlite` file. |
| `DB_USERNAME` | `root` | *(MySQL only)* Database username. |
| `DB_PASSWORD` | *(empty)* | *(MySQL only)* Database password. |

### Authentication (Sanctum)

| Variable | Default | Description |
| --- | --- | --- |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost,...` | Comma-separated list of domains that receive stateful (cookie-based) authentication. Used for SPA cookie auth. |

### Search (Scout)

| Variable | Default | Description |
| --- | --- | --- |
| `SCOUT_DRIVER` | `database` | Search engine driver. Options: `database` (SQL LIKE, no external service), `collection`, `algolia`, `meilisearch`, `typesense`. |

### Sysadmin Panel

| Variable | Default | Description |
| --- | --- | --- |
| `SYSADMIN_USERNAME` | `admin` | Username for the sysadmin panel, protected by the `SysadminAuth` middleware. |
| `SYSADMIN_PASSWORD` | *(bcrypt hash)* | Bcrypt-hashed password for the sysadmin panel. Generate a new hash with: `php artisan tinker` → `bcrypt('yourpassword')`. |

### Filesystem & Storage

| Variable | Default | Description |
| --- | --- | --- |
| `FILESYSTEM_DISK` | `local` | Default storage disk. Profile pictures are stored on the `public` disk (linked to `storage/app/public`). |

### Queue & Cache

| Variable | Default | Description |
| --- | --- | --- |
| `QUEUE_CONNECTION` | `database` | Queue driver. |
| `CACHE_STORE` | `database` | Cache store driver. |

### Mail

| Variable | Default | Description |
| --- | --- | --- |
| `MAIL_MAILER` | `log` | Mail driver. Use `log` in development to write emails to the log file instead of sending. |
| `MAIL_HOST` | `127.0.0.1` | SMTP host. |
| `MAIL_PORT` | `2525` | SMTP port. |
| `MAIL_USERNAME` | `null` | SMTP username. |
| `MAIL_PASSWORD` | `null` | SMTP password. |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Default sender email address. |

---

## API Routes Reference

All API routes are prefixed with `/api`. Routes marked with 🔒 require an `Authorization: Bearer <token>` header obtained from the login endpoint.

---

### Authentication

#### `POST /api/register`

Register a new user account.

**Request Body:**

| Field | Type | Rules |
| --- | --- | --- |
| `name` | string | Required, max 255 |
| `email` | string | Required, valid email, unique |
| `password` | string | Required, min 8, must be confirmed |
| `password_confirmation` | string | Required, must match `password` |

**Response `201`:** `UserResource`

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "image_url": "http://localhost/images/default_pfp.png"
}
```

---

#### `POST /api/login`

Authenticate a user and receive a Bearer token.

**Request Body:**

| Field | Type | Rules |
| --- | --- | --- |
| `email` | string | Required, valid email |
| `password` | string | Required, min 8 |

**Response `200`:**

```json
{
  "message": "Login successful",
  "access_token": "<sanctum_token>",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "image_url": "http://localhost/images/default_pfp.png"
  }
}
```

**Response `401`:** `{ "message": "Invalid credentials" }`

---

#### 🔒 `POST /api/logout`

Revoke the current Bearer token (invalidates the token server-side).

**Response `200`:** `{ "message": "Logged out successfully" }`

---

### Users

#### `GET /api/users`

List all users. Supports optional full-text search via Scout.

**Query Parameters:**

| Parameter | Description |
| --- | --- |
| `search` | *(optional)* Search users by name or email. |

**Response `200`:** Array of `UserResource`

---

#### `GET /api/users/{user}`

Get a single user's public profile by ID.

**Response `200`:** `UserResource`

---

#### `GET /api/users/{user}/threads`

Get all threads that a specific user is a member of.

**Response `200`:** Array of `ThreadResource`

---

#### 🔒 `PUT /api/users/{user}`

Update a user's profile. Authenticated user can only update their own profile (enforced by `UserPolicy`).

**Request Body:**

| Field | Type | Rules |
| --- | --- | --- |
| `name` | string | Required, max 255 |
| `email` | string | Required, valid email, unique (excluding current user) |

**Response `200`:** Updated `UserResource`

**Response `403`:** `{ "message": "You are not allowed to update this profile.", "error": "UNAUTHORIZED_ACCESS" }`

---

#### 🔒 `POST /api/users/profile-picture`

Upload or replace the authenticated user's profile picture. The old file is deleted from storage before saving the new one.

**Request Body (`multipart/form-data`):**

| Field | Type | Rules |
| --- | --- | --- |
| `image` | file | Required, image (jpeg/png/jpg/gif), max 2 MB |

**Response `200`:**

```json
{
  "message": "Profile picture updated successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "image_url": "http://localhost/storage/pfps/avatar.jpg"
  }
}
```

---

### Threads

#### `GET /api/threads`

List all threads with their member count. Supports optional full-text search.

**Query Parameters:**

| Parameter | Description |
| --- | --- |
| `search` | *(optional)* Search threads by name or description via Scout. |

**Response `200`:** Array of `ThreadResource`

```json
[
  {
    "id": 1,
    "name": "general",
    "description": "General discussion",
    "rules": null,
    "users_count": 42
  }
]
```

---

#### 🔒 `POST /api/threads`

Create a new thread. The creator is automatically joined as a member with **role ID 1** (owner).

**Request Body:**

| Field | Type | Rules |
| --- | --- | --- |
| `name` | string | Required, max 30 characters, unique across all threads |
| `description` | string | Optional, max 200 characters |

**Response `201`:** `ThreadResource`

---

#### 🔒 `GET /api/threads/{thread}`

Get a single thread's details, including its member count.

**Response `200`:** `ThreadResource`

---

#### 🔒 `POST /api/threads/{thread}/join`

Join a thread. The user is added as a member with **role ID 3**.

**Response `200`:** `{ "message": "You joined the thread" }`

**Response `409`:** `{ "message": "You are already a member of this thread" }`

---

#### 🔒 `DELETE /api/threads/{thread}/leave`

Leave a thread (removes the user from the thread's membership).

**Response `200`:** `{ "message": "You left the thread" }`

**Response `422`:** `{ "message": "You are not a member of this thread" }`

---

#### 🔒 `GET /api/threads/{thread}/posts`

Get all posts belonging to a thread. Supports sorting.

**Query Parameters:**

| Parameter | Description |
| --- | --- |
| `sort` | *(optional)* Pass `trending` to sort by trending score: `(upvotes - downvotes) / (hours_since_creation + 2)`. Defaults to newest first. |

**Response `200`:** Array of `PostResource`

```json
[
  {
    "id": 5,
    "content": "Hello world!",
    "user_id": 1,
    "thread_id": 2,
    "score": 14,
    "age": "3 hours ago"
  }
]
```

---

### Posts

#### 🔒 `GET /api/posts`

List all posts across all threads.

**Response `200`:** Array of `PostResource`

---

#### 🔒 `POST /api/threads/{thread}/post`

Create a new post inside a specific thread. **Requires the authenticated user to be a member of the thread** (enforced by `ThreadPolicy::userCheck` via `CreatePostRequest`).

**Request Body:**

| Field | Type | Rules |
| --- | --- | --- |
| `content` | string | Required |

**Response `201`:** `PostResource`

**Response `403`:** `{ "message": "You are not allowed to create a post in this thread.", "error": "UNAUTHORIZED_ACCESS" }`

---

#### 🔒 `GET /api/posts/{post}`

Get a single post by ID.

**Response `200`:** `PostResource`

---

### Comments

#### 🔒 `GET /api/posts/{post}/comments`

Get the comment tree for a post. Returns a nested tree up to **3 levels deep**. Each comment node includes its author and loaded replies.

**Response `200`:** Nested array of `CommentResource`

```json
[
  {
    "id": 1,
    "content": "Great post!",
    "depth": 0,
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "image_url": "http://localhost/images/default_pfp.png"
    },
    "age": "1 hour ago",
    "replies_count": 1,
    "replies": [
      {
        "id": 2,
        "content": "I agree!",
        "depth": 1,
        "user": {
          "id": 2,
          "name": "Jane Doe",
          "email": "jane@example.com",
          "image_url": "http://localhost/images/default_pfp.png"
        },
        "age": "45 minutes ago",
        "replies_count": 0,
        "replies": []
      }
    ]
  }
]
```

---

#### 🔒 `POST /api/posts/{post}/comments`

Create a new comment on a post. Can optionally be a reply by providing a `parent_id`. The `post_id` and `user_id` are injected server-side — do not include them in the body.

**Request Body:**

| Field | Type | Rules |
| --- | --- | --- |
| `content` | string | Required |
| `parent_id` | integer | Optional. Must be the ID of an existing comment **belonging to the same post**. |

**Response `201`:** `CommentResource`

---

#### 🔒 `GET /api/comments/{comment}/replies`

Get the full recursive subtree of replies for a specific comment (all descendants, with no depth limit).

**Response `200`:** Nested array of `CommentResource`

---

### Votes

#### 🔒 `POST /api/posts/{post}/vote`

Cast, change, or toggle a vote on a post. The vote behavior is as follows:

- **No existing vote** → creates a new vote.
- **Existing vote with the same `is_upvote`** → removes the vote (toggle off).
- **Existing vote with a different `is_upvote`** → flips the vote (upvote ↔ downvote).

The calculated vote score is exposed on every `PostResource` via the `score` field (`upvotes - downvotes`).

**Request Body:**

| Field | Type | Rules |
| --- | --- | --- |
| `is_upvote` | boolean | Required. `true` for upvote, `false` for downvote. |

**Response `201`:** Vote object `{ "id": 1, "post_id": 5, "user_id": 1, "is_upvote": true }`

**Response `204`:** *(no content)* — returned when the vote is toggled off (deleted).

---

## API Documentation (Scramble)

Interactive OpenAPI 3.1 documentation is auto-generated by **Dedoc Scramble** directly from the source code. It is accessible at:

```
GET /docs/api
```

The raw OpenAPI JSON specification is available at:

```
GET /docs/api.json
```

> Access to the docs is restricted in production by the `RestrictedDocsAccess` middleware.

---

## Running Tests

```bash
# Run all tests via the composer script (clears config cache first)
composer test

# Or directly with Pest / Artisan
php artisan test
./vendor/bin/pest
```

Tests are located in `tests/Feature/` and `tests/Unit/`.

---

## Docker

A `Dockerfile` is included for production deployments. It uses `php:8.5-fpm` as the base image with `pdo_mysql`, `mbstring`, `xml`, and `zip` extensions pre-installed.

```bash
# Build the image
docker build -t jedligram-backend .

# Run the container — provide env vars at runtime
docker run -p 8000:8000 \
  -e APP_KEY=your_key \
  -e APP_ENV=production \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=your_db_host \
  -e DB_DATABASE=jedligram \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=secret \
  -e FRONTEND_URL=https://yourfrontend.com \
  jedligram-backend
```

> **Note:** `php artisan config:cache` is intentionally **not** run at image build time, so environment variables can be injected at container runtime (e.g. via Kubernetes Secrets). A `deployment.yaml` is included for Kubernetes deployments.
