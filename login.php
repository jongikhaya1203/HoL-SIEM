<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Network Security Scanner</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .login-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }

        .login-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Network Security Scanner</h1>
            <p>Multi-Tenant Dashboard Access</p>
        </div>
        <div class="login-body">
            <?php
            session_start();
            require_once __DIR__ . '/classes/Database.php';

            $error = '';
            $success = '';

            // Handle login form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';

                if (empty($username) || empty($password)) {
                    $error = 'Please enter both username and password';
                } else {
                    try {
                        $db = Database::getInstance();

                        // Get user from database
                        $user = $db->fetchOne(
                            "SELECT tu.*, t.tenant_name, t.tenant_code, t.status as tenant_status
                             FROM tenant_users tu
                             JOIN tenants t ON tu.tenant_id = t.id
                             WHERE tu.username = ? AND tu.status = 'active'",
                            [$username]
                        );

                        if ($user && password_verify($password, $user['password_hash'])) {
                            // Check if tenant is active
                            if ($user['tenant_status'] !== 'active') {
                                $error = 'Your tenant account is not active. Please contact support.';
                            } else {
                                // Login successful
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['tenant_id'] = $user['tenant_id'];
                                $_SESSION['tenant_name'] = $user['tenant_name'];
                                $_SESSION['tenant_code'] = $user['tenant_code'];
                                $_SESSION['role'] = $user['role'];
                                $_SESSION['full_name'] = $user['full_name'];

                                // Update last_login
                                $db->query(
                                    "UPDATE tenant_users SET last_login = NOW() WHERE id = ?",
                                    [$user['id']]
                                );

                                // Redirect to dashboard
                                header('Location: index.php');
                                exit;
                            }
                        } else {
                            $error = 'Invalid username or password';
                        }
                    } catch (Exception $e) {
                        $error = 'Login error: ' . $e->getMessage();
                    }
                }
            }

            // Show logout success message
            if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
                $success = 'You have been logged out successfully';
            }

            // Display error message
            if ($error) {
                echo '<div class="alert alert-error">' . htmlspecialchars($error) . '</div>';
            }

            // Display success message
            if ($success) {
                echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>';
            }
            ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> Network Security Scanner. All rights reserved.
        </div>
    </div>
</body>
</html>
