# Custom Branding

IPplan supports custom branding, allowing you to personalize the interface with your organization's logo and images without modifying core files.

## Theme Override System

The theme override system allows you to replace default images with custom versions. This is useful for:

- Adding your organization's logo to the sidebar
- Customizing icons or other visual elements
- Maintaining customizations across updates

### How It Works

IPplan checks for images in the following order:

1. **theme-override/images/** - Your custom images (checked first)
2. **images/** - Default IPplan images (fallback)

If a file exists in `theme-override/images/`, it will be used instead of the default from `images/`.

### Directory Structure

```
ipplan/
├── theme-override/
│   └── images/
│       └── IPPlan_255.png    ← Your custom logo
├── images/
│   └── IPPlan_255.png        ← Default logo (ignored if override exists)
└── ...
```

## Adding a Custom Logo

### Step 1: Prepare Your Logo

Create a logo image with the following specifications:

- **Format:** PNG (recommended), SVG, or JPG
- **Size:** 160-200px wide for best sidebar fit
- **Name:** Use one of these filenames (checked in order):
  - `SystemLogo_621x146.png` (default)
  - `IPPlan_255.png`
  - `IPPlan_256.png`
  - `logo.png`
  - `ipplan-logo.png`
  - `logo.svg`

### Step 2: Add Your Logo

Place your logo in the theme override directory:

```bash
# Create the directory if it doesn't exist
mkdir -p /var/www/html/ipplan/theme-override/images

# Copy your logo
cp /path/to/your-logo.png /var/www/html/ipplan/theme-override/images/IPPlan_255.png

# Set permissions
chown www-data:www-data /var/www/html/ipplan/theme-override/images/IPPlan_255.png
```

### Step 3: Verify

Refresh your browser (Ctrl+F5) to see the new logo in the sidebar.

## Benefits of Theme Override

### Survives Updates

When you update IPplan to a new version, your custom branding remains intact because:

- The `theme-override/` directory is separate from core files
- Updates replace files in `images/` but not `theme-override/images/`
- Your customizations don't conflict with the update process

> **💡 Important:** When updating IPplan, make sure to preserve your `theme-override/` directory. If you are replacing files by deleting the old installation first, backup your `theme-override/` directory before updating and restore it afterward. See the [Upgrading Guide](upgrading.md) for detailed update instructions.

### Easy Rollback

To revert to the default logo, simply remove your override file:

```bash
rm /var/www/html/ipplan/theme-override/images/IPPlan_255.png
```

### No Core File Modifications

You never need to modify IPplan's core files, which:

- Makes updates safer and simpler
- Avoids merge conflicts
- Keeps your installation clean

## Theme Selection

IPplan Current Branch includes multiple built-in themes:

| Theme | Description |
|-------|-------------|
| **Current Branch - Dark** | Modern dark interface with cyan accents |
| **Current Branch - Light** | Modern light interface with cyan accents |
| **Classic** | Original IPplan appearance |
| **Red Grey** | Alternative classic color scheme |
| **Pastel** | Soft pastel colors |
| **Penguin** | Linux-themed classic appearance |

Users can select their preferred theme in **Settings → Display Settings**.

## Future Customization Options

The theme override system may be expanded in future versions to support:

- Custom CSS overrides
- Additional image replacements
- Theme color customization

## Troubleshooting

### Logo Not Appearing

1. **Check the filename** - Must match one of the expected names exactly
2. **Check permissions** - File must be readable by the web server
3. **Clear browser cache** - Hard refresh with Ctrl+F5
4. **Check the path** - File must be in `theme-override/images/`, not `theme-override/`

### Logo Appears Distorted

- Keep logo width under 200px
- Use PNG format for best quality
- Avoid very tall logos (sidebar has limited height)

### Override Not Working

Verify the file exists and has correct permissions:

```bash
ls -la /var/www/html/ipplan/theme-override/images/
```

Expected output should show your logo file with appropriate permissions.
