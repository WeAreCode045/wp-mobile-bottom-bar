# Mobile Bottom Bar Plugin - Code Structure

## Overview
The plugin has been refactored into a clean, modular WordPress plugin architecture following best practices.

## File Structure

```
wp-mobile-bottom-bar/
├── wp-mobile-bottom-bar.php          # Bootstrap file (31 lines)
├── includes/                          # Core plugin classes
│   ├── class-plugin.php              # Main coordinator (76 lines)
│   ├── class-settings.php            # Settings & sanitization (613 lines)
│   ├── class-admin.php               # Admin UI & REST API (269 lines)
│   ├── class-frontend.php            # Frontend rendering (595 lines)
│   ├── class-lighthouse.php          # Lighthouse integration (413 lines)
│   └── class-ajax.php                # AJAX handlers (180 lines)
├── assets/
│   ├── css/
│   │   └── frontend.css              # Frontend styles
│   ├── js/
│   │   ├── admin.js                  # Admin React app (built by Vite)
│   │   └── frontend.js               # Frontend interactions
│   └── vendor/
│       └── easepick/                 # Date picker library
└── templates/
    ├── contact-form-modal.php        # Contact form template
    └── multi-hotel-modal.php         # Hotel selection template
```

## Class Responsibilities

### MBB_Settings (`class-settings.php`)
- Get and save all plugin settings
- Sanitize and validate user input
- Manage default values
- Handle bars, styles, custom items, and form settings

### MBB_Admin (`class-admin.php`)
- Register admin menu page
- Enqueue admin assets (React app)
- Handle REST API endpoints
- Provide data to React frontend

### MBB_Frontend (`class-frontend.php`)
- Render mobile bottom bar HTML
- Enqueue frontend assets
- Handle bar selection and page targeting
- Resolve menu items and render layouts
- Apply CSS custom properties

### MBB_Lighthouse (`class-lighthouse.php`)
- Integrate with MyLighthouse booking system
- Fetch hotel data
- Render booking forms
- Handle single and multi-hotel modes

### MBB_Ajax (`class-ajax.php`)
- Handle contact form submissions
- Process direction requests (Google Maps)
- Send emails via SMTP or wp_mail

### Mobile_Bottom_Bar_Plugin (`class-plugin.php`)
- Initialize all components
- Coordinate dependencies
- Provide access to sub-components

## Key Improvements

1. **Separation of Concerns**: Each class has a single, clear responsibility
2. **Reduced File Size**: Main file reduced from 2007 to 31 lines
3. **Better Maintainability**: Easy to locate and modify specific functionality
4. **Dependency Injection**: Components are injected, making testing easier
5. **WordPress Standards**: Follows WordPress plugin development best practices

## Dependencies

- **Settings** → Used by all other classes
- **Lighthouse** → Used by Admin and Frontend
- **Admin** → Independent
- **Frontend** → Independent
- **Ajax** → Independent

## Development Workflow

1. **Settings changes**: Edit `class-settings.php`
2. **Admin UI changes**: Edit `class-admin.php` and React source
3. **Frontend rendering**: Edit `class-frontend.php`
4. **Booking integration**: Edit `class-lighthouse.php`
5. **AJAX handlers**: Edit `class-ajax.php`

## Build Process

```bash
npm run build
```

This compiles the React admin interface to `assets/js/admin.js`

## Git Ignore

The following are excluded from version control:
- `/src` (React source - kept in root for development)
- `node_modules/`
- `build/`
- `assets/js/.vite/`
- `FEATURE_IMPLEMENTATION.md`
- Development artifacts

Only the production-ready plugin folder is tracked.
