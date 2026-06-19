# Ecommerce Auth Service

Authentication and Authorization microservice for a distributed E-Commerce platform.

## Overview

The Auth Service is responsible for managing user authentication and authorization across the E-Commerce ecosystem.

### Features

* User Registration
* User Login
* User Logout
* OAuth2 Authentication
* JWT Access Tokens
* Refresh Tokens
* Role-Based Access Control (RBAC)
* Kafka Event Publishing
* Redis Caching
* Docker Support

---

## Tech Stack

| Component         | Technology       |
| ----------------- | ---------------- |
| Language          | PHP 8.5          |
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

### Register

```http
POST /api/register
```

Request:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123"
}
```

---

### Login

```http
POST /api/login
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
  "access_token": "jwt-token",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

---

### Logout

```http
POST /api/logout
```

---

### Current User

```http
GET /api/me
```

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

| Column | Type    |
| ------ | ------- |
| id     | bigint  |
| name   | varchar |

---

### permissions

| Column | Type    |
| ------ | ------- |
| id     | bigint  |
| name   | varchar |

---

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
docker-start.bat
```

**macOS/Linux:**
```bash
bash docker-start.sh
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
