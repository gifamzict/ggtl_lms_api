# GGTL Learning Management System - Laravel API

## 🚀 Backend API for GGTL LMS

This is the Laravel backend API for the GGTL Learning Management System. It provides RESTful endpoints for course management, user authentication, enrollments, and more.

## ✨ Features

- **Authentication** - Laravel Sanctum API authentication
- **Course Management** - CRUD operations for courses, lessons, categories
- **User Management** - Admin, instructor, and student roles
- **Enrollments** - Course enrollment and progress tracking
- **Payment Integration** - Paystack payment gateway
- **Reviews** - Course rating and review system
- **Admin Dashboard** - Statistics and analytics

## 📋 Requirements

- PHP 8.1 or higher
- Composer
- PostgreSQL (for production)
- SQLite (for development)

## ��️ Installation

### Local Development

```bash
# Clone the repository
git clone <repository-url>
cd ggtl_lms_api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

## 🌐 API Endpoints

### Public Endpoints
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `GET /api/courses` - Browse courses
- `GET /api/courses/{slug}` - Get course details
- `GET /api/categories` - Get all categories

### Authenticated Endpoints
- `GET /api/user` - Get authenticated user
- `GET /api/my-courses` - Get enrolled courses
- `POST /api/enroll` - Enroll in a course
- `GET /api/learn/{slug}` - Access course content

### Admin Endpoints
- `GET /api/admin/dashboard/stats` - Dashboard statistics
- `POST /api/admin/courses` - Create course
- `PUT /api/admin/courses/{id}` - Update course
- `DELETE /api/admin/courses/{id}` - Delete course

## 🚢 Deployment to Railway

This API is configured for Railway deployment with PostgreSQL.

### Environment Variables

```env
APP_NAME="GGTL API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-railway-domain.up.railway.app

DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

FRONTEND_URL=https://your-frontend-domain.com
CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com

# Add other environment variables as needed
```

## 📝 License

Private - GGTL LMS Project
