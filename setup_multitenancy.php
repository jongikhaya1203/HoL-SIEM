<?php
/**
 * Setup Multi-Tenancy for Network Security Scanner
 * Run once: php setup_multitenancy.php
 */
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "=== Setting up Multi-Tenancy ===\n\n";

// Step 1: Create tenants table
echo "Step 1: Creating tenants table...\n";
$sql = "CREATE TABLE IF NOT EXISTS tenants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_name VARCHAR(255) NOT NULL UNIQUE,
    tenant_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    max_agents INT DEFAULT 100,
    max_scans_per_day INT DEFAULT 1000,
    INDEX idx_tenant_code (tenant_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $db->query($sql, []);
    echo "✓ Tenants table created\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Step 2: Add tenant_id to agent_api_keys
echo "\nStep 2: Adding tenant_id to agent_api_keys...\n";
try {
    // Check if column exists
    $checkColumn = $db->fetchOne(
        "SHOW COLUMNS FROM agent_api_keys LIKE 'tenant_id'",
        []
    );

    if (!$checkColumn) {
        $db->query(
            "ALTER TABLE agent_api_keys
             ADD COLUMN tenant_id INT AFTER id,
             ADD FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE",
            []
        );
        echo "✓ Added tenant_id to agent_api_keys\n";
    } else {
        echo "  tenant_id column already exists\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Step 3: Add tenant_id to agents
echo "\nStep 3: Adding tenant_id to agents...\n";
try {
    $checkColumn = $db->fetchOne(
        "SHOW COLUMNS FROM agents LIKE 'tenant_id'",
        []
    );

    if (!$checkColumn) {
        $db->query(
            "ALTER TABLE agents
             ADD COLUMN tenant_id INT AFTER id,
             ADD INDEX idx_tenant_id (tenant_id),
             ADD FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE",
            []
        );
        echo "✓ Added tenant_id to agents\n";
    } else {
        echo "  tenant_id column already exists\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Step 3.5: Verify table structure
echo "\nStep 3.5: Verifying tenants table structure...\n";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM tenants", []);
    $columnNames = array_column($columns, 'Field');

    if (!in_array('description', $columnNames)) {
        echo "  Adding missing description column...\n";
        $db->query("ALTER TABLE tenants ADD COLUMN description TEXT AFTER tenant_code", []);
    }

    if (!in_array('contact_email', $columnNames)) {
        echo "  Adding missing contact_email column...\n";
        $db->query("ALTER TABLE tenants ADD COLUMN contact_email VARCHAR(255) AFTER description", []);
    }

    if (!in_array('contact_phone', $columnNames)) {
        echo "  Adding missing contact_phone column...\n";
        $db->query("ALTER TABLE tenants ADD COLUMN contact_phone VARCHAR(50) AFTER contact_email", []);
    }

    echo "✓ Table structure verified\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Step 4: Create default tenant
echo "\nStep 4: Creating default tenant...\n";
try {
    $existingTenant = $db->fetchOne(
        "SELECT id FROM tenants WHERE tenant_code = 'DEFAULT'",
        []
    );

    if (!$existingTenant) {
        $db->query(
            "INSERT INTO tenants (tenant_name, tenant_code, description, status, created_at)
             VALUES (?, ?, ?, 'active', NOW())",
            ['Default Organization', 'DEFAULT', 'Default tenant for initial setup']
        );
        $defaultTenantId = $db->lastInsertId();
        echo "✓ Default tenant created (ID: $defaultTenantId)\n";
    } else {
        $defaultTenantId = $existingTenant['id'];
        echo "  Default tenant already exists (ID: $defaultTenantId)\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 5: Migrate existing API keys
echo "\nStep 5: Migrating existing API keys...\n";
try {
    $keysWithoutTenant = $db->fetchAll(
        "SELECT id FROM agent_api_keys WHERE tenant_id IS NULL",
        []
    );

    if (!empty($keysWithoutTenant)) {
        $count = count($keysWithoutTenant);
        $db->query(
            "UPDATE agent_api_keys SET tenant_id = ? WHERE tenant_id IS NULL",
            [$defaultTenantId]
        );
        echo "✓ Migrated $count API key(s) to default tenant\n";
    } else {
        echo "  No API keys to migrate\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Step 6: Migrate existing agents
echo "\nStep 6: Migrating existing agents...\n";
try {
    $agentsWithoutTenant = $db->fetchAll(
        "SELECT id FROM agents WHERE tenant_id IS NULL",
        []
    );

    if (!empty($agentsWithoutTenant)) {
        $count = count($agentsWithoutTenant);
        $db->query(
            "UPDATE agents SET tenant_id = ? WHERE tenant_id IS NULL",
            [$defaultTenantId]
        );
        echo "✓ Migrated $count agent(s) to default tenant\n";
    } else {
        echo "  No agents to migrate\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// Step 7: Create session management table
echo "\nStep 7: Creating tenant session table...\n";
$sql = "CREATE TABLE IF NOT EXISTS tenant_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    tenant_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    last_activity DATETIME NOT NULL,
    ip_address VARCHAR(45),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $db->query($sql, []);
    echo "✓ Tenant sessions table created\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Multi-Tenancy Setup Complete! ===\n\n";

// Display summary
$tenants = $db->fetchAll("SELECT * FROM tenants", []);
$apiKeys = $db->fetchAll(
    "SELECT ak.*, t.tenant_name
     FROM agent_api_keys ak
     JOIN tenants t ON ak.tenant_id = t.id",
    []
);
$agents = $db->fetchAll(
    "SELECT tenant_id, COUNT(*) as count
     FROM agents
     GROUP BY tenant_id",
    []
);

echo "Summary:\n";
echo "--------\n";
echo "Tenants: " . count($tenants) . "\n";
foreach ($tenants as $tenant) {
    echo "  - {$tenant['tenant_name']} ({$tenant['tenant_code']}) - {$tenant['status']}\n";
}

echo "\nAPI Keys by Tenant:\n";
foreach ($apiKeys as $key) {
    echo "  - {$key['tenant_name']}: " . substr($key['api_key'], 0, 16) . "...\n";
}

echo "\nAgents by Tenant:\n";
foreach ($agents as $agent) {
    $tenant = $db->fetchOne("SELECT tenant_name FROM tenants WHERE id = ?", [$agent['tenant_id']]);
    echo "  - {$tenant['tenant_name']}: {$agent['count']} agent(s)\n";
}

echo "\nNext steps:\n";
echo "1. Access tenant management at: http://localhost/networkscan/tenants.php\n";
echo "2. Create new tenants and generate API keys\n";
echo "3. Deploy agents with tenant-specific API keys\n";
