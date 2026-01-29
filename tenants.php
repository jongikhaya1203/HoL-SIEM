<?php
/**
 * Tenant Management Interface
 * Create, view, and manage tenants and their API keys
 */
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_tenant':
                try {
                    $tenantName = $_POST['tenant_name'] ?? '';
                    $tenantCode = strtoupper($_POST['tenant_code'] ?? '');
                    $description = $_POST['description'] ?? '';
                    $contactEmail = $_POST['contact_email'] ?? '';
                    $contactPhone = $_POST['contact_phone'] ?? '';
                    $maxAgents = (int)($_POST['max_agents'] ?? 100);
                    $maxScansPerDay = (int)($_POST['max_scans_per_day'] ?? 1000);

                    if (empty($tenantName) || empty($tenantCode)) {
                        throw new Exception('Tenant name and code are required');
                    }

                    $db->query(
                        "INSERT INTO tenants (tenant_name, tenant_code, description, contact_email, contact_phone, status, created_at, max_agents, max_scans_per_day)
                         VALUES (?, ?, ?, ?, ?, 'active', NOW(), ?, ?)",
                        [$tenantName, $tenantCode, $description, $contactEmail, $contactPhone, $maxAgents, $maxScansPerDay]
                    );

                    $message = "Tenant '$tenantName' created successfully!";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = "Error creating tenant: " . $e->getMessage();
                    $messageType = 'error';
                }
                break;

            case 'generate_api_key':
                try {
                    $tenantId = (int)$_POST['tenant_id'];
                    $description = $_POST['key_description'] ?? 'Generated API Key';

                    // Generate unique API key
                    $apiKey = bin2hex(random_bytes(32));

                    $db->query(
                        "INSERT INTO agent_api_keys (tenant_id, api_key, description, status, created_at)
                         VALUES (?, ?, ?, 'active', NOW())",
                        [$tenantId, $apiKey, $description]
                    );

                    $message = "API key generated successfully: <code>$apiKey</code>";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = "Error generating API key: " . $e->getMessage();
                    $messageType = 'error';
                }
                break;

            case 'update_tenant_status':
                try {
                    $tenantId = (int)$_POST['tenant_id'];
                    $status = $_POST['status'];

                    $db->query(
                        "UPDATE tenants SET status = ?, updated_at = NOW() WHERE id = ?",
                        [$status, $tenantId]
                    );

                    $message = "Tenant status updated to '$status'";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = "Error updating tenant: " . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Fetch all tenants with statistics
$tenants = $db->fetchAll(
    "SELECT t.*,
            (SELECT COUNT(*) FROM agent_api_keys WHERE tenant_id = t.id) as api_key_count,
            (SELECT COUNT(*) FROM agents WHERE tenant_id = t.id) as agent_count
     FROM tenants t
     ORDER BY t.created_at DESC",
    []
);

// Fetch all API keys
$apiKeys = $db->fetchAll(
    "SELECT ak.*, t.tenant_name, t.tenant_code
     FROM agent_api_keys ak
     JOIN tenants t ON ak.tenant_id = t.id
     ORDER BY ak.created_at DESC",
    []
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Management - Network Security Scanner</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .subtitle {
            color: rgba(255,255,255,0.9);
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.1em;
        }

        .nav {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav a {
            color: #667eea;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .nav a:hover {
            background: #667eea;
            color: white;
        }

        .message {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .message.success {
            border-left: 4px solid #10b981;
            background: #d1fae5;
        }

        .message.error {
            border-left: 4px solid #ef4444;
            background: #fee2e2;
        }

        .message code {
            background: rgba(0,0,0,0.1);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 5px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card h2 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 0.9em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            color: #667eea;
            font-weight: 600;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #10b981;
        }

        .status-suspended {
            background: #fee2e2;
            color: #ef4444;
        }

        .status-inactive {
            background: #e5e7eb;
            color: #6b7280;
        }

        .code-box {
            background: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
            word-break: break-all;
        }

        .actions {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tenant Management</h1>
        <p class="subtitle">Manage organizations and generate API keys</p>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.html">Home</a>
            <a href="agents.php">Agents</a>
            <a href="tenants.php">Tenants</a>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= count($tenants) ?></div>
                <div class="stat-label">Total Tenants</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= array_sum(array_column($tenants, 'agent_count')) ?></div>
                <div class="stat-label">Total Agents</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($apiKeys) ?></div>
                <div class="stat-label">API Keys</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($tenants, fn($t) => $t['status'] === 'active')) ?></div>
                <div class="stat-label">Active Tenants</div>
            </div>
        </div>

        <!-- Create Tenant Form -->
        <div class="card">
            <h2>Create New Tenant</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_tenant">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tenant Name *</label>
                        <input type="text" name="tenant_name" required placeholder="e.g., Acme Corporation">
                    </div>
                    <div class="form-group">
                        <label>Tenant Code *</label>
                        <input type="text" name="tenant_code" required placeholder="e.g., ACME" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Contact Email</label>
                        <input type="email" name="contact_email" placeholder="admin@example.com">
                    </div>
                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="text" name="contact_phone" placeholder="+1-555-0123">
                    </div>
                    <div class="form-group">
                        <label>Max Agents</label>
                        <input type="number" name="max_agents" value="100" min="1">
                    </div>
                    <div class="form-group">
                        <label>Max Scans/Day</label>
                        <input type="number" name="max_scans_per_day" value="1000" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Optional description"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Create Tenant</button>
            </form>
        </div>

        <!-- Tenants List -->
        <div class="card">
            <h2>Existing Tenants</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tenant Name</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Agents</th>
                        <th>API Keys</th>
                        <th>Contact</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tenants as $tenant): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($tenant['tenant_name']) ?></strong></td>
                        <td><?= htmlspecialchars($tenant['tenant_code']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $tenant['status'] ?>">
                                <?= ucfirst($tenant['status']) ?>
                            </span>
                        </td>
                        <td><?= $tenant['agent_count'] ?> / <?= $tenant['max_agents'] ?? 100 ?></td>
                        <td><?= $tenant['api_key_count'] ?></td>
                        <td><?= htmlspecialchars($tenant['contact_email'] ?? '-') ?></td>
                        <td><?= date('M d, Y', strtotime($tenant['created_at'])) ?></td>
                        <td class="actions">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="generate_api_key">
                                <input type="hidden" name="tenant_id" value="<?= $tenant['id'] ?>">
                                <input type="hidden" name="key_description" value="API Key for <?= htmlspecialchars($tenant['tenant_name']) ?>">
                                <button type="submit" class="btn btn-success btn-small">Generate Key</button>
                            </form>
                            <?php if ($tenant['status'] === 'active'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_tenant_status">
                                <input type="hidden" name="tenant_id" value="<?= $tenant['id'] ?>">
                                <input type="hidden" name="status" value="suspended">
                                <button type="submit" class="btn btn-warning btn-small">Suspend</button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_tenant_status">
                                <input type="hidden" name="tenant_id" value="<?= $tenant['id'] ?>">
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="btn btn-success btn-small">Activate</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- API Keys List -->
        <div class="card">
            <h2>API Keys</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>API Key</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Used</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiKeys as $key): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($key['tenant_name']) ?></strong> (<?= htmlspecialchars($key['tenant_code']) ?>)</td>
                        <td><div class="code-box"><?= substr($key['api_key'], 0, 32) ?>...</div></td>
                        <td><?= htmlspecialchars($key['description'] ?: '-') ?></td>
                        <td>
                            <span class="status-badge status-<?= $key['status'] ?>">
                                <?= ucfirst($key['status']) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($key['created_at'])) ?></td>
                        <td><?= $key['last_used'] ? date('M d, Y H:i', strtotime($key['last_used'])) : 'Never' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
