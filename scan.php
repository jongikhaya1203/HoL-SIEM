<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Scan - Network Security Scanner</title>
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
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 13px;
        }

        .btn {
            padding: 15px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }

        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .nav-btn {
            display: inline-block;
            padding: 10px 20px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: #f5f5f5;
        }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .alert-warning {
            background: #fff3e0;
            border-color: #ff9800;
            color: #e65100;
        }

        .progress {
            display: none;
            margin: 20px 0;
        }

        .progress-bar {
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        #scanOutput {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            margin-top: 20px;
            white-space: pre-wrap;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        .scan-type-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="nav-btn">← Back to Dashboard</a>

        <div class="header">
            <h1>🎯 Start New Scan</h1>
            <p>Configure and initiate a network security assessment</p>
        </div>

        <div class="card">
            <form id="scanForm">
                <div class="form-group">
                    <label for="scanName">Scan Name</label>
                    <input type="text" id="scanName" name="name" placeholder="e.g., Production Network Scan">
                    <small>Optional: Give this scan a descriptive name</small>
                </div>

                <div class="form-group">
                    <label for="target">Target *</label>
                    <input type="text" id="target" name="target" required
                           placeholder="e.g., 192.168.1.1, 192.168.1.0/24, 192.168.1.1-10">
                    <small>IP address, CIDR notation, or IP range (comma-separated for multiple targets)</small>
                </div>

                <div class="form-group">
                    <label for="scanType">Scan Type *</label>
                    <select id="scanType" name="type" required>
                        <option value="quick">Quick Scan - Common ports only (~5-10 min)</option>
                        <option value="full" selected>Full Scan - Extended port range (~15-30 min)</option>
                        <option value="vulnerability">Vulnerability Scan - Focus on known vulnerabilities</option>
                        <option value="compliance">Compliance Scan - Include compliance checks</option>
                    </select>

                    <div class="scan-type-info" id="scanTypeInfo">
                        <strong>Full Scan:</strong> Scans common ports (1-1024) plus extended range (1024-10000).
                        Includes service detection, vulnerability assessment, and risk scoring.
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="runCompliance" name="compliance">
                    <label for="runCompliance">Run Compliance Checks (NIST, ISO 27001, CIS, PCI DSS, HIPAA)</label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="generateReport" name="generateReport" checked>
                    <label for="generateReport">Generate HTML Report after scan</label>
                </div>

                <div class="alert alert-warning">
                    <strong>⚠️ Important:</strong> Only scan networks you have explicit permission to test.
                    Unauthorized network scanning may be illegal and violate computer fraud laws.
                </div>

                <button type="submit" class="btn" id="startBtn">Start Scan</button>
            </form>

            <div class="progress" id="progressBar">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill">0%</div>
                </div>
            </div>

            <div id="scanOutput"></div>
        </div>
    </div>

    <script>
        const scanTypeDescriptions = {
            quick: 'Quick Scan: Scans only the most common 20-30 ports. Fastest option, suitable for basic discovery.',
            full: 'Full Scan: Scans common ports plus extended range. Includes service detection, vulnerability assessment, and risk scoring.',
            vulnerability: 'Vulnerability Scan: Focused scan that prioritizes vulnerability detection and CVE matching.',
            compliance: 'Compliance Scan: Comprehensive scan with detailed compliance checking against multiple security frameworks.'
        };

        document.getElementById('scanType').addEventListener('change', function() {
            document.getElementById('scanTypeInfo').innerHTML = '<strong>' +
                this.options[this.selectedIndex].text.split('-')[0] + ':</strong> ' +
                scanTypeDescriptions[this.value];
        });

        document.getElementById('scanForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                target: formData.get('target'),
                type: formData.get('type'),
                name: formData.get('name') || 'Web Scan ' + new Date().toLocaleString(),
                compliance: document.getElementById('runCompliance').checked,
                generateReport: document.getElementById('generateReport').checked
            };

            // Disable button
            const btn = document.getElementById('startBtn');
            btn.disabled = true;
            btn.textContent = 'Scanning...';

            // Show progress
            document.getElementById('progressBar').style.display = 'block';
            document.getElementById('scanOutput').style.display = 'block';
            document.getElementById('scanOutput').textContent = 'Initializing scan...\n';

            try {
                // Start scan asynchronously
                const response = await fetch('start_scan_async.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(data)
                });

                const startResult = await response.json();

                if (!startResult.success) {
                    throw new Error(startResult.error || 'Failed to start scan');
                }

                const scanId = startResult.scan_id;
                document.getElementById('scanOutput').textContent += 'Scan started! ID: ' + scanId + '\n';
                document.getElementById('scanOutput').textContent += 'Monitoring progress...\n\n';

                // Poll for scan status
                let lastMessage = '';
                const statusInterval = setInterval(async () => {
                    try {
                        const statusResponse = await fetch('get_scan_status.php?scan_id=' + scanId);
                        const statusResult = await statusResponse.json();

                        if (statusResult.success) {
                            const scan = statusResult.scan;
                            const progress = Math.max(0, Math.min(100, scan.progress || 0));

                            document.getElementById('progressFill').style.width = progress + '%';
                            document.getElementById('progressFill').textContent = progress + '%';

                            // Display progress message if available and new
                            if (scan.progress_message && scan.progress_message !== lastMessage) {
                                lastMessage = scan.progress_message;
                                const timestamp = new Date().toLocaleTimeString();
                                const scanOutput = document.getElementById('scanOutput');
                                scanOutput.textContent += `[${timestamp}] ${progress}% - ${scan.progress_message}\n`;
                                // Auto-scroll to bottom
                                scanOutput.scrollTop = scanOutput.scrollHeight;
                            }

                            if (scan.status === 'completed') {
                                clearInterval(statusInterval);

                                const scanData = {
                                    scan_id: scan.id,
                                    total_hosts: scan.total_hosts,
                                    total_vulnerabilities: scan.total_vulnerabilities,
                                    severity_counts: {
                                        critical: scan.critical_count,
                                        high: scan.high_count,
                                        medium: scan.medium_count,
                                        low: scan.low_count,
                                        info: scan.info_count
                                    }
                                };

                                let output = '\n✓ Scan completed successfully!\n\n';
                                output += '═══════════════════════════════════════════\n';
                                output += 'SCAN SUMMARY\n';
                                output += '═══════════════════════════════════════════\n';
                                output += 'Scan ID: ' + scanData.scan_id + '\n';
                                output += 'Hosts Scanned: ' + scanData.total_hosts + '\n';
                                output += 'Total Vulnerabilities: ' + scanData.total_vulnerabilities + '\n\n';

                                output += 'Severity Breakdown:\n';
                                output += '  Critical: ' + scanData.severity_counts.critical + '\n';
                                output += '  High: ' + scanData.severity_counts.high + '\n';
                                output += '  Medium: ' + scanData.severity_counts.medium + '\n';
                                output += '  Low: ' + scanData.severity_counts.low + '\n';
                                output += '  Info: ' + scanData.severity_counts.info + '\n\n';

                                if (data.generateReport) {
                                    output += '📄 Generating report...\n';

                                    try {
                                        const reportResponse = await fetch('api.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/x-www-form-urlencoded'
                                            },
                                            body: new URLSearchParams({
                                                action: 'report',
                                                scan_id: scanData.scan_id,
                                                format: 'html',
                                                type: 'full'
                                            })
                                        });

                                        const reportResult = await reportResponse.json();
                                        if (reportResult.success) {
                                            output += '✓ Report saved to: ' + reportResult.report.file_path + '\n';
                                        }
                                    } catch (reportError) {
                                        output += '✗ Report generation failed\n';
                                    }
                                }

                                output += '\n═══════════════════════════════════════════\n';
                                output += '\nView detailed results: index.php?scan=' + scanData.scan_id;

                                document.getElementById('scanOutput').textContent += output;

                                // Re-enable button
                                btn.disabled = false;
                                btn.textContent = 'Start Another Scan';

                                // Redirect to results after 3 seconds
                                setTimeout(() => {
                                    window.location.href = 'index.php';
                                }, 3000);

                            } else if (scan.status === 'failed') {
                                clearInterval(statusInterval);
                                throw new Error('Scan failed');
                            }
                        }
                    } catch (pollError) {
                        console.error('Polling error:', pollError);
                    }
                }, 2000); // Poll every 2 seconds

            } catch (error) {
                document.getElementById('scanOutput').textContent += '\n✗ Error: ' + error.message;
                btn.disabled = false;
                btn.textContent = 'Retry Scan';
                document.getElementById('progressFill').style.background = '#f44336';
            }
        });
    </script>
</body>
</html>
