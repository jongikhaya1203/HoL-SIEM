# Email Leak Tracker - Advanced DLP Feature

## Overview

The Email Leak Tracker is an advanced feature that tracks how emails are forwarded through multiple recipients, identifying unauthorized data leaks and insider threats. It provides complete visibility into email forwarding chains from the original sender to the final recipient.

## Key Features

### 🔗 **Chain Tracking**
- Track email forwarding across multiple hops
- Visualize complete path from sender to final recipient
- Identify when emails leave authorized channels
- Monitor both internal and external forwarding

### 🚨 **Leak Detection**
- Automatic detection of unauthorized recipients
- Classification of leak types (competitor, media, personal email)
- Risk scoring for each forwarding hop
- Incident creation for critical leaks

### 📊 **Analytics & Insights**
- Identify employees who frequently leak information
- Track leaks by destination type (competitor, personal, public)
- Monitor forwarding patterns and trends
- Generate incident reports

### 🎯 **Domain Classification**
- Categorize domains (internal, partner, competitor, personal, suspicious)
- Trust level management (trusted, neutral, suspicious, blocked)
- Whitelist/blacklist management
- Risk scoring per domain

## Installation

### Step 1: Install Leak Tracking Tables

```bash
cd C:\xampp\htdocs\networkscan
php install_leak_tracking.php
```

This creates:
- `email_forwarding_chains` - Tracks each forwarding hop
- `email_recipients` - Stores recipient information and stats
- `leak_incidents` - Records detected leak incidents
- `domain_classifications` - Domain trust and risk data

### Step 2: Generate Sample Data

```bash
php generate_leak_chains.php
```

This creates 10 realistic leak scenarios demonstrating various patterns.

### Step 3: Access the Tracker

Open: **http://localhost/networkscan/mailscan_leak_tracker.php**

## Sample Leak Scenarios

The system includes 10 pre-configured leak scenarios:

### 1. Simple Personal Email Leak
```
CFO → Employee → Personal Gmail
```
**Risk**: Medium - Confidential financial data sent to personal email

### 2. Multi-Hop Competitor Leak
```
CEO → Executive Team → Employee → Personal Yahoo → Competitor
```
**Risk**: Critical - Merger information reaches competitor through 4 hops

### 3. Media Leak
```
Legal Department → Management → News Reporter
```
**Risk**: High - Lawsuit information leaked to media

### 4. Customer Data Breach
```
Sales → Employee → Personal Gmail → External Friend
```
**Risk**: High - Customer database forwarded externally

### 5. BCC Leak
```
HR → Employee [BCC: Competitor]
```
**Risk**: Critical - Salary data secretly shared with competitor

### 6. Safe Internal Chain (No Leak)
```
Manager → Team → Employee 1 → Employee 2
```
**Risk**: Low - Proper internal communication

### 7. Partner-to-Competitor Leak
```
Product Team → Employee → Partner → Competitor
```
**Risk**: Critical - Trusted partner leaks to competitor

### 8. Rapid External Cascade
```
Research → Employee → Personal Gmail → Friend 1 → Friend 2 → Competitor
```
**Risk**: Critical - 5-hop leak of proprietary research

### 9. CC to Unauthorized Recipient
```
Strategy Team → Executives [CC: Competitor]
```
**Risk**: Critical - Competitive analysis shared directly

### 10. Long Internal Chain with Final Leak
```
Board → CEO → CFO → Finance Team → Employee 1 → Employee 2 → News Media
```
**Risk**: Critical - Board minutes leaked after long internal chain

## How It Works

### Email Forwarding Chain Tracking

1. **Record Each Hop**: Every time an email is forwarded, a new record is created
   ```php
   $tracker->recordForwarding(
       $chainId,           // Unique chain identifier
       $originalEmailId,   // Original email
       $hopNumber,         // Position in chain
       $fromAddress,       // Who forwarded it
       $toAddress,         // Who received it
       $subject,           // Email subject
       $forwardType        // direct_forward, cc, bcc, etc.
   );
   ```

2. **Risk Assessment**: Each hop is scored based on:
   - Destination domain classification
   - Number of hops (more hops = higher risk)
   - Forward type (BCC is riskier than direct forward)
   - Recipient trust level

3. **Leak Detection**: Automatically triggers when:
   - Email goes to external unauthorized domain
   - Risk score exceeds threshold (70+)
   - Recipient is on blacklist
   - Domain classified as competitor or suspicious

