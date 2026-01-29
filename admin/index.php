<?php
/**
 * CMS Admin Portal - Dashboard
 */
session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// Get current settings
$settings = [];
$result = $db->fetchAll("SELECT * FROM settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_logo') {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $newname = 'logo.' . $ext;
                $upload_path = __DIR__ . '/../assets/uploads/' . $newname;

                // Create upload directory if not exists
                if (!is_dir(__DIR__ . '/../assets/uploads')) {
                    mkdir(__DIR__ . '/../assets/uploads', 0755, true);
                }

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                    // Save to database
                    $logo_url = '/networkscan/assets/uploads/' . $newname;
                    $db->query(
                        "INSERT INTO settings (setting_key, setting_value) VALUES ('logo_url', ?)
                         ON DUPLICATE KEY UPDATE setting_value = ?",
                        [$logo_url, $logo_url]
                    );

                    $message = 'Logo uploaded successfully!';
                    $messageType = 'success';
                    $settings['logo_url'] = $logo_url;
                } else {
                    $message = 'Failed to upload logo';
                    $messageType = 'error';
                }
            } else {
                $message = 'Invalid file type. Allowed: JPG, PNG, GIF, SVG';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update_settings') {
        $app_name = $_POST['app_name'] ?? 'Network Security Scanner';
        $company_name = $_POST['company_name'] ?? '';
        $support_email = $_POST['support_email'] ?? '';
        $theme_color = $_POST['theme_color'] ?? '#667eea';

        $settingsToUpdate = [
            'app_name' => $app_name,
            'company_name' => $company_name,
            'support_email' => $support_email,
            'theme_color' => $theme_color
        ];

        foreach ($settingsToUpdate as $key => $value) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
            $settings[$key] = $value;
        }

        $message = 'Settings updated successfully!';
        $messageType = 'success';
    }
}

// Get statistics
$stats = [
    'total_scans' => $db->fetchOne("SELECT COUNT(*) as count FROM scans")['count'],
    'total_hosts' => $db->fetchOne("SELECT COUNT(*) as count FROM hosts")['count'],
    'total_vulnerabilities' => $db->fetchOne("SELECT COUNT(*) as count FROM scan_results WHERE status = 'open'")['count'],
    'total_reports' => $db->fetchOne("SELECT COUNT(*) as count FROM reports")['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Network Security Scanner</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="main-content">
            <?php include 'sidebar.php'; ?>

            <div class="content">
                <h1>Dashboard Settings</h1>

                <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['total_scans'] ?></div>
                            <div class="stat-label">Total Scans</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">🖥️</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['total_hosts'] ?></div>
                            <div class="stat-label">Hosts Scanned</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">🔴</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['total_vulnerabilities'] ?></div>
                            <div class="stat-label">Open Vulnerabilities</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">📄</div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $stats['total_reports'] ?></div>
                            <div class="stat-label">Reports Generated</div>
                        </div>
                    </div>
                </div>

                <!-- Logo Upload -->
                <div class="card">
                    <h2>Company Logo</h2>
                    <p>Upload a logo to display on the dashboard header</p>

                    <?php if (isset($settings['logo_url'])): ?>
                    <div class="current-logo">
                        <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Current Logo" style="max-width: 300px; max-height: 150px;">
                        <p><small>Current Logo</small></p>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_logo">

                        <div class="form-group">
                            <label for="logo">Choose Logo (JPG, PNG, GIF, SVG)</label>
                            <input type="file" id="logo" name="logo" accept="image/*" required>
                            <small>Maximum file size: 2MB. Recommended dimensions: 200x60px</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Upload Logo</button>
                    </form>
                </div>

                <!-- Application Settings -->
                <div class="card">
                    <h2>Application Settings</h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">

                        <div class="form-group">
                            <label for="app_name">Application Name</label>
                            <input type="text" id="app_name" name="app_name"
                                   value="<?= htmlspecialchars($settings['app_name'] ?? 'Network Security Scanner') ?>">
                        </div>

                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" name="company_name"
                                   value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="support_email">Support Email</label>
                            <input type="email" id="support_email" name="support_email"
                                   value="<?= htmlspecialchars($settings['support_email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="theme_color">Theme Color</label>
                            <input type="color" id="theme_color" name="theme_color"
                                   value="<?= htmlspecialchars($settings['theme_color'] ?? '#667eea') ?>">
                            <small>Primary color for the application theme</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="tasks.php" class="btn btn-secondary">Manage Tasks</a>
                        <a href="users.php" class="btn btn-secondary">User Management</a>
                        <a href="backup.php" class="btn btn-secondary">Backup Database</a>
                        <a href="../index.php" class="btn btn-secondary" target="_blank">View Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
