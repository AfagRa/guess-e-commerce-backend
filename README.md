Backend:
markdown# GUESS E-Commerce — Backend

Laravel REST API for the GUESS e-commerce clone project, built as a backend course final project.

## Tech Stack

- PHP 8.2 + Laravel 11
- Laravel Sanctum (token authentication)
- MySQL

## Getting Started
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env`:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=guess_db
DB_USERNAME=root
DB_PASSWORD=
FRONTEND_URL=http://localhost:5173

Then run:
```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

API runs at `http://localhost:8000`

## Features

- RESTful API with consistent JSON responses
- Token-based authentication via Sanctum
- Role-based access: user, admin, superadmin
- Product and category management with image upload
- Order, basket and wishlist management
- Database seeding from JSON source data

## Default Admin Accounts
Superadmin: afag@guess.com / admin1234
Admin:      admin1@guess.com / admin1234

## Frontend

Built to work with the React frontend running at `http://localhost:5173`.