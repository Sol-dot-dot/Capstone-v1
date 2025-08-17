<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.html');
    exit;
}

require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Returns - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%);
            color: #ffffff;
            min-height: 100vh;
        }

        .header {
            background: rgba(22, 33, 62, 0.95);
            backdrop-filter: blur(10px);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-link {
            background: #3498db;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .section {
            background: rgba(22, 33, 62, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .section-header {
            background: rgba(15, 20, 25, 0.8);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.4rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .section-content {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #ffffff;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            background: rgba(15, 20, 25, 0.6);
            color: #ffffff;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 1rem;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .message.success {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            border: 1px solid #27ae60;
        }

        .message.error {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }

        .message.warning {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
            border: 1px solid #f1c40f;
        }

        .book-item {
            background: rgba(15, 20, 25, 0.6);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #3498db;
        }

        .book-item.overdue {
            border-left-color: #e74c3c;
        }

        .book-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .book-info h4 {
            color: #3498db;
            margin-bottom: 0.5rem;
        }

        .book-info p {
            color: #bdc3c7;
            margin-bottom: 0.25rem;
        }

        .overdue-info {
            color: #e74c3c;
            font-weight: bold;
        }

        .fine-amount {
            color: #f39c12;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .search-results {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📖 Book Returns Management</h1>
            <div class="nav-links">
                <a href="admin_dashboard_new.php" class="nav-link">🏠 Dashboard</a>
                <a href="student_borrowing.php" class="nav-link">📚 Borrowing</a>
                <a href="borrowings_management.php" class="nav-link">📋 All Borrowings</a>
                <a href="admin_logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Student Search Section -->
        <div class="section">
            <div class="section-header">
                👤 Find Student with Borrowed Books
            </div>
            <div class="section-content">
                <div class="form-group">
                    <label for="studentSearch">Student ID or Name</label>
                    <input type="text" id="studentSearch" placeholder="Enter student ID (e.g., C22-0044) or name">
                </div>
                <button class="btn btn-primary" onclick="searchStudentBooks()">🔍 Search Borrowed Books</button>
                
                <div id="message"></div>
            </div>
        </div>

        <!-- Borrowed Books Section -->
        <div class="section" id="borrowedBooksSection" style="display: none;">
            <div class="section-header">
                📚 Student's Borrowed Books
            </div>
            <div class="section-content">
                <div id="studentInfo" class="message" style="display: none;"></div>
                <div id="borrowedBooks" class="search-results">
                    <!-- Borrowed books will appear here -->
                </div>
            </div>
        </div>

        <!-- Quick Return by Book Code -->
        <div class="section">
            <div class="section-header">
                🔍 Quick Return by Book Code
            </div>
            <div class="section-content">
                <div class="form-group">
                    <label for="bookCodeSearch">Book Code</label>
                    <input type="text" id="bookCodeSearch" placeholder="Enter book code (e.g., BK-001-123)">
                </div>
                <button class="btn btn-primary" onclick="searchByBookCode()">📖 Find Borrowing</button>
                
                <div id="bookCodeResults">
                    <!-- Book code search results will appear here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        async function searchStudentBooks() {
            const searchTerm = document.getElementById('studentSearch').value.trim();
            if (!searchTerm) {
                showMessage('Please enter a student ID or name', 'error');
                return;
            }

            try {
                const response = await fetch('../api/api_admin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=search_student&search_term=${encodeURIComponent(searchTerm)}`
                });

                const data = await response.json();
                if (data.success) {
                    await loadStudentBorrowedBooks(data.student.student_id);
                    displayStudentInfo(data.student);
                } else {
                    showMessage(data.error, 'error');
                    hideSection('borrowedBooksSection');
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            }
        }

        async function loadStudentBorrowedBooks(studentId) {
            try {
                const response = await fetch(`../api/api_borrowing.php?action=get_borrowed_books&student_id=${studentId}`);
                const data = await response.json();
                
                if (data.success) {
                    displayBorrowedBooks(data.borrowed_books, studentId);
                    document.getElementById('borrowedBooksSection').style.display = 'block';
                } else {
                    showMessage('No borrowed books found for this student', 'warning');
                    hideSection('borrowedBooksSection');
                }
            } catch (error) {
                showMessage('Error loading borrowed books: ' + error.message, 'error');
            }
        }

        function displayStudentInfo(student) {
            const infoDiv = document.getElementById('studentInfo');
            infoDiv.innerHTML = `
                <strong>Student:</strong> ${student.first_name || ''} ${student.last_name || ''} (${student.student_id})<br>
                <strong>Email:</strong> ${student.email}
            `;
            infoDiv.style.display = 'block';
            infoDiv.className = 'message';
        }

        function displayBorrowedBooks(books, studentId) {
            const container = document.getElementById('borrowedBooks');
            
            if (books.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #7f8c8d;">No books currently borrowed</p>';
                return;
            }

            let html = '';
            books.forEach(book => {
                const isOverdue = book.days_overdue > 0;
                const dueDate = new Date(book.due_date);
                const fineAmount = book.fine_amount || 0;
                
                html += `
                    <div class="book-item ${isOverdue ? 'overdue' : ''}">
                        <div class="book-details">
                            <div class="book-info">
                                <h4>${book.title}</h4>
                                <p><strong>Code:</strong> ${book.book_code}</p>
                                <p><strong>Due Date:</strong> ${dueDate.toLocaleDateString()}</p>
                                ${isOverdue ? `<p class="overdue-info">Overdue by ${book.days_overdue} days</p>` : ''}
                                ${fineAmount > 0 ? `<p class="fine-amount">Fine: $${fineAmount.toFixed(2)}</p>` : ''}
                            </div>
                            <div>
                                <button class="btn btn-success" onclick="returnBook(${book.id}, '${studentId}', '${book.title}', ${fineAmount})">
                                    ✅ Return Book
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        async function searchByBookCode() {
            const bookCode = document.getElementById('bookCodeSearch').value.trim();
            if (!bookCode) {
                showMessage('Please enter a book code', 'error');
                return;
            }

            try {
                // First get book details
                const bookResponse = await fetch(`../api/api_borrowing.php?action=scan_book&book_code=${bookCode}`);
                const bookData = await bookResponse.json();
                
                if (!bookData.success) {
                    showMessage('Book not found with code: ' + bookCode, 'error');
                    return;
                }

                // Then find active borrowing for this book
                const borrowingResponse = await fetch('../api/api_admin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=find_borrowing_by_book&book_code=${encodeURIComponent(bookCode)}`
                });

                const borrowingData = await borrowingResponse.json();
                if (borrowingData.success && borrowingData.borrowing) {
                    displayBookCodeResult(bookData.book, borrowingData.borrowing);
                } else {
                    showMessage('This book is not currently borrowed', 'warning');
                    document.getElementById('bookCodeResults').innerHTML = '';
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            }
        }

        function displayBookCodeResult(book, borrowing) {
            const container = document.getElementById('bookCodeResults');
            const isOverdue = borrowing.days_overdue > 0;
            const dueDate = new Date(borrowing.due_date);
            const fineAmount = borrowing.fine_amount || 0;
            
            container.innerHTML = `
                <div class="book-item ${isOverdue ? 'overdue' : ''}" style="margin-top: 1rem;">
                    <div class="book-details">
                        <div class="book-info">
                            <h4>${book.title}</h4>
                            <p><strong>Borrowed by:</strong> ${borrowing.student_name} (${borrowing.student_id})</p>
                            <p><strong>Due Date:</strong> ${dueDate.toLocaleDateString()}</p>
                            ${isOverdue ? `<p class="overdue-info">Overdue by ${borrowing.days_overdue} days</p>` : ''}
                            ${fineAmount > 0 ? `<p class="fine-amount">Fine: $${fineAmount.toFixed(2)}</p>` : ''}
                        </div>
                        <div>
                            <button class="btn btn-success" onclick="returnBook(${borrowing.id}, '${borrowing.student_id}', '${book.title}', ${fineAmount})">
                                ✅ Return Book
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        async function returnBook(borrowingId, studentId, bookTitle, fineAmount) {
            const confirmMessage = fineAmount > 0 
                ? `Return "${bookTitle}"?\n\nFine to be applied: $${fineAmount.toFixed(2)}`
                : `Return "${bookTitle}"?`;
                
            if (!confirm(confirmMessage)) return;

            try {
                const response = await fetch('../api/api_borrowing.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=return_book&borrowing_id=${borrowingId}&student_id=${studentId}`
                });

                const data = await response.json();
                if (data.success) {
                    showMessage(`Book returned successfully!${data.fine_amount > 0 ? ` Fine applied: $${data.fine_amount.toFixed(2)}` : ''}`, 'success');
                    
                    // Refresh the current view
                    const searchTerm = document.getElementById('studentSearch').value.trim();
                    if (searchTerm) {
                        setTimeout(() => searchStudentBooks(), 1000);
                    }
                    
                    // Clear book code results
                    document.getElementById('bookCodeResults').innerHTML = '';
                    document.getElementById('bookCodeSearch').value = '';
                } else {
                    showMessage(data.error, 'error');
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            }
        }

        function hideSection(sectionId) {
            document.getElementById(sectionId).style.display = 'none';
        }

        function showMessage(message, type = 'success') {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${message}</div>`;
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 5000);
        }

        // Allow Enter key to search
        document.getElementById('studentSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchStudentBooks();
            }
        });

        document.getElementById('bookCodeSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchByBookCode();
            }
        });
    </script>
</body>
</html>
