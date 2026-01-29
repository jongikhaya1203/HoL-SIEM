#!/usr/bin/env php
<?php
/**
 * Network Security Scanner - CLI Interface
 * Command-line tool for running network scans
 */

// Include required classes
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/NetworkScanner.php';
require_once __DIR__ . '/classes/ReportGenerator.php';
require_once __DIR__ . '/classes/ComplianceChecker.php';

// CLI color codes
define('COLOR_RESET', "\033[0m");
define('COLOR_RED', "\033[31m");
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_BOLD', "\033[1m");

function printBanner() {
    echo COLOR_CYAN . "
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║     🛡️  IOC Intelligent Operating Centre - CLI 🛡️          ║
║                                                              ║
║     AI-Powered Network Operations Platform                  ║
║     Real-Time Monitoring & Predictive Analytics             ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
" . COLOR_RESET . "\n";
}

function printHelp() {
    echo COLOR_BOLD . "Usage:\n" . COLOR_RESET;
    echo "  php scan_cli.php --target <ip_range> [options]\n\n";

    echo COLOR_BOLD . "Required Arguments:\n" . COLOR_RESET;
    echo "  --target <range>    Target IP address, range, or CIDR notation\n";
    echo "                      Examples: 192.168.1.1, 192.168.1.0/24, 192.168.1.1-10\n\n";

    echo COLOR_BOLD . "Options:\n" . COLOR_RESET;
    echo "  --type <type>       Scan type: quick, full, vulnerability, compliance\n";
    echo "                      Default: full\n";
    echo "  --name <name>       Custom scan name\n";
    echo "  --report <format>   Generate report: html, json, csv\n";
    echo "  --compliance        Run compliance checks\n";
    echo "  --help              Display this help message\n\n";

    echo COLOR_BOLD . "Examples:\n" . COLOR_RESET;
    echo "  # Quick scan of single host\n";
    echo "  php scan_cli.php --target 192.168.1.1 --type quick\n\n";

    echo "  # Full scan of subnet with HTML report\n";
    echo "  php scan_cli.php --target 192.168.1.0/24 --type full --report html\n\n";

    echo "  # Vulnerability scan with compliance checking\n";
    echo "  php scan_cli.php --target 10.0.0.0/24 --type vulnerability --compliance\n\n";

    echo COLOR_YELLOW . "WARNING: Only scan networks you have permission to test!\n" . COLOR_RESET;
    echo "Unauthorized scanning may be illegal.\n\n";
}

function parseArguments($argv) {
    $args = [
        'target' => null,
        'type' => 'full',
        'name' => null,
        'report' => null,
        'compliance' => false,
        'help' => false
    ];

    for ($i = 1; $i < count($argv); $i++) {
        switch ($argv[$i]) {
            case '--target':
                $args['target'] = $argv[++$i] ?? null;
                break;

            case '--type':
                $args['type'] = $argv[++$i] ?? 'full';
                break;

            case '--name':
                $args['name'] = $argv[++$i] ?? null;
                break;

            case '--report':
                $args['report'] = $argv[++$i] ?? 'html';
                break;

            case '--compliance':
                $args['compliance'] = true;
                break;

            case '--help':
            case '-h':
                $args['help'] = true;
                break;
        }
    }

    return $args;
}

function printSuccess($message) {
    echo COLOR_GREEN . "✓ " . $message . COLOR_RESET . "\n";
}

function printError($message) {
    echo COLOR_RED . "✗ " . $message . COLOR_RESET . "\n";
}

function printWarning($message) {
    echo COLOR_YELLOW . "⚠ " . $message . COLOR_RESET . "\n";
}

function printInfo($message) {
    echo COLOR_BLUE . "ℹ " . $message . COLOR_RESET . "\n";
}

