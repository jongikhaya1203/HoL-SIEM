<?php
session_start();
require_once __DIR__ . '/includes/Database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Default credentials for initial setup (should be changed)
    $defaultUser = 'admin';
    $defaultPass = 'admin123';

    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM cpanel_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['cpanel_logged_in'] = true;
            $_SESSION['cpanel_user_id'] = $user['id'];
            $_SESSION['cpanel_username'] = $user['username'];
            $_SESSION['cpanel_role'] = $user['role'];
            header('Location: index.php');
            exit;
        } elseif ($username === $defaultUser && $password === $defaultPass) {
            // Default login for initial setup
            $_SESSION['cpanel_logged_in'] = true;
            $_SESSION['cpanel_user_id'] = 0;
            $_SESSION['cpanel_username'] = 'admin';
            $_SESSION['cpanel_role'] = 'super_admin';
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    } catch (Exception $e) {
        // Table doesn't exist, use default credentials
        if ($username === $defaultUser && $password === $defaultPass) {
            $_SESSION['cpanel_logged_in'] = true;
            $_SESSION['cpanel_user_id'] = 0;
            $_SESSION['cpanel_username'] = 'admin';
            $_SESSION['cpanel_role'] = 'super_admin';
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IOC Control Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 50px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .login-logo svg {
            width: 40px;
            height: 40px;
            color: white;
        }
        .login-header h1 {
            font-size: 24px;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        .login-header p {
            color: #6b7280;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .login-footer p {
            color: #9ca3af;
            font-size: 12px;
        }
        .default-creds {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #0369a1;
        }
        .default-creds strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            <h1>IOC Control Panel</h1>
            <p>Intelligent Operating Centre Configuration</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="default-creds">
            <strong>Default Credentials:</strong>
            Username: admin | Password: admin123
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="login-btn">Sign In</button>
        </form>

        <div class="login-footer">
            <p>IOC Intelligent Operating Centre v1.0</p>
        </div>
    </div>
</body>
</html>
