# 🍳 Cookify — PHP Cookbook Web Application

A full-stack PHP web application for sharing, discovering, and rating recipes. Built as a final assignment for the **Web Programming** course.

**Author:** Vladimir Mijajlovic · Index: 160/25  
**GitHub:** [github.com/vladadev/cookify](https://github.com/vladadev/cookify)

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8+ · PDO · Prepared Statements |
| Database | MySQL 8 (XAMPP / phpMyAdmin) |
| Frontend | HTML5 · CSS3 · Vanilla JavaScript (Fetch API) |
| Email | PHPMailer 6.x · Gmail SMTP (STARTTLS port 587) |
| Images | PHP GD Library (thumbnail generation) |
| Server | Apache (XAMPP) |

---

## Features

### User Features
- Registration with **email activation** (PHPMailer + Gmail SMTP)
- Login with **account lockout** after 3 failed attempts in 5 minutes → lock warning email
- Browse recipes with **AJAX filtering, sorting, and pagination** (no page reload)
- Filter by category · Sort by title, rating, prep time, date
- View recipe detail with full ingredients list
- **Rate recipes** with 1–5 star system (one rating per user per recipe)
- **Leave comments** on recipes
- Upload recipe images (JPG/PNG/GIF, max 5MB) — GD creates thumbnail + keeps original
- Add, edit, and delete own recipes
- Dynamic ingredient selection via AJAX on recipe forms

### Admin Features
- Dashboard with stats: total users, recipes, comments, logins today
- **Page visit statistics** parsed from `logs/access.log`
- Full user management: create, change role, reset password, unlock, delete
- Manage all recipes, categories, ingredients, comments
- Add new ingredients that users can select when creating recipes

---

## Project Structure

```
cookify/
├── index.php                   ← Homepage (root)
├── database.sql                ← Schema + seed data
├── .env                        ← Credentials (gitignored)
│
├── config/
│   ├── config.php              ← Constants, reads .env
│   └── connection.php          ← get_db() PDO singleton
│
├── models/                     ← Database functions (PDO prepared statements)
│   ├── users.php
│   ├── recipes.php
│   ├── categories.php
│   ├── ingredients.php
│   ├── comments.php
│   └── ratings.php
│
├── views/                      ← All pages
│   ├── login.php
│   ├── register.php
│   ├── activate.php
│   ├── logout.php
│   ├── recipe.php
│   ├── add_recipe.php
│   ├── edit_recipe.php
│   ├── my_recipes.php
│   ├── author.php
│   │
│   ├── fixed/                  ← Layout partials
│   │   ├── head.php            ← DOCTYPE → <body>
│   │   ├── top-nav.php         ← <header> + <nav> + <main>
│   │   └── footer.php          ← </main> + <footer> + JS + </html>
│   │
│   └── admin/                  ← Admin panel (role-protected)
│       ├── index.php           ← Dashboard + access stats
│       ├── users.php
│       ├── recipes.php
│       ├── categories.php
│       ├── ingredients.php
│       └── comments.php
│
├── api/                        ← JSON endpoints for AJAX
│   ├── get_recipes.php         ← Filter / sort / paginate
│   └── get_ingredients.php     ← Ingredient list
│
├── includes/                   ← Utility functions
│   ├── auth.php                ← is_logged_in, require_login, require_admin…
│   ├── logger.php              ← log_access() → logs/access.log
│   ├── upload.php              ← upload_image() + GD thumbnail
│   └── mailer.php              ← PHPMailer wrapper functions
│
├── assets/
│   ├── css/style.css           ← All styles, responsive design
│   └── js/
│       ├── main.js             ← AJAX recipe grid, pagination, filters
│       └── recipe-form.js      ← Dynamic ingredient rows (AJAX)
│
├── uploads/
│   ├── original/               ← Full-size recipe images
│   └── thumbs/                 ← Thumbnails (max 400×300 px)
│
└── logs/
    └── access.log              ← Page visit log
```

---

## Database Design

7 tables with **6 one-to-many** and **1 many-to-many** relation:

```
users ──────────────────────────────────┐
  │ 1:n → recipes                       │
  │ 1:n → comments                      │
  │ 1:n → ratings                       │ (all 1:n from users)
                                        │
categories ─── 1:n ──► recipes          │
                           │            │
                    1:n ──► comments ◄──┘
                    1:n ──► ratings  ◄──┘
                    m:n ──► ingredients
                           (via recipe_ingredients bridge table)
```

| Table | Description |
|-------|-------------|
| `users` | Authentication, roles, lockout mechanism |
| `categories` | Recipe categories |
| `recipes` | Core entity — title, description, images, difficulty |
| `ingredients` | Ingredient catalog with unit of measure |
| `recipe_ingredients` | Bridge table (m:n) — stores quantity per ingredient per recipe |
| `comments` | User comments on recipes |
| `ratings` | 1–5 star ratings, UNIQUE(recipe_id, user_id) |

---

## Local Setup (XAMPP)

### 1. Clone the repository
```bash
git clone https://github.com/vladadev/cookify.git
cd cookify
```

### 2. Install PHP dependencies
```bash
composer install
```

### 3. Create the database
Open **phpMyAdmin** → Import → select `database.sql`

### 4. Configure credentials
```bash
cp config/config.example.php .env
```
Edit `.env`:
```ini
DB_HOST=localhost
DB_NAME=cookify
DB_USER=root
DB_PASS=

BASE_URL=http://localhost/cookify

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM=your@gmail.com
MAIL_FROM_NAME=Cookify
```

### 5. Enable PHP extensions (php.ini)
Make sure these are enabled in `C:\xampp\php\php.ini`:
```
extension=gd
extension=zip
extension=pdo_mysql
```
Restart Apache after changes.

### 6. Open the app
```
http://localhost/cookify/
```

### Default seed accounts
| Email | Password | Role |
|-------|----------|------|
| admin@cookify.com | password | admin |
| john@example.com | password | user |
| sarah@example.com | password | user |

---

## Security

- All database queries use **PDO prepared statements** (SQL injection prevention)
- Passwords hashed with **bcrypt** (`PASSWORD_BCRYPT`)
- File uploads validated by **MIME type** (finfo) + extension whitelist
- Session-based authentication with role checks on every protected page
- Account lockout after 3 failed logins within 5 minutes
- HTML output escaped with `htmlspecialchars()` (XSS prevention)
- Credentials stored in `.env` file (excluded from version control)

---

## Gmail App Password Setup

To enable email sending:
1. Enable 2-Factor Authentication on your Google account
2. Go to **Google Account → Security → App Passwords**
3. Generate a password for "Mail"
4. Use it as `MAIL_PASSWORD` in `.env`

---

*Built with PHP 8, MySQL, and vanilla JavaScript. No frameworks.*
