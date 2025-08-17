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
    <title>Books Management - Admin Dashboard</title>
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

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #2a3f5f;
            border-radius: 8px;
            background: #0f1419;
            color: #ffffff;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
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

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #2a3f5f;
        }

        th {
            background: #0f1419;
            font-weight: 600;
            color: #ffffff;
            font-size: 0.95rem;
        }

        td {
            color: #bdc3c7;
        }

        tr:hover {
            background: #1e2a3a;
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

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📚 Books Management</h1>
            <div class="nav-links">
                <a href="admin_dashboard.php" class="nav-link">🏠 Dashboard</a>
                <a href="categories_management.php" class="nav-link">🏷️ Categories</a>
                <a href="borrowings_management.php" class="nav-link">📋 Borrowings</a>
                <a href="admin_logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Add New Book Section -->
        <div class="section">
            <div class="section-header">
                ➕ Add New Book
            </div>
            <div class="section-content">
                <div id="message"></div>
                <form id="addBookForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Book Title *</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="isbn">ISBN</label>
                            <input type="text" id="isbn" name="isbn">
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="total_copies">Total Copies</label>
                            <input type="number" id="total_copies" name="total_copies" value="1" min="1">
                        </div>
                        <div class="form-group">
                            <label for="author_first">Author First Name *</label>
                            <input type="text" id="author_first" name="author_first" required>
                        </div>
                        <div class="form-group">
                            <label for="author_last">Author Last Name *</label>
                            <input type="text" id="author_last" name="author_last" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">📚 Add Book</button>
                </form>
            </div>
        </div>

        <!-- Books List Section -->
        <div class="section">
            <div class="section-header">
                📖 All Books
            </div>
            <div class="section-content">
                <div class="loading" id="loading">Loading books...</div>
                <div class="table-container">
                    <table id="booksTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>ISBN</th>
                                <th>Copies</th>
                                <th>Available</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="booksTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load categories for dropdown
        async function loadCategories() {
            try {
                const response = await fetch('../api/api_admin.php?action=categories_management');
                const data = await response.json();
                
                if (data.success) {
                    const categorySelect = document.getElementById('category_id');
                    categorySelect.innerHTML = '<option value="">Select Category</option>';
                    
                    data.categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        categorySelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        // Load books
        async function loadBooks() {
            const loading = document.getElementById('loading');
            const tableBody = document.getElementById('booksTableBody');
            
            loading.style.display = 'block';
            
            try {
                const response = await fetch('../api/api_admin.php?action=books_management');
                const data = await response.json();
                
                if (data.success) {
                    tableBody.innerHTML = '';
                    
                    data.books.forEach(book => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${book.id}</td>
                            <td><strong>${book.title}</strong></td>
                            <td>${book.author_name || 'Unknown'}</td>
                            <td><span style="background: ${book.category_color || '#3498db'}; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem;">${book.category_name || 'Uncategorized'}</span></td>
                            <td>${book.isbn || 'N/A'}</td>
                            <td>${book.total_copies || 0}</td>
                            <td>${book.available_copies || 0}</td>
                            <td>${book.rating || 0}/5 (${book.total_ratings || 0})</td>
                            <td>
                                <button class="btn btn-danger" onclick="deleteBook(${book.id})">🗑️ Delete</button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: #e74c3c;">Error loading books</td></tr>';
                }
            } catch (error) {
                console.error('Error loading books:', error);
                tableBody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: #e74c3c;">Error loading books</td></tr>';
            } finally {
                loading.style.display = 'none';
            }
        }

        // Add book form handler
        document.getElementById('addBookForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add_book');
            
            const messageDiv = document.getElementById('message');
            
            try {
                const response = await fetch('../api/api_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    messageDiv.innerHTML = '<div class="message success">✅ Book added successfully!</div>';
                    this.reset();
                    loadBooks(); // Reload books list
                } else {
                    messageDiv.innerHTML = `<div class="message error">❌ Error: ${data.error}</div>`;
                }
            } catch (error) {
                messageDiv.innerHTML = '<div class="message error">❌ Network error. Please try again.</div>';
            }
        });

        // Delete book function
        async function deleteBook(bookId) {
            if (!confirm('Are you sure you want to delete this book?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_book');
                formData.append('book_id', bookId);
                
                const response = await fetch('../api/api_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadBooks(); // Reload books list
                } else {
                    alert('Error deleting book: ' + data.error);
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadBooks();
        });
    </script>
</body>
</html>