### Domain Classification System

Domains are automatically classified:

| Classification | Examples | Risk Score | Description |
|----------------|----------|------------|-------------|
| **Internal** | techcorp.com | 0 | Company domains |
| **Partner** | partner.com, vendor.com | 20-30 | Trusted business partners |
| **Competitor** | rival-corp.com | 100 | Direct competitors |
| **Personal** | gmail.com, yahoo.com | 70 | Personal email services |
| **Public** | newsoutlet.com | 85 | Media/public entities |
| **Suspicious** | tempmail.com | 95 | Disposable/anonymous emails |

### Risk Scoring Formula

```
Risk Score =
  + Base hop risk (10 × hop number, max 30)
  + Domain risk score (0-100)
  + Forward type risk (5-40)

Maximum: 100
```

**Risk Levels**:
- **Low (0-39)**: Normal internal forwarding
- **Medium (40-69)**: Potential concern, monitor
- **High (70-89)**: Unauthorized forward, investigate
- **Critical (90-100)**: Severe leak, immediate action

## Using the Leak Tracker

### Dashboard View

The main dashboard shows:
- **Total Chains Tracked**: All forwarding chains
- **Leak Incidents**: Detected unauthorized leaks
- **Critical Incidents**: Highest severity leaks
- **External Forwards**: Count of external forwarding
- **Unauthorized Forwards**: Blocked/suspicious recipients

### Incident Table

Each incident displays:
- **Incident ID**: Unique identifier
- **Source**: Who initiated the leak
- **Destination**: Final unauthorized recipient
- **Hops**: Number of forwards in chain
- **Type**: external_leak, competitor_leak, personal_email, etc.
- **Severity**: critical, high, medium, low
- **Status**: new, investigating, confirmed, resolved
- **Action**: Link to view full chain

### Chain Visualization

Click "View Chain" to see:
- **Visual flow diagram** of email path
- **Each hop** with sender/receiver details
- **Risk scores** for each hop
- **Classifications** (internal, external, unauthorized)
- **Timestamps** for each forward
- **Leak alert** if unauthorized recipient detected

## API Usage

### Track an Email Chain Programmatically

```php
require_once 'EmailLeakTracker.php';

$tracker = new EmailLeakTracker();

// Define forwarding path
$forwardingPath = [
    [
        'from' => 'ceo@company.com',
        'to' => 'employee@company.com',
        'subject' => 'Confidential Strategy',
        'type' => 'direct_forward'
    ],
    [
        'from' => 'employee@company.com',
        'to' => 'personal@gmail.com',
        'subject' => 'FW: Strategy',
        'type' => 'personal_email'
    ]
];

// Track the chain
$chainId = $tracker->trackEmailChain('EMAIL-001', $forwardingPath);

// Get chain details
$chain = $tracker->getEmailChain($chainId);

// Get statistics
$stats = $tracker->getTrackingStats();
```

### Get Top Leakers

```php
$topLeakers = $tracker->getTopLeakers(10);

foreach ($topLeakers as $leaker) {
    echo "{$leaker['email_address']}: {$leaker['leak_incidents']} leaks\n";
}
```

### Get Recent Incidents

```php
// All incidents
$incidents = $tracker->getLeakIncidents(50);

// Only new incidents
$newIncidents = $tracker->getLeakIncidents(50, 'new');

// Only critical severity
$criticalIncidents = $tracker->getLeakIncidents(50, null);
// Then filter by severity in your code
```

## Database Schema

### email_forwarding_chains
Records each hop in a forwarding chain
- `chain_id`: Groups hops together
- `hop_number`: Position in sequence
- `from_address` / `to_address`: Sender and recipient
- `is_external`: Whether recipient is outside organization
- `is_unauthorized`: Whether recipient is not allowed
- `leak_risk_score`: Calculated risk (0-100)

### email_recipients
Stores recipient information and statistics
- `email_address`: Unique email
- `recipient_type`: internal_employee, external_partner, personal_email, etc.
- `trust_level`: trusted, suspicious, unauthorized, blocked
- `total_emails_forwarded`: Forwarding activity
- `leak_incidents`: Number of times involved in leaks

### leak_incidents
Records detected leak events
- `incident_id`: Unique identifier
- `leak_source` / `leak_destination`: Origin and final recipient
- `severity`: critical, high, medium, low
- `incident_type`: external_leak, competitor_leak, public_exposure, etc.
- `investigation_status`: new, investigating, confirmed, resolved

