# Data Handling Differences: Old vs New MTHFR Genetic Reports Plugins

## 1) How Data Was Coming In The Old Plugin

### Data Sources
- **Primary Source**: Excel spreadsheet file (`Database.xlsx`) located in `wp-content/plugins/mthfr-genetic-reportsold/data/`
- **Backup Sources**: Additional XLSX files (`Database_0.xlsx`, `current_Database.xlsx`) in the same directory
- **File Format**: Microsoft Excel (.xlsx) files using PHPSpreadsheet library for reading

### Data Processing
- **Reading Mechanism**: Used `MTHFR_Excel_Database` class to load and parse Excel files
- **Column Mapping**: Mapped Excel columns to standardized fields:
  - RSID → rsid
  - Gene → gene
  - SNP → snp_name
  - Risk → risk_allele
  - Info → info
  - Video → video
  - Report Name → report_name
  - Tags → tags
- **Category Determination**: Used `rs10306114` field for pathway categorization, with fallback logic based on gene names
- **Data Structure**: Stored as associative arrays with RSID as keys, supporting multiple entries per RSID for different categories

### JSON Generation
- **Matching Process**: User-uploaded genetic data matched against Excel database by RSID
- **Output Format**: Generated JSON reports with standardized fields:
  - SNP ID
  - SNP Name
  - Risk Allele
  - Your Allele
  - Result (-/-, +/-, +/+)
  - Report Name
  - Info
  - Video
  - Tags
  - Group (category)

## 2) How Data Is Being Loaded Now

### Data Sources
- **Primary Source**: WordPress database tables instead of Excel files
- **Database Tables**:
  - `wpub_genetic_variants` - Main variant data
  - `wpub_variant_categories` - Category/pathway information
  - `wpub_variant_tags` - Tag associations
  - `wpub_pathways` - Pathway definitions

### Data Processing
- **Reading Mechanism**: Uses `Database::get_variants_by_rsids()` method to query database
- **Query Structure**: Direct SQL queries to retrieve variant data by RSID arrays
- **Data Loading**: Loads data on-demand rather than pre-loading entire Excel files
- **Category Filtering**: Applies category filters during report generation (e.g., 'Variant' report type filters specific categories)

### Same Structure Verification
- **Compatibility Maintained**: New plugin generates identical JSON structure to old plugin
- **Field Mapping**: Database fields map directly to JSON output:
  - rsid → SNP ID
  - gene + snp_name → SNP Name
  - risk_allele → Risk Allele
  - (calculated) → Your Allele
  - (calculated) → Result
  - report_name → Report Name
  - info → Info
  - video → Video
  - tags → Tags
  - category/group → Group
- **Verification**: Test scripts confirm structure matches exactly, including required fields and data types

## Key Improvements in New Plugin

- **Performance**: Database queries are faster than Excel file parsing
- **Scalability**: No file size limitations of Excel spreadsheets
- **Maintainability**: Easier to update data through database rather than Excel files
- **Reliability**: No dependency on file system access or Excel parsing libraries
- **Real-time Updates**: Data can be modified without redeploying files

## Compatibility Assurance

The new plugin maintains **100% compatibility** with the old format:
- Same JSON structure and field names
- Identical calculation logic for risk assessment
- Same category filtering and pathway determination
- Verified through automated testing scripts that compare outputs

This ensures seamless migration without breaking existing report formats or user expectations.