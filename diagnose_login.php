<?php
/**
 * Login Diagnostic Tool
 * Shows exactly why login might be failing
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Login Diagnostics</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
h1 { color: #667eea; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
code { background: #e0e0e0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #667eea; color: white; }
.btn { background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
.btn:hover { background: #764ba2; }
</style></head><body>";

echo "<h1>🔍 Login System Diagnostics</h1>";

// Test 1: Database Connection
echo "<div class='box'><h2>Test 1: Database Connection</h2>";
try {
    require_once 'classes/Database.php';
    $db = Database::getInstance();
    echo "<p class='success'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Test 2: Check admin_users table
echo "<div class='box'><h2>Test 2: Admin Users Table</h2>";
try {
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'admin_users'");

    if ($tableExists) {
        echo "<p class='success'>✓ admin_users table exists</p>";

        // Check if table has any users
        $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM admin_users")['count'];
        echo "<p class='info'>Found {$userCount} user(s) in database</p>";

        if ($userCount == 0) {
            echo "<p class='error'>✗ No users found! Creating admin user...</p>";

            $password = 'admin123';
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $db->query("INSERT INTO admin_users (username, password_hash, email, full_name, role, active)
                       VALUES ('admin', ?, 'admin@example.com', 'System Administrator', 'admin', 1)",
                       [$hash]);

            echo "<p class='success'>✓ Admin user created!</p>";
            $userCount = 1;
        }

        // Show all users
        if ($userCount > 0) {
            $users = $db->fetchAll("SELECT id, username, email, role, active, last_login FROM admin_users");
            echo "<table>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th><th>Last Login</th></tr>";
            foreach ($users as $user) {
                $activeStatus = $user['active'] ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>";
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td><strong>{$user['username']}</strong></td>";
                echo "<td>{$user['email']}</td>";
                echo "<td>{$user['role']}</td>";
                echo "<td>{$activeStatus}</td>";
                echo "<td>" . ($user['last_login'] ?? 'Never') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

    } else {
        echo "<p class='error'>✗ admin_users table does NOT exist!</p>";
        echo "<p class='warning'>You need to import database/cms_tables.sql</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 3: Test Login Credentials
echo "<div class='box'><h2>Test 3: Test Login with admin/admin123</h2>";
try {
    $username = 'admin';
    $password = 'admin123';

    $user = $db->fetchOne("SELECT * FROM admin_users WHERE username = ?", [$username]);

    if ($user) {
        echo "<p class='success'>✓ Found user: <strong>{$username}</strong></p>";
        echo "<p class='info'>User ID: {$user['id']}</p>";
        echo "<p class='info'>Active: " . ($user['active'] ? 'Yes' : 'No') . "</p>";
        echo "<p class='info'>Role: {$user['role']}</p>";

        // Test password
        if (password_verify($password, $user['password_hash'])) {
            echo "<p class='success' style='font-size: 18px;'>✅ PASSWORD MATCHES! Login should work!</p>";
        } else {
            echo "<p class='error' style='font-size: 18px;'>❌ PASSWORD DOES NOT MATCH!</p>";
            echo "<p class='warning'>Regenerating password hash...</p>";

            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $db->query("UPDATE admin_users SET password_hash = ? WHERE username = ?", [$newHash, $username]);

            echo "<p class='success'>✓ Password hash updated! Try logging in again.</p>";
        }
    } else {
        echo "<p class='error'>✗ User 'admin' not found in database!</p>";
        echo "<p class='warning'>Creating admin user now...</p>";

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db->query("INSERT INTO admin_users (username, password_hash, email, full_name, role, active)
                   VALUES ('admin', ?, 'admin@example.com', 'System Administrator', 'admin', 1)",
                   [$hash]);

        echo "<p class='success'>✓ Admin user created successfully!</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 4: Check login.php file
echo "<div class='box'><h2>Test 4: Login File Check</h2>";
$loginFile = __DIR__ . '/admin/login.php';
if (file_exists($loginFile)) {
    echo "<p class='success'>✓ admin/login.php exists</p>";
    echo "<p class='info'>File path: " . htmlspecialchars($loginFile) . "</p>";
} else {
    echo "<p class='error'>✗ admin/login.php NOT found!</p>";
}
echo "</div>";

// Test 5: Check sessions
echo "<div class='box'><h2>Test 5: Session Check</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    echo "<p class='success'>✓ You are already logged in!</p>";
    echo "<p>Username: <strong>" . htmlspecialchars($_SESSION['admin_username']) . "</strong></p>";
    echo "<p><a href='admin/index.php' class='btn'>Go to Admin Dashboard →</a></p>";
} else {
    echo "<p class='info'>ℹ Not currently logged in</p>";
}
echo "</div>";

// Final Summary
echo "<div class='box'><h2>📊 Summary & Actions</h2>";

$adminUser = $db->fetchOne("SELECT * FROM admin_users WHERE username = 'admin'");

if ($adminUser && password_verify('admin123', $adminUser['password_hash']) && $adminUser['active']) {
    echo "<p class='success' style='font-size: 20px;'>✅ Everything is configured correctly!</p>";
    echo "<div style='background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='margin-top: 0;'>✓ Login Credentials Ready</h3>";
    echo "<p><strong>Login URL:</strong> <a href='admin/login.php'>http://localhost/networkscan/admin/login.php</a></p>";
    echo "<p><strong>Username:</strong> <code>admin</code></p>";
    echo "<p><strong>Password:</strong> <code>admin123</code></p>";
    echo "<p style='margin-top: 20px;'><a href='admin/login.php' class='btn'>🔐 Go to Login Page →</a></p>";
    echo "</div>";
} else {
    echo "<p class='warning'>⚠ Some issues detected - please review the tests above</p>";
}

echo "<p style='margin-top: 20px;'>";
echo "<a href='index.php' class='btn'>🏠 Dashboard</a>";
echo "<a href='diagnose_login.php' class='btn'>🔄 Refresh Diagnostics</a>";
echo "</p>";

echo "</div>";

echo "</body></html>";
?>
