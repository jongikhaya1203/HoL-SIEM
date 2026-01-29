# 🎨 Logo & Branding Settings - User Guide

## Quick Access

Navigate to: **http://localhost/networkscan/admin/logo_settings.php**

Or click the orange **🎨 Branding** button on the main dashboard navigation.

## Features

### 1. Application Name
- Change the name displayed in the header
- Default: "IOC Intelligent Operating Centre"
- Updates across all pages instantly

### 2. Company Logo Upload
- **Supported formats**: JPG, PNG, GIF, SVG
- **Maximum size**: 5MB
- **Recommended dimensions**: 200px width × 70px height
- **Best for**: Transparent PNG logos

### 3. Theme Color
- Customize the primary color scheme
- Use color picker or enter hex code (e.g., #667eea)
- Changes navigation buttons and accent colors

### 4. Live Preview
- See your changes before applying them
- Preview shows exactly how logo and branding appear on dashboard

## How to Upload a Logo

### Method 1: Click to Upload
1. Go to **Logo & Branding Settings** page
2. Click the **upload area** (with 📤 icon)
3. Select your logo file
4. Logo automatically uploads and displays

### Method 2: Drag and Drop
1. Open the **Logo & Branding Settings** page
2. Drag your logo file into the upload area
3. Drop to upload instantly

## Managing Your Logo

### View Current Logo
- Current logo displays at the top of the settings page
- Also visible in the preview section

### Remove Logo
- Click the **"Remove Logo"** button below the current logo
- Confirmation dialog will appear
- Logo file is deleted from server

### Replace Logo
- Simply upload a new logo
- Old logo is automatically deleted
- New logo takes effect immediately

## Technical Details

### Storage Location
- Logos are stored in: `uploads/` folder
- Filename format: `logo_[timestamp].[extension]`
- Example: `logo_1699123456.png`

### Database Storage
- Settings are stored in the `site_settings` table
- Table auto-creates on first use
- Includes: logo_url, app_name, theme_color

### Security Features
- File type validation (images only)
- Size limit enforcement (5MB max)
- Automatic old file cleanup
- Sanitized database inputs

## Logo Design Tips

### Best Practices
✅ Use transparent PNG for best results
✅ Keep width around 200px, height around 70px
✅ Use high contrast colors
✅ Test on both light and dark backgrounds
✅ Optimize file size before uploading

### Avoid
❌ Very large file sizes (>1MB)
❌ Low resolution logos (will appear pixelated)
❌ Logos with white backgrounds (use transparent)
❌ Extremely wide or tall dimensions

## Example Dimensions

| Logo Type | Recommended Size | Format |
|-----------|-----------------|--------|
| Horizontal Logo | 200px × 70px | PNG (transparent) |
| Square Logo | 70px × 70px | PNG (transparent) |
| Icon Only | 50px × 50px | SVG or PNG |

## Troubleshooting

### Logo Not Appearing
1. Check file size (must be under 5MB)
2. Verify file format (JPG, PNG, GIF, SVG only)
3. Clear browser cache (Ctrl+F5)
4. Check uploads/ folder permissions

### Upload Fails
1. Ensure `uploads/` folder exists
2. Check folder permissions (755 or 777)
3. Verify file isn't corrupted
4. Try different browser

### Logo Too Big/Small
1. Edit dimensions before uploading
2. Use image editing software to resize
3. Recommended: 200×70px for horizontal logos

## Quick Test

1. Go to http://localhost/networkscan/admin/logo_settings.php
2. Upload any small image file
3. Return to main dashboard (http://localhost/networkscan/index.php)
4. Logo should appear in top-left header

## Color Scheme Examples

Try these popular color schemes:

- **Purple** (Default): `#667eea`
- **Blue**: `#2196F3`
- **Green**: `#4CAF50`
- **Orange**: `#FF9800`
- **Red**: `#f44336`
- **Dark**: `#263238`
- **Teal**: `#009688`

## Advanced Customization

### Direct Database Edit
If needed, you can manually edit settings:

```sql
-- Update app name
UPDATE site_settings SET setting_value = 'Your Company Name'
WHERE setting_key = 'app_name';

-- Update theme color
UPDATE site_settings SET setting_value = '#FF5722'
WHERE setting_key = 'theme_color';

-- Update logo path
UPDATE site_settings SET setting_value = 'uploads/logo.png'
WHERE setting_key = 'logo_url';
```

### Manual File Upload
1. Place logo in: `C:\xampp\htdocs\networkscan\uploads\`
2. Name it: `logo.png` (or any name)
3. Update database:
```sql
UPDATE site_settings SET setting_value = 'uploads/logo.png'
WHERE setting_key = 'logo_url';
```

## Support

If you encounter issues:
1. Check PHP error logs: `C:\xampp\php\logs\php_error_log`
2. Verify MySQL is running in XAMPP
3. Check file/folder permissions
4. Test with different image file

---

**Created**: 2025
**Last Updated**: 2025-11-03
**Version**: 1.0
