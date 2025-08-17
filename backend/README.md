# Book Borrowing System - Backend Documentation

## Overview
This is the backend system for the **Book Borrowing System with Data Driven Reader Engagement and Recommendation utilizing Retrieval-Augmented Generation (RAG)**.

## Directory Structure

```
backend/
├── config.php                 # Database configuration
├── composer.json              # PHP dependencies
├── composer.lock              # Dependency lock file
├── composer.phar              # Composer executable
├── vendor/                    # Third-party packages
│
├── api/                       # REST API endpoints
│   ├── api_books.php         # Book management API
│   ├── api_borrowing.php     # Borrowing system API
│   ├── api_chatbot.php       # AI chatbot integration
│   ├── api_engagement.php    # Reader engagement tracking
│   ├── api_profile.php       # User profile management
│   ├── api_recommendations.php # RAG-powered recommendations
│   ├── api_search.php        # Advanced search functionality
│   └── api_student.php       # Student data management
│
├── auth/                      # Authentication system
│   ├── login.php             # Student login
│   ├── register_student.php  # Student registration
│   ├── send_code.php         # Email verification code
│   └── verify_code.php       # Code verification
│
├── admin/                     # Admin dashboard
│   ├── admin_auth.php        # Admin authentication
│   ├── admin_dashboard.php   # Admin dashboard interface
│   ├── admin_login.html      # Admin login page
│   └── admin_logout.php      # Admin logout
│
├── database/                  # Database setup and management
│   ├── capstone.db           # SQLite database (development)
│   ├── schema.sql            # Database schema
│   ├── sample_data.sql       # Sample data for testing
│   ├── init_complete_db.php  # Complete database initialization
│   ├── setup_complete_db.php # Complete setup with data
│   ├── setup_production_db.php # Production database setup
│   ├── quick_db_setup.php    # Quick development setup
│   ├── enhance_database.php  # Add advanced features
│   ├── setup_student_data.php # Student data setup
│   └── quick_setup.php       # Quick setup utility
│
└── utils/                     # Utility scripts
    ├── database_setup.php    # Database setup utilities
    ├── add_cover_images.php  # Add book cover images
    └── update_books_with_covers.php # Update existing books with covers
```

## Core Features

### 1. **RAG-Enhanced Recommendation System**
- **File**: `api/api_recommendations.php`
- Personalized book recommendations using AI
- Content-based and collaborative filtering
- Semantic search capabilities
- Confidence scoring for recommendations

### 2. **Data-Driven Reader Engagement**
- **File**: `api/api_engagement.php`
- Reading streak tracking
- Achievement system with gamification
- Reading goals and progress monitoring
- Leaderboard functionality
- Reading analytics and insights

### 3. **Advanced Search System**
- **File**: `api/api_search.php`
- Multi-field search with filters
- Auto-suggestions and search history
- Semantic search integration
- Search analytics and personalization

### 4. **Book Management**
- **File**: `api/api_books.php`
- Complete book catalog management
- Category and author management
- Book availability tracking
- Cover image support

### 5. **Borrowing System**
- **File**: `api/api_borrowing.php`
- Book borrowing and return management
- Borrowing history tracking
- Due date management
- Overdue notifications

### 6. **User Authentication**
- **Directory**: `auth/`
- Secure student registration and login
- Email verification system
- Password hashing and security

### 7. **Admin Dashboard**
- **Directory**: `admin/`
- Administrative interface
- Student management
- System monitoring
- Analytics dashboard

## Database Schema

The system uses MySQL in production and SQLite for development. Key tables include:

- `student_records` - Student information
- `students` - Authentication data
- `books` - Book catalog
- `categories` - Book categories
- `authors` - Author information
- `borrowings` - Borrowing transactions
- `reading_preferences` - User preferences for RAG
- `book_embeddings` - AI embeddings for recommendations
- `reading_sessions` - Engagement tracking
- `search_history` - Search analytics

## Setup Instructions

### Development Setup
1. Run `php database/quick_db_setup.php` for quick development setup
2. Use SQLite database (automatically created)

### Production Setup
1. Configure MySQL database in `config.php`
2. Run `php database/setup_production_db.php`
3. Ensure proper file permissions

### Enhanced Features Setup
1. Run `php database/enhance_database.php` to add RAG features
2. Run `php utils/add_cover_images.php` for book covers

## API Endpoints

All API endpoints return JSON responses and support CORS.

### Books API (`/api/api_books.php`)
- `GET ?action=all` - Get all books
- `GET ?action=popular` - Get popular books
- `GET ?action=categories` - Get book categories

### Borrowing API (`/api/api_borrowing.php`)
- `POST` - Borrow a book
- `PUT` - Return a book
- `GET ?action=history&student_id=X` - Get borrowing history

### Recommendations API (`/api/api_recommendations.php`)
- `GET ?action=personalized&student_id=X` - Get personalized recommendations
- `GET ?action=similar&book_id=X` - Get similar books
- `POST` - Update reading preferences

### Search API (`/api/api_search.php`)
- `GET ?action=search&query=X` - Perform search
- `GET ?action=suggestions&query=X` - Get search suggestions
- `GET ?action=advanced` - Advanced search with filters

### Engagement API (`/api/api_engagement.php`)
- `GET ?action=reading_streak&student_id=X` - Get reading streak
- `GET ?action=achievements&student_id=X` - Get achievements
- `POST` - Track reading session

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- CORS headers for API security
- Session management for admin authentication
- Email verification for student registration

## Dependencies

- **PHPMailer** - Email functionality
- **MySQL/SQLite** - Database
- **PHP 7.4+** - Runtime environment

## Maintenance

### Regular Tasks
1. Monitor database performance
2. Clean up old verification codes
3. Update book recommendations
4. Backup database regularly

### Troubleshooting
- Check `config.php` for database connection issues
- Verify file permissions for uploads
- Monitor error logs for API issues
- Ensure email configuration for verification codes

## Recent Cleanup (2025-08-17)

### Files Removed
- All test files (`test_*.php`)
- Debug files (`debug_*.php`, `simple_*.php`)
- Duplicate files (`api_profile_fixed.php`, `working_login.php`)
- Obsolete setup files (`init_db.php`, `setup_db.php`, etc.)
- Verification utilities (`verify_api.php`, `check_student_data.php`)

### Files Organized
- API files moved to `api/` directory
- Authentication files moved to `auth/` directory
- Admin files moved to `admin/` directory
- Database files moved to `database/` directory
- Utility files moved to `utils/` directory

### Path Updates
- Updated all `require_once` statements to use relative paths
- Fixed config.php references in all moved files
- Maintained backward compatibility for existing integrations
