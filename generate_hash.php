<?php
/**
 * Generate Password Hash for admin123
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$password = 'admin123';
$existingHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "<!DOCTYPE html><html><head><title>Password Hash Generator</title>";
echo "<style>
body { font-family: Arial; padding: 40px; background: #f5f5f5; }
.box { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #667eea; }
.result { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; word-break: break-all; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
code { background: #e0e0e0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style></head><body>";

echo "<div class='box'>";
echo "<h1>🔑 Password Hash Generator</h1>";

echo "<h2>Testing Existing Hash</h2>";
echo "<p><strong>Password:</strong> <code>admin123</code></p>";
echo "<p><strong>Existing Hash:</strong></p>";
echo "<div class='result'>$existingHash</div>";

if (password_verify($password, $existingHash)) {
    echo "<p class='success'>✓ Existing hash is VALID - password 'admin123' matches!</p>";
} else {
    echo "<p class='error'>✗ Existing hash is INVALID - password 'admin123' does NOT match!</p>";
}

echo "<hr style='margin: 30px 0;'>";

echo "<h2>Generating New Hash</h2>";
$newHash = password_hash($password, PASSWORD_DEFAULT);

echo "<p><strong>New Hash for 'admin123':</strong></p>";
echo "<div class='result'>$newHash</div>";

echo "<p class='success'>✓ New hash generated successfully!</p>";

// Verify new hash
if (password_verify($password, $newHash)) {
    echo "<p class='success'>✓ New hash verified - password 'admin123' matches!</p>";
}

echo "<hr style='margin: 30px 0;'>";

echo "<h2>📋 Update Instructions</h2>";
echo "<p>Copy the new hash above and update <code>admin/login.php</code> line 22:</p>";
echo "<div class='result'>\$admin_password_hash = '$newHash'; // admin123</div>";

echo "</div>";
echo "</body></html>";
?>
