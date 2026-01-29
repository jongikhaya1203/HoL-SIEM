#!/usr/bin/env php
<?php
/**
 * Network Security Scanner - Setup Script
 * Automated installation and configuration
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                                                              ║\n";
echo "║     🛡️  Network Security Scanner - Setup Wizard 🛡️          ║\n";
echo "║                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check PHP version
echo "Checking PHP version... ";
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    echo "✗ FAILED\n";
    echo "PHP 7.4 or higher required. Current version: " . PHP_VERSION . "\n";
    exit(1);
}
echo "✓ OK (" . PHP_VERSION . ")\n";

// Check required extensions
echo "Checking required PHP extensions...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
$missing = [];

foreach ($required_extensions as $ext) {
    echo "  - {$ext}: ";
    if (extension_loaded($ext)) {
        echo "✓\n";
    } else {
        echo "✗ MISSING\n";
        $missing[] = $ext;
    }
}

if (!empty($missing)) {
    echo "\nMissing extensions: " . implode(', ', $missing) . "\n";
    echo "Please install missing extensions and run setup again.\n";
    exit(1);
}

echo "\n";

// Database configuration
echo "═══════════════════════════════════════════════════════════════\n";
echo "DATABASE CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════════════\n";

echo "\nEnter database host [localhost]: ";
$db_host = trim(fgets(STDIN)) ?: 'localhost';

echo "Enter database port [3306]: ";
$db_port = trim(fgets(STDIN)) ?: '3306';

echo "Enter database name [network_security_scanner]: ";
$db_name = trim(fgets(STDIN)) ?: 'network_security_scanner';

echo "Enter database username [root]: ";
$db_user = trim(fgets(STDIN)) ?: 'root';

echo "Enter database password: ";
$db_pass = trim(fgets(STDIN));

// Test database connection
echo "\nTesting database connection... ";
try {
    $dsn = "mysql:host={$db_host};port={$db_port}";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ OK\n";
} catch (PDOException $e) {
    echo "✗ FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Create database
echo "Creating database... ";
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ OK\n";
} catch (PDOException $e) {
    echo "✗ FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Import schema
echo "Importing database schema... ";
try {
    $pdo->exec("USE {$db_name}");
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');

    // Remove USE statement from schema
    $schema = preg_replace('/USE\s+\w+;/', '', $schema);

    // Execute schema
    $pdo->exec($schema);
    echo "✓ OK\n";
} catch (PDOException $e) {
    echo "✗ FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Update configuration file
echo "Updating configuration file... ";
$config = "<?php\n";
$config .= "/**\n";
$config .= " * Database Configuration\n";
$config .= " * Network Security Assessment Tool\n";
$config .= " */\n\n";
$config .= "return [\n";
$config .= "    'host' => '{$db_host}',\n";
$config .= "    'port' => '{$db_port}',\n";
$config .= "    'database' => '{$db_name}',\n";
$config .= "    'username' => '{$db_user}',\n";
$config .= "    'password' => '{$db_pass}',\n";
$config .= "    'charset' => 'utf8mb4',\n";
$config .= "    'collation' => 'utf8mb4_unicode_ci',\n";
$config .= "    'options' => [\n";
$config .= "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
$config .= "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
$config .= "        PDO::ATTR_EMULATE_PREPARES => false,\n";
$config .= "        PDO::ATTR_PERSISTENT => false\n";
$config .= "    ]\n";
$config .= "];\n";

file_put_contents(__DIR__ . '/config/database.php', $config);
echo "✓ OK\n";

// Create reports directory
echo "Creating reports directory... ";
if (!is_dir(__DIR__ . '/reports')) {
    mkdir(__DIR__ . '/reports', 0755, true);
}
echo "✓ OK\n";

// Initialize compliance controls
echo "Initializing compliance controls... ";
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/ComplianceChecker.php';

try {
    $compliance = new ComplianceChecker();
    $compliance->initializeDefaultControls();
    echo "✓ OK\n";
} catch (Exception $e) {
    echo "✗ FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}

// Setup complete
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                                                              ║\n";
echo "║     ✓ Setup Complete! Your scanner is ready to use.         ║\n";
echo "║                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Next steps:\n";
echo "  1. Access the web interface at: http://localhost/networkscan/\n";
echo "  2. Or run a scan from CLI: php scan_cli.php --help\n";
echo "\n";

echo "Quick start examples:\n";
echo "  # Quick scan\n";
echo "  php scan_cli.php --target 192.168.1.1 --type quick\n\n";

echo "  # Full scan with report\n";
echo "  php scan_cli.php --target 192.168.1.0/24 --report html\n\n";

echo "⚠️  Important: Only scan networks you have permission to test!\n";
echo "\n";
