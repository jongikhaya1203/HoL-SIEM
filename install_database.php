<?php
/**
 * IOC Network Security Scanner - Database Installation
 */

echo "<h1>IOC Database Installation</h1>";

// Configuration - matches config/database.php
$dbHost = 'localhost';
$dbPort = 3307;
$dbUser = 'root';
$dbPass = '';
$dbName = 'network_security_scanner';

echo "<p>Connecting to MySQL on port $dbPort...</p>";

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>Connected to MySQL server</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>Connection failed: " . $e->getMessage() . "</p><p>Please ensure XAMPP MySQL is running!</p>");
}

echo "<p>Creating database...</p>";

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color:green'>Database '$dbName' created/verified</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>Database creation failed: " . $e->getMessage() . "</p>");
}

echo "<p>Running schema files...</p>";

try {
    $pdo->exec("USE `$dbName`");

    // SQL files to execute in order
    $sqlFiles = [
        'database/schema.sql',
        'database/cms_tables.sql',
        'database/modules_tables.sql'
    ];

    foreach ($sqlFiles as $file) {
        $filepath = __DIR__ . '/' . $file;
        if (file_exists($filepath)) {
            echo "<h3>Running: $file</h3>";
            $sql = file_get_contents($filepath);

            // Remove CREATE DATABASE and USE statements
            $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
            $sql = preg_replace('/USE\s+[^;]+;/i', '', $sql);

            // Split into statements
            $statements = preg_split('/;[\s]*[\n\r]+/', $sql);

            $success = 0;
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (empty($stmt) || strpos($stmt, '--') === 0) continue;

                try {
                    $pdo->exec($stmt);
                    $success++;
                } catch (PDOException $e) {
                    // Ignore duplicate/exists errors
                    if (strpos($e->getMessage(), 'already exists') === false &&
                        strpos($e->getMessage(), 'Duplicate') === false) {
                        echo "<p style='color:orange'>Warning: " . substr($e->getMessage(), 0, 80) . "...</p>";
                    }
                }
            }
            echo "<p style='color:green'>Executed $success statements from $file</p>";
        } else {
            echo "<p style='color:red'>File not found: $file</p>";
        }
    }

    // Show created tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tables Created (" . count($tables) . "):</h3><ul>";
    foreach ($tables as $t) echo "<li>$t</li>";
    echo "</ul>";

    echo "<h2 style='color:green'>Installation Complete!</h2>";
    echo "<p><a href='index.php'>Dashboard</a> | <a href='admin/login.php'>Admin Login</a> | <a href='setup_all_tables.php'>Setup Additional Tables</a></p>";

} catch (PDOException $e) {
    die("<p style='color:red'>Error: " . $e->getMessage() . "</p>");
}
?>
