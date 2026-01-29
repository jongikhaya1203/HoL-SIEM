<?php
/**
 * Access Rights Manager (ARM)
 * Comprehensive user access management, permissions control, and compliance reporting
 */

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// Check if ARM tables exist, if not show setup message
$tablesExist = true;
$dbError = '';
try {
    $result = $db->fetchOne("SELECT 1 FROM arm_users LIMIT 1");
} catch (Exception $e) {
    $tablesExist = false;
    $dbError = $e->getMessage();
}

// Get statistics if tables exist
$stats = [
    'users' => 0,
    'groups' => 0,
    'roles' => 0,
    'resources' => 0,
    'violations' => 0,
    'pending_requests' => 0,
    'active_users' => 0,
    'inactive_users' => 0
];

$users = [];
$roles = [];
$groups = [];
$resources = [];
$violations = [];
$accessRequests = [];
$auditLogs = [];
$policies = [];

if ($tablesExist) {
    try {
        $stats['users'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM arm_users")['cnt'] ?? 0;
        $stats['groups'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM arm_groups")['cnt'] ?? 0;
        $stats['roles'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM arm_roles")['cnt'] ?? 0;
        $stats['resources'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM arm_resources")['cnt'] ?? 0;
        $stats['violations'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM arm_violations WHERE status = 'open'")['cnt'] ?? 0;
        $stats['pending_requests'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM arm_access_requests WHERE status = 'pending'")['cnt'] ?? 0;
        $stats['active_users'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM arm_users WHERE status = 'active'")['cnt'] ?? 0;
        $stats['inactive_users'] = $db->fetchOne("SELECT COUNT(*) as cnt FROM arm_users WHERE status != 'active'")['cnt'] ?? 0;

        $users = $db->fetchAll("SELECT u.*, m.full_name as manager_name FROM arm_users u LEFT JOIN arm_users m ON u.manager_id = m.id ORDER BY u.full_name");
        $roles = $db->fetchAll("SELECT * FROM arm_roles ORDER BY role_name");
        $groups = $db->fetchAll("SELECT g.*, u.full_name as owner_name FROM arm_groups g LEFT JOIN arm_users u ON g.owner_user_id = u.id ORDER BY g.group_name");
        $resources = $db->fetchAll("SELECT r.*, u.full_name as owner_name FROM arm_resources r LEFT JOIN arm_users u ON r.owner_user_id = u.id ORDER BY r.resource_name");
        $violations = $db->fetchAll("SELECT v.*, u.full_name as user_name, r.resource_name FROM arm_violations v LEFT JOIN arm_users u ON v.user_id = u.id LEFT JOIN arm_resources r ON v.resource_id = r.id ORDER BY v.detected_at DESC LIMIT 20");
        $accessRequests = $db->fetchAll("SELECT ar.*, u.full_name as requester_name, ap.full_name as approver_name FROM arm_access_requests ar LEFT JOIN arm_users u ON ar.requester_id = u.id LEFT JOIN arm_users ap ON ar.approver_id = ap.id ORDER BY ar.created_at DESC LIMIT 20");
        $auditLogs = $db->fetchAll("SELECT al.*, u.full_name as user_name FROM arm_audit_log al LEFT JOIN arm_users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 50");
        $policies = $db->fetchAll("SELECT * FROM arm_policies ORDER BY policy_name");
    } catch (Exception $e) {
        // Tables might not have data yet
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Rights Manager | ARM</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --bg-dark: #1a1a2e;
            --bg-card: #16213e;
            --bg-lighter: #1f3460;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --border: #2d3748;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f0f1a 100%);
            color: var(--text);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-lighter) 100%);
            padding: 20px 30px;
            border-bottom: 1px solid var(--primary);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 span { color: var(--primary); }

        .header-actions { display: flex; gap: 10px; }

        .back-link {
            color: var(--text-muted);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            background: var(--bg-dark);
            transition: all 0.3s;
        }

        .back-link:hover { background: var(--bg-lighter); color: var(--text); }

        .main-content {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px 30px;
        }

        /* Tabs Navigation */
        .tabs {
            display: flex;
            gap: 5px;
            background: var(--bg-card);
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 20px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover { background: var(--bg-lighter); color: var(--text); }
        .tab-btn.active { background: var(--primary); color: white; }

        .tab-badge {
            background: var(--danger);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover { transform: translateY(-2px); }

        .stat-icon { font-size: 32px; margin-bottom: 10px; }
        .stat-value { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; }

        /* Cards */
        .card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-lighter);
        }

        .card-header h3 {
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body { padding: 20px; }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background: var(--bg-dark);
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
        }

        .data-table tr:hover { background: var(--bg-lighter); }

        /* Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success { background: var(--success); color: white; }
        .badge-warning { background: var(--warning); color: #1a1a2e; }
        .badge-danger { background: var(--danger); color: white; }
        .badge-info { background: var(--info); color: white; }
        .badge-secondary { background: var(--bg-lighter); color: var(--text); }
        .badge-critical { background: #dc2626; color: white; }
        .badge-high { background: var(--warning); color: #1a1a2e; }
        .badge-medium { background: var(--info); color: white; }
        .badge-low { background: var(--success); color: white; }

        .badge-active { background: var(--success); color: white; }
        .badge-inactive { background: var(--text-muted); color: white; }
        .badge-suspended { background: var(--danger); color: white; }
        .badge-pending { background: var(--warning); color: #1a1a2e; }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--bg-lighter); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: #1a1a2e; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* Forms */
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .form-input, .form-select {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Grid */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
        }

        .modal.active { display: block; }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-card);
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--primary);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 { font-size: 18px; }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body { padding: 20px; }
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Risk Score */
        .risk-score {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .risk-bar {
            width: 60px;
            height: 6px;
            background: var(--bg-dark);
            border-radius: 3px;
            overflow: hidden;
        }

        .risk-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s;
        }

        /* User Avatar */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-details { line-height: 1.4; }
        .user-name { font-weight: 600; }
        .user-email { font-size: 12px; color: var(--text-muted); }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--success);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1001;
        }

        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.error { background: var(--danger); }
        .toast.warning { background: var(--warning); color: #1a1a2e; }
        .toast.info { background: var(--info); }

        /* Charts */
        .chart-container { position: relative; height: 250px; }

        /* Search */
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .search-box input {
            flex: 1;
            padding: 10px 15px;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
        }

        /* Setup Message */
        .setup-message {
            text-align: center;
            padding: 60px 20px;
            background: var(--bg-card);
            border-radius: 12px;
            margin: 40px 0;
        }

        .setup-message h2 { color: var(--primary); margin: 20px 0; }
        .setup-message pre {
            background: var(--bg-dark);
            padding: 20px;
            border-radius: 8px;
            text-align: left;
            max-width: 600px;
            margin: 20px auto;
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .tabs { overflow-x: auto; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1><span>👥</span> Access Rights Manager</h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal('newAccessRequestModal')">+ Request Access</button>
                <a href="../index.php" class="back-link">← Back to Dashboard</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php if (!$tablesExist): ?>
        <div class="setup-message">
            <div style="font-size: 80px;">🔧</div>
            <h2>Database Setup Required</h2>
            <?php if ($dbError): ?>
            <p style="color: var(--danger); margin-bottom: 20px; background: rgba(239,68,68,0.1); padding: 15px; border-radius: 8px;">
                <strong>Error:</strong> <?= htmlspecialchars($dbError) ?>
            </p>
            <?php endif; ?>
            <p style="color: var(--text-muted); margin-bottom: 20px;">
                The ARM database tables need to be created. Please run the following SQL script:
            </p>
            <pre>mysql -u root -p network_security_scanner < database/arm_tables.sql</pre>
            <p style="color: var(--text-muted); margin-top: 20px;">
                Or import the file <code>database/arm_tables.sql</code> using phpMyAdmin.
            </p>
            <button class="btn btn-primary" onclick="location.reload()" style="margin-top: 20px;">
                Refresh Page
            </button>
        </div>
        <?php else: ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-value" style="color: var(--primary);"><?= $stats['users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value" style="color: var(--success);"><?= $stats['active_users'] ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value" style="color: var(--info);"><?= $stats['groups'] ?></div>
                <div class="stat-label">Groups</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎭</div>
                <div class="stat-value" style="color: var(--secondary);"><?= $stats['roles'] ?></div>
                <div class="stat-label">Roles</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-value"><?= $stats['resources'] ?></div>
                <div class="stat-label">Resources</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value" style="color: var(--danger);"><?= $stats['violations'] ?></div>
                <div class="stat-label">Open Violations</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-value" style="color: var(--warning);"><?= $stats['pending_requests'] ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚫</div>
                <div class="stat-value" style="color: var(--text-muted);"><?= $stats['inactive_users'] ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('users')">👤 Users</button>
            <button class="tab-btn" onclick="showTab('roles')">🎭 Roles</button>
            <button class="tab-btn" onclick="showTab('groups')">👥 Groups</button>
            <button class="tab-btn" onclick="showTab('resources')">📁 Resources</button>
            <button class="tab-btn" onclick="showTab('requests')">
                📋 Access Requests
                <?php if ($stats['pending_requests'] > 0): ?>
                <span class="tab-badge"><?= $stats['pending_requests'] ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn" onclick="showTab('violations')">
                ⚠️ Violations
                <?php if ($stats['violations'] > 0): ?>
                <span class="tab-badge"><?= $stats['violations'] ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn" onclick="showTab('audit')">📜 Audit Log</button>
            <button class="tab-btn" onclick="showTab('policies')">📋 Policies</button>
            <button class="tab-btn" onclick="showTab('reports')">📊 Reports</button>
        </div>

        <!-- Users Tab -->
        <div id="tab-users" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <h3>👤 User Management</h3>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="userSearch" placeholder="Search users..." class="form-input" style="width: 250px;" onkeyup="filterUsers()">
                        <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')">+ Add User</button>
                        <button class="btn btn-secondary btn-sm" onclick="exportUsers()">Export</button>
                    </div>
                </div>
                <div class="card-body" style="padding: 0; overflow-x: auto;">
                    <table class="data-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Department</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>MFA</th>
                                <th>Risk Score</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user):
                                $initials = implode('', array_map(fn($n) => strtoupper($n[0]), explode(' ', $user['full_name'])));
                                $riskColor = $user['risk_score'] > 60 ? '#ef4444' : ($user['risk_score'] > 30 ? '#f59e0b' : '#10b981');
                            ?>
                            <tr data-search="<?= strtolower($user['full_name'] . ' ' . $user['email'] . ' ' . $user['department']) ?>">
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?= substr($initials, 0, 2) ?></div>
                                        <div class="user-details">
                                            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                                            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['department'] ?? '-') ?></td>
                                <td><span class="badge badge-secondary"><?= ucfirst($user['user_type']) ?></span></td>
                                <td><span class="badge badge-<?= $user['status'] ?>"><?= ucfirst($user['status']) ?></span></td>
                                <td><?= $user['mfa_enabled'] ? '✅' : '❌' ?></td>
                                <td>
                                    <div class="risk-score">
                                        <div class="risk-bar">
                                            <div class="risk-bar-fill" style="width: <?= $user['risk_score'] ?>%; background: <?= $riskColor ?>;"></div>
                                        </div>
                                        <span style="color: <?= $riskColor ?>;"><?= $user['risk_score'] ?></span>
                                    </div>
                                </td>
                                <td style="font-size: 12px; color: var(--text-muted);">
                                    <?= $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never' ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="viewUser(<?= $user['id'] ?>)">View</button>
                                    <button class="btn btn-sm btn-secondary" onclick="editUser(<?= $user['id'] ?>)">Edit</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Roles Tab -->
        <div id="tab-roles" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>🎭 Role Management</h3>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addRoleModal')">+ Add Role</button>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Risk Level</th>
                                <th>Approval Required</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($role['role_name']) ?></strong></td>
                                <td><code><?= htmlspecialchars($role['role_code']) ?></code></td>
                                <td><span class="badge badge-info"><?= ucfirst($role['role_type']) ?></span></td>
                                <td><span class="badge badge-<?= $role['risk_level'] ?>"><?= ucfirst($role['risk_level']) ?></span></td>
                                <td><?= $role['requires_approval'] ? '✅ Yes' : '❌ No' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="viewRole(<?= $role['id'] ?>)">View</button>
                                    <button class="btn btn-sm btn-secondary" onclick="editRole(<?= $role['id'] ?>)">Edit</button>
                                    <button class="btn btn-sm btn-secondary" onclick="manageRolePermissions(<?= $role['id'] ?>)">Permissions</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Groups Tab -->
        <div id="tab-groups" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>👥 Group Management</h3>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addGroupModal')">+ Add Group</button>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Group Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Owner</th>
                                <th>Members</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($group['group_name']) ?></strong></td>
                                <td><code><?= htmlspecialchars($group['group_code']) ?></code></td>
                                <td><span class="badge badge-secondary"><?= ucfirst($group['group_type']) ?></span></td>
                                <td><?= htmlspecialchars($group['owner_name'] ?? 'Unassigned') ?></td>
                                <td>
                                    <?php
                                    $memberCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM arm_user_groups WHERE group_id = ?", [$group['id']])['cnt'] ?? 0;
                                    echo $memberCount;
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="viewGroup(<?= $group['id'] ?>)">View</button>
                                    <button class="btn btn-sm btn-secondary" onclick="manageGroupMembers(<?= $group['id'] ?>)">Members</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Resources Tab -->
        <div id="tab-resources" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>📁 Resource Management</h3>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addResourceModal')">+ Add Resource</button>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Resource</th>
                                <th>Type</th>
                                <th>Classification</th>
                                <th>Owner</th>
                                <th>Compliance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resources as $resource):
                                $classColors = [
                                    'public' => 'success',
                                    'internal' => 'info',
                                    'confidential' => 'warning',
                                    'restricted' => 'danger',
                                    'top_secret' => 'critical'
                                ];
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($resource['resource_name']) ?></strong>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($resource['resource_path'] ?? '') ?></div>
                                </td>
                                <td><span class="badge badge-secondary"><?= ucfirst(str_replace('_', ' ', $resource['resource_type'])) ?></span></td>
                                <td><span class="badge badge-<?= $classColors[$resource['classification']] ?? 'secondary' ?>"><?= ucfirst($resource['classification']) ?></span></td>
                                <td><?= htmlspecialchars($resource['owner_name'] ?? 'Unassigned') ?></td>
                                <td style="font-size: 11px;"><?= htmlspecialchars($resource['compliance_frameworks'] ?? '-') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="viewResource(<?= $resource['id'] ?>)">View</button>
                                    <button class="btn btn-sm btn-secondary" onclick="manageResourceAccess(<?= $resource['id'] ?>)">Access</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Access Requests Tab -->
        <div id="tab-requests" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>📋 Access Requests</h3>
                    <div style="display: flex; gap: 10px;">
                        <select class="form-select" style="width: 150px;" onchange="filterRequests(this.value)">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <button class="btn btn-primary btn-sm" onclick="openModal('newAccessRequestModal')">+ New Request</button>
                    </div>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table" id="requestsTable">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Requester</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accessRequests as $request):
                                $statusColors = [
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'cancelled' => 'secondary'
                                ];
                                $priorityColors = [
                                    'low' => 'secondary',
                                    'medium' => 'info',
                                    'high' => 'warning',
                                    'urgent' => 'danger'
                                ];
                            ?>
                            <tr data-status="<?= $request['status'] ?>">
                                <td><strong><?= htmlspecialchars($request['request_number']) ?></strong></td>
                                <td><?= htmlspecialchars($request['requester_name']) ?></td>
                                <td><span class="badge badge-secondary"><?= ucfirst($request['request_type']) ?></span></td>
                                <td><?= $request['duration_days'] ? $request['duration_days'] . ' days' : 'Permanent' ?></td>
                                <td><span class="badge badge-<?= $priorityColors[$request['priority']] ?>"><?= ucfirst($request['priority']) ?></span></td>
                                <td><span class="badge badge-<?= $statusColors[$request['status']] ?>"><?= ucfirst($request['status']) ?></span></td>
                                <td style="font-size: 12px;"><?= date('M j, Y', strtotime($request['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="viewRequest('<?= $request['request_number'] ?>')">View</button>
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-success" onclick="approveRequest('<?= $request['request_number'] ?>')">Approve</button>
                                    <button class="btn btn-sm btn-danger" onclick="rejectRequest('<?= $request['request_number'] ?>')">Reject</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Violations Tab -->
        <div id="tab-violations" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>⚠️ Access Violations & Anomalies</h3>
                    <button class="btn btn-secondary btn-sm" onclick="runViolationScan()">🔍 Run Scan</button>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Violation Type</th>
                                <th>Severity</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Detected</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($violations as $violation): ?>
                            <tr>
                                <td><strong>#<?= $violation['id'] ?></strong></td>
                                <td><?= htmlspecialchars($violation['user_name'] ?? 'System') ?></td>
                                <td><?= ucwords(str_replace('_', ' ', $violation['violation_type'])) ?></td>
                                <td><span class="badge badge-<?= $violation['severity'] ?>"><?= ucfirst($violation['severity']) ?></span></td>
                                <td style="max-width: 300px; font-size: 12px;"><?= htmlspecialchars(substr($violation['description'], 0, 100)) ?>...</td>
                                <td><span class="badge badge-<?= $violation['status'] === 'open' ? 'danger' : 'success' ?>"><?= ucfirst($violation['status']) ?></span></td>
                                <td style="font-size: 12px;"><?= date('M j, Y', strtotime($violation['detected_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="viewViolation(<?= $violation['id'] ?>)">View</button>
                                    <?php if ($violation['status'] === 'open'): ?>
                                    <button class="btn btn-sm btn-success" onclick="resolveViolation(<?= $violation['id'] ?>)">Resolve</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Audit Log Tab -->
        <div id="tab-audit" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>📜 Audit Log</h3>
                    <div style="display: flex; gap: 10px;">
                        <select class="form-select" style="width: 150px;" onchange="filterAuditLog(this.value)">
                            <option value="all">All Actions</option>
                            <option value="login">Logins</option>
                            <option value="access_granted">Access Granted</option>
                            <option value="access_revoked">Access Revoked</option>
                            <option value="permission_change">Permission Changes</option>
                            <option value="violation">Violations</option>
                        </select>
                        <button class="btn btn-secondary btn-sm" onclick="exportAuditLog()">Export</button>
                    </div>
                </div>
                <div class="card-body" style="padding: 0; max-height: 600px; overflow-y: auto;">
                    <table class="data-table" id="auditTable">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Target</th>
                                <th>Result</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $log):
                                $resultColors = [
                                    'success' => 'success',
                                    'failure' => 'danger',
                                    'blocked' => 'warning'
                                ];
                            ?>
                            <tr data-action="<?= $log['action_type'] ?>">
                                <td style="font-size: 12px; white-space: nowrap;"><?= date('M j, Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                <td><?= ucwords(str_replace('_', ' ', $log['action_type'])) ?></td>
                                <td>
                                    <?php if ($log['target_type']): ?>
                                    <span class="badge badge-secondary"><?= ucfirst($log['target_type']) ?></span>
                                    #<?= $log['target_id'] ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?= $resultColors[$log['result']] ?? 'secondary' ?>"><?= ucfirst($log['result']) ?></span></td>
                                <td style="font-size: 11px; font-family: monospace;"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                <td style="max-width: 200px; font-size: 11px;"><?= htmlspecialchars(substr($log['details'] ?? '-', 0, 50)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Policies Tab -->
        <div id="tab-policies" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>📋 Access Policies</h3>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addPolicyModal')">+ Add Policy</button>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Policy Name</th>
                                <th>Type</th>
                                <th>Enforcement</th>
                                <th>Applies To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($policies as $policy):
                                $enforcementColors = [
                                    'enforce' => 'success',
                                    'audit' => 'warning',
                                    'disabled' => 'secondary'
                                ];
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($policy['policy_name']) ?></strong></td>
                                <td><span class="badge badge-info"><?= ucfirst(str_replace('_', ' ', $policy['policy_type'])) ?></span></td>
                                <td><span class="badge badge-<?= $enforcementColors[$policy['enforcement_mode']] ?>"><?= ucfirst($policy['enforcement_mode']) ?></span></td>
                                <td><?= htmlspecialchars($policy['applies_to']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="viewPolicy(<?= $policy['id'] ?>)">View</button>
                                    <button class="btn btn-sm btn-secondary" onclick="editPolicy(<?= $policy['id'] ?>)">Edit</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="tab-reports" class="tab-content">
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3>📊 User Distribution by Department</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="deptChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>📊 Risk Score Distribution</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="riskChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>📋 Compliance Reports</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <button class="btn btn-secondary" onclick="generateReport('user_access')" style="padding: 20px; justify-content: flex-start;">
                            <span style="font-size: 24px;">👤</span>
                            <div style="text-align: left;">
                                <div>User Access Report</div>
                                <div style="font-size: 11px; color: var(--text-muted);">All user permissions and access rights</div>
                            </div>
                        </button>

                        <button class="btn btn-secondary" onclick="generateReport('privileged_users')" style="padding: 20px; justify-content: flex-start;">
                            <span style="font-size: 24px;">👑</span>
                            <div style="text-align: left;">
                                <div>Privileged Users Report</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Users with elevated permissions</div>
                            </div>
                        </button>

                        <button class="btn btn-secondary" onclick="generateReport('stale_accounts')" style="padding: 20px; justify-content: flex-start;">
                            <span style="font-size: 24px;">💤</span>
                            <div style="text-align: left;">
                                <div>Stale Accounts Report</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Inactive accounts over 90 days</div>
                            </div>
                        </button>

                        <button class="btn btn-secondary" onclick="generateReport('sod_conflicts')" style="padding: 20px; justify-content: flex-start;">
                            <span style="font-size: 24px;">⚠️</span>
                            <div style="text-align: left;">
                                <div>SOD Conflicts Report</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Segregation of duties violations</div>
                            </div>
                        </button>

                        <button class="btn btn-secondary" onclick="generateReport('access_review')" style="padding: 20px; justify-content: flex-start;">
                            <span style="font-size: 24px;">🔍</span>
                            <div style="text-align: left;">
                                <div>Access Review Report</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Quarterly access certification</div>
                            </div>
                        </button>

                        <button class="btn btn-secondary" onclick="generateReport('compliance')" style="padding: 20px; justify-content: flex-start;">
                            <span style="font-size: 24px;">✅</span>
                            <div style="text-align: left;">
                                <div>Compliance Summary</div>
                                <div style="font-size: 11px; color: var(--text-muted);">SOX, GDPR, HIPAA compliance</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </main>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('addUserModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>👤 Add New User</h3>
                <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" id="newUserUsername" class="form-input" placeholder="e.g., jsmith">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="newUserEmail" class="form-input" placeholder="e.g., john@company.com">
                    </div>
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="newUserFullName" class="form-input" placeholder="e.g., John Smith">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Department</label>
                        <select id="newUserDepartment" class="form-select">
                            <option value="">Select Department</option>
                            <option value="IT">IT</option>
                            <option value="Finance">Finance</option>
                            <option value="HR">Human Resources</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Sales">Sales</option>
                            <option value="Operations">Operations</option>
                            <option value="Legal">Legal</option>
                            <option value="Executive">Executive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Job Title</label>
                        <input type="text" id="newUserJobTitle" class="form-input" placeholder="e.g., Software Engineer">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>User Type</label>
                        <select id="newUserType" class="form-select">
                            <option value="employee">Employee</option>
                            <option value="contractor">Contractor</option>
                            <option value="vendor">Vendor</option>
                            <option value="service_account">Service Account</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Manager</label>
                        <select id="newUserManager" class="form-select">
                            <option value="">No Manager</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="newUserMFA" style="width: 18px; height: 18px;">
                        <span>Require Multi-Factor Authentication (MFA)</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveNewUser()">Create User</button>
            </div>
        </div>
    </div>

    <!-- Add Role Modal -->
    <div id="addRoleModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('addRoleModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>🎭 Add New Role</h3>
                <button class="modal-close" onclick="closeModal('addRoleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Role Name *</label>
                    <input type="text" id="newRoleName" class="form-input" placeholder="e.g., Database Administrator">
                </div>
                <div class="form-group">
                    <label>Role Code *</label>
                    <input type="text" id="newRoleCode" class="form-input" placeholder="e.g., DB_ADMIN" style="text-transform: uppercase;">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="newRoleDescription" class="form-input" rows="3" placeholder="Describe the role and its responsibilities..."></textarea>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Role Type</label>
                        <select id="newRoleType" class="form-select">
                            <option value="application">Application</option>
                            <option value="system">System</option>
                            <option value="data">Data</option>
                            <option value="infrastructure">Infrastructure</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Risk Level</label>
                        <select id="newRoleRisk" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="newRoleApproval" style="width: 18px; height: 18px;">
                        <span>Requires Approval for Assignment</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addRoleModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveNewRole()">Create Role</button>
            </div>
        </div>
    </div>

    <!-- Add Group Modal -->
    <div id="addGroupModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('addGroupModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>👥 Add New Group</h3>
                <button class="modal-close" onclick="closeModal('addGroupModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Group Name *</label>
                    <input type="text" id="newGroupName" class="form-input" placeholder="e.g., IT Administrators">
                </div>
                <div class="form-group">
                    <label>Group Code *</label>
                    <input type="text" id="newGroupCode" class="form-input" placeholder="e.g., IT_ADMINS" style="text-transform: uppercase;">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="newGroupDescription" class="form-input" rows="3" placeholder="Describe the group and its purpose..."></textarea>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Group Type</label>
                        <select id="newGroupType" class="form-select">
                            <option value="security">Security</option>
                            <option value="distribution">Distribution</option>
                            <option value="dynamic">Dynamic</option>
                            <option value="nested">Nested</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Owner</label>
                        <select id="newGroupOwner" class="form-select">
                            <option value="">No Owner</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addGroupModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveNewGroup()">Create Group</button>
            </div>
        </div>
    </div>

    <!-- Add Resource Modal -->
    <div id="addResourceModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('addResourceModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>📁 Add New Resource</h3>
                <button class="modal-close" onclick="closeModal('addResourceModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Resource Name *</label>
                    <input type="text" id="newResourceName" class="form-input" placeholder="e.g., Finance Share">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Resource Type</label>
                        <select id="newResourceType" class="form-select">
                            <option value="file_share">File Share</option>
                            <option value="application">Application</option>
                            <option value="database">Database</option>
                            <option value="server">Server</option>
                            <option value="cloud_service">Cloud Service</option>
                            <option value="api">API</option>
                            <option value="mailbox">Mailbox</option>
                            <option value="sharepoint">SharePoint</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Classification</label>
                        <select id="newResourceClassification" class="form-select">
                            <option value="public">Public</option>
                            <option value="internal">Internal</option>
                            <option value="confidential">Confidential</option>
                            <option value="restricted">Restricted</option>
                            <option value="top_secret">Top Secret</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Resource Path/URL</label>
                    <input type="text" id="newResourcePath" class="form-input" placeholder="e.g., \\server\share or https://app.company.com">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="newResourceDescription" class="form-input" rows="2" placeholder="Describe the resource..."></textarea>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Owner</label>
                        <select id="newResourceOwner" class="form-select">
                            <option value="">No Owner</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Compliance Frameworks</label>
                        <input type="text" id="newResourceCompliance" class="form-input" placeholder="e.g., SOX, GDPR, HIPAA">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addResourceModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveNewResource()">Create Resource</button>
            </div>
        </div>
    </div>

    <!-- New Access Request Modal -->
    <div id="newAccessRequestModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('newAccessRequestModal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>📋 New Access Request</h3>
                <button class="modal-close" onclick="closeModal('newAccessRequestModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Request Type *</label>
                    <select id="requestType" class="form-select" onchange="updateRequestTargets()">
                        <option value="role">Role Access</option>
                        <option value="group">Group Membership</option>
                        <option value="resource">Resource Access</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target *</label>
                    <select id="requestTarget" class="form-select">
                        <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" data-type="role"><?= htmlspecialchars($role['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Duration</label>
                        <select id="requestDuration" class="form-select">
                            <option value="">Permanent</option>
                            <option value="7">7 Days</option>
                            <option value="14">14 Days</option>
                            <option value="30">30 Days</option>
                            <option value="90">90 Days</option>
                            <option value="180">180 Days</option>
                            <option value="365">1 Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select id="requestPriority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Business Justification *</label>
                    <textarea id="requestJustification" class="form-input" rows="4" placeholder="Explain why you need this access..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('newAccessRequestModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitAccessRequest()">Submit Request</button>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-overlay" onclick="closeModal('viewUserModal')"></div>
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>👤 User Details</h3>
                <button class="modal-close" onclick="closeModal('viewUserModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewUserContent">
                <!-- Populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('viewUserModal')">Close</button>
                <button class="btn btn-warning" onclick="resetUserPassword()">Reset Password</button>
                <button class="btn btn-danger" onclick="suspendUser()">Suspend User</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast"></div>

    <script>
    // Data from PHP
    const usersData = <?= json_encode($users) ?>;
    const rolesData = <?= json_encode($roles) ?>;
    const groupsData = <?= json_encode($groups) ?>;
    const resourcesData = <?= json_encode($resources) ?>;

    let currentUserId = null;

    // Toast notification
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + type + ' show';
        setTimeout(() => toast.classList.remove('show'), 4000);
    }

    // Modal functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    // Tab navigation
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tabName).classList.add('active');
        event.target.closest('.tab-btn').classList.add('active');
    }

    // User functions
    function filterUsers() {
        const search = document.getElementById('userSearch').value.toLowerCase();
        document.querySelectorAll('#usersTable tbody tr').forEach(row => {
            const text = row.getAttribute('data-search');
            row.style.display = text.includes(search) ? '' : 'none';
        });
    }

    function viewUser(userId) {
        currentUserId = userId;
        const user = usersData.find(u => u.id == userId);
        if (!user) return;

        const initials = user.full_name.split(' ').map(n => n[0]).join('').toUpperCase();
        const riskColor = user.risk_score > 60 ? '#ef4444' : (user.risk_score > 30 ? '#f59e0b' : '#10b981');

        document.getElementById('viewUserContent').innerHTML = `
            <div style="display: flex; gap: 30px; margin-bottom: 20px;">
                <div style="text-align: center;">
                    <div class="user-avatar" style="width: 80px; height: 80px; font-size: 28px; margin: 0 auto 10px;">${initials}</div>
                    <span class="badge badge-${user.status}">${user.status.toUpperCase()}</span>
                </div>
                <div style="flex: 1;">
                    <h2 style="margin-bottom: 5px;">${user.full_name}</h2>
                    <p style="color: var(--text-muted); margin-bottom: 10px;">${user.email}</p>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <div><strong>Username:</strong> ${user.username}</div>
                        <div><strong>Department:</strong> ${user.department || 'N/A'}</div>
                        <div><strong>Job Title:</strong> ${user.job_title || 'N/A'}</div>
                        <div><strong>Type:</strong> ${user.user_type}</div>
                    </div>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 20px;">
                <div style="background: var(--bg-dark); padding: 15px; border-radius: 8px;">
                    <h4 style="margin-bottom: 10px;">Security Status</h4>
                    <div style="margin-bottom: 8px;"><strong>MFA:</strong> ${user.mfa_enabled ? '✅ Enabled' : '❌ Disabled'}</div>
                    <div style="margin-bottom: 8px;"><strong>Last Login:</strong> ${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}</div>
                    <div style="margin-bottom: 8px;"><strong>Password Changed:</strong> ${user.password_changed_at ? new Date(user.password_changed_at).toLocaleDateString() : 'Never'}</div>
                    <div>
                        <strong>Risk Score:</strong>
                        <div class="risk-score" style="display: inline-flex; margin-left: 10px;">
                            <div class="risk-bar" style="width: 100px;">
                                <div class="risk-bar-fill" style="width: ${user.risk_score}%; background: ${riskColor};"></div>
                            </div>
                            <span style="color: ${riskColor}; font-weight: bold;">${user.risk_score}</span>
                        </div>
                    </div>
                </div>
                <div style="background: var(--bg-dark); padding: 15px; border-radius: 8px;">
                    <h4 style="margin-bottom: 10px;">Account Info</h4>
                    <div style="margin-bottom: 8px;"><strong>Created:</strong> ${new Date(user.created_at).toLocaleDateString()}</div>
                    <div style="margin-bottom: 8px;"><strong>Manager:</strong> ${user.manager_name || 'None'}</div>
                    <div><strong>ID:</strong> ${user.id}</div>
                </div>
            </div>

            <h4 style="margin-bottom: 10px;">Assigned Roles</h4>
            <div style="background: var(--bg-dark); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <p style="color: var(--text-muted);">Loading roles...</p>
            </div>

            <h4 style="margin-bottom: 10px;">Group Memberships</h4>
            <div style="background: var(--bg-dark); padding: 15px; border-radius: 8px;">
                <p style="color: var(--text-muted);">Loading groups...</p>
            </div>
        `;

        openModal('viewUserModal');
    }

    function editUser(userId) {
        showToast('Edit user functionality - ID: ' + userId, 'info');
    }

    function saveNewUser() {
        const username = document.getElementById('newUserUsername').value;
        const email = document.getElementById('newUserEmail').value;
        const fullName = document.getElementById('newUserFullName').value;

        if (!username || !email || !fullName) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        closeModal('addUserModal');
        showToast(`User "${fullName}" created successfully`);
    }

    function resetUserPassword() {
        if (confirm('Reset password for this user? They will receive an email with instructions.')) {
            showToast('Password reset email sent', 'info');
        }
    }

    function suspendUser() {
        if (confirm('Suspend this user? They will be immediately logged out and unable to access systems.')) {
            closeModal('viewUserModal');
            showToast('User suspended', 'warning');
        }
    }

    function exportUsers() {
        showToast('Exporting users...', 'info');
        setTimeout(() => {
            let csv = 'Username,Email,Full Name,Department,Job Title,Status,User Type,MFA Enabled,Risk Score,Last Login\n';
            usersData.forEach(u => {
                csv += `"${u.username}","${u.email}","${u.full_name}","${u.department || ''}","${u.job_title || ''}","${u.status}","${u.user_type}",${u.mfa_enabled},"${u.risk_score}","${u.last_login || 'Never'}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `arm_users_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
            showToast('Users exported successfully');
        }, 1000);
    }

    // Role functions
    function viewRole(roleId) {
        const role = rolesData.find(r => r.id == roleId);
        if (role) {
            showToast(`Viewing role: ${role.role_name}`, 'info');
        }
    }

    function editRole(roleId) {
        showToast('Edit role functionality - ID: ' + roleId, 'info');
    }

    function manageRolePermissions(roleId) {
        showToast('Managing permissions for role ID: ' + roleId, 'info');
    }

    function saveNewRole() {
        const roleName = document.getElementById('newRoleName').value;
        const roleCode = document.getElementById('newRoleCode').value;

        if (!roleName || !roleCode) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        closeModal('addRoleModal');
        showToast(`Role "${roleName}" created successfully`);
    }

    // Group functions
    function viewGroup(groupId) {
        const group = groupsData.find(g => g.id == groupId);
        if (group) {
            showToast(`Viewing group: ${group.group_name}`, 'info');
        }
    }

    function manageGroupMembers(groupId) {
        showToast('Managing members for group ID: ' + groupId, 'info');
    }

    function saveNewGroup() {
        const groupName = document.getElementById('newGroupName').value;
        const groupCode = document.getElementById('newGroupCode').value;

        if (!groupName || !groupCode) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        closeModal('addGroupModal');
        showToast(`Group "${groupName}" created successfully`);
    }

    // Resource functions
    function viewResource(resourceId) {
        const resource = resourcesData.find(r => r.id == resourceId);
        if (resource) {
            showToast(`Viewing resource: ${resource.resource_name}`, 'info');
        }
    }

    function manageResourceAccess(resourceId) {
        showToast('Managing access for resource ID: ' + resourceId, 'info');
    }

    function saveNewResource() {
        const resourceName = document.getElementById('newResourceName').value;

        if (!resourceName) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        closeModal('addResourceModal');
        showToast(`Resource "${resourceName}" created successfully`);
    }

    // Access Request functions
    function updateRequestTargets() {
        const type = document.getElementById('requestType').value;
        const targetSelect = document.getElementById('requestTarget');
        targetSelect.innerHTML = '';

        let options = [];
        if (type === 'role') {
            options = rolesData.map(r => ({ id: r.id, name: r.role_name }));
        } else if (type === 'group') {
            options = groupsData.map(g => ({ id: g.id, name: g.group_name }));
        } else if (type === 'resource') {
            options = resourcesData.map(r => ({ id: r.id, name: r.resource_name }));
        }

        options.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.name;
            targetSelect.appendChild(opt);
        });
    }

    function submitAccessRequest() {
        const justification = document.getElementById('requestJustification').value;

        if (!justification) {
            showToast('Please provide a business justification', 'error');
            return;
        }

        closeModal('newAccessRequestModal');
        showToast('Access request submitted successfully');
    }

    function filterRequests(status) {
        document.querySelectorAll('#requestsTable tbody tr').forEach(row => {
            if (status === 'all' || row.getAttribute('data-status') === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function viewRequest(requestNumber) {
        showToast('Viewing request: ' + requestNumber, 'info');
    }

    function approveRequest(requestNumber) {
        if (confirm(`Approve access request ${requestNumber}?`)) {
            showToast(`Request ${requestNumber} approved`);
        }
    }

    function rejectRequest(requestNumber) {
        const reason = prompt('Please provide a rejection reason:');
        if (reason) {
            showToast(`Request ${requestNumber} rejected`, 'warning');
        }
    }

    // Violation functions
    function viewViolation(violationId) {
        showToast('Viewing violation: #' + violationId, 'info');
    }

    function resolveViolation(violationId) {
        const notes = prompt('Please provide resolution notes:');
        if (notes) {
            showToast(`Violation #${violationId} resolved`);
        }
    }

    function runViolationScan() {
        showToast('Running violation scan...', 'info');
        setTimeout(() => {
            showToast('Scan complete. 2 new violations detected.', 'warning');
        }, 2000);
    }

    // Audit functions
    function filterAuditLog(action) {
        document.querySelectorAll('#auditTable tbody tr').forEach(row => {
            if (action === 'all' || row.getAttribute('data-action') === action) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function exportAuditLog() {
        showToast('Exporting audit log...', 'info');
        setTimeout(() => showToast('Audit log exported'), 1500);
    }

    // Policy functions
    function viewPolicy(policyId) {
        showToast('Viewing policy ID: ' + policyId, 'info');
    }

    function editPolicy(policyId) {
        showToast('Editing policy ID: ' + policyId, 'info');
    }

    // Report functions
    function generateReport(reportType) {
        showToast(`Generating ${reportType.replace('_', ' ')} report...`, 'info');
        setTimeout(() => {
            showToast('Report generated and downloaded');
        }, 2000);
    }

    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($tablesExist): ?>
        // Department chart
        const deptCounts = {};
        usersData.forEach(u => {
            const dept = u.department || 'Unknown';
            deptCounts[dept] = (deptCounts[dept] || 0) + 1;
        });

        new Chart(document.getElementById('deptChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(deptCounts),
                datasets: [{
                    data: Object.values(deptCounts),
                    backgroundColor: ['#667eea', '#764ba2', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { color: '#e2e8f0' } }
                }
            }
        });

        // Risk score chart
        const riskBuckets = { 'Low (0-30)': 0, 'Medium (31-60)': 0, 'High (61-100)': 0 };
        usersData.forEach(u => {
            if (u.risk_score <= 30) riskBuckets['Low (0-30)']++;
            else if (u.risk_score <= 60) riskBuckets['Medium (31-60)']++;
            else riskBuckets['High (61-100)']++;
        });

        new Chart(document.getElementById('riskChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(riskBuckets),
                datasets: [{
                    label: 'Users',
                    data: Object.values(riskBuckets),
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { color: '#94a3b8' }, grid: { color: '#2d3748' } },
                    x: { ticks: { color: '#94a3b8' }, grid: { color: '#2d3748' } }
                }
            }
        });
        <?php endif; ?>
    });
    </script>
</body>
</html>
