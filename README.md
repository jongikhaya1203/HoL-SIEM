# 🛡️ IOC Intelligent Operating Centre

Enterprise-grade network operations and performance management platform built with PHP and MySQL. Powered by **AI/ML Analytics** for intelligent monitoring, predictive analytics, and automated insights.

## 📋 Overview

IOC (Intelligent Operating Centre) is a comprehensive network operations platform that delivers real-time monitoring, performance analysis, network topology visualization, and AI-powered predictive analytics. The platform combines traditional network management capabilities with modern machine learning techniques to provide proactive network operations management, automated anomaly detection, and intelligent recommendations for optimization and capacity planning.

## ✨ Key Features

### 🔍 Network Discovery & Scanning
- **Automatic Host Discovery**: Identify active hosts on the network
- **Port Scanning**: TCP/UDP port enumeration with customizable ranges
- **Service Detection**: Banner grabbing and version fingerprinting
- **Network Topology Mapping**: Visualize network structure and assets

### 🎯 Vulnerability Assessment
- **CVE Database Integration**: Match services against known vulnerabilities
- **CVSS Scoring**: Industry-standard vulnerability severity rating (0-10 scale)
- **Risk Prioritization**: Automated risk scoring based on exploitability and impact
- **Multiple Scan Types**: Quick, Full, Vulnerability-focused, and Compliance scans

### 🔐 Security Checks
- **Weak Protocol Detection**: Identify insecure protocols (FTP, Telnet, HTTP)
- **SSL/TLS Security**: Certificate validation and encryption assessment
- **Default Credentials**: Check for common default passwords
- **Open Database Detection**: Identify publicly accessible databases
- **Security Headers**: Verify presence of critical HTTP security headers
- **Information Disclosure**: Detect version leakage and banner exposure

### 📊 Compliance Frameworks
Support for major security and compliance frameworks:
- **NIST Cybersecurity Framework (CSF)**
- **ISO 27001** - Information Security Management
- **CIS Controls** - Center for Internet Security
- **PCI DSS** - Payment Card Industry Data Security Standard
- **HIPAA** - Health Insurance Portability and Accountability Act
- **SOC 2** - Service Organization Control 2

### 📈 Reporting & Analytics
- **Executive Summary Reports**: Business-focused summaries for C-level stakeholders
- **Technical Deep-Dive Reports**: Detailed findings for security teams
- **Compliance Reports**: Framework-specific compliance assessments
- **Multiple Export Formats**: HTML, JSON, CSV
- **Mitigation Recommendations**: Step-by-step remediation guidance
- **Risk Dashboards**: Real-time visibility into security posture

### 🔄 Automation
- **Scheduled Scanning**: Automated recurring scans
- **REST API**: Programmatic access to all features
- **CLI Interface**: Command-line tool for automation and integration
- **Audit Logging**: Complete activity tracking

## 🏆 Enterprise-Grade Capabilities

IOC implements industry-leading network operations capabilities:

1. **AI/ML Analytics**: Intelligent anomaly detection, predictive failure analysis, and automated insights
2. **Real-Time Monitoring**: Continuous device and network performance monitoring with configurable polling
3. **SNMP Support**: Full SNMPv1/v2c/v3 protocol support with trap receiver for enterprise infrastructure
4. **Compliance Management**: Multi-framework support
5. **Automated Remediation**: Guided mitigation plans
6. **Executive Reporting**: Business-aligned security metrics

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server (XAMPP, WAMP, or similar)
- Network scanning requires appropriate system permissions

### Step 1: Clone or Download

```bash
# Download to your web server directory
cd C:\xampp\htdocs\
# Or your web root directory

# Files should be in: C:\xampp\htdocs\networkscan\
```

### Step 2: Database Setup

1. Start your MySQL server
2. Import the database schema:

```bash
mysql -u root -p < database/schema.sql
```

Or use phpMyAdmin to import `database/schema.sql`

3. Update database credentials in `config/database.php` if needed:

```php
'host' => 'localhost',
'database' => 'network_security_scanner',
'username' => 'root',
'password' => ''
```

### Step 3: Set Permissions

