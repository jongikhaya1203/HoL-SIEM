<?php
/**
 * Network Security Scanner - REST API
 * Provides programmatic access to scanning functionality
 */

// Suppress all error display to prevent breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Increase execution time for scans (10 minutes)
set_time_limit(600);
ini_set('max_execution_time', '600');

// Set custom error handler to log errors instead of displaying them
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log error to file instead of displaying
    error_log("API Error [$errno]: $errstr in $errfile on line $errline");
    return true; // Don't execute PHP internal error handler
});

// Start output buffering to prevent any echo statements from breaking JSON
ob_start();

// Helper function to safely clean output buffer
function cleanOutputBuffer() {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    ob_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/NetworkScanner.php';
require_once __DIR__ . '/classes/ReportGenerator.php';
require_once __DIR__ . '/classes/ComplianceChecker.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get action from query parameter
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Show documentation if no action provided
if (empty($action)) {
    showApiDocumentation();
    exit;
}

try {
    $db = Database::getInstance();

    switch ($action) {
        case 'stats':
            // Get dashboard statistics
            $stats = [
                'total_scans' => $db->fetchOne("SELECT COUNT(*) as count FROM scans")['count'],
                'total_vulnerabilities' => $db->fetchOne(
                    "SELECT COUNT(*) as count FROM scan_results WHERE status = 'open'"
                )['count'],
                'critical_vulnerabilities' => $db->fetchOne(
                    "SELECT COUNT(*) as count FROM scan_results sr
                     JOIN vulnerabilities v ON sr.vulnerability_id = v.id
                     WHERE sr.status = 'open' AND v.severity = 'critical'"
                )['count'],
                'compliance_score' => calculateComplianceScore()
            ];

            ob_clean();
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'scans':
            // List all scans
            $scans = $db->fetchAll(
                "SELECT * FROM scans ORDER BY created_at DESC LIMIT 50"
            );

            ob_clean();
            echo json_encode(['success' => true, 'scans' => $scans]);
            break;

        case 'scan':
            // Get specific scan details
            $scanId = $_GET['id'] ?? null;

            if (!$scanId) {
                throw new Exception('Scan ID required');
            }

            $scanner = new NetworkScanner(true);
            $scanData = $scanner->getScanResults($scanId);

            ob_clean();
            echo json_encode(['success' => true, 'scan' => $scanData]);
            break;

        case 'start_scan':
            // Start a new scan
            $target = $_POST['target'] ?? null;
            $type = $_POST['type'] ?? 'full';
            $name = $_POST['name'] ?? 'API Scan ' . date('Y-m-d H:i:s');

            if (!$target) {
                throw new Exception('Target required');
            }

            try {
                // Create scanner in silent mode (no echo output)
                $scanner = new NetworkScanner(true);
                $result = $scanner->scan($target, $type, ['scan_name' => $name]);

                cleanOutputBuffer();
                echo json_encode(['success' => true, 'result' => $result]);
                exit;
            } catch (Exception $scanError) {
                cleanOutputBuffer();
                throw new Exception('Scan failed: ' . $scanError->getMessage());
            }

        case 'report':
            // Generate report
            $scanId = $_POST['scan_id'] ?? $_GET['scan_id'] ?? null;
            $format = $_POST['format'] ?? $_GET['format'] ?? 'json';
            $type = $_POST['type'] ?? $_GET['type'] ?? 'full';

            if (!$scanId) {
                throw new Exception('Scan ID required');
            }

            $reportGenerator = new ReportGenerator();
            $report = $reportGenerator->generateReport($scanId, $type, $format);

            ob_clean();
            echo json_encode(['success' => true, 'report' => $report]);
            break;

        case 'compliance':
            // Get compliance summary
            $scanId = $_GET['scan_id'] ?? null;

            if (!$scanId) {
                throw new Exception('Scan ID required');
            }

            $complianceChecker = new ComplianceChecker();
            $summary = $complianceChecker->getComplianceSummary($scanId);

            ob_clean();
            echo json_encode(['success' => true, 'compliance' => $summary]);
            break;

        case 'vulnerabilities':
            // List vulnerabilities
            $scanId = $_GET['scan_id'] ?? null;
            $severity = $_GET['severity'] ?? null;

            $sql = "SELECT v.*, sr.status, h.ip_address
                    FROM vulnerabilities v
                    JOIN scan_results sr ON v.id = sr.vulnerability_id
                    JOIN hosts h ON sr.host_id = h.id";

            $params = [];
            $where = [];

            if ($scanId) {
                $where[] = "sr.scan_id = ?";
                $params[] = $scanId;
            }

            if ($severity) {
                $where[] = "v.severity = ?";
                $params[] = $severity;
            }

            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }

            $sql .= " ORDER BY v.cvss_score DESC LIMIT 100";

            $vulnerabilities = $db->fetchAll($sql, $params);

            ob_clean();
            echo json_encode(['success' => true, 'vulnerabilities' => $vulnerabilities]);
            break;

        case 'hosts':
            // List hosts
            $scanId = $_GET['scan_id'] ?? null;

            if (!$scanId) {
                throw new Exception('Scan ID required');
            }

            $hosts = $db->fetchAll(
                "SELECT * FROM hosts WHERE scan_id = ? ORDER BY risk_score DESC",
                [$scanId]
            );

            ob_clean();
            echo json_encode(['success' => true, 'hosts' => $hosts]);
            break;

        case 'mitigation':
            // Get mitigation plan
            $vulnId = $_GET['vulnerability_id'] ?? null;

            if (!$vulnId) {
                throw new Exception('Vulnerability ID required');
            }

            $mitigation = $db->fetchAll(
                "SELECT * FROM mitigation_plans WHERE vulnerability_id = ?",
                [$vulnId]
            );

            ob_clean();
            echo json_encode(['success' => true, 'mitigation' => $mitigation]);
            break;

        case 'export':
            // Export data
            $scanId = $_GET['scan_id'] ?? null;
            $format = $_GET['format'] ?? 'json';

            if (!$scanId) {
                throw new Exception('Scan ID required');
            }

            $scanner = new NetworkScanner(true);
            $data = $scanner->getScanResults($scanId);

            if ($format === 'csv') {
                ob_clean();
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="scan_' . $scanId . '.csv"');

                // Output CSV
                $output = fopen('php://output', 'w');
                fputcsv($output, ['IP Address', 'Hostname', 'Risk Score', 'Vulnerabilities', 'Open Ports']);

                foreach ($data['hosts'] as $host) {
                    fputcsv($output, [
                        $host['ip_address'],
                        $host['hostname'],
                        $host['risk_score'],
                        count($host['vulnerabilities']),
                        $host['open_ports']
                    ]);
                }

                fclose($output);
            } else {
                ob_clean();
                echo json_encode($data, JSON_PRETTY_PRINT);
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);

    // Clean all output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

function showApiDocumentation() {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>API Documentation | Network Security Scanner</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
                min-height: 100vh;
            }
            .container { max-width: 1200px; margin: 0 auto; }
            .header {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                margin-bottom: 30px;
                text-align: center;
            }
            .header h1 { color: #667eea; font-size: 42px; margin-bottom: 15px; }
            .header p { color: #666; font-size: 18px; }
            .card {
                background: white;
                border-radius: 15px;
                padding: 30px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                margin-bottom: 25px;
            }
            .card h2 {
                color: #667eea;
                font-size: 24px;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 3px solid #667eea;
            }
            .endpoint {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                margin: 15px 0;
                border-left: 4px solid #667eea;
            }
            .endpoint h3 {
                color: #333;
                font-size: 18px;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .method {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: bold;
                color: white;
            }
            .method-get { background: #4CAF50; }
            .method-post { background: #2196F3; }
            .method-put { background: #FF9800; }
            .method-delete { background: #f44336; }
            .description { color: #666; margin: 10px 0; line-height: 1.6; }
            .code-block {
                background: #2d2d2d;
                color: #f8f8f2;
                padding: 15px;
                border-radius: 8px;
                overflow-x: auto;
                margin: 10px 0;
                font-family: 'Courier New', monospace;
                font-size: 13px;
            }
            .params {
                margin: 10px 0;
            }
            .param {
                padding: 8px 12px;
                background: white;
                border-radius: 6px;
                margin: 5px 0;
                display: flex;
                gap: 10px;
            }
            .param-name {
                font-weight: bold;
                color: #667eea;
                min-width: 120px;
            }
            .param-type {
                color: #999;
                font-size: 12px;
                min-width: 80px;
            }
            .param-desc { color: #666; flex-grow: 1; }
            .required { color: #f44336; font-size: 11px; font-weight: bold; }
            .optional { color: #4CAF50; font-size: 11px; font-weight: bold; }
            .response {
                background: #f0f4ff;
                padding: 15px;
                border-radius: 8px;
                margin: 10px 0;
                border-left: 3px solid #2196F3;
            }
            .response-title {
                font-weight: bold;
                color: #2196F3;
                margin-bottom: 10px;
            }
            .footer {
                background: rgba(255,255,255,0.1);
                color: white;
                text-align: center;
                padding: 20px;
                border-radius: 10px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔌 API Documentation</h1>
                <p>Network Security Scanner REST API v1.0</p>
                <div style="margin-top: 20px;">
                    <code style="background: #f5f5f5; padding: 10px 20px; border-radius: 6px; color: #667eea; font-size: 14px;">
                        Base URL: <?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) ?>/api.php
                    </code>
                </div>
            </div>

            <!-- Authentication -->
            <div class="card">
                <h2>🔐 Authentication</h2>
                <p style="color: #666; line-height: 1.8;">
                    Currently, the API operates without authentication. In production environments,
                    you should implement API keys or OAuth tokens for security. Add authentication
                    headers to your requests when authentication is enabled.
                </p>
            </div>

            <!-- Endpoints -->
            <div class="card">
                <h2>📡 Available Endpoints</h2>

                <!-- Stats -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-get">GET</span>
                        Get Dashboard Statistics
                    </h3>
                    <div class="description">
                        Retrieve overall statistics including total scans, vulnerabilities, and compliance scores.
                    </div>
                    <div class="code-block">GET /api.php?action=stats</div>
                    <div class="response">
                        <div class="response-title">Response Example:</div>
                        <div class="code-block">{
  "success": true,
  "stats": {
    "total_scans": 156,
    "total_vulnerabilities": 342,
    "critical_vulnerabilities": 28,
    "compliance_score": 87.5
  }
}</div>
                    </div>
                </div>

                <!-- List Scans -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-get">GET</span>
                        List All Scans
                    </h3>
                    <div class="description">
                        Get a list of all security scans (limited to 50 most recent).
                    </div>
                    <div class="code-block">GET /api.php?action=scans</div>
                    <div class="response">
                        <div class="response-title">Response Example:</div>
                        <div class="code-block">{
  "success": true,
  "scans": [
    {
      "id": 1,
      "target_range": "192.168.1.0/24",
      "scan_type": "full",
      "status": "completed",
      "created_at": "2025-01-15 14:30:00"
    }
  ]
}</div>
                    </div>
                </div>

                <!-- Get Scan -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-get">GET</span>
                        Get Specific Scan
                    </h3>
                    <div class="description">
                        Retrieve detailed information about a specific scan including all findings.
                    </div>
                    <div class="params">
                        <div class="param">
                            <span class="param-name">id</span>
                            <span class="param-type">integer</span>
                            <span class="required">REQUIRED</span>
                            <span class="param-desc">The scan ID</span>
                        </div>
                    </div>
                    <div class="code-block">GET /api.php?action=scan&id=1</div>
                </div>

                <!-- Start Scan -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-post">POST</span>
                        Start New Scan
                    </h3>
                    <div class="description">
                        Initiate a new security scan on the specified target.
                    </div>
                    <div class="params">
                        <div class="param">
                            <span class="param-name">target</span>
                            <span class="param-type">string</span>
                            <span class="required">REQUIRED</span>
                            <span class="param-desc">IP address, range, or hostname (e.g., 192.168.1.0/24)</span>
                        </div>
                        <div class="param">
                            <span class="param-name">type</span>
                            <span class="param-type">string</span>
                            <span class="optional">OPTIONAL</span>
                            <span class="param-desc">Scan type: "quick" or "full" (default: "full")</span>
                        </div>
                        <div class="param">
                            <span class="param-name">name</span>
                            <span class="param-type">string</span>
                            <span class="optional">OPTIONAL</span>
                            <span class="param-desc">Custom scan name</span>
                        </div>
                    </div>
                    <div class="code-block">POST /api.php?action=start_scan
Content-Type: application/x-www-form-urlencoded

target=192.168.1.0/24&type=full&name=Production Scan</div>
                </div>

                <!-- Vulnerabilities -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-get">GET</span>
                        List Vulnerabilities
                    </h3>
                    <div class="description">
                        Get a list of discovered vulnerabilities, optionally filtered by scan or severity.
                    </div>
                    <div class="params">
                        <div class="param">
                            <span class="param-name">scan_id</span>
                            <span class="param-type">integer</span>
                            <span class="optional">OPTIONAL</span>
                            <span class="param-desc">Filter by specific scan ID</span>
                        </div>
                        <div class="param">
                            <span class="param-name">severity</span>
                            <span class="param-type">string</span>
                            <span class="optional">OPTIONAL</span>
                            <span class="param-desc">Filter by severity: "critical", "high", "medium", "low"</span>
                        </div>
                    </div>
                    <div class="code-block">GET /api.php?action=vulnerabilities&severity=critical
GET /api.php?action=vulnerabilities&scan_id=1</div>
                </div>

                <!-- Hosts -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-get">GET</span>
                        List Hosts
                    </h3>
                    <div class="description">
                        Get all hosts discovered in a specific scan.
                    </div>
                    <div class="params">
                        <div class="param">
                            <span class="param-name">scan_id</span>
                            <span class="param-type">integer</span>
                            <span class="required">REQUIRED</span>
                            <span class="param-desc">The scan ID</span>
                        </div>
                    </div>
                    <div class="code-block">GET /api.php?action=hosts&scan_id=1</div>
                </div>

                <!-- Report -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-post">POST</span>
                        Generate Report
                    </h3>
                    <div class="description">
                        Generate a report for a specific scan in various formats.
                    </div>
                    <div class="params">
                        <div class="param">
                            <span class="param-name">scan_id</span>
                            <span class="param-type">integer</span>
                            <span class="required">REQUIRED</span>
                            <span class="param-desc">The scan ID</span>
                        </div>
                        <div class="param">
                            <span class="param-name">format</span>
                            <span class="param-type">string</span>
                            <span class="optional">OPTIONAL</span>
                            <span class="param-desc">Format: "json", "pdf", "html" (default: "json")</span>
                        </div>
                        <div class="param">
                            <span class="param-name">type</span>
                            <span class="param-type">string</span>
                            <span class="optional">OPTIONAL</span>
                            <span class="param-desc">Report type: "full", "summary", "executive" (default: "full")</span>
                        </div>
                    </div>
                    <div class="code-block">POST /api.php?action=report
Content-Type: application/x-www-form-urlencoded

scan_id=1&format=json&type=full</div>
                </div>

                <!-- Compliance -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-get">GET</span>
                        Get Compliance Summary
                    </h3>
                    <div class="description">
                        Retrieve compliance check results for a specific scan.
                    </div>
                    <div class="params">
                        <div class="param">
                            <span class="param-name">scan_id</span>
                            <span class="param-type">integer</span>
                            <span class="required">REQUIRED</span>
                            <span class="param-desc">The scan ID</span>
                        </div>
                    </div>
                    <div class="code-block">GET /api.php?action=compliance&scan_id=1</div>
                </div>

                <!-- Mitigation -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-get">GET</span>
                        Get Mitigation Plan
                    </h3>
                    <div class="description">
                        Get recommended mitigation steps for a specific vulnerability.
                    </div>
                    <div class="params">
                        <div class="param">
                            <span class="param-name">vulnerability_id</span>
                            <span class="param-type">integer</span>
                            <span class="required">REQUIRED</span>
                            <span class="param-desc">The vulnerability ID</span>
                        </div>
                    </div>
                    <div class="code-block">GET /api.php?action=mitigation&vulnerability_id=42</div>
                </div>

                <!-- Export -->
                <div class="endpoint">
                    <h3>
                        <span class="method method-get">GET</span>
                        Export Scan Data
                    </h3>
                    <div class="description">
                        Export scan results in JSON or CSV format for external analysis.
                    </div>
                    <div class="params">
                        <div class="param">
                            <span class="param-name">scan_id</span>
                            <span class="param-type">integer</span>
                            <span class="required">REQUIRED</span>
                            <span class="param-desc">The scan ID</span>
                        </div>
                        <div class="param">
                            <span class="param-name">format</span>
                            <span class="param-type">string</span>
                            <span class="optional">OPTIONAL</span>
                            <span class="param-desc">Export format: "json" or "csv" (default: "json")</span>
                        </div>
                    </div>
                    <div class="code-block">GET /api.php?action=export&scan_id=1&format=csv</div>
                </div>
            </div>

            <!-- Error Handling -->
            <div class="card">
                <h2>⚠️ Error Handling</h2>
                <div class="description" style="margin-bottom: 15px;">
                    All API errors return a JSON response with the following format:
                </div>
                <div class="code-block">{
  "success": false,
  "error": "Error message description"
}</div>
                <div class="description" style="margin-top: 15px;">
                    Common HTTP status codes:
                </div>
                <ul style="margin: 15px 0; padding-left: 30px; color: #666; line-height: 2;">
                    <li><strong>200</strong> - Success</li>
                    <li><strong>400</strong> - Bad Request (missing parameters, invalid data)</li>
                    <li><strong>404</strong> - Not Found (resource doesn't exist)</li>
                    <li><strong>500</strong> - Internal Server Error</li>
                </ul>
            </div>

            <!-- Example Usage -->
            <div class="card">
                <h2>💡 Example Usage</h2>

                <h3 style="color: #333; margin: 20px 0 10px;">cURL Example:</h3>
                <div class="code-block"># Get statistics
curl "http://localhost/networkscan/api.php?action=stats"

# Start a new scan
curl -X POST "http://localhost/networkscan/api.php?action=start_scan" \
  -d "target=192.168.1.0/24" \
  -d "type=full" \
  -d "name=API Test Scan"

# Get vulnerabilities
curl "http://localhost/networkscan/api.php?action=vulnerabilities&severity=critical"</div>

                <h3 style="color: #333; margin: 20px 0 10px;">JavaScript (Fetch API) Example:</h3>
                <div class="code-block">// Get statistics
fetch('/networkscan/api.php?action=stats')
  .then(response => response.json())
  .then(data => console.log(data));

// Start a scan
fetch('/networkscan/api.php?action=start_scan', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: 'target=192.168.1.0/24&type=full'
})
  .then(response => response.json())
  .then(data => console.log(data));</div>

                <h3 style="color: #333; margin: 20px 0 10px;">Python Example:</h3>
                <div class="code-block">import requests

# Get statistics
response = requests.get('http://localhost/networkscan/api.php?action=stats')
data = response.json()
print(data)

# Start a scan
response = requests.post(
    'http://localhost/networkscan/api.php?action=start_scan',
    data={'target': '192.168.1.0/24', 'type': 'full'}
)
print(response.json())</div>
            </div>

            <div class="footer">
                <p>&copy; <?= date('Y') ?> Network Security Scanner API. All rights reserved.</p>
                <p style="margin-top: 10px; font-size: 14px;">
                    <a href="../index.php" style="color: white; text-decoration: none;">← Back to Dashboard</a>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function calculateComplianceScore() {
    $db = Database::getInstance();

    $result = $db->fetchOne(
        "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pass' THEN 1 ELSE 0 END) as passed
         FROM compliance_checks"
    );

    if ($result['total'] == 0) {
        return 0;
    }

    return round(($result['passed'] / $result['total']) * 100, 2);
}
