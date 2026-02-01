<?php
/**
 * Logo & Branding Settings - CMS
 * Upload and manage company logo
 */

require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Create settings table if it doesn't exist
try {
    $conn = $db->getConnection();
    $conn->exec("CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type VARCHAR(50) DEFAULT 'text',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle logo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $uploadDir = __DIR__ . '/../uploads/';

    // Create uploads directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file = $_FILES['logo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $error = "Invalid file type. Please upload a JPEG, PNG, GIF, or SVG image.";
        } elseif ($file['size'] > $maxSize) {
            $error = "File is too large. Maximum size is 5MB.";
        } else {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Delete old logo if exists
                $stmt = $db->query("SELECT setting_value FROM site_settings WHERE setting_key = 'logo_url'");
                $oldLogo = $stmt->fetch();
                if ($oldLogo && !empty($oldLogo['setting_value'])) {
                    $oldPath = __DIR__ . '/../' . $oldLogo['setting_value'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                // Save to database
                $logoUrl = 'uploads/' . $filename;
                $db->query("INSERT INTO site_settings (setting_key, setting_value, setting_type)
                           VALUES ('logo_url', ?, 'image')
                           ON DUPLICATE KEY UPDATE setting_value = ?",
                           [$logoUrl, $logoUrl]);

                $message = "Logo uploaded successfully!";
            } else {
                $error = "Failed to upload file. Please check directory permissions.";
            }
        }
    } else {
        $error = "Upload error: " . $file['error'];
    }
}

// Handle logo removal
if (isset($_POST['remove_logo'])) {
    $stmt = $db->query("SELECT setting_value FROM site_settings WHERE setting_key = 'logo_url'");
    $logo = $stmt->fetch();

    if ($logo && !empty($logo['setting_value'])) {
        $logoPath = __DIR__ . '/../' . $logo['setting_value'];
        if (file_exists($logoPath)) {
            unlink($logoPath);
        }

        $db->query("DELETE FROM site_settings WHERE setting_key = 'logo_url'");
        $message = "Logo removed successfully!";
    }
}

// Handle app name update
if (isset($_POST['update_name'])) {
    $appName = trim($_POST['app_name']);
    if (!empty($appName)) {
        $db->query("INSERT INTO site_settings (setting_key, setting_value, setting_type)
                   VALUES ('app_name', ?, 'text')
                   ON DUPLICATE KEY UPDATE setting_value = ?",
                   [$appName, $appName]);
        $message = "Application name updated successfully!";
    }
}

// Handle theme color update
if (isset($_POST['update_theme'])) {
    $themeColor = trim($_POST['theme_color']);
    if (!empty($themeColor)) {
        $db->query("INSERT INTO site_settings (setting_key, setting_value, setting_type)
                   VALUES ('theme_color', ?, 'color')
                   ON DUPLICATE KEY UPDATE setting_value = ?",
                   [$themeColor, $themeColor]);
        $message = "Theme color updated successfully!";
    }
}

// Get current settings
$stmt = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('logo_url', 'app_name', 'theme_color')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$currentLogo = $settings['logo_url'] ?? '';
$currentAppName = $settings['app_name'] ?? 'HoL Intelligent Operating Centre';
$currentThemeColor = $settings['theme_color'] ?? '#667eea';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo & Branding Settings</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 25px 35px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            color: #667eea;
            font-size: 32px;
        }
        .back-btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-group input[type="text"],
        .form-group input[type="color"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .file-upload {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #f8f9ff;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload:hover {
            background: #f0f2ff;
            border-color: #5568d3;
        }
        .file-upload input[type="file"] {
            display: none;
        }
        .file-upload-label {
            cursor: pointer;
            display: block;
        }
        .file-upload-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .file-upload-text {
            font-size: 16px;
            color: #666;
        }
        .current-logo {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .current-logo img {
            max-width: 100%;
            max-height: 150px;
            display: block;
            margin: 0 auto 15px;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .color-preview {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            border: 2px solid #ddd;
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🎨 Logo & Branding Settings</h1>
            <a href="../index.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message">✓ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">✗ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Application Name -->
        <div class="card">
            <h2>Application Name</h2>
            <form method="post">
                <div class="form-group">
                    <label>Application Name</label>
                    <input type="text" name="app_name" value="<?php echo htmlspecialchars($currentAppName); ?>" required>
                </div>
                <button type="submit" name="update_name" class="btn">Update Name</button>
            </form>
        </div>

        <!-- Logo Upload -->
        <div class="card">
            <h2>Company Logo</h2>

            <?php if (!empty($currentLogo)): ?>
                <div class="current-logo">
                    <img src="../<?php echo htmlspecialchars($currentLogo); ?>" alt="Current Logo">
                    <form method="post" style="display: inline;">
                        <button type="submit" name="remove_logo" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove the logo?')">Remove Logo</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="info">
                ℹ️ Accepted formats: JPG, PNG, GIF, SVG • Maximum size: 5MB • Recommended: 200x70px for best display
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="file-upload" onclick="document.getElementById('logo-upload').click()">
                    <label for="logo-upload" class="file-upload-label">
                        <div class="file-upload-icon">📤</div>
                        <div class="file-upload-text">
                            Click to upload logo<br>
                            <small>or drag and drop</small>
                        </div>
                    </label>
                    <input type="file" id="logo-upload" name="logo" accept="image/*" onchange="this.form.submit()">
                </div>
            </form>
        </div>

        <!-- Theme Color -->
        <div class="card">
            <h2>Theme Color</h2>
            <form method="post">
                <div class="form-group">
                    <label>Primary Theme Color</label>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <input type="color" name="theme_color" value="<?php echo htmlspecialchars($currentThemeColor); ?>" style="width: 100px; height: 50px; padding: 5px;">
                        <input type="text" name="theme_color" value="<?php echo htmlspecialchars($currentThemeColor); ?>" style="flex: 1;" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                </div>
                <button type="submit" name="update_theme" class="btn">Update Theme Color</button>
            </form>
        </div>

        <!-- Preview -->
        <div class="card">
            <h2>Preview</h2>
            <div style="background: white; padding: 25px; border-radius: 10px; border: 2px solid #e0e0e0;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <?php if (!empty($currentLogo)): ?>
                        <img src="../<?php echo htmlspecialchars($currentLogo); ?>" alt="Logo Preview" style="max-height: 70px; max-width: 200px;">
                    <?php endif; ?>
                    <div>
                        <h3 style="color: <?php echo htmlspecialchars($currentThemeColor); ?>; margin: 0;">
                            <?php echo htmlspecialchars($currentAppName); ?>
                        </h3>
                        <p style="color: #666; margin: 5px 0 0;">Network Security Scanner</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
