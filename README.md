# Book Borrowing System with AI-Driven Reader Engagement

A comprehensive library management system with mobile app and web admin dashboard, featuring AI-powered book recommendations and chatbot assistance.

## 🚀 Features

### Mobile Application (Flutter)
- **Dark Library Theme UI** - Modern, user-friendly interface
- **Book Discovery** - Browse popular books and categories
- **AI Recommendations** - Personalized book suggestions
- **Borrowing Management** - Track borrowed books and due dates
- **AI Chatbot** - Interactive assistant for book recommendations and help
- **User Profile** - Reading statistics and preferences
- **Bookmarks** - Save books for later reading

### Web Admin Dashboard (PHP)
- **Dark Theme Interface** - Consistent with mobile app design
- **Student Management** - View and manage student accounts
- **Book Catalog** - Add, edit, and manage book inventory
- **Borrowing Oversight** - Monitor all borrowing activities
- **Analytics** - Reading patterns and library statistics

### Backend API (PHP/MySQL)
- **RESTful APIs** - Comprehensive endpoints for all features
- **Database Schema** - Optimized for book management and recommendations
- **AI Integration** - Rule-based chatbot with personalized responses
- **Security** - Proper authentication and data validation

## 📁 Project Structure

```
Capstone-v1/
├── backend/                 # PHP Backend & APIs
│   ├── config.php          # Database configuration
│   ├── schema.sql          # Database schema
│   ├── sample_data.sql     # Sample data for testing
│   ├── api_books.php       # Book management API
│   ├── api_borrowing.php   # Borrowing system API
│   ├── api_profile.php     # User profile API
│   ├── api_chatbot.php     # AI chatbot API
│   ├── admin_dashboard.php # Web admin interface
│   └── login.php           # Authentication endpoints
├── mobile/                 # Flutter Mobile App
│   ├── lib/
│   │   ├── main.dart       # Login/Registration
│   │   └── landing_page.dart # Main app interface
│   └── pubspec.yaml        # Flutter dependencies
└── README.md              # This file
```

## 🛠️ Setup Instructions

### Prerequisites
- PHP 7.4+ with MySQL extension
- MySQL/MariaDB database
- Flutter SDK 3.0+
- Dart SDK 2.18+

### Backend Setup
1. **Database Configuration**
   ```bash
   # Update backend/config.php with your database credentials
   $DB_HOST = 'localhost';
   $DB_NAME = 'capstone_db';
   $DB_USER = 'your_username';
   $DB_PASS = 'your_password';
   ```

2. **Database Initialization**
   ```bash
   # Create database and run schema
   mysql -u root -p -e "CREATE DATABASE capstone_db;"
   mysql -u root -p capstone_db < backend/schema.sql
   mysql -u root -p capstone_db < backend/sample_data.sql
   ```

3. **Start PHP Server**
   ```bash
   cd backend
   php -S localhost:8080
   ```

### Mobile App Setup
1. **Install Dependencies**
   ```bash
   cd mobile
   flutter pub get
   ```

2. **Run the App**
   ```bash
   flutter run
   ```

## 🔧 API Endpoints

### Books API (`/api_books.php`)
- `GET ?action=categories` - Get all book categories
- `GET ?action=popular` - Get popular books
- `GET ?action=recommendations&student_id=X` - Get personalized recommendations
- `GET ?action=search&query=X` - Search books
- `POST action=bookmark` - Toggle bookmark
- `POST action=review` - Add/update book review

### Borrowing API (`/api_borrowing.php`)
- `GET ?action=active&student_id=X` - Get active borrowings
- `GET ?action=history&student_id=X` - Get borrowing history
- `GET ?action=overdue&student_id=X` - Get overdue books
- `POST action=borrow` - Borrow a book
- `POST action=return` - Return a book
- `POST action=renew` - Renew a book

### Profile API (`/api_profile.php`)
- `GET ?action=profile&student_id=X` - Get user profile
- `GET ?action=bookmarks&student_id=X` - Get bookmarked books
- `GET ?action=notifications&student_id=X` - Get notifications
- `POST action=update_profile` - Update user profile

### Chatbot API (`/api_chatbot.php`)
- `GET ?action=conversations&student_id=X` - Get chat conversations
- `GET ?action=messages&conversation_id=X` - Get chat messages
- `POST action=start_conversation` - Start new conversation
- `POST action=send_message` - Send message to AI

## 🎯 Key Features Implemented

### ✅ Database Schema
- **13 tables** supporting complete book borrowing system
- **Foreign key relationships** for data integrity
- **Indexes** for optimal performance
- **Sample data** for immediate testing

### ✅ API Endpoints
- **4 comprehensive APIs** covering all system functionality
- **RESTful design** with proper HTTP methods
- **Error handling** and validation
- **CORS support** for mobile app integration

### ✅ Mobile App Integration
- **Real-time data** from backend APIs
- **Loading states** and error handling
- **Dynamic UI** based on actual book data
- **Category-based color coding**
- **Rating display** and availability status

### ✅ AI Chatbot
- **Rule-based responses** for common queries
- **Personalized recommendations** based on user data
- **Borrowing status** information
- **Help and guidance** for library features

### ✅ Admin Dashboard
- **Dark theme** matching mobile app
- **Student management** interface
- **Book catalog** administration
- **Borrowing oversight** tools

## 🚀 Testing the System

### Sample Login Credentials
- **Student ID**: `C22-0044`
- **Admin**: `admin@library.com` / `admin123`

### Test Scenarios
1. **Mobile App**: Login → Browse books → View recommendations → Check borrowed books
2. **Admin Dashboard**: Login → View students → Manage books → Monitor borrowings
3. **API Testing**: Use test endpoints to verify database and API functionality

## 🔮 Future Enhancements
- Integration with external book APIs (Google Books, OpenLibrary)
- Advanced AI using machine learning models
- Push notifications for due dates
- QR code scanning for book checkout
- Reading analytics and insights
- Social features (book clubs, reviews sharing)

## 📊 Database Statistics
- **8 Categories** (Fiction, Science, Engineering, etc.)
- **8 Sample Books** with authors and ratings
- **4 Sample Students** with borrowing history
- **AI Recommendations** based on reading preferences
- **Notification System** for user engagement

---

**Project Status**: ✅ **Completed** - Full-featured Book Borrowing System with AI integration ready for deployment and testing.
