<?php
session_start();

// Check if admin is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: admin_dashboard_new.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Admin Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1A1A2E 0%, #16213E 50%, #0F1419 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }

        .login-container {
            background: rgba(22, 33, 62, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #27ae60, #e74c3c, #f39c12);
            border-radius: 20px 20px 0 0;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #3498db, #27ae60);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-header p {
            color: #bdc3c7;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(15, 20, 25, 0.6);
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 20px rgba(52, 152, 219, 0.3);
            transform: translateY(-2px);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(52, 152, 219, 0.4);
        }

        .login-btn:active {
            transform: translateY(-1px);
        }

        .error-message {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid rgba(231, 76, 60, 0.3);
            font-weight: 500;
            text-align: center;
        }

        .success-message {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid rgba(39, 174, 96, 0.3);
            font-weight: 500;
            text-align: center;
        }

        .loading {
            display: none;
            text-align: center;
            color: #3498db;
            margin-top: 1rem;
        }

        .footer-info {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .system-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #95a5a6;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 2rem;
            }
            
            .login-header h1 {
                font-size: 2rem;
            }
        }

        .input-icon {
            position: relative;
        }

        .input-icon input {
            padding-left: 3rem;
        }

        .input-icon::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            background-size: contain;
            opacity: 0.6;
            z-index: 1;
        }

        .input-icon.email::before {
            content: '📧';
            font-size: 16px;
        }

        .input-icon.password::before {
            content: '🔒';
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>📚 Library Admin</h1>
            <p>Management System Login</p>
        </div>

        <form id="loginForm" method="POST" action="admin_auth.php">
            <div id="message"></div>
            
            <div class="form-group">
                <label for="email">Admin Email</label>
                <div class="input-icon email">
                    <input type="email" id="email" name="email" placeholder="Enter your admin email" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon password">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" class="login-btn">
                🚀 Login to Dashboard
            </button>

            <div class="loading" id="loading">
                <p>🔄 Authenticating...</p>
            </div>
        </form>

        <div class="footer-info">
            <p><strong>Library Management System</strong></p>
            <p>Secure Admin Access Portal</p>
            <div class="system-info">
                <span>Version 2.0</span>
                <span id="currentTime"></span>
            </div>
        </div>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('currentTime').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const messageDiv = document.getElementById('message');
            const loadingDiv = document.getElementById('loading');
            
            if (!email || !password) {
                showMessage('Please fill in all fields', 'error');
                return;
            }
            
            // Show loading
            loadingDiv.style.display = 'block';
            
            try {
                const response = await fetch('admin_auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'admin_dashboard_new.php';
                    }, 1000);
                } else {
                    showMessage(data.error || 'Login failed', 'error');
                }
            } catch (error) {
                showMessage('Network error. Please try again.', 'error');
            } finally {
                loadingDiv.style.display = 'none';
            }
        });
        
        function showMessage(message, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="${type}-message">${message}</div>`;
            
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 5000);
        }

        // Auto-focus email field
        document.getElementById('email').focus();

        // Handle Enter key navigation
        document.getElementById('email').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>