Ensure the `reports/` directory is writable:

```bash
chmod 755 reports/
```

### Step 4: Access the Application

Open your web browser and navigate to:
```
http://localhost/networkscan/
```

## 💻 Usage

### Web Interface

1. **Dashboard** (`index.php`)
   - View scan statistics
   - Monitor compliance scores
   - Access recent scans

2. **New Scan** (`scan.php`)
   - Configure scan parameters
   - Select scan type
   - Enable compliance checks
   - Generate reports

3. **API Endpoint** (`api.php`)
   - RESTful API for automation
   - JSON responses
   - Multiple actions supported

### CLI Interface

The command-line interface provides powerful automation capabilities:

```bash
# Quick scan of single host
php scan_cli.php --target 192.168.1.1 --type quick

# Full scan of subnet with HTML report
php scan_cli.php --target 192.168.1.0/24 --type full --report html

# Vulnerability scan with compliance checking
php scan_cli.php --target 10.0.0.0/24 --type vulnerability --compliance

# Custom scan with specific name
php scan_cli.php --target 192.168.1.100 --name "DMZ Scan" --type full --report json
```

#### CLI Options

```
Required:
  --target <range>    Target IP, CIDR, or range
                      Examples: 192.168.1.1, 192.168.1.0/24, 192.168.1.1-10

Optional:
  --type <type>       Scan type: quick, full, vulnerability, compliance
  --name <name>       Custom scan name
  --report <format>   Generate report: html, json, csv
  --compliance        Run compliance checks
  --help              Display help message
```

### REST API

#### Get Statistics
```http
GET /networkscan/api.php?action=stats
```

#### List Scans
```http
GET /networkscan/api.php?action=scans
```

#### Start New Scan
```http
POST /networkscan/api.php?action=start_scan
Content-Type: application/x-www-form-urlencoded

target=192.168.1.0/24&type=full&name=API%20Scan
```

#### Get Scan Results
```http
GET /networkscan/api.php?action=scan&id=1
```

#### Generate Report
```http
POST /networkscan/api.php?action=report
Content-Type: application/x-www-form-urlencoded

scan_id=1&format=html&type=full
```

#### Get Vulnerabilities
```http
GET /networkscan/api.php?action=vulnerabilities&scan_id=1&severity=critical
```

#### Export Data
```http
GET /networkscan/api.php?action=export&scan_id=1&format=csv
```

## 📁 Project Structure

```
networkscan/
├── classes/
│   ├── Database.php              # Database connection manager
│   ├── NetworkScanner.php        # Main scanning orchestrator
│   ├── PortScanner.php           # Port scanning engine
│   ├── ServiceDetector.php       # Service identification
│   ├── VulnerabilityScanner.php  # Vulnerability assessment
│   ├── ComplianceChecker.php     # Compliance validation
│   └── ReportGenerator.php       # Report generation
├── config/
│   └── database.php              # Database configuration
├── database/
│   └── schema.sql                # Database schema
├── templates/
│   └── report_template.php       # HTML report template
├── reports/                      # Generated reports (auto-created)
├── index.php                     # Main dashboard
├── scan.php                      # Scan configuration interface
├── scan_cli.php                  # CLI scanner
├── api.php                       # REST API endpoint
└── README.md                     # This file
```

## 🎯 Scan Types

### Quick Scan
- Scans common ports (20-30 ports)
- Fast execution (~5-10 minutes)
- Basic service detection
- Suitable for discovery

### Full Scan
- Scans extended port range (1-10000)
- Comprehensive service detection
- Complete vulnerability assessment
- Risk scoring and prioritization
- Recommended for thorough assessment

### Vulnerability Scan
- Focused on known vulnerabilities
- CVE database matching
- CVSS scoring
- Mitigation recommendations

### Compliance Scan
- Includes all Full Scan features
- Multi-framework compliance checking
- Detailed compliance reporting
- Control-by-control assessment

## 📊 Risk Scoring

The tool uses a comprehensive risk scoring system:

