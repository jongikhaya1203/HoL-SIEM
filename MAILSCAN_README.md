# Email Data Loss Prevention (DLP) System

A comprehensive email scanning platform designed to detect and prevent leaks of sensitive company information through email communications.

## Features

### 🔍 **Advanced Detection Engine**
- **Multi-pattern matching**: Supports regex patterns, keyword matching, and custom pattern detection
- **Real-time scanning**: Instant analysis of email content against detection rules
- **Context-aware detection**: Captures surrounding context for each match
- **Risk scoring**: Automatic calculation of risk scores based on severity levels

### 📋 **Rule Management System**
- **Pre-configured rules**: 12 built-in detection rules for common sensitive data types
- **Custom rule creation**: Add your own detection patterns and keywords
- **Rule categories**: Organize rules by type (PII, Financial, Security, Healthcare, etc.)
- **Severity levels**: Critical, High, Medium, and Low priority classifications
- **Enable/disable rules**: Toggle rules on/off without deletion

### 📊 **Monitoring Dashboard**
- **Real-time statistics**: Track total emails, flagged emails, and detection matches
- **Advanced filtering**: Filter by status, severity, and other criteria
- **Detailed email view**: See full email content with highlighted matches
- **Risk visualization**: Clear display of risk scores and match counts

### 🎯 **Detection Categories**

The system includes pre-configured detection for:
- **Financial Data**: Credit cards, bank account numbers
- **Personal Identifiable Information (PII)**: SSN, phone numbers, addresses
- **Security Credentials**: API keys, passwords, AWS keys, tokens
- **Healthcare Data**: Medical record numbers, patient IDs
- **Confidential Content**: Keywords like "confidential", "secret", "classified"
- **Compliance**: Insider trading keywords, proprietary information
- **Network Information**: IP addresses, server credentials

## Installation & Setup

