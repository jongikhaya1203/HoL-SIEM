<?php
/**
 * Setup User Authentication System
 * Creates tenant_users table and login functionality
 */
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "=== Setting up User Authentication System ===\n\n";

try {
    // Step 1: Create tenant_users table
    echo "Step 1: Creating tenant_users table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS tenant_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        tenant_id INT NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        full_name VARCHAR(255),
        role ENUM('admin', 'user') DEFAULT 'user',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at DATETIME NOT NULL,
        last_login DATETIME,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        INDEX idx_username (username),
        INDEX idx_tenant_id (tenant_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $db->query($sql, []);
    echo "✓ tenant_users table created\n";

    // Step 2: Check if default tenant exists
    echo "\nStep 2: Checking for default tenant...\n";
    $defaultTenant = $db->fetchOne(
        "SELECT id FROM tenants WHERE tenant_code = 'DEFAULT'",
        []
    );

    if (!$defaultTenant) {
        echo "Creating default tenant...\n";
        $db->query(
            "INSERT INTO tenants (tenant_name, tenant_code, description, status, created_at)
             VALUES (?, ?, ?, 'active', NOW())",
            ['Default Organization', 'DEFAULT', 'Default tenant for initial setup']
        );
        $defaultTenantId = $db->lastInsertId();
        echo "✓ Default tenant created (ID: $defaultTenantId)\n";
    } else {
        $defaultTenantId = $defaultTenant['id'];
        echo "✓ Default tenant exists (ID: $defaultTenantId)\n";
    }

    // Step 3: Create default admin user for default tenant
    echo "\nStep 3: Creating default admin user...\n";
    $existingUser = $db->fetchOne(
        "SELECT id FROM tenant_users WHERE username = 'admin'",
        []
    );

    if (!$existingUser) {
        // Default password: admin123 (should be changed after first login)
        $defaultPassword = 'admin123';
        $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $db->query(
            "INSERT INTO tenant_users (tenant_id, username, password_hash, email, full_name, role, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW())",
            [$defaultTenantId, 'admin', $passwordHash, 'admin@localhost', 'Default Admin']
        );
        echo "✓ Default admin user created\n";
        echo "  Username: admin\n";
        echo "  Password: $defaultPassword\n";
        echo "  ⚠ IMPORTANT: Change this password after first login!\n";
    } else {
        echo "  Admin user already exists\n";
    }

    // Step 4: Create users for existing tenants
    echo "\nStep 4: Creating admin users for existing tenants...\n";
    $tenants = $db->fetchAll(
        "SELECT id, tenant_name, tenant_code FROM tenants WHERE tenant_code != 'DEFAULT'",
        []
    );

    foreach ($tenants as $tenant) {
        $username = strtolower($tenant['tenant_code']) . '_admin';
        $existingTenantUser = $db->fetchOne(
            "SELECT id FROM tenant_users WHERE tenant_id = ? AND role = 'admin'",
            [$tenant['id']]
        );

        if (!$existingTenantUser) {
            $defaultPassword = 'admin123';
            $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

            $db->query(
                "INSERT INTO tenant_users (tenant_id, username, password_hash, email, full_name, role, status, created_at)
                 VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW())",
                [
                    $tenant['id'],
                    $username,
                    $passwordHash,
                    $username . '@' . strtolower($tenant['tenant_code']) . '.local',
                    $tenant['tenant_name'] . ' Admin'
                ]
            );
            echo "  ✓ Created admin for tenant: {$tenant['tenant_name']}\n";
            echo "    Username: $username\n";
            echo "    Password: $defaultPassword\n";
        }
    }

    // Step 5: Display summary
    echo "\n=== Authentication System Setup Complete! ===\n\n";

    $allUsers = $db->fetchAll(
        "SELECT tu.*, t.tenant_name, t.tenant_code
         FROM tenant_users tu
         JOIN tenants t ON tu.tenant_id = t.id
         ORDER BY t.tenant_code, tu.username",
        []
    );

    echo "Summary of Users:\n";
    echo "----------------\n";
    foreach ($allUsers as $user) {
        echo "Tenant: {$user['tenant_name']} ({$user['tenant_code']})\n";
        echo "  Username: {$user['username']}\n";
        echo "  Role: {$user['role']}\n";
        echo "  Status: {$user['status']}\n";
        echo "  Email: {$user['email']}\n\n";
    }

    echo "\nNext Steps:\n";
    echo "1. Access the login page at: http://localhost/networkscan/login.php\n";
    echo "2. Log in with username: admin, password: admin123\n";
    echo "3. Change the default password immediately\n";
    echo "4. Create additional users from the Tenants page\n\n";

    // Check for API keys
    echo "Checking for API Keys:\n";
    echo "---------------------\n";
    $apiKeys = $db->fetchAll(
        "SELECT ak.*, t.tenant_name
         FROM agent_api_keys ak
         JOIN tenants t ON ak.tenant_id = t.id
         WHERE ak.status = 'active'",
        []
    );

    if (!empty($apiKeys)) {
        foreach ($apiKeys as $key) {
            echo "Tenant: {$key['tenant_name']}\n";
            echo "  API Key: " . substr($key['api_key'], 0, 20) . "...\n";
            echo "  Download Windows Agent:\n";
            echo "    http://localhost/networkscan/download_agent.php?api_key={$key['api_key']}&platform=windows\n";
            echo "  Download Linux Agent:\n";
            echo "    http://localhost/networkscan/download_agent.php?api_key={$key['api_key']}&platform=linux\n\n";
        }
    } else {
        echo "No API keys found. Create API keys from the Tenants page after logging in.\n\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