- **Critical (9.0-10.0)**: Immediate threat requiring 24-48 hour remediation
- **High (7.0-8.9)**: Significant risk requiring 1-2 week remediation
- **Medium (4.0-6.9)**: Moderate risk requiring monthly remediation
- **Low (1.0-3.9)**: Minor issues for quarterly review
- **Info (0.1-0.9)**: Informational findings

Risk scores are calculated using:
- CVSS v3 base scores
- Number of affected hosts
- Service criticality
- Exploitability factors

## 🔒 Security Best Practices

### Legal Compliance
⚠️ **IMPORTANT**: Only scan networks you own or have explicit written permission to test. Unauthorized scanning may violate:
- Computer Fraud and Abuse Act (CFAA)
- Computer Misuse Act
- Local cybersecurity laws

### Responsible Usage
- Obtain written authorization before scanning
- Schedule scans during maintenance windows
- Monitor scan impact on production systems
- Secure scan results and reports
- Follow responsible disclosure for findings

### Tool Security
- Change default database credentials
- Restrict web interface access
- Use HTTPS for production deployments
- Implement authentication and authorization
- Regular security updates

## 📖 Database Schema

### Core Tables
- `scans` - Scan sessions and metadata
- `hosts` - Discovered network hosts
- `ports` - Open ports and services
- `vulnerabilities` - CVE database
- `scan_results` - Vulnerability findings
- `mitigation_plans` - Remediation guidance
- `compliance_frameworks` - Security standards
- `compliance_controls` - Individual controls
- `compliance_checks` - Assessment results
- `reports` - Generated reports
- `scheduled_scans` - Automation configuration
- `audit_log` - Activity tracking

## 🛠️ Customization

### Adding Custom Vulnerabilities

```php
INSERT INTO vulnerabilities (cve_id, title, description, severity, cvss_score)
VALUES ('CVE-2024-XXXXX', 'Custom Vulnerability', 'Description', 'high', 8.5);
```

### Adding Mitigation Plans

```php
INSERT INTO mitigation_plans (vulnerability_id, mitigation_title, mitigation_steps)
VALUES (1, 'Fix Title', 'Step 1\nStep 2\nStep 3');
```

### Custom Compliance Controls

Extend `ComplianceChecker.php` to add custom control checks.

## 🐛 Troubleshooting

### Scan Not Starting
- Check PHP execution permissions
- Verify network connectivity
- Ensure firewall allows outbound connections
- Check PHP `allow_url_fopen` setting

### Database Connection Errors
- Verify MySQL service is running
- Check database credentials in `config/database.php`
- Ensure database schema is imported
- Verify user permissions

### Port Scanning Issues
- Some ports require elevated privileges
- Firewall may block scanning
- Timeout settings may need adjustment

### Performance Optimization
- Reduce scan timeout for faster scans
- Use Quick Scan for large networks
- Schedule intensive scans during off-hours
- Index database tables for better performance

## 📝 Changelog

### Version 1.0.0 (2025-01-25)
- Initial release
- Network discovery and port scanning
- Vulnerability assessment with CVE integration
- Multi-framework compliance checking
- Executive and technical reporting
- REST API and CLI interface
- Gartner best practices alignment

## 🤝 Contributing

Contributions are welcome! Please consider:
- Adding new vulnerability checks
- Expanding compliance framework support
- Improving detection accuracy
- Adding new export formats
- Performance optimizations

## 📄 License

This tool is provided for authorized security testing and educational purposes only.

## ⚠️ Disclaimer

This tool is provided "as is" without warranty. Users are responsible for:
- Obtaining proper authorization before scanning
- Compliance with applicable laws and regulations
- Secure handling of scan results
- Responsible disclosure of findings

Unauthorized network scanning is illegal and unethical.

## 📞 Support

For issues, questions, or contributions:
- Review documentation thoroughly
- Check troubleshooting section
- Ensure latest version is installed
- Verify proper configuration

## 🎓 Learning Resources

- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CVE Database](https://cve.mitre.org/)
- [CVSS Specification](https://www.first.org/cvss/)
- [CIS Controls](https://www.cisecurity.org/controls/)

---

**Built with PHP & MySQL** | AI-Powered Network Operations Platform | © 2025
