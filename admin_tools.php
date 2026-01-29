<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tools - Network Security Scanner</title>
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
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 40px;
            font-size: 36px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }

        .card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .card h2 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #764ba2;
            transform: scale(1.02);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #333;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .credentials {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 14px;
            border-left: 4px solid #1565c0;
        }

        .credentials strong {
            color: #1565c0;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: background 0.3s;
        }

        .back-link a:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ IOC Admin Tools</h1>

        <div class="grid">
            <!-- Quick Logo Upload -->
            <div class="card">
                <div class="card-icon">🖼️</div>
                <h2>Quick Logo Upload</h2>
                <p>Upload your company logo immediately without login. Changes appear instantly on dashboard.</p>
                <a href="quick_logo_upload.php" class="btn">Upload Logo Now →</a>
                <div class="credentials">
                    <strong>✓ No login required</strong><br>
                    Perfect for quick logo updates
                </div>
            </div>

            <!-- CMS Login -->
            <div class="card">
                <div class="card-icon">🔐</div>
                <h2>Full CMS Portal</h2>
                <p>Access the complete admin portal to manage settings, tasks, users, and more.</p>
                <a href="admin/login.php" class="btn">Go to Login →</a>
                <div class="credentials">
                    <strong>Username:</strong> admin<br>
                    <strong>Password:</strong> admin123
                </div>
            </div>

            <!-- Login Diagnostic -->
            <div class="card">
                <div class="card-icon">🔍</div>
                <h2>Login Diagnostics</h2>
                <p>Having trouble logging in? Run diagnostics to automatically detect and fix login issues.</p>
                <a href="diagnose_login.php" class="btn">Run Diagnostics →</a>
                <p style="margin-top: 15px; font-size: 13px;">
                    <strong>This tool will:</strong><br>
                    • Check database connection<br>
                    • Verify admin user exists<br>
                    • Test password authentication<br>
                    • Auto-fix any issues found
                </p>
            </div>

            <!-- Setup Admin -->
            <div class="card">
                <div class="card-icon">⚙️</div>
                <h2>Setup Admin User</h2>
                <p>Create or reset the admin user account with fresh credentials.</p>
                <a href="setup_admin.php" class="btn">Setup Admin →</a>
                <p style="margin-top: 15px; font-size: 13px; color: #999;">
                    Use this if the admin account is missing or password is forgotten.
                </p>
            </div>

            <!-- Module Management -->
            <div class="card">
                <div class="card-icon">📦</div>
                <h2>Module Management</h2>
                <p>Verify module installation, populate modules, and check supporting tables.</p>
                <a href="test_modules.php" class="btn">Test Modules →</a>
                <a href="populate_modules.php" class="btn-secondary">Populate Modules</a>
                <a href="create_module_tables.php" class="btn-secondary">Create Tables</a>
            </div>

            <!-- Quick Fix -->
            <div class="card">
                <div class="card-icon">🔧</div>
                <h2>System Quick Fix</h2>
                <p>All-in-one diagnostic tool to check database, tables, modules, and settings.</p>
                <a href="quick_fix.php" class="btn">Run Quick Fix →</a>
                <p style="margin-top: 15px; font-size: 13px; color: #999;">
                    First-time setup or troubleshooting? Start here.
                </p>
            </div>
        </div>

        <div class="back-link">
            <a href="index.php">← Back to Main Dashboard</a>
        </div>
    </div>
</body>
</html>
