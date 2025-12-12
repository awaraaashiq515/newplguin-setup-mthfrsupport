# MTHFR Report Generator Plugin - Folder Structure

## Overview

This document outlines the proposed clean folder structure for the reorganized MTHFR Report Generator plugin. All data is read exclusively from the database, with no XLSX file dependencies.

## Folder Structure

```
mthfr-genetic-reports/
├── composer.json
├── composer.lock
├── mthfr-genetic-reports.php          # Main plugin file
├── includes/                          # Core PHP classes
│   ├── class-database.php
│   ├── class-genetic-data.php
│   ├── class-genetic-database.php     # Database-only genetic data handler
│   ├── class-report-utils.php
│   ├── class-woocommerce-integration.php
│   ├── class-zip-processor.php
│   └── class-debug-system.php
├── src/                               # Namespaced classes
│   └── Core/
│       ├── Report/
│       │   └── ReportGenerator.php
│       └── PDF/
│           ├── PdfGenerator.php
│           ├── PdfContentGenerator.php
│           ├── PdfBookmarkGenerator.php
│           ├── PdfUtils.php
│           ├── PdfHtmlHeadGenerator.php
│           ├── PdfHeaderGenerator.php
│           ├── PdfStatsSummaryGenerator.php
│           ├── PdfCategoriesContentGenerator.php
│           ├── PdfFiguresContentGenerator.php
│           └── PdfDisclaimerGenerator.php
├── admin/                             # Admin interface
│   ├── class-admin-handler.php
│   └── templates/
│       ├── admin-dashboard.php
│       ├── debug-page-enhanced.php
│       └── debug-page.php
├── api/                               # REST API endpoints
│   └── class-api-endpoints.php
├── assets/                            # Static assets
│   ├── css/
│   │   ├── admin-style.css
│   │   └── enhanced-admin.css
│   └── js/
│       ├── admin-script.js
│       └── enhanced-admin.js
├── languages/                         # Internationalization files
├── templates/                         # Additional templates (if needed)
└── vendor/                            # Composer dependencies
```

## Key Changes from Old Structure

- **Removed**: `data/` folder (no XLSX files)
- **Removed**: `classes/class-database-importer.php` (XLSX import functionality)
- **Removed**: XLSX import features from main plugin file
- **Renamed**: `class-excel-database.php` → `class-genetic-database.php` (database-only)
- **Reorganized**: Merged `classes/` into `includes/`
- **Added**: `admin/` folder for admin-specific code
- **Added**: `languages/` for future i18n support
- **Moved**: Templates to `admin/templates/`

## Data Flow

All genetic variant data is stored in and read from the WordPress database tables:
- `wp_genetic_variants`
- `wp_variant_categories`
- `wp_variant_tags`
- `wp_pathways`
- `wp_user_uploads`
- `wp_user_reports`

No external data files are used.