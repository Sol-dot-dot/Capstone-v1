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
    <title>Categories Management - Admin Dashboard</title>
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

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #2a3f5f;
            border-radius: 8px;
            background: #0f1419;
            color: #ffffff;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .color-picker {
            width: 60px;
            height: 40px;
            border: 2px solid #2a3f5f;
            border-radius: 8px;
            background: transparent;
            cursor: pointer;
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

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .category-card {
            background: #0f1419;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #2a3f5f;
            transition: transform 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-3px);
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .category-color {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #2a3f5f;
        }

        .category-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ffffff;
        }

        .category-stats {
            color: #bdc3c7;
            margin-bottom: 1rem;
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
            <h1>🏷️ Categories Management</h1>
            <div class="nav-links">
                <a href="admin_dashboard.php" class="nav-link">🏠 Dashboard</a>
                <a href="books_management.php" class="nav-link">📚 Books</a>
                <a href="borrowings_management.php" class="nav-link">📋 Borrowings</a>
                <a href="admin_logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Add New Category Section -->
        <div class="section">
            <div class="section-header">
                ➕ Add New Category
            </div>
            <div class="section-content">
                <div id="message"></div>
                <form id="addCategoryForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Category Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="color">Category Color</label>
                            <input type="color" id="color" name="color" value="#3498db" class="color-picker">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">🏷️ Add Category</button>
                </form>
            </div>
        </div>

        <!-- Categories List Section -->
        <div class="section">
            <div class="section-header">
                📂 All Categories
            </div>
            <div class="section-content">
                <div class="loading" id="loading">Loading categories...</div>
                <div class="categories-grid" id="categoriesGrid">
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load categories
        async function loadCategories() {
            const loading = document.getElementById('loading');
            const grid = document.getElementById('categoriesGrid');
            
            loading.style.display = 'block';
            
            try {
                const response = await fetch('../api/api_admin.php?action=categories_management');
                const data = await response.json();
                
                if (data.success) {
                    grid.innerHTML = '';
                    
                    data.categories.forEach(category => {
                        const card = document.createElement('div');
                        card.className = 'category-card';
                        card.innerHTML = `
                            <div class="category-header">
                                <div class="category-color" style="background-color: ${category.color || '#3498db'}"></div>
                                <div class="category-name">${category.name}</div>
                            </div>
                            <div class="category-stats">
                                📚 ${category.book_count || 0} books
                            </div>
                            <button class="btn btn-danger" onclick="deleteCategory(${category.id}, '${category.name}')">🗑️ Delete</button>
                        `;
                        grid.appendChild(card);
                    });
                    
                    if (data.categories.length === 0) {
                        grid.innerHTML = '<div style="text-align: center; color: #7f8c8d; padding: 3rem; font-style: italic;">No categories found</div>';
                    }
                } else {
                    grid.innerHTML = '<div style="text-align: center; color: #e74c3c; padding: 3rem;">Error loading categories</div>';
                }
            } catch (error) {
                console.error('Error loading categories:', error);
                grid.innerHTML = '<div style="text-align: center; color: #e74c3c; padding: 3rem;">Error loading categories</div>';
            } finally {
                loading.style.display = 'none';
            }
        }

        // Add category form handler
        document.getElementById('addCategoryForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add_category');
            
            const messageDiv = document.getElementById('message');
            
            try {
                const response = await fetch('../api/api_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    messageDiv.innerHTML = '<div class="message success">✅ Category added successfully!</div>';
                    this.reset();
                    document.getElementById('color').value = '#3498db'; // Reset color picker
                    loadCategories(); // Reload categories list
                } else {
                    messageDiv.innerHTML = `<div class="message error">❌ Error: ${data.error}</div>`;
                }
            } catch (error) {
                messageDiv.innerHTML = '<div class="message error">❌ Network error. Please try again.</div>';
            }
        });

        // Delete category function
        async function deleteCategory(categoryId, categoryName) {
            if (!confirm(`Are you sure you want to delete the category "${categoryName}"? This will affect all books in this category.`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_category');
                formData.append('category_id', categoryId);
                
                const response = await fetch('../api/api_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadCategories(); // Reload categories list
                } else {
                    alert('Error deleting category: ' + data.error);
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
        });
    </script>
</body>
</html>
