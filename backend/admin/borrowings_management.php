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
    <title>Borrowings Management - Admin Dashboard</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #16213E;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #2a3f5f;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .stat-card.active h3 { color: #27ae60; }
        .stat-card.overdue h3 { color: #e74c3c; }
        .stat-card.returned h3 { color: #3498db; }

        .stat-card p {
            color: #bdc3c7;
            font-weight: 500;
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

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }

        .status-overdue {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .status-returned {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
            color: #3498db;
        }

        .days-borrowed {
            font-size: 0.9rem;
            color: #95a5a6;
        }

        .overdue-days {
            color: #e74c3c;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📋 Borrowings Management</h1>
            <div class="nav-links">
                <a href="admin_dashboard.php" class="nav-link">🏠 Dashboard</a>
                <a href="books_management.php" class="nav-link">📚 Books</a>
                <a href="categories_management.php" class="nav-link">🏷️ Categories</a>
                <a href="admin_logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card active">
                <h3 id="activeBorrowings">0</h3>
                <p>Active Borrowings</p>
            </div>
            <div class="stat-card overdue">
                <h3 id="overdueBorrowings">0</h3>
                <p>Overdue Books</p>
            </div>
            <div class="stat-card returned">
                <h3 id="returnedBorrowings">0</h3>
                <p>Returned This Month</p>
            </div>
        </div>

        <!-- Borrowings List Section -->
        <div class="section">
            <div class="section-header">
                📖 All Borrowings
            </div>
            <div class="section-content">
                <div class="loading" id="loading">Loading borrowings...</div>
                <div class="table-container">
                    <table id="borrowingsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Book</th>
                                <th>Borrowed Date</th>
                                <th>Due Date</th>
                                <th>Days Borrowed</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="borrowingsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load borrowings and statistics
        async function loadBorrowings() {
            const loading = document.getElementById('loading');
            const tableBody = document.getElementById('borrowingsTableBody');
            
            loading.style.display = 'block';
            
            try {
                const response = await fetch('../api/api_admin.php?action=borrowings_management');
                const data = await response.json();
                
                if (data.success) {
                    tableBody.innerHTML = '';
                    
                    let activeCount = 0;
                    let overdueCount = 0;
                    let returnedThisMonth = 0;
                    
                    data.borrowings.forEach(borrowing => {
                        // Count statistics
                        if (borrowing.status === 'active') activeCount++;
                        else if (borrowing.status === 'overdue') overdueCount++;
                        else if (borrowing.status === 'returned') {
                            const returnDate = new Date(borrowing.returned_date);
                            const now = new Date();
                            const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
                            if (returnDate >= monthStart) returnedThisMonth++;
                        }
                        
                        const row = document.createElement('tr');
                        const daysBorrowed = borrowing.days_borrowed || 0;
                        const isDueToday = daysBorrowed >= 14; // Assuming 14 days loan period
                        const isOverdue = daysBorrowed > 14;
                        
                        let statusClass = 'status-active';
                        let statusText = 'Active';
                        if (borrowing.status === 'overdue' || isOverdue) {
                            statusClass = 'status-overdue';
                            statusText = 'Overdue';
                        } else if (borrowing.status === 'returned') {
                            statusClass = 'status-returned';
                            statusText = 'Returned';
                        }
                        
                        let actionsHtml = '';
                        if (borrowing.status === 'active' || borrowing.status === 'overdue') {
                            actionsHtml = `<button class="btn btn-success" onclick="returnBook(${borrowing.id})">📚 Return</button>`;
                            if (isOverdue) {
                                actionsHtml += `<button class="btn btn-warning" onclick="sendReminder(${borrowing.id})">📧 Remind</button>`;
                            }
                        }
                        
                        row.innerHTML = `
                            <td>${borrowing.id}</td>
                            <td><strong>${(borrowing.first_name || '') + ' ' + (borrowing.last_name || '')}</strong><br>
                                <small style="color: #95a5a6;">${borrowing.student_id}</small></td>
                            <td><strong>${borrowing.book_title || 'Unknown Book'}</strong></td>
                            <td>${new Date(borrowing.borrowed_date).toLocaleDateString()}</td>
                            <td>${borrowing.due_date ? new Date(borrowing.due_date).toLocaleDateString() : 'N/A'}</td>
                            <td class="days-borrowed ${isOverdue ? 'overdue-days' : ''}">${daysBorrowed} days</td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                            <td>${actionsHtml}</td>
                        `;
                        tableBody.appendChild(row);
                    });
                    
                    // Update statistics
                    document.getElementById('activeBorrowings').textContent = activeCount;
                    document.getElementById('overdueBorrowings').textContent = overdueCount;
                    document.getElementById('returnedBorrowings').textContent = returnedThisMonth;
                    
                    if (data.borrowings.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #7f8c8d; padding: 3rem; font-style: italic;">No borrowings found</td></tr>';
                    }
                } else {
                    tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #e74c3c;">Error loading borrowings</td></tr>';
                }
            } catch (error) {
                console.error('Error loading borrowings:', error);
                tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #e74c3c;">Error loading borrowings</td></tr>';
            } finally {
                loading.style.display = 'none';
            }
        }

        // Return book function
        async function returnBook(borrowingId) {
            if (!confirm('Mark this book as returned?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'return_book');
                formData.append('borrowing_id', borrowingId);
                
                const response = await fetch('../api/api_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadBorrowings(); // Reload borrowings list
                } else {
                    alert('Error returning book: ' + data.error);
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        }

        // Send reminder function
        async function sendReminder(borrowingId) {
            try {
                const formData = new FormData();
                formData.append('action', 'send_reminder');
                formData.append('borrowing_id', borrowingId);
                
                const response = await fetch('../api/api_admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Reminder sent successfully!');
                } else {
                    alert('Error sending reminder: ' + data.error);
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadBorrowings();
            
            // Auto-refresh every 60 seconds
            setInterval(loadBorrowings, 60000);
        });
    </script>
</body>
</html>
