# Ecommerce Auth Service

Authentication and Authorization microservice for a distributed E-Commerce platform.

## Overview

The Auth Service is responsible for managing user authentication and authorization across the E-Commerce ecosystem.

### Features

* User Registration (default **customer** role)
* User Login / Logout
* OAuth2 Authentication (Laravel Passport)
* JWT Access Tokens & Refresh Tokens
* Role-Based Access Control (admin, support, customer)
* Paginated user listing (admin & support)
* User search by email (admin & support)
* Cached user count endpoint (admin & support)
* Welcome email on registration (queued)
* Soft-delete user accounts
* OpenAPI documentation (Scramble)
* Docker Support

---

## Tech Stack

| Component         | Technology       |
| ----------------- | ---------------- |
| Language          | PHP 8.3+         |
| Framework         | Laravel          |
| Authentication    | Laravel Passport |
| Database          | MySQL            |
| Cache             | Redis            |
| Messaging         | Kafka            |
| Containerization  | Docker           |
| API Documentation | Swagger/OpenAPI  |

---

## Architecture

```text
                API Gateway
                      |
                      |
             +--------+--------+
             |                 |
             |                 |
             v                 v

      Ecommerce Auth Service

             |
      +------+------+
      |             |
      v             v

    MySQL         Redis

             |
             v

           Kafka
```

---

## Responsibilities

### Authentication

* Register users
* Login users
* Logout users
* Get Users Data
* Generate access tokens
* Refresh expired tokens

### Authorization

* Roles
* Permissions
* OAuth Scopes

### Event Publishing

The service publishes domain events to Kafka.

Examples:

* UserRegistered
* UserLoggedIn
* UserLoggedOut

---

## API Endpoints

All endpoints are versioned under `/api/v1`. Responses use a standard envelope:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {}
}
```

Protected routes require `Authorization: Bearer {access_token}`.

---

### Register

```http
POST /api/v1/auth/register
```

Request:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

New users are assigned the **customer** role by default and receive a **welcome email** (queued).

Optional `role` field: only an authenticated **admin** may register a user with `admin` or `support` roles. Public registration always uses `customer`.

---

### Login

```http
POST /api/v1/auth/login
```

Request:

```json
{
  "email": "john@example.com",
  "password": "secret123"
}
```

Response:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": { "id": 1, "name": "John Doe", "email": "john@example.com", "roles": [] },
    "token": {
      "access_token": "jwt-token",
      "refresh_token": "refresh-token",
      "token_type": "Bearer",
      "expires_in": 1296000
    }
  }
}
```

---

### Logout

```http
POST /api/v1/auth/logout
```

Requires authentication.

---

### Current User

```http
GET /api/v1/auth/me
```

Requires authentication. Returns the authenticated user profile with roles.

---

### List Users (paginated)

```http
GET /api/v1/users?page=1&per_page=15
```

Requires authentication. Restricted to **admin** and **support** roles.

Query parameters:

| Parameter  | Type    | Default | Description              |
| ---------- | ------- | ------- | ------------------------ |
| `page`     | integer | 1       | Page number              |
| `per_page` | integer | 15      | Items per page (max 100) |

---

### Search Users by Email

```http
GET /api/v1/users/search?email=john
```

Requires authentication. Restricted to **admin** and **support** roles.

Performs a partial, case-insensitive match on the email field.

---

### User Count

```http
GET /api/v1/users/count
```

Requires authentication. Restricted to **admin** and **support** roles.

Returns the total number of active (non-deleted) users. The count is cached for performance and automatically invalidated when users are created or soft-deleted.

---

### Delete User

```http
DELETE /api/v1/users/{user}
```

Requires authentication. Users may delete their own account; administrators may delete any account.

---

### List Roles

```http
GET /api/v1/roles
```

Requires authentication. Restricted to **admin** role only.

---

### Assign Role to User

```http
POST /api/v1/users/{user}/roles
```

Requires authentication. **Admin** and **support** staff may access this endpoint.

| Role being assigned | Who can assign        |
| ------------------- | --------------------- |
| `customer`          | Admin or support      |
| `admin`, `support`  | Admin only            |

Enforced by `EnsureAdminForNonCustomerRole` middleware and `UserPolicy::assignRole`.

Request:

```json
{
  "role": "support"
}
```

---

## Roles

| Slug       | Name          | Description                              |
| ---------- | ------------- | ---------------------------------------- |
| `admin`    | Administrator | Full administrative access               |
| `support`  | Support       | Read access to user management endpoints   |
| `customer` | Customer      | Default role for registered users        |

Staff-only endpoints (`/api/v1/users`, `/api/v1/users/search`, `/api/v1/users/count`) require the authenticated user to have the **admin** or **support** role.

---

## Email & Queues

