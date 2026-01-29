<?php
require_once 'mailscan_config.php';
require_once 'EmailScanner.php';

$db = getDBConnection();
$scanner = new EmailScanner();

echo "=== Email DLP System - Sample Data Generator ===\n\n";

// Sample emails with various types of sensitive information
$sampleEmails = [
    [
        'sender_email' => 'finance@techcorp.com',
        'sender_name' => 'Sarah Johnson',
        'recipient_email' => 'external.partner@competitor.com',
        'subject' => 'CONFIDENTIAL: Q4 Financial Report',
        'body_text' => "Hi Team,

Please find below our confidential Q4 financial results that will be announced next week.

Revenue: $15.5 Million
Net Profit: $3.2 Million

This is classified information and should not be shared outside the organization.

For verification, use API key: AKIAIOSFODNN7EXAMPLE

Thanks,
Sarah"
    ],
    [
        'sender_email' => 'hr@techcorp.com',
        'sender_name' => 'Michael Chen',
        'recipient_email' => 'personal.email@gmail.com',
        'subject' => 'Employee Records - Urgent',
        'body_text' => "Hi,

I'm forwarding the employee salary data to my personal email for weekend work.

John Doe - SSN: 123-45-6789 - Salary: $95,000
Jane Smith - SSN: 987-65-4321 - Salary: $110,000

Also, here's the company credit card: 4532-1234-5678-9010, expires 12/25, CVV: 123

Thanks"
    ],
    [
        'sender_email' => 'dev@techcorp.com',
        'sender_name' => 'Alex Rodriguez',
        'recipient_email' => 'contractor@external.com',
        'subject' => 'Production Database Access',
        'body_text' => "Hey,

Here are the production credentials you need:

Database: prod-db.techcorp.com
Username: admin
Password: Pr0dP@ssw0rd2024!

AWS Access Key: AKIAI44QH8DHBEXAMPLE
Secret Key: je7MtGbClwBF/2Zp9Utk/h3yCo8nvbEXAMPLEKEY

API Key: sk_test_EXAMPLE_KEY_FOR_TESTING_ONLY_12345

Let me know if you need anything else.

Alex"
    ],
    [
        'sender_email' => 'legal@techcorp.com',
        'sender_name' => 'Jennifer Williams',
        'recipient_email' => 'reporter@newsoutlet.com',
        'subject' => 'Re: Pending Lawsuit Information',
        'body_text' => "This email contains proprietary trade secrets about our upcoming product launch.

The confidential release date is March 15th, 2024. We have insider information about the competitor's strategy.

Patient records from our healthcare division:
- Patient ID: MRN-445566
- Medical Record Number: 778899

Please keep this internal only.

Jennifer"
    ],
    [
        'sender_email' => 'sales@techcorp.com',
        'sender_name' => 'Robert Taylor',
        'recipient_email' => 'friend@personal.com',
        'subject' => 'Customer List - FYI',
        'body_text' => "Hey buddy,

Check out our top customers and their contact info:

1. ABC Corp - CEO John Smith - john.smith@abccorp.com - 555-123-4567
2. XYZ Inc - CFO Mary Jones - mary.jones@xyzinc.com - 555-987-6543

Their credit cards on file:
ABC Corp: 5425-2334-3010-9800
XYZ Inc: 3782-822463-10005

This is secret information but thought you'd find it interesting!

Rob"
    ],
    [
        'sender_email' => 'marketing@techcorp.com',
        'sender_name' => 'Emily Davis',
        'recipient_email' => 'team@techcorp.com',
        'subject' => 'Campaign Performance Report',
        'body_text' => "Team,

Great work on the Q4 campaign! Here are the results:

- Email opens: 45,000
- Click-through rate: 12%
- Conversions: 2,340

The campaign performed well within normal parameters. No sensitive information to report.

Best regards,
Emily"
    ],
    [
        'sender_email' => 'ceo@techcorp.com',
        'sender_name' => 'David Anderson',
        'recipient_email' => 'board@techcorp.com',
        'subject' => 'Merger Discussion - HIGHLY CONFIDENTIAL',
        'body_text' => "Board Members,

This is highly confidential and classified information regarding our secret merger talks with CompetitorCorp.

The proposed acquisition price is $500 Million. This insider information must not be leaked before the official announcement.

We have proprietary data showing their customer churn rate is 15% higher than publicly reported.

Do not distribute this email outside the board.

David Anderson
CEO"
    ],
    [
        'sender_email' => 'it.security@techcorp.com',
        'sender_name' => 'Kevin Martinez',
        'recipient_email' => 'external.auditor@audit.com',
        'subject' => 'Security Audit - Access Credentials',
        'body_text' => "Hi Auditor,

For the security audit, here are our system access details:

Server IP: 192.168.1.100
Admin Panel: https://admin.techcorp.com
Username: sysadmin
Password is: AdminP@ss123

VPN Access Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9

Also, our AWS key: AKIA1234567890EXAMPLE

Kevin Martinez
IT Security"
    ],
    [
        'sender_email' => 'customer.service@techcorp.com',
        'sender_name' => 'Lisa Thompson',
        'recipient_email' => 'customer@example.com',
        'subject' => 'Thank you for your purchase!',
        'body_text' => "Dear Customer,

Thank you for your recent purchase! Your order #12345 has been processed.

We appreciate your business and hope you enjoy your new product.

If you have any questions, please contact us at support@techcorp.com or call 1-800-555-0123.

Best regards,
Lisa Thompson
Customer Service Team"
    ],
    [
        'sender_email' => 'research@techcorp.com',
        'sender_name' => 'Dr. James Wilson',
        'recipient_email' => 'university@external.edu',
        'subject' => 'Research Data Sharing - Medical Study',
        'body_text' => "Dear Colleague,

I'm sharing our confidential medical research data for the joint study.

Patient records included:
- Patient ID: MRN-112233, SSN: 456-78-9012
- Patient ID: MRN-445566, SSN: 789-01-2345

Medical record numbers and full patient histories are attached.

This data is proprietary and contains trade secret methodologies.

Please keep confidential.

Dr. James Wilson"
    ]
];

