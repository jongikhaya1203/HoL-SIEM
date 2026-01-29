<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email DLP System - Home</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            width: 100%;
        }
        .hero {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        .hero h1 {
            font-size: 48px;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .hero p {
            font-size: 20px;
            opacity: 0.95;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        .card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .card-title {
            font-size: 22px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .card-description {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
        }
        .setup-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .setup-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .setup-steps {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .step {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
        }
        .step:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
            font-size: 18px;
        }
        .step:last-child {
            margin-bottom: 0;
        }
        code {
            background: #2c3e50;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .feature-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2c3e50;
            font-size: 14px;
        }
        .feature-item:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            font-size: 18px;
        }
        .footer {
            text-align: center;
            color: white;
            opacity: 0.9;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>📧 Email DLP System</h1>
            <p>Detect and prevent sensitive information leaks through email</p>
        </div>

        <div class="setup-box">
            <div class="setup-title">
                🚀 Quick Start
            </div>
            <div class="setup-steps">
                <div class="step">
                    <strong>Step 1:</strong> Run setup command:<br>
                    <code>php setup_mailscan.php</code>
                </div>
                <div class="step">
                    <strong>Step 2:</strong> Generate sample data:<br>
                    <code>php generate_sample_data.php</code>
                </div>
                <div class="step">
                    <strong>Step 3:</strong> Click any card below to start using the system
                </div>
            </div>

            <div class="feature-list">
                <div class="feature-item">Real-time scanning</div>
                <div class="feature-item">12 pre-configured rules</div>
                <div class="feature-item">Custom rule creation</div>
                <div class="feature-item">Risk scoring</div>
                <div class="feature-item">Multiple detection types</div>
                <div class="feature-item">Context extraction</div>
                <div class="feature-item">Detailed reporting</div>
                <div class="feature-item">Sample data included</div>
            </div>
        </div>

        <div class="cards">
            <a href="mailscan_dashboard.php" class="card">
                <div class="card-icon">📊</div>
                <div class="card-title">Dashboard</div>
                <div class="card-description">
                    View flagged emails, statistics, and detection results. Monitor all email activity in one place.
                </div>
            </a>

            <a href="mailscan_rules.php" class="card">
                <div class="card-icon">🔍</div>
                <div class="card-title">Detection Rules</div>
                <div class="card-description">
                    Manage detection patterns, create custom rules, and configure sensitivity levels.
                </div>
            </a>

            <a href="mailscan_scan.php" class="card">
                <div class="card-icon">📨</div>
                <div class="card-title">Scan Email</div>
                <div class="card-description">
                    Submit emails for instant analysis. Test detection rules and see results in real-time.
                </div>
            </a>

            <a href="mailscan_leak_tracker.php" class="card" style="border: 2px solid #e91e63;">
                <div class="card-icon">🔗</div>
                <div class="card-title">Leak Tracker</div>
                <div class="card-description">
                    Track email forwarding chains from original sender to final recipient. Identify leak paths and insider threats.
                </div>
            </a>
        </div>

        <div class="footer">
            <p>Email Data Loss Prevention System v1.0</p>
            <p>Protecting your sensitive information from unauthorized disclosure</p>
        </div>
    </div>
</body>
</html>
