# IOC Intelligent Operating Centre - Installer Build Instructions

## Prerequisites

1. **Inno Setup 6.x** - Download from https://jrsoftware.org/isinfo.php
2. **Application icon** (optional) - Place at `assets/icon.ico`

## Quick Build (Application Only)

1. Install Inno Setup
2. Open `installer/networkscanscada_setup.iss` in Inno Setup
3. Press F9 or click "Compile" to build
4. Find the installer at `installer/output/IOC_Setup_1.0.0.exe`

## Full Build (With XAMPP Portable)

For a self-contained installer that includes XAMPP:

1. Download XAMPP Portable from https://www.apachefriends.org/download.html
2. Extract XAMPP to `installer/xampp/` folder
3. Open the .iss file in Inno Setup
4. Compile the installer

**Note**: Including XAMPP will increase installer size to ~150-200 MB.

## Directory Structure

```
installer/
├── networkscanscada_setup.iss   # Main installer script
├── BUILD_INSTRUCTIONS.md         # This file
├── output/                       # Compiled installer output
├── scripts/                      # Helper batch scripts
│   ├── configure.bat
│   ├── setup_database.bat
│   ├── start_services.bat
│   ├── stop_services.bat
│   └── open_admin.bat
└── xampp/                        # (Optional) XAMPP portable files
```

## Customization

### Change Application Name/Version

Edit these lines in `networkscanscada_setup.iss`:

```
#define MyAppName "IOC Intelligent Operating Centre"
#define MyAppVersion "1.0.0"
#define MyAppPublisher "Your Company Name"
#define MyAppURL "https://yourcompany.com"
```

### Add Custom Icon

1. Create a 256x256 .ico file
2. Save it as `assets/icon.ico`
3. The installer will automatically use it

### Change Default Database Settings

Edit the `InitializeWizard` procedure in the .iss file to change defaults:

```pascal
DBPage.Values[0] := 'localhost';      // Host
DBPage.Values[1] := '3306';           // Port
DBPage.Values[2] := 'networkscan';    // Database name
DBPage.Values[3] := 'root';           // Username
DBPage.Values[4] := '';               // Password
```

## Installation Types

The installer supports three installation types:

1. **Full Installation** - Includes XAMPP portable (requires xampp folder)
2. **Application Only** - Requires existing XAMPP installation
3. **Custom** - User selects components

## Post-Installation

After installation, users can:

1. Use the Start Menu shortcut to launch IOC
2. Access the admin panel at http://localhost/networkscanscada/admin/
3. Default credentials: admin / admin123

## Troubleshooting

### "XAMPP not found" during build
- Ensure XAMPP files are in `installer/xampp/` folder
- Or change installation type to "Application Only"

### Database connection fails after install
- Ensure MySQL is running
- Check credentials in `config/database.php`
- Run the database setup script manually

### Icon not showing
- Ensure `assets/icon.ico` exists
- Icon must be valid Windows .ico format (256x256 recommended)

## Command-Line Installation

The generated installer supports silent installation:

```batch
IOC_Setup_1.0.0.exe /SILENT /DIR="C:\Program Files\IOC"
```

Additional parameters:
- `/SILENT` - Silent install with progress
- `/VERYSILENT` - No UI at all
- `/DIR="path"` - Custom installation directory
- `/NOICONS` - Don't create Start Menu shortcuts
- `/TASKS="desktopicon"` - Create desktop shortcut

## Building from Command Line

```batch
"C:\Program Files (x86)\Inno Setup 6\ISCC.exe" networkscanscada_setup.iss
```
