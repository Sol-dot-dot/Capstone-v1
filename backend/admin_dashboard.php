<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.html');
    exit;
}

require_once 'config.php';

// Get recent student logins (last 50)
try {
    $stmt = $pdo->prepare("
        SELECT sl.*, sr.first_name, sr.last_name 
        FROM student_logins sl 
        LEFT JOIN student_records sr ON sl.student_id = sr.student_id 
        ORDER BY sl.login_time DESC 
        LIMIT 50
    ");
    $stmt->execute();
    $recent_logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_logins = [];
}

// Get all student accounts
try {
    $stmt = $pdo->prepare("
        SELECT s.student_id, s.email, sr.first_name, sr.last_name, s.account_status, s.created_at,
        (SELECT COUNT(*) FROM student_logins WHERE student_id = s.student_id) as login_count,
        (SELECT MAX(login_time) FROM student_logins WHERE student_id = s.student_id) as last_login
        FROM students s 
        LEFT JOIN student_records sr ON s.student_id = sr.student_id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $students = [];
}

// Get statistics
try {
    $stats = [];
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $stats['total_students'] = $stmt->fetch()['count'];
    
    // Active students
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE account_status = 'active'");
    $stats['active_students'] = $stmt->fetch()['count'];
    
    // Total logins today
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM student_logins WHERE DATE(login_time) = CURDATE()");
    $stats['logins_today'] = $stmt->fetch()['count'];
    
    // Total logins this week
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM student_logins WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['logins_week'] = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $stats = [
        'total_students' => 0,
        'active_students' => 0,
        'logins_today' => 0,
        'logins_week' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Winsurfs</title>
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

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: #16213E;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            text-align: center;
            border: 1px solid #2a3f5f;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
        }

        .stat-card h3 {
            color: #3498db;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .stat-card p {
            color: #bdc3c7;
            font-size: 1rem;
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

        .status-active {
            color: #27ae60;
            font-weight: 600;
            background: rgba(39, 174, 96, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
            background: rgba(231, 76, 60, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .login-time {
            font-size: 0.9rem;
            color: #95a5a6;
        }

        .no-data {
            text-align: center;
            color: #7f8c8d;
            padding: 3rem;
            font-style: italic;
            font-size: 1.1rem;
        }

        .refresh-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            margin-bottom: 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📚 Book Borrowing System - Admin Dashboard</h1>
            <div class="admin-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_students']; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['active_students']; ?></h3>
                <p>Active Students</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['logins_today']; ?></h3>
                <p>Logins Today</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['logins_week']; ?></h3>
                <p>Logins This Week</p>
            </div>
        </div>

        <!-- Books Management Section -->
        <div class="section">
            <div class="section-header">
                📚 Books Management
            </div>
            <div class="section-content">
                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <button class="refresh-btn" onclick="location.href='books_management.php'">📖 Manage Books</button>
                    <button class="refresh-btn" onclick="location.href='borrowings_management.php'">📋 Manage Borrowings</button>
                    <button class="refresh-btn" onclick="location.href='categories_management.php'">🏷️ Manage Categories</button>
                </div>
                <?php
                try {
                    // Get book statistics
                    $stmt = $pdo->query("SELECT COUNT(*) as total_books FROM books");
                    $total_books = $stmt->fetch()['total_books'];
                    
                    $stmt = $pdo->query("SELECT COUNT(*) as active_borrowings FROM borrowings WHERE status = 'active'");
                    $active_borrowings = $stmt->fetch()['active_borrowings'];
                    
                    $stmt = $pdo->query("SELECT COUNT(*) as overdue_books FROM borrowings WHERE status = 'overdue'");
                    $overdue_books = $stmt->fetch()['overdue_books'];
                    
                    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;'>";
                    echo "<div style='background: #0f1419; padding: 1rem; border-radius: 8px; text-align: center;'>";
                    echo "<h4 style='color: #3498db; margin-bottom: 0.5rem;'>$total_books</h4>";
                    echo "<p style='color: #bdc3c7; margin: 0;'>Total Books</p>";
                    echo "</div>";
                    echo "<div style='background: #0f1419; padding: 1rem; border-radius: 8px; text-align: center;'>";
                    echo "<h4 style='color: #27ae60; margin-bottom: 0.5rem;'>$active_borrowings</h4>";
                    echo "<p style='color: #bdc3c7; margin: 0;'>Active Borrowings</p>";
                    echo "</div>";
                    echo "<div style='background: #0f1419; padding: 1rem; border-radius: 8px; text-align: center;'>";
                    echo "<h4 style='color: #e74c3c; margin-bottom: 0.5rem;'>$overdue_books</h4>";
                    echo "<p style='color: #bdc3c7; margin: 0;'>Overdue Books</p>";
                    echo "</div>";
                    echo "</div>";
                } catch (Exception $e) {
                    echo "<p style='color: #e74c3c;'>Error loading book statistics</p>";
                }
                ?>
            </div>
        </div>

        <!-- Recent Logins Section -->
        <div class="section">
            <div class="section-header">
                📊 Recent Student Logins
            </div>
            <div class="section-content">
                <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
                <div class="table-container">
                    <?php if (empty($recent_logins)): ?>
                        <div class="no-data">No recent logins found</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Login Time</th>
                                    <th>IP Address</th>
                                    <th>Device</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logins as $login): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($login['student_id']); ?></td>
                                    <td>
                                        <?php 
                                        $name = trim(($login['first_name'] ?? '') . ' ' . ($login['last_name'] ?? ''));
                                        echo $name ?: 'N/A';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($login['email'] ?: 'N/A'); ?></td>
                                    <td class="login-time">
                                        <?php echo date('M j, Y g:i A', strtotime($login['login_time'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($login['user_agent'], 0, 50)) . '...'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Student Accounts Section -->
        <div class="section">
            <div class="section-header">
                👥 Student Accounts
            </div>
            <div class="section-content">
                <div class="table-container">
                    <?php if (empty($students)): ?>
                        <div class="no-data">No student accounts found</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Login Count</th>
                                    <th>Last Login</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td>
                                        <?php 
                                        $name = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
                                        echo $name ?: 'N/A';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td>
                                        <span class="status-<?php echo $student['account_status']; ?>">
                                            <?php echo ucfirst($student['account_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $student['login_count']; ?></td>
                                    <td class="login-time">
                                        <?php 
                                        echo $student['last_login'] 
                                            ? date('M j, Y g:i A', strtotime($student['last_login']))
                                            : 'Never';
                                        ?>
                                    </td>
                                    <td class="login-time">
                                        <?php echo date('M j, Y', strtotime($student['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds for real-time updates
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