// Main execution
try {
    printBanner();

    // Parse arguments
    $args = parseArguments($argv);

    // Show help
    if ($args['help']) {
        printHelp();
        exit(0);
    }

    // Validate required arguments
    if (!$args['target']) {
        printError("Target is required!");
        echo "\nUse --help for usage information.\n";
        exit(1);
    }

    // Confirm scan
    printInfo("Target: " . $args['target']);
    printInfo("Scan Type: " . $args['type']);
    printInfo("Compliance Checks: " . ($args['compliance'] ? 'Yes' : 'No'));

    if ($args['report']) {
        printInfo("Report Format: " . $args['report']);
    }

    echo "\n";
    printWarning("This will scan the specified network for vulnerabilities.");
    echo "Do you want to continue? (y/N): ";

    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);

    if (strtolower($confirmation) !== 'y') {
        printInfo("Scan cancelled.");
        exit(0);
    }

    echo "\n";
    printInfo("Starting scan...\n");

    // Initialize scanner
    $scanner = new NetworkScanner();

    // Set options
    $options = [
        'scan_name' => $args['name'] ?? 'CLI Scan ' . date('Y-m-d H:i:s')
    ];

    // Run scan
    $startTime = microtime(true);
    $result = $scanner->scan($args['target'], $args['type'], $options);
    $endTime = microtime(true);

    $duration = round($endTime - $startTime, 2);

    echo "\n";
    echo str_repeat("=", 60) . "\n";
    printSuccess("Scan completed in {$duration} seconds!");
    echo str_repeat("=", 60) . "\n\n";

    // Display summary
    echo COLOR_BOLD . "Scan Summary:\n" . COLOR_RESET;
    echo "  Scan ID: " . $result['scan_id'] . "\n";
    echo "  Hosts Scanned: " . $result['total_hosts'] . "\n";
    echo "  Total Vulnerabilities: " . $result['total_vulnerabilities'] . "\n\n";

    echo COLOR_BOLD . "Severity Breakdown:\n" . COLOR_RESET;
    echo "  " . COLOR_RED . "Critical: " . $result['severity_counts']['critical'] . COLOR_RESET . "\n";
    echo "  " . COLOR_YELLOW . "High: " . $result['severity_counts']['high'] . COLOR_RESET . "\n";
    echo "  " . COLOR_YELLOW . "Medium: " . $result['severity_counts']['medium'] . COLOR_RESET . "\n";
    echo "  " . COLOR_GREEN . "Low: " . $result['severity_counts']['low'] . COLOR_RESET . "\n";
    echo "  " . COLOR_CYAN . "Info: " . $result['severity_counts']['info'] . COLOR_RESET . "\n\n";

    // Run compliance checks
    if ($args['compliance']) {
        printInfo("Running compliance checks...\n");

        $complianceChecker = new ComplianceChecker();
        $complianceChecker->initializeDefaultControls();
        $complianceResults = $complianceChecker->runComplianceChecks($result['scan_id']);

        echo COLOR_BOLD . "\nCompliance Results:\n" . COLOR_RESET;
        $summary = $complianceChecker->getComplianceSummary($result['scan_id']);

        foreach ($summary as $framework) {
            if ($framework['total_checks'] > 0) {
                $percentage = $framework['compliance_percentage'];
                $color = $percentage >= 80 ? COLOR_GREEN :
                        ($percentage >= 60 ? COLOR_YELLOW : COLOR_RED);

                echo "  {$framework['name']}: {$color}{$percentage}%{COLOR_RESET} ";
                echo "({$framework['passed']}/{$framework['total_checks']} passed)\n";
            }
        }
        echo "\n";
    }

    // Generate report
    if ($args['report']) {
        printInfo("Generating report...");

        $reportGenerator = new ReportGenerator();
        $reportType = $args['compliance'] ? 'full' : 'technical';
        $reportResult = $reportGenerator->generateReport(
            $result['scan_id'],
            $reportType,
            $args['report']
        );

        printSuccess("Report generated: " . $reportResult['file_path']);
        echo "\n";
    }

    // Final recommendations
    if ($result['severity_counts']['critical'] > 0) {
        echo COLOR_RED . COLOR_BOLD;
        echo "⚠️  CRITICAL: " . $result['severity_counts']['critical'] . " critical vulnerabilities found!\n";
        echo "These require immediate attention within 24-48 hours.\n";
        echo COLOR_RESET . "\n";
    } elseif ($result['severity_counts']['high'] > 0) {
        echo COLOR_YELLOW . COLOR_BOLD;
        echo "⚠️  WARNING: " . $result['severity_counts']['high'] . " high-severity vulnerabilities found.\n";
        echo "These should be addressed within 1-2 weeks.\n";
        echo COLOR_RESET . "\n";
    } else {
        printSuccess("No critical or high-severity vulnerabilities found!");
    }

    // Usage tip
    echo COLOR_CYAN;
    echo "Tip: View detailed results at: http://localhost/networkscan/index.php\n";
    echo "     Or generate a report with: --report html\n";
    echo COLOR_RESET;

} catch (Exception $e) {
    printError("Error: " . $e->getMessage());
    echo "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
