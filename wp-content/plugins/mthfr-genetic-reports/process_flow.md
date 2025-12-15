# MTHFR Genetic Reports Plugin - Process Flow Documentation

## Overview

The MTHFR Genetic Reports plugin is a WordPress plugin that generates personalized genetic analysis reports based on user-uploaded genetic data. The plugin processes genetic variant data from XLSX files, matches it against a comprehensive genetic database, and generates detailed PDF reports with interpretations.

## Key Components

### Core Classes
- **MTHFR_Genetic_Reports**: Main plugin class handling initialization and admin interface
- **MTHFR\Core\Report\ReportGenerator**: Core report generation logic
- **MTHFR\Core\Database\Database**: Database operations and data management
- **MTHFR\Core\Database\ExcelDatabase**: Genetic database initialization and matching
- **MTHFR_Async_Report_Generator**: Background job processing for report generation
- **XLSX_Chunk_Importer**: Large file import processing
- **MTHFR_PDF_Generator**: PDF report creation
- **MTHFR_API_Endpoints**: REST API endpoints for external integration

### Database Tables
- `wp_user_uploads`: Stores uploaded genetic data files
- `wp_user_reports`: Stores generated report metadata and file paths
- `wp_genetic_variants`: Comprehensive genetic variants database
- `wp_import_data`: Temporary storage for XLSX import data
- `wp_mthfr_async_jobs`: Async job tracking

## Process Flow

### Phase 1: Database Setup and Initialization

1. **Plugin Activation**
   - Creates required database tables
   - Runs database migrations for existing installations
   - Initializes genetic variants database from XLSX files if empty

2. **Database Initialization**
   - `ExcelDatabase::initialize_databases()` checks for genetic data
   - If no data exists, imports legacy XLSX files using `Database_Importer`
   - Loads genetic variants into memory/database for matching

### Phase 2: Genetic Data Import (Admin Process)

#### Option A: Standard XLSX Import
1. **Admin Upload**
   - Admin accesses `/wp-admin/admin.php?page=mthfr-data-import`
   - Uploads XLSX file containing genetic variant data
   - File validation (type, size, format)

2. **File Processing**
   - `MTHFR_Database_Importer::import_from_xlsx()` processes the file
   - Reads XLSX using PHPSpreadsheet library
   - Extracts genetic variants (RSID, genotype, gene information)
   - Inserts data into `wp_genetic_variants` table

#### Option B: Chunked Import (Large Files)
1. **Chunked Upload**
   - For files >1000 rows, uses `XLSX_Chunk_Importer`
   - File uploaded to temporary directory
   - Import session created with transient storage

2. **Batch Processing**
   - File processed in chunks of 400 rows
   - Each chunk inserted into `wp_import_data` table
   - Progress tracked via AJAX calls
   - Prevents timeout on large files

3. **Data Integration**
   - Chunked data processed into `wp_genetic_variants` table
   - Categories and tags assigned to variants
   - Database indexes optimized for performance

### Phase 3: Report Generation (User Process)

#### Step 1: User Upload
1. **File Upload**
   - User uploads genetic data file (TXT/ZIP format)
   - File stored in WordPress uploads directory
   - Record created in `wp_user_uploads` table

2. **File Processing**
   - `MTHFR_ZIP_Processor` handles ZIP files (multiple genetic files)
   - `ReportGenerator::process_text_file()` processes individual files
   - Extracts RSID, genotype, and genetic information
   - Validates genetic data format

#### Step 2: Report Generation Trigger
1. **API Call**
   - External system calls `POST /wp-json/mthfr/v1/generate-report`
   - Parameters: `upload_id`, `order_id`, `product_name`, `has_subscription`

2. **Async Job Queue**
   - `MTHFR_Async_Report_Generator::schedule_report_generation()` queues job
   - Uses Action Scheduler for background processing
   - Prevents checkout/API timeouts

#### Step 3: Background Report Generation
1. **Job Processing**
   - `MTHFR_Async_Report_Generator::process_report_generation()` executes
   - Calls `ReportGenerator::generate_report()` with parameters

2. **Report Type Determination**
   - Based on `product_name`: Covid, Methylation, Excipient, Detox, Immune, Variant, Bundled
   - Determines category filters for report content

3. **Genetic Data Matching**
   - `ExcelDatabase::get_database()` loads genetic variants database
   - `Database::get_variants_by_rsids()` performs lazy loading
   - Matches user RSIDs against database using optimized queries

