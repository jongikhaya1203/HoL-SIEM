<?php
session_start();
require_once __DIR__ . '/includes/Database.php';

if (!isset($_SESSION['cpanel_logged_in']) || $_SESSION['cpanel_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_user':
            try {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    throw new Exception('Passwords do not match');
                }

                $stmt = $db->prepare("INSERT INTO cpanel_users (username, password_hash, email, role, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['username'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['email'],
                    $_POST['role'],
                    isset($_POST['is_active']) ? 1 : 0
                ]);
                $message = 'User created successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error creating user: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'update_user':
            try {
                $updates = [
                    'username' => $_POST['username'],
                    'email' => $_POST['email'],
                    'role' => $_POST['role'],
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];

                $sql = "UPDATE cpanel_users SET username = ?, email = ?, role = ?, is_active = ?";
                $params = array_values($updates);

                if (!empty($_POST['password'])) {
                    if ($_POST['password'] !== $_POST['confirm_password']) {
                        throw new Exception('Passwords do not match');
                    }
                    $sql .= ", password_hash = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id = ?";
                $params[] = $_POST['user_id'];

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $message = 'User updated successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error updating user: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'delete_user':
            try {
                if ($_POST['user_id'] == $_SESSION['cpanel_user_id']) {
                    throw new Exception('Cannot delete your own account');
                }
                $stmt = $db->prepare("DELETE FROM cpanel_users WHERE id = ?");
                $stmt->execute([$_POST['user_id']]);
                $message = 'User deleted successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error deleting user: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'toggle_user':
            try {
                if ($_POST['user_id'] == $_SESSION['cpanel_user_id']) {
                    throw new Exception('Cannot disable your own account');
                }
                $stmt = $db->prepare("UPDATE cpanel_users SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$_POST['user_id']]);
                $message = 'User status toggled';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;

        case 'reset_password':
            try {
                $newPassword = bin2hex(random_bytes(8));
                $stmt = $db->prepare("UPDATE cpanel_users SET password_hash = ? WHERE id = ?");
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_POST['user_id']]);
                $message = "Password reset. New password: $newPassword";
                $messageType = 'warning';
            } catch (Exception $e) {
                $message = 'Error resetting password: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;
    }
}

// Get users
$users = [];
try {
    $stmt = $db->query("SELECT * FROM cpanel_users ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist - use default admin
    $users = [
        ['id' => 1, 'username' => 'admin', 'email' => 'admin@example.com', 'role' => 'super_admin', 'is_active' => 1, 'last_login' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s')]
    ];
}

// Statistics
$stats = [
    'total' => count($users),
    'active' => count(array_filter($users, fn($u) => $u['is_active'])),
    'admins' => count(array_filter($users, fn($u) => in_array($u['role'], ['super_admin', 'admin']))),
    'recent' => count(array_filter($users, fn($u) => strtotime($u['last_login'] ?? '2000-01-01') > strtotime('-7 days')))
];

$currentPage = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - IOC Control Panel</title>
    <link rel="stylesheet" href="assets/css/cpanel.css">
</head>
<body>
    <div class="cpanel-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="cpanel-main">
            <?php include 'includes/header.php'; ?>

            <div class="cpanel-content">
                <div class="page-header">
                    <h1>User Management</h1>
                    <p>Manage control panel users and permissions</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($messageType === 'success'): ?>
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            <?php elseif ($messageType === 'warning'): ?>
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            <?php else: ?>
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            <?php endif; ?>
                        </svg>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-icon modules">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $stats['total'] ?></span>
                            <span class="stat-label">Total Users</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon protocols">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $stats['active'] ?></span>
                            <span class="stat-label">Active Users</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon api">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $stats['admins'] ?></span>
                            <span class="stat-label">Administrators</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon channels">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?= $stats['recent'] ?></span>
                            <span class="stat-label">Active This Week</span>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="section-card">
                    <div class="section-header">
                        <h2>Users</h2>
                        <button class="btn btn-primary btn-sm" onclick="showAddUser()">+ Add User</button>
                    </div>
                    <div class="section-body" style="padding: 0;">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                                            <?php if ($user['id'] == ($_SESSION['cpanel_user_id'] ?? 0)): ?>
                                                <span class="badge-you">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                                    <td>
                                        <span class="role-badge role-<?= $user['role'] ?>">
                                            <?= ucwords(str_replace('_', ' ', $user['role'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="btn btn-secondary btn-sm" onclick='editUser(<?= json_encode($user) ?>)'>Edit</button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Reset password for this user?');">Reset PW</button>
                                            </form>
                                            <?php if ($user['id'] != ($_SESSION['cpanel_user_id'] ?? 0)): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?');">Delete</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Role Descriptions -->
                <div class="section-card" style="margin-top: 20px;">
                    <div class="section-header">
                        <h2>Role Permissions</h2>
                    </div>
                    <div class="section-body">
                        <div class="role-grid">
                            <div class="role-item">
                                <span class="role-badge role-super_admin">Super Admin</span>
                                <p>Full access to all features, including user management, security settings, and system configuration.</p>
                            </div>
                            <div class="role-item">
                                <span class="role-badge role-admin">Admin</span>
                                <p>Access to most features except security settings and user role management.</p>
                            </div>
                            <div class="role-item">
                                <span class="role-badge role-operator">Operator</span>
                                <p>Can manage modules, protocols, and view reports. Cannot modify security settings.</p>
                            </div>
                            <div class="role-item">
                                <span class="role-badge role-viewer">Viewer</span>
                                <p>Read-only access to dashboards and reports. Cannot make any changes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="userModalTitle">Add User</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="userForm">
                    <input type="hidden" name="action" value="add_user" id="userAction">
                    <input type="hidden" name="user_id" id="userId">

                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" id="userName" required pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="userEmail">
                    </div>

                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" id="userRole" required>
                            <option value="viewer">Viewer</option>
                            <option value="operator">Operator</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label id="passwordLabel">Password *</label>
                            <input type="password" name="password" id="userPassword" minlength="8">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" id="userConfirmPassword" minlength="8">
                        </div>
                    </div>

                    <div class="toggle-item">
                        <div class="toggle-info">
                            <span class="toggle-label">Active</span>
                            <span class="toggle-desc">User can log in</span>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" id="userActive" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        .badge-you {
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        .role-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .role-super_admin { background: #fee2e2; color: #991b1b; }
        .role-admin { background: #fef3c7; color: #92400e; }
        .role-operator { background: #dbeafe; color: #1e40af; }
        .role-viewer { background: #e2e8f0; color: #475569; }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .role-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .role-item {
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .role-item .role-badge {
            margin-bottom: 10px;
            display: inline-block;
        }
        .role-item p {
            font-size: 13px;
            color: var(--text-secondary);
            margin: 0;
        }
        @media (max-width: 768px) {
            .role-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="assets/js/cpanel.js"></script>
    <script>
        function showAddUser() {
            document.getElementById('userModalTitle').textContent = 'Add User';
            document.getElementById('userAction').value = 'add_user';
            document.getElementById('userForm').reset();
            document.getElementById('userActive').checked = true;
            document.getElementById('userPassword').required = true;
            document.getElementById('userConfirmPassword').required = true;
            document.getElementById('passwordLabel').textContent = 'Password *';
            document.getElementById('userModal').classList.add('active');
        }

        function editUser(user) {
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('userAction').value = 'update_user';
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.username;
            document.getElementById('userEmail').value = user.email || '';
            document.getElementById('userRole').value = user.role;
            document.getElementById('userActive').checked = user.is_active == 1;
            document.getElementById('userPassword').value = '';
            document.getElementById('userConfirmPassword').value = '';
            document.getElementById('userPassword').required = false;
            document.getElementById('userConfirmPassword').required = false;
            document.getElementById('passwordLabel').textContent = 'Password (leave blank to keep current)';
            document.getElementById('userModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }
    </script>
</body>
</html>