On registration, a `UserRegistered` event dispatches a queued `SendWelcomeEmail` listener that sends a welcome message to the new user.

User create/delete events flush the cached user count via `FlushUserCountCache`.

Configure mail and queue in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

QUEUE_CONNECTION=redis
```

For local development with Docker, use `log` or `smtp` (Mailpit) for mail and `redis` or `sync` for queues.

---

## Authentication Flow

```text
Client
  |
  | Login Request
  |
  v

Auth Service
  |
  | Validate Credentials
  |
  v

Laravel Passport
  |
  | Generate JWT
  |
  v

Client
```

---

## Token Validation Flow

```text
Client
  |
  | Bearer Token
  |
  v

Product Service (Go)
  |
  | Verify JWT Signature
  |
  v

Authorized Request
```

---

## Database

### users

| Column     | Type      |
| ---------- | --------- |
| id         | bigint    |
| name       | varchar   |
| email      | varchar   |
| password   | varchar   |
| created_at | timestamp |

---

### roles

| Column      | Type      |
| ----------- | --------- |
| id          | bigint    |
| name        | varchar   |
| slug        | varchar   |
| description | text      |
| created_at  | timestamp |

---

### role_user

Pivot table linking users to roles.

---

## Testing

Run the test suite:

```bash
php artisan test
```

Or with Docker:

```bash
docker-compose exec app php artisan test
```

Tests cover role definitions, user repository queries, user management authorization (admin/support vs customer), and registration role assignment.

## Kafka Topics

### user.registered

```json
{
  "event": "UserRegistered",
  "user_id": 1,
  "email": "john@example.com"
}
```

### user.logged-in

```json
{
  "event": "UserLoggedIn",
  "user_id": 1
}
```

### user.logged-out

```json
{
  "event": "UserLoggedOut",
  "user_id": 1
}
```

---

## Environment Variables

```env
APP_NAME=AuthService

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306

REDIS_HOST=redis

KAFKA_BROKER=kafka:9092

PASSPORT_PRIVATE_KEY=
PASSPORT_PUBLIC_KEY=
```

---

## Running Locally

### Clone Repository

```bash
git clone https://github.com/WaelAlQawasmi/ecommerce-auth-service.git
cd ecommerce-auth-service
```

### Install Dependencies

```bash
composer install
npm install
```

### Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

### Run Migrations

```bash
php artisan migrate
```

### Build Frontend Assets

```bash
npm run build
```

### Start Application

```bash
php artisan serve
```

---

## Docker

### Quick Start (Recommended)

The fastest way to get the application running with MySQL, Redis, and Nginx:

**Windows:**
```bash
run-production.bat
```

**macOS/Linux:**
```bash
bash run-production.sh
```

Or run directly:

```bash
docker-compose up -d --build
```

### Access Application

- **Application**: http://localhost
- **Database**: MySQL on localhost:3306
- **Redis**: localhost:6379

### Default Credentials

```
MySQL User: auth_user
MySQL Password: authpassword123
MySQL Database: ecommerce_auth
MySQL Root Password: rootpassword123
```

### Essential Commands

```bash
# View logs
docker-compose logs -f app

# Run migrations
docker-compose exec app php artisan migrate

# Run seeders
docker-compose exec app php artisan db:seed

# Access Laravel Tinker
docker-compose exec app php artisan tinker

# Run tests
docker-compose exec app php artisan test

# Stop containers
docker-compose down
```

### Using Makefile (macOS/Linux)

```bash
make help              # Show all available commands
make up                # Start containers
make down              # Stop containers
make migrate           # Run migrations
make seed              # Seed database
make logs-app          # View app logs
```

### Documentation

- **[QUICKSTART.md](./QUICKSTART.md)** - Get up and running in minutes
- **[DOCKER.md](./DOCKER.md)** - Comprehensive Docker guide

The Docker setup includes:
- ✅ PHP 8.3 FPM
- ✅ MySQL 8.0 with persistent volumes
- ✅ Nginx with SSL support
- ✅ Redis 7 for caching
- ✅ Health checks
- ✅ Automatic migrations
- ✅ Development and production configurations

---

## Security

* OAuth2 Authentication
* JWT Access Tokens
* Password Hashing
* Token Expiration
* Role-Based Access Control
* API Rate Limiting

---

## Future Enhancements

* Multi-Factor Authentication (MFA)
* Single Sign-On (SSO)
* Social Login
* OpenID Connect
* Audit Logging
* Multi-Tenant Authentication

---

## Repository Structure

```text
ecommerce-auth-service
│
├── app
├── bootstrap
├── config
├── database
├── routes
├── tests
├── docker
├── docs
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## License

This project is part of a microservices-based E-Commerce platform created for learning, portfolio development, and distributed systems practice.