4. **Data Processing**
   - `ReportGenerator::create_json_report()` processes matched variants
   - Applies category filtering based on report type
   - Formats genotypes and calculates risk results (+/-, +/-, +/+)
   - Creates structured JSON report data

5. **PDF Generation**
   - `ReportGenerator::generate_pdf_with_validation()` calls PDF generator
   - `MTHFR_PDF_Generator::generate_pdf()` creates PDF content
   - Includes charts, interpretations, and formatted genetic data
   - Handles subscription-based content restrictions

#### Step 4: File Storage and Database Update
1. **File Saving**
   - JSON report saved to `/wp-content/uploads/user_reports/upload_{upload_id}/`
   - PDF report saved to same directory
   - Files named with timestamp and order information

2. **Database Update**
   - `Database::save_report()` updates `wp_user_reports` table
   - Stores file paths, URLs, report metadata
   - Links to WooCommerce order and upload records

3. **Completion Notification**
   - Email sent to customer when report is ready
   - Status updated in async jobs table
   - API returns success/failure status

### Phase 4: Report Access and Delivery

1. **Report Retrieval**
   - Customer accesses reports via account dashboard
   - Files served from WordPress uploads directory
   - Download links generated from stored URLs

2. **Status Checking**
   - `GET /wp-json/mthfr/v1/report-status/{order_id}` for status updates
   - Returns current processing status and completion information

## Data Flow Architecture

### Input Processing
```
User File Upload → File Validation → Format Detection → Data Extraction → Database Storage
```

### Report Generation Pipeline
```
API Request → Async Queue → Background Processing → Data Matching → JSON Creation → PDF Generation → File Storage → Database Update → Notification
```

### Database Relationships
```
wp_user_uploads (1) ←→ (many) wp_user_reports
wp_user_reports (many) ←→ (1) wp_genetic_variants (matched data)
wp_mthfr_async_jobs (1) ←→ (1) wp_user_reports
```

## Key Features

### Performance Optimizations
- **Lazy Loading**: Database queries load only relevant RSIDs
- **Chunked Processing**: Large files processed in batches
- **Caching**: Genetic data cached with WordPress object cache
- **Async Processing**: Report generation doesn't block user interface

### Error Handling
- **Retry Logic**: Failed reports automatically retried up to 3 times
- **Fallback Database**: Minimal database available if main database fails
- **Validation**: Comprehensive input validation at each step
- **Logging**: Detailed error logging for debugging

### Security Features
- **File Type Validation**: Only allowed file types accepted
- **Nonce Verification**: CSRF protection on admin operations
- **Permission Checks**: User capability validation
- **Data Sanitization**: All inputs sanitized before processing

### Integration Points
- **WooCommerce**: Order management and customer data
- **WordPress REST API**: External system integration
- **Action Scheduler**: Background job processing
- **PHPSpreadsheet**: Excel file processing

## File Structure

```
wp-content/plugins/mthfr-genetic-reports/
├── mthfr-genetic-reports.php          # Main plugin file
├── api/
│   └── class-api-endpoints.php        # REST API endpoints
├── includes/
│   ├── class-async-report-generator.php    # Background processing
│   ├── class-xlsx-chunk-importer.php      # Large file imports
│   ├── class-admin-handler.php            # Admin interface
│   └── class-woocommerce-integration.php  # WooCommerce hooks
├── src/Core/
│   ├── Database/
│   │   ├── Database.php                  # Database operations
│   │   └── ExcelDatabase.php             # Genetic data management
│   ├── Report/
│   │   └── ReportGenerator.php           # Report generation logic
│   └── PDF/                              # PDF generation classes
├── templates/                            # Admin templates
└── assets/                               # CSS/JS assets
```

## Monitoring and Maintenance

### Admin Interface
- **Status Dashboard**: Component health and database statistics
- **Async Jobs Monitor**: Track background job progress
- **Data Import Tools**: Manual import and re-import capabilities
- **API Testing**: Endpoint validation and testing tools

### Performance Monitoring
- **Cache Statistics**: Hit/miss ratios for database queries
- **Import Progress**: Real-time progress for large file imports
- **Error Logs**: Comprehensive error tracking and reporting

This documentation provides a comprehensive overview of the MTHFR Genetic Reports plugin's process flow, from initial data import through final report generation and delivery.