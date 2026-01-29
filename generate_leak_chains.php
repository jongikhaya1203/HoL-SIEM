<?php
require_once 'mailscan_config.php';
require_once 'EmailLeakTracker.php';

$db = getDBConnection();
$tracker = new EmailLeakTracker();

echo "=== Email Leak Chain Generator ===\n\n";

// Sample leak scenarios demonstrating different patterns
$leakScenarios = [
    // Scenario 1: Simple external leak to personal email
    [
        'description' => 'Employee forwards confidential email to personal Gmail',
        'original_email_id' => 'EMAIL-001',
        'path' => [
            ['from' => 'cfo@techcorp.com', 'to' => 'john.smith@techcorp.com', 'subject' => 'Q4 Financial Results - CONFIDENTIAL', 'type' => 'direct_forward'],
            ['from' => 'john.smith@techcorp.com', 'to' => 'john.personal@gmail.com', 'subject' => 'FW: Q4 Financial Results', 'type' => 'personal_email']
        ]
    ],

    // Scenario 2: Multi-hop leak to competitor
    [
        'description' => 'Email forwarded through multiple hops, eventually reaching competitor',
        'original_email_id' => 'EMAIL-002',
        'path' => [
            ['from' => 'ceo@techcorp.com', 'to' => 'executive-team@techcorp.com', 'subject' => 'Merger Discussion - Top Secret', 'type' => 'direct_forward'],
            ['from' => 'sarah.johnson@techcorp.com', 'to' => 'robert.taylor@techcorp.com', 'subject' => 'FW: Merger Discussion', 'type' => 'direct_forward'],
            ['from' => 'robert.taylor@techcorp.com', 'to' => 'sarah.home@yahoo.com', 'subject' => 'FW: Important Info', 'type' => 'personal_email'],
            ['from' => 'sarah.home@yahoo.com', 'to' => 'competitor@rival-corp.com', 'subject' => 'FW: Insider Info', 'type' => 'external_leak']
        ]
    ],

    // Scenario 3: Leak to news media
    [
        'description' => 'Insider leaks company information to news reporter',
        'original_email_id' => 'EMAIL-003',
        'path' => [
            ['from' => 'legal@techcorp.com', 'to' => 'management@techcorp.com', 'subject' => 'Pending Lawsuit - Confidential', 'type' => 'direct_forward'],
            ['from' => 'mike.chen@techcorp.com', 'to' => 'reporter@newsoutlet.com', 'subject' => 'Anonymous Tip', 'type' => 'external_leak']
        ]
    ],

    // Scenario 4: Multiple personal email forwards
    [
        'description' => 'Employee forwards customer data to multiple personal accounts',
        'original_email_id' => 'EMAIL-004',
        'path' => [
            ['from' => 'sales@techcorp.com', 'to' => 'robert.taylor@techcorp.com', 'subject' => 'Customer Database Export', 'type' => 'direct_forward'],
            ['from' => 'robert.taylor@techcorp.com', 'to' => 'robert.personal@gmail.com', 'subject' => 'FW: Customer List', 'type' => 'personal_email'],
            ['from' => 'robert.personal@gmail.com', 'to' => 'external.friend@gmail.com', 'subject' => 'Check this out', 'type' => 'external_leak']
        ]
    ],

    // Scenario 5: BCC leak to external party
    [
        'description' => 'Employee secretly BCCs external contact on internal discussions',
        'original_email_id' => 'EMAIL-005',
        'path' => [
            ['from' => 'hr@techcorp.com', 'to' => 'lisa.williams@techcorp.com', 'subject' => 'Employee Salary Adjustments 2024', 'type' => 'direct_forward'],
            ['from' => 'lisa.williams@techcorp.com', 'to' => 'competitor@rival-corp.com', 'subject' => 'RE: Salary Info', 'type' => 'bcc']
        ]
    ],

    // Scenario 6: Safe internal forwarding (no leak)
    [
        'description' => 'Normal internal email chain within company',
        'original_email_id' => 'EMAIL-006',
        'path' => [
            ['from' => 'manager@techcorp.com', 'to' => 'team@techcorp.com', 'subject' => 'Project Update', 'type' => 'direct_forward'],
            ['from' => 'john.smith@techcorp.com', 'to' => 'sarah.johnson@techcorp.com', 'subject' => 'FW: Project Update', 'type' => 'direct_forward'],
            ['from' => 'sarah.johnson@techcorp.com', 'to' => 'mike.chen@techcorp.com', 'subject' => 'FW: Project Update', 'type' => 'direct_forward']
        ]
    ],

    // Scenario 7: Leak to trusted partner escalates to competitor
    [
        'description' => 'Email shared with partner who then leaks to competitor',
        'original_email_id' => 'EMAIL-007',
        'path' => [
            ['from' => 'product@techcorp.com', 'to' => 'john.smith@techcorp.com', 'subject' => 'New Product Launch Strategy', 'type' => 'direct_forward'],
            ['from' => 'john.smith@techcorp.com', 'to' => 'partner@partner.com', 'subject' => 'FW: Product Strategy', 'type' => 'direct_forward'],
            ['from' => 'partner@partner.com', 'to' => 'competitor@rival-corp.com', 'subject' => 'FW: Competitor Intel', 'type' => 'external_leak']
        ]
    ],

    // Scenario 8: Rapid multi-hop external leak
    [
        'description' => 'Confidential information rapidly forwarded through external emails',
        'original_email_id' => 'EMAIL-008',
        'path' => [
            ['from' => 'research@techcorp.com', 'to' => 'emily.davis@techcorp.com', 'subject' => 'Proprietary Research Data', 'type' => 'direct_forward'],
            ['from' => 'emily.davis@techcorp.com', 'to' => 'emily.personal@gmail.com', 'subject' => 'Research for review', 'type' => 'personal_email'],
            ['from' => 'emily.personal@gmail.com', 'to' => 'friend1@yahoo.com', 'subject' => 'Interesting data', 'type' => 'external_leak'],
            ['from' => 'friend1@yahoo.com', 'to' => 'friend2@hotmail.com', 'subject' => 'FW: Data', 'type' => 'external_leak'],
            ['from' => 'friend2@hotmail.com', 'to' => 'competitor@rival-corp.com', 'subject' => 'You need to see this', 'type' => 'external_leak']
        ]
    ],

    // Scenario 9: CC leak to unauthorized recipient
    [
        'description' => 'Employee CCs competitor on internal email thread',
        'original_email_id' => 'EMAIL-009',
        'path' => [
            ['from' => 'strategy@techcorp.com', 'to' => 'executives@techcorp.com', 'subject' => 'Competitive Analysis 2024', 'type' => 'direct_forward'],
            ['from' => 'robert.taylor@techcorp.com', 'to' => 'competitor@rival-corp.com', 'subject' => 'RE: Competitive Analysis', 'type' => 'cc']
        ]
    ],

    // Scenario 10: Long internal chain ending in external leak
    [
        'description' => 'Email forwarded through department, final recipient leaks externally',
        'original_email_id' => 'EMAIL-010',
        'path' => [
            ['from' => 'board@techcorp.com', 'to' => 'ceo@techcorp.com', 'subject' => 'Board Meeting Minutes - Confidential', 'type' => 'direct_forward'],
            ['from' => 'ceo@techcorp.com', 'to' => 'cfo@techcorp.com', 'subject' => 'FW: Board Minutes', 'type' => 'direct_forward'],
            ['from' => 'cfo@techcorp.com', 'to' => 'finance-team@techcorp.com', 'subject' => 'FW: Board Minutes', 'type' => 'direct_forward'],
            ['from' => 'john.smith@techcorp.com', 'to' => 'sarah.johnson@techcorp.com', 'subject' => 'FW: Board Minutes', 'type' => 'direct_forward'],
            ['from' => 'sarah.johnson@techcorp.com', 'to' => 'reporter@newsoutlet.com', 'subject' => 'Confidential Source', 'type' => 'external_leak']
        ]
    ]
];

