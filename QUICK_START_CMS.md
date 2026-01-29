# Quick Start Guide - CMS Portal

## 🚀 5-Minute Setup

### Step 1: Import CMS Tables (1 minute)

Open your command prompt/terminal and run:

```bash
cd C:\xampp\htdocs\networkscan

# If MySQL is in PATH:
mysql -u root -p network_security_scanner < database\cms_tables.sql

# Otherwise, use full path:
C:\xampp\mysql\bin\mysql.exe -u root -p network_security_scanner < database\cms_tables.sql
```

Press Enter when prompted for password (default XAMPP has no password).

**OR** use phpMyAdmin:
1. Go to http://localhost/phpmyadmin
2. Select `network_security_scanner` database
3. Click "Import" tab
4. Choose `database/cms_tables.sql`
5. Click "Go"

### Step 2: Access Admin Portal (1 minute)

Open your browser and go to:
```
http://localhost/networkscan/admin/login.php
```

Login with:
- **Username**: `admin`
- **Password**: `admin123`

### Step 3: Upload Your Logo (2 minutes)

1. Click "Choose Logo" button
2. Select your company logo (PNG, JPG, GIF, or SVG)
3. Click "Upload Logo"
4. Done! Visit the main dashboard to see your logo

### Step 4: Customize Settings (1 minute)

Scroll down to "Application Settings":
- Change **Application Name** to your company/project name
- Add **Company Name**
- Set **Support Email**
- Pick your **Theme Color**
- Click "Save Settings"

## ✅ That's It!

Your CMS portal is now configured. Here's what you can do:

### Manage Tasks
Go to **Task Manager** to:
- Track vulnerability remediation tasks
- Organize security assessment work
- Monitor progress with 15 pre-loaded SolarWinds benchmark tasks

### Review Recommendations
Go to **SolarWinds Benchmark** to:
- See detailed feature comparison
- Review 12-month implementation roadmap
- Understand cost savings vs. SolarWinds ($8,000-15,000 over 3 years)
- Get strategic recommendations

### View Your Branded Dashboard
Click "View Dashboard" in the sidebar to see:
- Your custom logo in the header
- Your application name
- All scanning features with your branding

## 📋 Pre-Loaded Features

The CMS comes with 15 pre-loaded tasks from the SolarWinds benchmark analysis:

1. ✅ Implement Real-Time Network Monitoring (HIGH)
2. ✅ Network Performance Baselines (HIGH)
3. ✅ Add Network Device Discovery (HIGH)
4. ✅ SNMP Monitoring Integration (CRITICAL)
5. ✅ Network Topology Visualization (MEDIUM)
6. ✅ NetFlow/sFlow Analysis (HIGH)
7. ✅ Alerting and Notification System (CRITICAL)
8. ✅ Configuration Management (HIGH)
9. ✅ VoIP Monitoring (MEDIUM)
10. ✅ Log Management Integration (MEDIUM)
11. ✅ Custom Dashboard Widgets (MEDIUM)
12. ✅ Mobile App Development (LOW)
13. ✅ API Rate Limiting (MEDIUM)
14. ✅ Multi-Tenant Support (LOW)
15. ✅ Automated Remediation (HIGH)

## 🎯 Next Steps

1. **Change Default Password** (IMPORTANT!)
   ```sql
   -- Generate hash for your new password in PHP:
   -- <?php echo password_hash('your_new_password', PASSWORD_DEFAULT); ?>

   -- Then update in MySQL:
   UPDATE admin_users
   SET password_hash = 'your_generated_hash'
   WHERE username = 'admin';
   ```

2. **Start Managing Tasks**
   - Review the 15 pre-loaded tasks
   - Prioritize based on your needs
   - Assign due dates
   - Track progress

3. **Customize Branding**
   - Upload your logo
   - Set your theme color
   - Update application name

4. **Run Your First Scan**
   - Go to main dashboard
   - Click "New Scan"
   - Scan your network
   - Generate reports with your branding

## 📚 Documentation

- **Full CMS Guide**: See `CMS_README.md`
- **SolarWinds Benchmark**: See `SOLARWINDS_BENCHMARK.md`
- **Main Application**: See `README.md`
- **Installation**: See `INSTALL.md`

## 🆘 Quick Troubleshooting

**Can't login?**
- Default username: `admin`, password: `admin123`
- Clear browser cache/cookies
- Check if `admin_users` table exists

**Logo not showing?**
- Check upload succeeded (green success message)
- Verify file in `assets/uploads/` folder
- Clear browser cache

**Tasks not loading?**
- Verify `tasks` table exists in database
- Check if CMS tables were imported correctly
- Run: `SELECT COUNT(*) FROM tasks;` should return 15+

**Settings not applying?**
- Clear browser cache
- Check database: `SELECT * FROM settings;`
- Restart web server

## 💡 Pro Tips

1. **Bookmark Admin Portal**: Save `/admin/` URL for quick access
2. **Regular Reviews**: Check tasks weekly to track progress
3. **Use Categories**: Organize tasks by type (Security, Feature, etc.)
4. **Set Due Dates**: Keep your team accountable
5. **Read the Benchmark**: Understand how we compare to SolarWinds

## 🎉 You're Ready!

The CMS portal is now fully configured. You have:
- ✅ Branded dashboard with logo support
- ✅ Task management system
- ✅ 15 SolarWinds benchmark tasks pre-loaded
- ✅ Feature comparison and roadmap
- ✅ Cost-benefit analysis
- ✅ Strategic recommendations

Start managing your network security assessments with style! 🛡️

---

**Need Help?** Check the full documentation in `CMS_README.md`
