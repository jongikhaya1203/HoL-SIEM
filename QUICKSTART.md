# Email DLP System - Quick Start Guide

## 🚀 Get Started in 3 Minutes

### Step 1: Setup Database (30 seconds)

Open your terminal/command prompt and run:

```bash
cd C:\xampp\htdocs\networkscan
php setup_mailscan.php
```

This will:
- Create the database
- Set up all tables
- Import detection rules

### Step 2: Generate Sample Data (30 seconds)

```bash
php generate_sample_data.php
```

This creates 10 sample emails with various types of sensitive information.

### Step 3: Access the System (1 minute)

Open your browser and visit:

**Main Dashboard**: http://localhost/networkscan/mailscan_dashboard.php

---

## 📊 What You'll See

### Dashboard
- **Total Emails**: Count of all scanned emails
- **Flagged Emails**: Emails with sensitive information detected
- **Total Matches**: Number of detection rule matches
- **Active Rules**: Number of enabled detection rules

### Sample Detections

The system will flag emails containing:
- Credit card numbers (4532-1234-5678-9010)
- Social Security Numbers (123-45-6789)
- API Keys and AWS credentials
- Passwords and database credentials
- Confidential keywords
- Medical record numbers
- IP addresses
- Phone numbers

---

## 🎯 Try It Yourself

### Scan a New Email

1. Go to: http://localhost/networkscan/mailscan_scan.php
2. Click "Load Sample Email" to populate with test data
3. Click "Scan Email"
4. See instant results with risk score and matches

### Manage Detection Rules

1. Go to: http://localhost/networkscan/mailscan_rules.php
2. View all 12 pre-configured detection rules
3. Click "Add New Rule" to create custom patterns
4. Enable/disable rules as needed

### View Email Details

1. From the dashboard, click "View Details" on any flagged email
2. See full email content
3. View all detected patterns with context
4. See risk score and severity levels

---

## 🔍 Detection Categories

The system includes rules for:

| Category | Examples | Severity |
|----------|----------|----------|
| **Financial** | Credit cards, bank accounts | Critical |
| **PII** | SSN, addresses | Critical |
| **Security** | API keys, passwords, AWS keys | Critical |
| **Healthcare** | Patient IDs, medical records | High |
| **Confidential** | "secret", "classified" | High |
| **Contact** | Emails, phone numbers | Low |
| **Network** | IP addresses | Low |

---

## 📝 Key Features Demonstrated

### Real-Time Scanning
Every email is scanned against all active rules instantly.

### Risk Scoring
- Critical match = 100 points
- High match = 50 points
- Medium match = 25 points
- Low match = 10 points

### Context Extraction
See surrounding text for each match to reduce false positives.

### Multiple Pattern Types
- **Regex**: Complex patterns (credit cards, SSN)
- **Keywords**: Simple word matching (confidential, secret)
- **Custom**: Extensible for your needs

---

## 🛠️ Customization

### Add Your Own Rule

1. Go to Detection Rules
2. Click "Add New Rule"
3. Fill in:
   - Rule Name: "Employee IDs"
   - Pattern: `EMP-\d{5}` (regex)
   - Severity: Medium
   - Category: Internal

### Create Test Email

```
Subject: Employee Data
Body: Here is employee info: EMP-12345 works in department XYZ
```

The system will detect the pattern!

---

## 📚 Full Documentation

For complete documentation, see: `MAILSCAN_README.md`

Topics covered:
- Full feature list
- Security considerations
- API integration
- Extending the system
- Production deployment
- Troubleshooting

---

## ✅ Quick Checklist

- [ ] Run `setup_mailscan.php`
- [ ] Run `generate_sample_data.php`
- [ ] Access dashboard
- [ ] View sample flagged emails
- [ ] Check detection rules
- [ ] Scan a test email
- [ ] View email details
- [ ] Create a custom rule

---

## 🎓 Example Use Cases

### 1. Prevent Data Leaks
Detect when employees send customer data, credentials, or financial info to external addresses.

### 2. Compliance Monitoring
Ensure HIPAA, PCI-DSS, and other compliance by flagging protected data types.

### 3. Insider Threat Detection
Monitor for keywords indicating insider trading or confidential leaks.

### 4. Security Auditing
Track exposure of API keys, passwords, and system credentials.

---

## 💡 Pro Tips

1. **Start with High Severity Rules**: Focus on critical data first
2. **Test Rules Thoroughly**: Use the scan interface to test patterns
3. **Review Context**: Check the context snippet to avoid false positives
4. **Disable Noisy Rules**: Turn off rules that generate too many matches
5. **Create Categories**: Group similar rules for easier management

---

## 🚨 Sample Email Highlights

After running `generate_sample_data.php`, check out:

1. **"Q4 Financial Report"** - Contains API key + confidential keywords
2. **"Employee Records"** - SSN + credit card leak
3. **"Production Database Access"** - Multiple AWS keys + passwords
4. **"Customer List"** - Credit cards + customer PII

---

## 🔗 URLs Reference

- **Dashboard**: http://localhost/networkscan/mailscan_dashboard.php
- **Rules**: http://localhost/networkscan/mailscan_rules.php
- **Scan**: http://localhost/networkscan/mailscan_scan.php

---

Happy scanning! Your data is now protected. 🛡️