echo "Generating " . count($leakScenarios) . " email leak scenarios...\n\n";

$totalChains = 0;
$totalHops = 0;
$totalLeaks = 0;

foreach ($leakScenarios as $index => $scenario) {
    $scenarioNum = $index + 1;
    echo "Scenario $scenarioNum: {$scenario['description']}\n";

    try {
        $chainId = $tracker->trackEmailChain($scenario['original_email_id'], $scenario['path']);

        $hops = count($scenario['path']);
        $totalHops += $hops;
        $totalChains++;

        echo "  ✓ Chain ID: $chainId\n";
        echo "  → Path: ";

        foreach ($scenario['path'] as $i => $hop) {
            echo $hop['from'];
            if ($i < count($scenario['path']) - 1) {
                echo " → ";
            }
        }
        echo " → " . end($scenario['path'])['to'] . "\n";
        echo "  → Total hops: $hops\n";

        // Check if leak was detected
        $lastHop = end($scenario['path']);
        if (strpos($lastHop['to'], '@techcorp.com') === false) {
            echo "  ⚠️ LEAK DETECTED to: {$lastHop['to']}\n";
            $totalLeaks++;
        } else {
            echo "  ✓ Internal forwarding only\n";
        }

    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

// Display summary
echo "\n=== Summary ===\n";
echo "Total Chains Created: $totalChains\n";
echo "Total Forwarding Hops: $totalHops\n";
echo "External Leaks Detected: $totalLeaks\n";

// Get statistics from tracker
$stats = $tracker->getTrackingStats();
echo "\n=== Current System Statistics ===\n";
echo "Total Chains in System: {$stats['total_chains']}\n";
echo "Total Leak Incidents: {$stats['total_incidents']}\n";
echo "Critical Incidents: {$stats['critical_incidents']}\n";
echo "External Forwards: {$stats['external_forwards']}\n";
echo "Unauthorized Forwards: {$stats['unauthorized_forwards']}\n";

// Display incident breakdown
if (!empty($stats['incidents_by_type'])) {
    echo "\nIncidents by Type:\n";
    foreach ($stats['incidents_by_type'] as $type) {
        echo "  - " . ucfirst(str_replace('_', ' ', $type['incident_type'])) . ": {$type['count']}\n";
    }
}

// Display top leakers
$topLeakers = $tracker->getTopLeakers(5);
if (!empty($topLeakers)) {
    echo "\nTop 5 Email Leakers:\n";
    foreach ($topLeakers as $i => $leaker) {
        $rank = $i + 1;
        echo "  $rank. {$leaker['email_address']} - {$leaker['leak_incidents']} leak(s)\n";
    }
}

echo "\n✓ Sample leak chain data generated successfully!\n\n";
echo "View the leak tracking dashboard at:\n";
echo "http://localhost/networkscan/mailscan_leak_tracker.php\n";
?>
