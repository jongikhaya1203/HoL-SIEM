<?php
/**
 * Quick Logo Upload (No Login Required)
 * Temporary page to upload logo without CMS authentication
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'classes/Database.php';

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle logo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        $filename = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $newname = 'logo.' . $ext;
            $upload_path = __DIR__ . '/assets/uploads/' . $newname;

            // Create upload directory if not exists
            if (!is_dir(__DIR__ . '/assets/uploads')) {
                mkdir(__DIR__ . '/assets/uploads', 0755, true);
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
            } else {
                $message = 'Failed to upload logo';
                $messageType = 'error';
            }
        } else {
            $message = 'Invalid file type. Allowed: JPG, PNG, GIF, SVG';
            $messageType = 'error';
        }
    } else {
        $message = 'Upload error: ' . $_FILES['logo']['error'];
        $messageType = 'error';
    }
}

// Get current logo
$currentLogo = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'logo_url'");
$logo_url = $currentLogo['setting_value'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Logo Upload</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-color: #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-color: #c62828;
        }

        .current-logo {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .current-logo img {
            max-width: 100%;
            max-height: 150px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #667eea;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            background: #f8f9fa;
        }

        input[type="file"]:hover {
            background: #e3f2fd;
        }

        .hint {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #1565c0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Quick Logo Upload</h1>
        <p class="subtitle">Upload your company logo to display on the dashboard</p>

        <div class="info-box">
            <strong>ℹ️ Note:</strong> This is a simplified upload page. For full CMS access, please login at
            <a href="admin/login.php">admin/login.php</a> with credentials: admin/admin123
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if ($logo_url): ?>
        <div class="current-logo">
            <img src="<?= htmlspecialchars($logo_url) ?>" alt="Current Logo">
            <p style="color: #666; font-size: 14px;">Current Logo</p>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="logo">Choose Company Logo</label>
                <input type="file" id="logo" name="logo" accept="image/*" required>
                <p class="hint">Accepted formats: JPG, PNG, GIF, SVG (Max 2MB)</p>
                <p class="hint">Recommended size: 200x60 pixels</p>
            </div>

            <button type="submit" class="btn">📤 Upload Logo</button>
        </form>

        <div class="back-link">
            <a href="index.php">← Back to Dashboard</a> |
            <a href="admin/login.php">Go to Full CMS →</a>
        </div>
    </div>
</body>
</html>
