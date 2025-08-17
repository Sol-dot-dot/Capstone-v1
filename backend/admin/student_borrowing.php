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
    <title>Student Borrowing - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1A1A2E;
            color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .header {
            background: #16213E;
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            border-bottom: 1px solid #2a3f5f;
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
            background: #16213E;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid #2a3f5f;
        }

        .section-header {
            background: #0f1419;
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.4rem;
            font-weight: 700;
            border-bottom: 1px solid #2a3f5f;
        }

        .section-content {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
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

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #2a3f5f;
            border-radius: 8px;
            background: #0f1419;
            color: #ffffff;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
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

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .student-info {
            background: #0f1419;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .book-item {
            background: #0f1419;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .eligibility-card {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .eligible {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
        }

        .not-eligible {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
            color: #3498db;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-item {
            background: #0f1419;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #bdc3c7;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📚 Student Borrowing System</h1>
            <div class="nav-links">
                <a href="admin_dashboard.php" class="nav-link">🏠 Dashboard</a>
                <a href="books_management.php" class="nav-link">📚 Books</a>
                <a href="borrowings_management.php" class="nav-link">📋 Borrowings</a>
                <a href="admin_logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Student Search Section -->
        <div class="section">
            <div class="section-header">
                👤 Find Student
            </div>
            <div class="section-content">
                <div class="form-group">
                    <label for="studentSearch">Student ID or Name</label>
                    <input type="text" id="studentSearch" placeholder="Enter student ID (e.g., C22-0044) or name">
                </div>
                <button class="btn btn-primary" onclick="searchStudent()">🔍 Search Student</button>
                
                <div id="studentInfo" style="display: none;">
                    <div class="student-info">
                        <h3 id="studentName">Student Name</h3>
                        <p id="studentDetails">Student Details</p>
                        <div class="stats-row" id="studentStats">
                            <!-- Student borrowing stats will be populated here -->
                        </div>
                        <div id="eligibilityStatus" class="eligibility-card">
                            <!-- Eligibility status will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Book Borrowing Section -->
        <div class="section" id="borrowingSection" style="display: none;">
            <div class="section-header">
                📖 Process Book Borrowing
            </div>
            <div class="section-content">
                <div id="message"></div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="bookCode">Book Code</label>
                        <input type="text" id="bookCode" placeholder="Enter book code (e.g., BK-001-123)">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary" onclick="scanBook()">📚 Add Book</button>
                    </div>
                </div>

                <div id="scannedBooks">
                    <!-- Scanned books will appear here -->
                </div>

                <div id="borrowActions" style="display: none;">
                    <button class="btn btn-success" onclick="processBorrowing()">✅ Process Borrowing</button>
                    <button class="btn btn-danger" onclick="clearBooks()">🗑️ Clear All</button>
                </div>
            </div>
        </div>

        <!-- Current Borrowed Books Section -->
        <div class="section" id="currentBooksSection" style="display: none;">
            <div class="section-header">
                📋 Student's Current Books
            </div>
            <div class="section-content">
                <div id="currentBooks">
                    <!-- Current borrowed books will appear here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStudent = null;
        let scannedBooks = [];

        async function searchStudent() {
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
                    currentStudent = data.student;
                    displayStudentInfo(data.student, data.eligibility);
                    loadCurrentBooks(data.student.student_id);
                } else {
                    showMessage(data.error, 'error');
                    hideStudentSections();
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            }
        }

        function displayStudentInfo(student, eligibility) {
            document.getElementById('studentName').textContent = 
                `${student.first_name || ''} ${student.last_name || ''}`.trim() || 'Unknown Name';
            
            document.getElementById('studentDetails').innerHTML = 
                `<strong>ID:</strong> ${student.student_id}<br><strong>Email:</strong> ${student.email}`;

            // Display stats
            const statsHtml = `
                <div class="stat-item">
                    <div class="stat-value">${eligibility.current_books}</div>
                    <div class="stat-label">Current Books</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${eligibility.max_concurrent}</div>
                    <div class="stat-label">Max Allowed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${eligibility.semester_total}</div>
                    <div class="stat-label">This Semester</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">$${eligibility.pending_fines.toFixed(2)}</div>
                    <div class="stat-label">Pending Fines</div>
                </div>
            `;
            document.getElementById('studentStats').innerHTML = statsHtml;

            // Display eligibility
            const eligibilityDiv = document.getElementById('eligibilityStatus');
            eligibilityDiv.className = `eligibility-card ${eligibility.can_borrow ? 'eligible' : 'not-eligible'}`;
            
            let eligibilityHtml = `
                <h4>${eligibility.can_borrow ? '✅ Eligible to Borrow' : '❌ Not Eligible'}</h4>
            `;
            
            if (eligibility.reasons.length > 0) {
                eligibilityHtml += '<ul>';
                eligibility.reasons.forEach(reason => {
                    eligibilityHtml += `<li>${reason}</li>`;
                });
                eligibilityHtml += '</ul>';
            }
            
            eligibilityDiv.innerHTML = eligibilityHtml;

            // Show sections
            document.getElementById('studentInfo').style.display = 'block';
            document.getElementById('borrowingSection').style.display = 'block';
            document.getElementById('currentBooksSection').style.display = 'block';
        }

        async function scanBook() {
            const bookCode = document.getElementById('bookCode').value.trim();
            if (!bookCode) {
                showMessage('Please enter a book code', 'error');
                return;
            }

            if (scannedBooks.length >= 3) {
                showMessage('Maximum 3 books can be borrowed at once', 'error');
                return;
            }

            if (scannedBooks.some(book => book.book_code === bookCode)) {
                showMessage('Book already added', 'error');
                return;
            }

            try {
                const response = await fetch(`../api/api_borrowing.php?action=scan_book&book_code=${bookCode}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.book.is_available) {
                        scannedBooks.push(data.book);
                        document.getElementById('bookCode').value = '';
                        displayScannedBooks();
                        showMessage('Book added successfully', 'success');
                    } else {
                        showMessage('Book is not available for borrowing', 'error');
                    }
                } else {
                    showMessage(data.error, 'error');
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            }
        }

        function displayScannedBooks() {
            const container = document.getElementById('scannedBooks');
            
            if (scannedBooks.length === 0) {
                container.innerHTML = '';
                document.getElementById('borrowActions').style.display = 'none';
                return;
            }

            let html = '<h4>Books to Borrow:</h4>';
            scannedBooks.forEach((book, index) => {
                html += `
                    <div class="book-item">
                        <div>
                            <strong>${book.title}</strong><br>
                            <small>Code: ${book.book_code} | Author: ${book.author_name || 'Unknown'}</small>
                        </div>
                        <button class="btn btn-danger" onclick="removeBook(${index})">Remove</button>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            document.getElementById('borrowActions').style.display = 'block';
        }

        function removeBook(index) {
            scannedBooks.splice(index, 1);
            displayScannedBooks();
        }

        function clearBooks() {
            scannedBooks = [];
            displayScannedBooks();
        }

        async function processBorrowing() {
            if (!currentStudent) {
                showMessage('Please select a student first', 'error');
                return;
            }

            if (scannedBooks.length === 0) {
                showMessage('Please add at least one book', 'error');
                return;
            }

            try {
                const bookCodes = scannedBooks.map(book => book.book_code);
                
                const response = await fetch('../api/api_borrowing.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=borrow_books&student_id=${currentStudent.student_id}&book_codes=${JSON.stringify(bookCodes)}`
                });

                const data = await response.json();
                if (data.success) {
                    showMessage(`Successfully processed borrowing of ${scannedBooks.length} book(s)!`, 'success');
                    clearBooks();
                    // Refresh student info and current books
                    searchStudent();
                } else {
                    showMessage(data.error, 'error');
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            }
        }

        async function loadCurrentBooks(studentId) {
            try {
                const response = await fetch(`../api/api_borrowing.php?action=get_borrowed_books&student_id=${studentId}`);
                const data = await response.json();
                
                if (data.success) {
                    displayCurrentBooks(data.borrowed_books);
                }
            } catch (error) {
                console.error('Error loading current books:', error);
            }
        }

        function displayCurrentBooks(books) {
            const container = document.getElementById('currentBooks');
            
            if (books.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #7f8c8d;">No books currently borrowed</p>';
                return;
            }

            let html = '';
            books.forEach(book => {
                const isOverdue = book.days_overdue > 0;
                const dueDate = new Date(book.due_date);
                
                html += `
                    <div class="book-item" style="border-left: 4px solid ${isOverdue ? '#e74c3c' : '#3498db'}">
                        <div>
                            <strong>${book.title}</strong><br>
                            <small>Code: ${book.book_code} | Due: ${dueDate.toLocaleDateString()}</small>
                            ${isOverdue ? `<br><small style="color: #e74c3c;">Overdue by ${book.days_overdue} days - Fine: $${book.fine_amount.toFixed(2)}</small>` : ''}
                        </div>
                        <button class="btn btn-success" onclick="returnBook(${book.id})">Return</button>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        async function returnBook(borrowingId) {
            if (!confirm('Process book return?')) return;

            try {
                const response = await fetch('../api/api_borrowing.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=return_book&borrowing_id=${borrowingId}&student_id=${currentStudent.student_id}`
                });

                const data = await response.json();
                if (data.success) {
                    showMessage('Book returned successfully!', 'success');
                    if (data.fine_amount > 0) {
                        showMessage(`Fine applied: $${data.fine_amount.toFixed(2)}`, 'warning');
                    }
                    // Refresh student info and current books
                    searchStudent();
                } else {
                    showMessage(data.error, 'error');
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            }
        }

        function hideStudentSections() {
            document.getElementById('studentInfo').style.display = 'none';
            document.getElementById('borrowingSection').style.display = 'none';
            document.getElementById('currentBooksSection').style.display = 'none';
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
                searchStudent();
            }
        });

        document.getElementById('bookCode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                scanBook();
            }
        });
    </script>
</body>
</html>
