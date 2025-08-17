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
    <title>Library Admin Dashboard</title>
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
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            color: #bdc3c7;
        }

        .logout-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .main-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .main-action-btn {
            background: linear-gradient(135deg, #16213E, #0f1419);
            border: 2px solid transparent;
            border-radius: 20px;
            padding: 2.5rem;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 2rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        .main-action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .main-action-btn:hover::before {
            left: 100%;
        }

        .borrow-btn {
            border-color: #27ae60;
            background: linear-gradient(135deg, #27ae60, #229954);
        }

        .borrow-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(39, 174, 96, 0.4);
        }

        .return-btn {
            border-color: #3498db;
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .return-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(52, 152, 219, 0.4);
        }

        .main-action-icon {
            font-size: 3rem;
            min-width: 80px;
            text-align: center;
        }

        .main-action-content {
            flex: 1;
        }

        .main-action-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .main-action-desc {
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.4;
        }

        .main-action-arrow {
            font-size: 2rem;
            opacity: 0.7;
            transition: all 0.3s ease;
        }

        .main-action-btn:hover .main-action-arrow {
            opacity: 1;
            transform: translateX(10px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(22, 33, 62, 0.8);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
            border-color: rgba(255,255,255,0.2);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            color: #3498db;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .stat-label {
            color: #bdc3c7;
            font-size: 1rem;
            font-weight: 500;
        }

        .section {
            background: rgba(22, 33, 62, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
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

        .icon-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1.5rem;
        }

        .icon-action-card {
            background: rgba(15, 20, 25, 0.6);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
            text-decoration: none;
            color: white;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .icon-action-card:hover {
            transform: translateY(-5px);
            border-color: #3498db;
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
            background: rgba(52, 152, 219, 0.1);
        }

        .icon-action-icon {
            font-size: 2.5rem;
        }

        .icon-action-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #bdc3c7;
        }

        .icon-action-card:hover .icon-action-title {
            color: #3498db;
        }

        @media (max-width: 768px) {
            .main-actions {
                grid-template-columns: 1fr;
            }
            
            .main-action-btn {
                padding: 2rem;
                gap: 1.5rem;
            }
            
            .main-action-title {
                font-size: 1.5rem;
            }
            
            .icon-actions-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
            color: #3498db;
        }

        .welcome-section {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(39, 174, 96, 0.1));
            border: 1px solid rgba(52, 152, 219, 0.3);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 3rem;
            text-align: center;
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #3498db;
        }

        .welcome-desc {
            color: #bdc3c7;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📚 Library Management System</h1>
            <div class="admin-info">
                <span>Welcome, Admin</span>
                <a href="admin_logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-title">Library Administration Dashboard</div>
            <div class="welcome-desc">Manage your library operations efficiently and professionally</div>
        </div>

        <!-- Main Action Buttons -->
        <div class="main-actions">
            <a href="student_borrowing.php" class="main-action-btn borrow-btn">
                <div class="main-action-icon">📚</div>
                <div class="main-action-content">
                    <div class="main-action-title">Process Book Borrowing</div>
                    <div class="main-action-desc">Help students borrow books from the library collection</div>
                </div>
                <div class="main-action-arrow">→</div>
            </a>
            
            <a href="book_returns.php" class="main-action-btn return-btn">
                <div class="main-action-icon">📖</div>
                <div class="main-action-content">
                    <div class="main-action-title">Process Book Returns</div>
                    <div class="main-action-desc">Handle book returns and calculate fines</div>
                </div>
                <div class="main-action-arrow">→</div>
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-number" id="totalBooks">0</div>
                <div class="stat-label">Total Books</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number" id="totalStudents">0</div>
                <div class="stat-label">Total Students</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-number" id="activeBorrowings">0</div>
                <div class="stat-label">Active Borrowings</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-number" id="overdueBorrowings">0</div>
                <div class="stat-label">Overdue Books</div>
            </div>
        </div>

        <!-- Management Tools -->
        <div class="section">
            <div class="section-header">
                🛠️ Management Tools
            </div>
            <div class="section-content">
                <div class="icon-actions-grid">
                    <a href="books_management.php" class="icon-action-card">
                        <div class="icon-action-icon">📚</div>
                        <div class="icon-action-title">Books</div>
                    </a>
                    
                    <a href="categories_management.php" class="icon-action-card">
                        <div class="icon-action-icon">🏷️</div>
                        <div class="icon-action-title">Categories</div>
                    </a>
                    
                    <a href="borrowings_management.php" class="icon-action-card">
                        <div class="icon-action-icon">📋</div>
                        <div class="icon-action-title">Borrowings</div>
                    </a>
                    
                    <a href="students_management.php" class="icon-action-card">
                        <div class="icon-action-icon">👥</div>
                        <div class="icon-action-title">Students</div>
                    </a>
                    
                    <a href="reports.php" class="icon-action-card">
                        <div class="icon-action-icon">📊</div>
                        <div class="icon-action-title">Reports</div>
                    </a>
                    
                    <a href="settings.php" class="icon-action-card">
                        <div class="icon-action-icon">⚙️</div>
                        <div class="icon-action-title">Settings</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard statistics
        async function loadDashboardStats() {
            try {
                const response = await fetch('../api/api_admin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=get_dashboard_stats'
                });

                const data = await response.json();
                if (data.success) {
                    document.getElementById('totalBooks').textContent = data.stats.total_books || 0;
                    document.getElementById('totalStudents').textContent = data.stats.total_students || 0;
                    document.getElementById('activeBorrowings').textContent = data.stats.active_borrowings || 0;
                    document.getElementById('overdueBorrowings').textContent = data.stats.overdue_borrowings || 0;
                }
            } catch (error) {
                console.error('Error loading dashboard stats:', error);
            }
        }

        // Load stats on page load
        document.addEventListener('DOMContentLoaded', loadDashboardStats);

        // Auto-refresh stats every 30 seconds
        setInterval(loadDashboardStats, 30000);
    </script>
</body>
</html>