echo "Inserting sample emails and scanning them...\n\n";

$totalInserted = 0;
$totalScanned = 0;
$totalMatches = 0;

foreach ($sampleEmails as $index => $emailData) {
    try {
        // Generate unique email ID
        $email_id = 'SAMPLE-' . date('Ymd') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

        // Insert email
        $stmt = $db->prepare("
            INSERT INTO email_logs
            (email_id, sender_email, sender_name, recipient_email, subject, body_text, received_date)
            VALUES (?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)
        ");

        $stmt->execute([
            $email_id,
            $emailData['sender_email'],
            $emailData['sender_name'],
            $emailData['recipient_email'],
            $emailData['subject'],
            $emailData['body_text'],
            rand(0, 30) // Random date within last 30 days
        ]);

        $totalInserted++;
        echo "✓ Inserted: {$emailData['subject']}\n";

        // Scan the email
        $result = $scanner->scanEmail($email_id);
        $totalScanned++;

        if ($result['total_matches'] > 0) {
            $totalMatches += $result['total_matches'];
            echo "  └─ Status: FLAGGED | Risk Score: {$result['risk_score']} | Matches: {$result['total_matches']}\n";
        } else {
            echo "  └─ Status: Clean | No sensitive data detected\n";
        }

    } catch (Exception $e) {
        echo "✗ Error with email '{$emailData['subject']}': " . $e->getMessage() . "\n";
    }

    echo "\n";
}

// Display summary
echo "\n=== Summary ===\n";
echo "Total Emails Inserted: $totalInserted\n";
echo "Total Emails Scanned: $totalScanned\n";
echo "Total Matches Found: $totalMatches\n";

// Get statistics
$stats = $scanner->getStatistics();
echo "\n=== Current System Statistics ===\n";
echo "Total Emails in System: {$stats['total_emails']}\n";
echo "Flagged Emails: {$stats['flagged_emails']}\n";
echo "Total Detection Matches: {$stats['total_matches']}\n";
echo "Active Rules: {$stats['active_rules']}\n";

if (!empty($stats['by_severity'])) {
    echo "\nMatches by Severity:\n";
    foreach ($stats['by_severity'] as $sev) {
        echo "  - " . ucfirst($sev['severity']) . ": {$sev['count']}\n";
    }
}

echo "\n✓ Sample data generation complete!\n";
echo "\nYou can now view the results at:\n";
echo "- Dashboard: http://localhost/networkscan/mailscan_dashboard.php\n";
echo "- Rules: http://localhost/networkscan/mailscan_rules.php\n";
echo "- Scan Email: http://localhost/networkscan/mailscan_scan.php\n";
?>
