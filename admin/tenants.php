<?php
/**
 * CMS Admin Portal - Tenant Management
 */
session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_tenant') {
        $tenant_name = trim($_POST['tenant_name'] ?? '');
        $tenant_code = strtoupper(trim($_POST['tenant_code'] ?? ''));
        $contact_name = trim($_POST['contact_name'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $max_users = intval($_POST['max_users'] ?? 10);
        $max_storage_gb = intval($_POST['max_storage_gb'] ?? 10);
        $status = $_POST['status'] ?? 'active';

        if ($tenant_name && $tenant_code && $contact_email) {
            try {
                // Check if tenant code already exists
                $existing = $db->fetchOne("SELECT id FROM tenants WHERE tenant_code = ?", [$tenant_code]);

                if ($existing) {
                    $message = 'Tenant code already exists!';
                    $messageType = 'error';
                } else {
                    // Create tenants table if not exists
                    $db->query("CREATE TABLE IF NOT EXISTS tenants (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        tenant_name VARCHAR(255) NOT NULL,
                        tenant_code VARCHAR(50) UNIQUE NOT NULL,
                        contact_name VARCHAR(255),
                        contact_email VARCHAR(255) NOT NULL,
                        contact_phone VARCHAR(50),
                        max_users INT DEFAULT 10,
                        max_storage_gb INT DEFAULT 10,
                        current_users INT DEFAULT 0,
                        current_storage_gb DECIMAL(10,2) DEFAULT 0,
                        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_tenant_code (tenant_code),
                        INDEX idx_status (status)
                    )");

                    $db->query(
                        "INSERT INTO tenants (tenant_name, tenant_code, contact_name, contact_email, contact_phone, max_users, max_storage_gb, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$tenant_name, $tenant_code, $contact_name, $contact_email, $contact_phone, $max_users, $max_storage_gb, $status]
                    );

                    $message = 'Tenant created successfully!';
                    $messageType = 'success';
                }
            } catch (Exception $e) {
                $message = 'Error creating tenant: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'Please fill all required fields';
            $messageType = 'error';
        }
    } elseif ($action === 'update_tenant') {
        $tenant_id = intval($_POST['tenant_id'] ?? 0);
        $tenant_name = trim($_POST['tenant_name'] ?? '');
        $contact_name = trim($_POST['contact_name'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $max_users = intval($_POST['max_users'] ?? 10);
        $max_storage_gb = intval($_POST['max_storage_gb'] ?? 10);
        $status = $_POST['status'] ?? 'active';

        if ($tenant_id && $tenant_name && $contact_email) {
            try {
                $db->query(
                    "UPDATE tenants SET tenant_name = ?, contact_name = ?, contact_email = ?, contact_phone = ?,
                     max_users = ?, max_storage_gb = ?, status = ? WHERE id = ?",
                    [$tenant_name, $contact_name, $contact_email, $contact_phone, $max_users, $max_storage_gb, $status, $tenant_id]
                );

                $message = 'Tenant updated successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error updating tenant: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_tenant') {
        $tenant_id = intval($_POST['tenant_id'] ?? 0);

        if ($tenant_id) {
            try {
                $db->query("DELETE FROM tenants WHERE id = ?", [$tenant_id]);
                $message = 'Tenant deleted successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error deleting tenant: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Create table if not exists
try {
    $db->query("CREATE TABLE IF NOT EXISTS tenants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_name VARCHAR(255) NOT NULL,
        tenant_code VARCHAR(50) UNIQUE NOT NULL,
        contact_name VARCHAR(255),
        contact_email VARCHAR(255) NOT NULL,
        contact_phone VARCHAR(50),
        max_users INT DEFAULT 10,
        max_storage_gb INT DEFAULT 10,
        current_users INT DEFAULT 0,
        current_storage_gb DECIMAL(10,2) DEFAULT 0,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tenant_code (tenant_code),
        INDEX idx_status (status)
    )");
} catch (Exception $e) {
    // Table might already exist
}

// Get all tenants
$tenants = $db->fetchAll("SELECT * FROM tenants ORDER BY created_at DESC");

// Calculate statistics
$stats = [
    'total_tenants' => count($tenants),
    'active_tenants' => count(array_filter($tenants, fn($t) => $t['status'] === 'active')),
    'inactive_tenants' => count(array_filter($tenants, fn($t) => $t['status'] === 'inactive')),
    'suspended_tenants' => count(array_filter($tenants, fn($t) => $t['status'] === 'suspended')),
    'total_users' => array_sum(array_column($tenants, 'current_users')),
    'total_storage' => array_sum(array_column($tenants, 'current_storage_gb'))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Management - Network Security Scanner</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .tenant-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .tenant-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .tenant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .tenant-card.inactive {
            border-left-color: #ff9800;
            opacity: 0.8;
        }

        .tenant-card.suspended {
            border-left-color: #f44336;
            opacity: 0.7;
        }

        .tenant-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .tenant-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .tenant-code {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
        }

        .tenant-info {
            margin: 10px 0;
            font-size: 14px;
            color: #666;
        }

        .tenant-info strong {
            color: #333;
        }

        .tenant-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
        }

        .tenant-stat {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .tenant-stat-value {
            font-size: 20px;
            font-weight: 600;
            color: #667eea;
        }

        .tenant-stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }

        .progress-bar {
            background: #e0e0e0;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s;
        }

        .progress-fill.warning {
            background: linear-gradient(90deg, #ff9800, #ff6b35);
        }

        .progress-fill.danger {
            background: linear-gradient(90deg, #f44336, #e91e63);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.inactive {
            background: #fff3e0;
            color: #e65100;
        }

        .status-badge.suspended {
            background: #ffebee;
            color: #c62828;
        }

        .tenant-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .tenant-actions button,
        .tenant-actions a {
            flex: 1;
            padding: 8px 12px;
            font-size: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn-edit {
            background: #2196F3;
            color: white;
        }

        .btn-edit:hover {
            background: #1976D2;
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #d32f2f;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-close {
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .modal-close:hover {
            color: #333;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <?php include 'sidebar.php'; ?>

            <div class="content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h1>Tenant Management</h1>
                    <button class="btn btn-primary" onclick="openModal('createModal')">➕ Add New Tenant</button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🏢</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['total_tenants'] ?></div>
                            <div class="stat-label">Total Tenants</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['active_tenants'] ?></div>
                            <div class="stat-label">Active Tenants</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['total_users'] ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">💾</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= number_format($stats['total_storage'], 1) ?> GB</div>
                            <div class="stat-label">Total Storage</div>
                        </div>
                    </div>
                </div>

                <!-- Tenants Grid -->
                <?php if (empty($tenants)): ?>
                <div class="card" style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🏢</div>
                    <h2>No Tenants Yet</h2>
                    <p style="color: #666; margin-bottom: 20px;">Create your first tenant to get started with multi-tenant management</p>
                    <button class="btn btn-primary" onclick="openModal('createModal')">Create First Tenant</button>
                </div>
                <?php else: ?>
                <div class="tenant-grid">
                    <?php foreach ($tenants as $tenant): ?>
                    <?php
                        $user_percent = $tenant['max_users'] > 0 ? ($tenant['current_users'] / $tenant['max_users']) * 100 : 0;
                        $storage_percent = $tenant['max_storage_gb'] > 0 ? ($tenant['current_storage_gb'] / $tenant['max_storage_gb']) * 100 : 0;

                        $user_class = $user_percent >= 90 ? 'danger' : ($user_percent >= 75 ? 'warning' : '');
                        $storage_class = $storage_percent >= 90 ? 'danger' : ($storage_percent >= 75 ? 'warning' : '');
                    ?>
                    <div class="tenant-card <?= $tenant['status'] ?>">
                        <div class="tenant-header">
                            <div>
                                <div class="tenant-name"><?= htmlspecialchars($tenant['tenant_name']) ?></div>
                                <span class="tenant-code"><?= htmlspecialchars($tenant['tenant_code']) ?></span>
                            </div>
                            <span class="status-badge <?= $tenant['status'] ?>"><?= ucfirst($tenant['status']) ?></span>
                        </div>

                        <div class="tenant-info">
                            <strong>📧 Contact:</strong> <?= htmlspecialchars($tenant['contact_email']) ?><br>
                            <?php if ($tenant['contact_name']): ?>
                            <strong>👤 Name:</strong> <?= htmlspecialchars($tenant['contact_name']) ?><br>
                            <?php endif; ?>
                            <?php if ($tenant['contact_phone']): ?>
                            <strong>📞 Phone:</strong> <?= htmlspecialchars($tenant['contact_phone']) ?><br>
                            <?php endif; ?>
                            <strong>📅 Created:</strong> <?= date('M d, Y', strtotime($tenant['created_at'])) ?>
                        </div>

                        <div class="tenant-stats">
                            <div class="tenant-stat">
                                <div class="tenant-stat-value"><?= $tenant['current_users'] ?>/<?= $tenant['max_users'] ?></div>
                                <div class="tenant-stat-label">Users</div>
                                <div class="progress-bar">
                                    <div class="progress-fill <?= $user_class ?>" style="width: <?= min($user_percent, 100) ?>%"></div>
                                </div>
                            </div>

                            <div class="tenant-stat">
                                <div class="tenant-stat-value"><?= number_format($tenant['current_storage_gb'], 1) ?>/<?= $tenant['max_storage_gb'] ?> GB</div>
                                <div class="tenant-stat-label">Storage</div>
                                <div class="progress-bar">
                                    <div class="progress-fill <?= $storage_class ?>" style="width: <?= min($storage_percent, 100) ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="tenant-actions">
                            <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($tenant)) ?>)">✏️ Edit</button>
                            <button class="btn-delete" onclick="deleteTenant(<?= $tenant['id'] ?>, '<?= htmlspecialchars($tenant['tenant_name']) ?>')">🗑️ Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Tenant Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Tenant</h2>
                <span class="modal-close" onclick="closeModal('createModal')">&times;</span>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="create_tenant">

                <div class="form-row">
                    <div class="form-group">
                        <label for="tenant_name">Tenant Name *</label>
                        <input type="text" id="tenant_name" name="tenant_name" required>
                    </div>

                    <div class="form-group">
                        <label for="tenant_code">Tenant Code *</label>
                        <input type="text" id="tenant_code" name="tenant_code" maxlength="50"
                               style="text-transform: uppercase;" required
                               placeholder="e.g., ACME001">
                        <small>Unique identifier (letters, numbers, underscore only)</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="contact_name">Contact Name</label>
                    <input type="text" id="contact_name" name="contact_name">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email">Contact Email *</label>
                        <input type="email" id="contact_email" name="contact_email" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="max_users">Max Users</label>
                        <input type="number" id="max_users" name="max_users" value="10" min="1">
                    </div>

                    <div class="form-group">
                        <label for="max_storage_gb">Max Storage (GB)</label>
                        <input type="number" id="max_storage_gb" name="max_storage_gb" value="10" min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Tenant</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Tenant Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Tenant</h2>
                <span class="modal-close" onclick="closeModal('editModal')">&times;</span>
            </div>

            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_tenant">
                <input type="hidden" name="tenant_id" id="edit_tenant_id">

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_tenant_name">Tenant Name *</label>
                        <input type="text" id="edit_tenant_name" name="tenant_name" required>
                    </div>

                    <div class="form-group">
                        <label>Tenant Code</label>
                        <input type="text" id="edit_tenant_code" disabled style="background: #f5f5f5;">
                        <small>Tenant code cannot be changed</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_contact_name">Contact Name</label>
                    <input type="text" id="edit_contact_name" name="contact_name">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_contact_email">Contact Email *</label>
                        <input type="email" id="edit_contact_email" name="contact_email" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_contact_phone">Contact Phone</label>
                        <input type="tel" id="edit_contact_phone" name="contact_phone">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_max_users">Max Users</label>
                        <input type="number" id="edit_max_users" name="max_users" min="1">
                    </div>

                    <div class="form-group">
                        <label for="edit_max_storage_gb">Max Storage (GB)</label>
                        <input type="number" id="edit_max_storage_gb" name="max_storage_gb" min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Tenant</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Form (hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_tenant">
        <input type="hidden" name="tenant_id" id="delete_tenant_id">
    </form>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function openEditModal(tenant) {
            document.getElementById('edit_tenant_id').value = tenant.id;
            document.getElementById('edit_tenant_name').value = tenant.tenant_name;
            document.getElementById('edit_tenant_code').value = tenant.tenant_code;
            document.getElementById('edit_contact_name').value = tenant.contact_name || '';
            document.getElementById('edit_contact_email').value = tenant.contact_email;
            document.getElementById('edit_contact_phone').value = tenant.contact_phone || '';
            document.getElementById('edit_max_users').value = tenant.max_users;
            document.getElementById('edit_max_storage_gb').value = tenant.max_storage_gb;
            document.getElementById('edit_status').value = tenant.status;

            openModal('editModal');
        }

        function deleteTenant(tenantId, tenantName) {
            if (confirm('Are you sure you want to delete tenant "' + tenantName + '"?\n\nThis action cannot be undone!')) {
                document.getElementById('delete_tenant_id').value = tenantId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

        // Auto-uppercase tenant code input
        document.getElementById('tenant_code').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
        });
    </script>
</body>
</html>
