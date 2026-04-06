# GUESS — E-Commerce Backend

A RESTful API built with Laravel, serving as the backend for the GUESS e-commerce
clone project. Developed as a backend course final project.

---

## Tech Stack

| Tool | Purpose |
|------|---------|
| PHP 8.2 + Laravel 11 | Server-side framework |
| Laravel Sanctum | Token-based authentication |
| MySQL | Relational database |
| Laragon | Local development environment |

---

## Features

- RESTful API with consistent `{ data, message }` JSON response format
- Token-based authentication via Laravel Sanctum
- Role-based access control across three levels: `user`, `admin`, `superadmin`
- Full product CRUD with multi-color image management
- Image upload from device stored to local filesystem
- Category management with parent/child hierarchy
- Order, basket and wishlist management per user
- Database seeding from JSON source data

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MySQL
- Laragon (Windows)

### Installation

```bash
cd C:/laragon/www/guess-backend

cp .env.example .env
php artisan key:generate
```

### Environment

Open `.env` and update the database section:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=guess_db
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173
```

### Database Setup

Create a database named `guess_db` in MySQL, then run:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### Development Server

```bash
php artisan serve
```

API runs at `http://localhost:8000`

---

## Authentication

All authenticated requests must include the token in the header:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

Tokens are issued on login and stored by the frontend in `localStorage`.

---

## Role System

```
user          Browse products, manage own basket, wishlist and orders
admin         All user permissions + manage products, categories and orders
superadmin    All admin permissions + manage users and roles
```

---

## Default Admin Accounts

Seeded automatically when running `php artisan db:seed`.

```
Superadmin    afag@guess.com       admin1234
Admin         admin1@guess.com     admin1234
Admin         admin2@guess.com     admin1234
```

---

## Useful Commands

```bash
# Reset database and reseed from scratch
php artisan migrate:fresh --seed

# List all registered API routes
php artisan route:list

# Clear application cache
php artisan config:clear && php artisan cache:clear

# Access database via CLI
php artisan tinker
```

---

## Frontend Repository

The React frontend that consumes this API:
[GUESS E-Commerce Frontend](https://github.com/AfagRa/guess-clone)