### domain_classifications
Categorizes email domains
- `domain`: Email domain (e.g., gmail.com)
- `classification`: internal, partner, competitor, personal, public, suspicious
- `trust_level`: trusted, neutral, suspicious, blocked
- `risk_score`: 0-100

## Use Cases

### 1. Insider Threat Detection
Identify employees who repeatedly forward confidential information to unauthorized recipients.

**Query**: Top leakers with most incidents
```sql
SELECT email_address, leak_incidents, total_emails_forwarded
FROM email_recipients
WHERE leak_incidents > 0
ORDER BY leak_incidents DESC;
```

### 2. Competitor Intelligence Leak
Track emails that reach competitor domains to prevent competitive disadvantage.

**Filter**: Show only competitor leaks in dashboard

### 3. Data Breach Investigation
When a leak is discovered, trace the complete path to identify the source.

**Action**: Click "View Chain" to see full forwarding history

### 4. Policy Compliance
Ensure employees don't send sensitive data to personal emails.

**Report**: Filter incidents by type "personal_email"

### 5. Partner Trust Validation
Monitor if trusted partners leak your information externally.

**Pattern**: partner.com → competitor.com chains

## Best Practices

### 1. Regular Monitoring
- Check dashboard daily for new incidents
- Review critical incidents immediately
- Investigate patterns in top leakers

### 2. Domain Management
- Keep domain classifications up-to-date
- Add known partners to whitelist
- Block suspicious domains proactively

### 3. Incident Response
- Set status to "investigating" when reviewing
- Add notes about findings
- Mark as "false_positive" if legitimate
- Update to "resolved" with resolution notes

### 4. Employee Training
- Share leak statistics (anonymized) with staff
- Educate about risks of personal email forwarding
- Establish clear email forwarding policies

### 5. Integration
- Combine with email content scanning (mailscan_dashboard.php)
- Cross-reference leaked emails with sensitive data detection
- Generate comprehensive DLP reports

## Extending the System

### Add Custom Domain Classifications

```sql
INSERT INTO domain_classifications
(domain, classification, trust_level, risk_score, notes)
VALUES
('newpartner.com', 'partner', 'trusted', 25, 'New business partner'),
('unknown-domain.com', 'suspicious', 'blocked', 90, 'Unknown entity');
```

### Create Custom Incident Types

Modify the ENUM in `leak_incidents` table:
```sql
ALTER TABLE leak_incidents
MODIFY incident_type ENUM('internal_forward', 'external_leak', 'public_exposure',
                          'competitor_leak', 'personal_email', 'regulatory_violation');
```

### Add Automated Alerting

Extend EmailLeakTracker class:
```php
private function createLeakIncident($chainId, $originalEmailId, $source, $destination, $totalHops) {
    // ... existing code ...

    // Add email alert
    if ($severity === 'critical') {
        $this->sendAlert($incidentId, $source, $destination);
    }
}
```

## Troubleshooting

### No Chains Appearing
- Run `php generate_leak_chains.php` to create sample data
- Check database connection in mailscan_config.php
- Verify tables were created with `install_leak_tracking.php`

### Risk Scores Always Low
- Review domain classifications
- Check if domains are properly categorized
- Ensure risk_score values are set in domain_classifications

### Incidents Not Created
- Verify is_unauthorized logic in EmailLeakTracker.php
- Check domain_classifications for destination domains
- Ensure leak risk threshold is appropriate

## Performance Optimization

For large deployments:

1. **Index Optimization**: Already configured on key fields
2. **Archiving**: Move old chains to archive table after 90 days
3. **Caching**: Cache domain classifications in memory
4. **Batch Processing**: Process chains in batches during off-hours

## Security Considerations

- **Access Control**: Restrict leak tracker to security team only
- **Audit Logging**: All views are logged in audit_log table
- **Data Retention**: Define policy for chain data retention
- **Encryption**: Encrypt sensitive email subjects/content at rest

## Version

- **Version**: 1.0
- **Last Updated**: 2025
- **Requires**: PHP 7.4+, MySQL 5.7+, mailscan_dlp database

## Support

For issues or questions:
1. Check MAILSCAN_README.md for basic DLP setup
2. Review sample scenarios in generate_leak_chains.php
3. Examine EmailLeakTracker.php for implementation details

---

**Protect your organization from data leaks with comprehensive email chain tracking!** 🔒