### Prerequisites
- XAMPP (or similar PHP/MySQL environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Step 1: Database Setup

1. Start XAMPP and ensure Apache and MySQL are running

2. Import the database schema:
   ```bash
   # Option 1: Using phpMyAdmin
   - Open http://localhost/phpmyadmin
   - Create a new database named 'mailscan_dlp'
   - Import the file: mailscan_db.sql

   # Option 2: Using MySQL command line
   mysql -u root -p < mailscan_db.sql
   ```

### Step 2: Configuration

1. The default configuration is in `mailscan_config.php`:
   ```php
   DB_HOST: localhost
   DB_USER: root
   DB_PASS: (empty)
   DB_NAME: mailscan_dlp
   ```

2. If your MySQL credentials are different, edit `mailscan_config.php`

### Step 3: Generate Sample Data

Run the sample data generator to populate the database with example emails:

```bash
cd C:\xampp\htdocs\networkscan
php generate_sample_data.php
```

This will:
- Insert 10 sample emails with varying levels of sensitive information
- Automatically scan each email against all detection rules
- Display a summary of matches and risk scores

### Step 4: Access the System

Open your web browser and navigate to:

- **Dashboard**: http://localhost/networkscan/mailscan_dashboard.php
- **Detection Rules**: http://localhost/networkscan/mailscan_rules.php
- **Scan Email**: http://localhost/networkscan/mailscan_scan.php

## Usage Guide

### Viewing Flagged Emails

1. Navigate to the **Dashboard**
2. View statistics: total emails, flagged emails, matches, active rules
3. Filter emails by status (flagged, scanned, pending) or severity
4. Click "View Details" on any email to see full scan results

### Managing Detection Rules

1. Navigate to **Detection Rules**
2. View all active and inactive rules
3. Click "Add New Rule" to create a custom detection rule:
   - Enter rule name and description
   - Choose rule type (Regex, Keyword, or Pattern)
   - Enter the pattern/keywords
   - Set severity level and action
   - Assign a category
4. Enable/disable rules as needed
5. Delete rules that are no longer needed

### Scanning Emails

1. Navigate to **Scan Email**
2. Fill in the email details:
   - Sender email and name
   - Recipient email
   - Subject
   - Email body
3. Click "Load Sample Email" to populate with example data
4. Click "Scan Email" to analyze
5. View immediate results with:
   - Risk score
   - Number of matches
   - Detected patterns with severity levels
   - Match locations and context

## Sample Data Overview

The system includes 10 sample emails demonstrating various scenarios:

1. **Confidential Financial Report** - Contains API keys, classified keywords
2. **Employee Records** - SSN, credit card, salary data leak
3. **Production Credentials** - Database passwords, AWS keys, API tokens
4. **Legal Information** - Trade secrets, patient records, insider info
5. **Customer Data Leak** - Customer emails, phone numbers, credit cards
6. **Clean Marketing Email** - No sensitive data (baseline)
7. **Merger Discussion** - Highly confidential, insider information
8. **Security Audit** - IP addresses, admin credentials, tokens
9. **Customer Service** - Clean email (baseline)
10. **Medical Research** - Patient data, SSN, medical records

## Detection Rule Examples

### Credit Card Detection (Regex)
```regex
\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})\b
```
Detects: Visa, MasterCard, Amex, Discover card numbers

### SSN Detection (Regex)
```regex
\b\d{3}-\d{2}-\d{4}\b
```
Detects: Social Security Numbers in XXX-XX-XXXX format

### Confidential Keywords (Keyword)
```
confidential|secret|classified|internal only|do not distribute|proprietary|trade secret
```
Detects: Common confidentiality markers

### AWS Keys (Regex)
```regex
(?:AKIA|A3T|AGPA|AIDA|AROA|AIPA|ANPA|ANVA|ASIA)[A-Z0-9]{16}
```
Detects: AWS access key patterns

## System Architecture

```
┌─────────────────────────────────────────────┐
│           Web Interface Layer               │
├─────────────────────────────────────────────┤
│  Dashboard  │  Rules  │  Scan Email         │
└─────────────────────────────────────────────┘
                    │
┌─────────────────────────────────────────────┐
│         Email Scanning Engine               │
│  - Pattern Matching                         │
│  - Regex Processing                         │
│  - Risk Scoring                             │
│  - Context Extraction                       │
└─────────────────────────────────────────────┘
                    │
┌─────────────────────────────────────────────┐
│            Database Layer                   │
│  - email_logs                               │
│  - detection_rules                          │
│  - scan_results                             │
│  - audit_log                                │
└─────────────────────────────────────────────┘
```

## File Structure

```
networkscan/
├── mailscan_config.php          # Configuration and DB connection
├── EmailScanner.php             # Core scanning engine
├── mailscan_dashboard.php       # Main dashboard interface
├── mailscan_details.php         # Email detail view
├── mailscan_rules.php           # Rule management interface
├── mailscan_scan.php            # Email scanning interface
├── mailscan_db.sql              # Database schema
├── generate_sample_data.php     # Sample data generator
└── MAILSCAN_README.md           # This file
```

## Security Considerations

### For Production Use:

1. **Database Security**
   - Use strong database passwords
   - Limit database user privileges
   - Enable SSL for database connections

2. **Authentication**
   - Add user authentication system
   - Implement role-based access control (RBAC)
   - Add session management

3. **Data Protection**
   - Encrypt sensitive data at rest
   - Use HTTPS for all communications
   - Implement audit logging

4. **Input Validation**
   - Validate all user inputs
   - Sanitize data before database insertion
   - Prevent SQL injection with prepared statements (already implemented)

5. **Access Control**
   - Restrict access to authorized personnel only
   - Log all access attempts
   - Implement IP whitelisting if needed

## Extending the System

### Adding New Detection Rules

Create custom detection patterns for your organization:

```sql
INSERT INTO detection_rules
(rule_name, rule_description, rule_type, pattern, severity, category)
VALUES
('Custom Pattern', 'Detects custom sensitive data', 'regex',
'your-regex-pattern', 'high', 'Custom Category');
```

### Integrating with Email Servers

To integrate with real email servers:

1. Add email fetching capability (IMAP/POP3)
2. Implement automatic scanning of incoming/outgoing emails
3. Add quarantine functionality for blocked emails
4. Set up real-time alerts for high-risk detections

### API Integration

The EmailScanner class can be used programmatically:

```php
require_once 'EmailScanner.php';

$scanner = new EmailScanner();
$result = $scanner->scanEmail($emailId);

if ($result['status'] === 'flagged') {
    // Take action: alert, block, quarantine
}
```

## Performance Tips

- **Index Optimization**: Database indexes are pre-configured for optimal query performance
- **Batch Scanning**: Process multiple emails in batches during off-peak hours
- **Rule Optimization**: Disable unused rules to improve scan speed
- **Caching**: Consider caching frequently accessed data

## Troubleshooting

### Database Connection Issues
```
Error: Connection failed
Solution: Check MySQL is running, verify credentials in mailscan_config.php
```

### Rules Not Detecting
```
Issue: Patterns not matching expected content
Solution: Test regex patterns, ensure rules are enabled, check pattern syntax
```

### Sample Data Not Loading
```
Issue: generate_sample_data.php fails
Solution: Ensure database is created, check PHP error logs, verify file permissions
```

## Support & Customization

This system is designed to be:
- **Extensible**: Add new detection rules and categories
- **Customizable**: Modify UI, add features, integrate with existing systems
- **Scalable**: Can handle large volumes of emails with proper optimization

## License

This is a demonstration system for data loss prevention capabilities. Customize and extend as needed for your organization's requirements.

## Version

- **Version**: 1.0
- **Last Updated**: 2025
- **PHP Version**: 7.4+
- **Database**: MySQL 5.7+
