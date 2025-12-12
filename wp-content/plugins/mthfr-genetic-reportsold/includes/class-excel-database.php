<?php
/**
 * MTHFR Excel Database Handler - Enhanced Production Version
 * Reads and processes genetic data from Excel database files
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include PHPSpreadsheet if available
if (file_exists(MTHFR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once MTHFR_PLUGIN_PATH . 'vendor/autoload.php';
}

class MTHFR_Excel_Database {
    
    private static $database_cache = null;
    private static $meth_database_cache = null;
    
    /**
     * Initialize databases method
     */
    public static function initialize_databases() {
        error_log('MTHFR: Initializing Excel databases...');
        
        $data_dir = MTHFR_PLUGIN_PATH . 'data';
        
        if (!file_exists($data_dir)) {
            wp_mkdir_p($data_dir);
        }
        
        // Initialize main database
        self::get_database();
        
        // Initialize methylation database
        self::get_meth_database();
        
        error_log('MTHFR: Excel databases initialization complete');
    }
    
    /**
     * Get main database data with comprehensive error handling
     */
    public static function get_database() {
        if (self::$database_cache !== null) {
            return self::$database_cache;
        }

        error_log('MTHFR: Loading database files...');

        $possible_paths = array(
            MTHFR_PLUGIN_PATH . 'data/Database.xlsx',
        );

        $combined_database = array();
        $total_loaded = 0;
        $files_found = 0;

        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $files_found++;
                error_log("MTHFR: Found database file at: {$path}");

                try {
                    $data = (pathinfo($path, PATHINFO_EXTENSION) === 'csv') 
                        ? self::read_csv_file($path) 
                        : self::read_excel_file($path);

                    if (!empty($data) && is_array($data)) {
                        error_log("MTHFR: Successfully loaded " . count($data) . " variants from {$path}");
                        
                        foreach ($data as $rsid => $variant) {
                            if (!isset($combined_database[$rsid])) {
                                $combined_database[$rsid] = $variant;
                                $total_loaded++;
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("MTHFR: Error processing file {$path}: " . $e->getMessage());
                }
            }
        }

        if ($files_found === 0) {
            error_log('MTHFR: No database files found in expected locations');
            $combined_database = self::create_fallback_database();
        }

        if (empty($combined_database)) {
            error_log('MTHFR: Using fallback database');
            $combined_database = self::create_fallback_database();
        } else {
            error_log("MTHFR: Total variants loaded: {$total_loaded}");
        }

        self::$database_cache = $combined_database;
        return self::$database_cache;
    }
    
    /**
     * Read Excel file with multi-category support
     */
    private static function read_excel_file($file_path) {
        try {
            error_log("MTHFR: Reading Excel file: {$file_path}");
            
            if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                error_log('MTHFR: PHPSpreadsheet not available');
                return self::create_fallback_database();
            }
            
            if (!is_readable($file_path)) {
                throw new Exception("Excel file is not readable: {$file_path}");
            }
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $database = array();
            $headers = array();
            
            // Get headers from first row
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
            
            for ($col = 1; $col <= $highestCol; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getCalculatedValue();
                if (!empty($cellValue)) {
                    $header = trim($cellValue);
                    $headers[$col] = $header;
                }
            }

            if (empty($headers)) {
                error_log('MTHFR: No headers found in Excel file');
                return self::create_fallback_database();
            }
            
            // Find RSID column
            $rsid_column = null;
            foreach ($headers as $col_num => $header) {
                if (in_array(strtolower(trim($header)), array('rsid', 'rs_id', 'snp_id', 'snp id'))) {
                    $rsid_column = $col_num;
                    break;
                }
            }
            
            if ($rsid_column === null) {
                error_log('MTHFR: No RSID column found in headers');
                return self::create_fallback_database();
            }
            
            // Read data rows
            $row = 2;
            $processed_count = 0;
            $skipped_count = 0;
            
            while ($row <= $worksheet->getHighestRow()) {
                $rsid_value = $worksheet->getCellByColumnAndRow($rsid_column, $row)->getCalculatedValue();
                
                if (empty($rsid_value) || strpos($rsid_value, 'rs') !== 0) {
                    $skipped_count++;
                    $row++;
                    continue;
                }
                
                $row_data = array();
                
                foreach ($headers as $col_num => $header) {
                    $value = $worksheet->getCellByColumnAndRow($col_num, $row)->getCalculatedValue();
                    $row_data[trim($header)] = $value;
                }
                
                $variant = self::map_row_to_variant_structure($row_data);
                
                if ($variant && !empty($variant['RSID'])) {
                    if (!isset($database[$variant['RSID']])) {
                        $database[$variant['RSID']] = array();
                    }
                    
                    // Check for duplicate categories
                    $category_exists = false;
                    foreach ($database[$variant['RSID']] as $existing_variant) {
                        if ($existing_variant['rs10306114'] === $variant['rs10306114']) {
                            $category_exists = true;
                            break;
                        }
                    }
                    
                    if (!$category_exists) {
                        $database[$variant['RSID']][] = $variant;
                        $processed_count++;
                    }
                }
                
                $row++;
                
                // Prevent memory issues
                if ($processed_count >= 10000) {
                    error_log("MTHFR: Reached processing limit");
                    break;
                }
            }
            
            error_log("MTHFR: Excel processing complete - Processed: {$processed_count}, Skipped: {$skipped_count}");
         
            return $database;
            
        } catch (Exception $e) {
            error_log("MTHFR: Error reading Excel file: " . $e->getMessage());
            return self::create_fallback_database();
        }
    }
    
    /**
     * Map row data to variant structure
     */
    private static function map_row_to_variant_structure($row_data) {
        if (empty($row_data)) {
            return null;
        }
        
        $rsid = self::get_field_value($row_data, array('RSID', 'rsid', 'SNP ID', 'rs_id', 'SNP_ID', 'snp_id'));
        $gene = self::get_field_value($row_data, array('Gene', 'gene', 'GENE', 'Gene Name', 'gene_name'));
        $snp = self::get_field_value($row_data, array('SNP', 'snp', 'Variant', 'variant', 'SNP Name', 'snp_name'));
        $risk = self::get_field_value($row_data, array('Risk', 'risk', 'Risk Allele', 'risk_allele', 'Risk_Allele'));
        $pathway = self::get_field_value($row_data, array('Pathway', 'pathway', 'Category', 'category', 'rs10306114'));
        $report_name = self::get_field_value($row_data, array('Report Name', 'report_name', 'Report_Name', 'report'));
        $info = self::get_field_value($row_data, array('Info', 'info', 'Description', 'description', 'Details'));
        $video = self::get_field_value($row_data, array('Video', 'video', 'Video Link', 'video_link'));
        $tags = self::get_field_value($row_data, array('Tags', 'tags', 'Keywords', 'keywords'));
        
        // Validate required fields
        if (empty($rsid) || strpos($rsid, 'rs') !== 0) {
            return null;
        }
        
        if (empty($gene)) {
            return null;
        }
        
        // Determine pathway if not provided
        if (empty($pathway)) {
            $pathway = self::determine_pathway_from_gene($gene);
        }
        
        return array(
            'RSID' => $rsid,
            'Gene' => $gene,
            'SNP' => $snp ?: '',
            'Risk' => $risk ?: 'Unknown',
            'rs10306114' => $pathway,
            'Report Name' => $report_name ?: 'MTHFRSupport Variant Report v2.5',
            'Info' => $info ?: null,
            'Video' => $video ?: null,
            'Tags' => $tags ?: null
        );
    }

    /**
     * Get field value with fallback options
     */
    private static function get_field_value($row_data, $possible_names) {
        if (!is_array($row_data)) {
            return '';
        }
        
        foreach ($possible_names as $name) {
            if (isset($row_data[$name])) {
                $value = $row_data[$name];
                if ($value !== null && $value !== '' && trim($value) !== '') {
                    return trim($value);
                }
            }
        }
        return '';
    }
    
    /**
     * Determine pathway from gene name
     */
    public static function determine_pathway_from_gene($gene) {
        $gene_pathway_map = array(
            'MTHFR' => 'Methylation & Methionine/Homocysteine Pathways',
            'MTR' => 'Methylation & Methionine/Homocysteine Pathways',
            'MTRR' => 'Methylation & Methionine/Homocysteine Pathways',
            'AHCY' => 'Methylation & Methionine/Homocysteine Pathways',
            'BHMT' => 'Methylation & Methionine/Homocysteine Pathways',
            'COMT' => 'COMT Activity',
            'ALDH2' => 'COMT Activity',
            'ALDH3A2' => 'COMT Activity',
            'ANK3' => 'COMT Activity',
            'CACNA1C' => 'COMT Activity',
            'CYP1A1' => 'COMT Activity',
            'CYP1B1' => 'COMT Activity',
            'DBH' => 'COMT Activity',
            'DRD1' => 'COMT Activity',
            'DRD2' => 'COMT Activity',
            'DRD3' => 'COMT Activity',
            'DRD4' => 'COMT Activity',
            'MAOB' => 'COMT Activity',
            'PAH' => 'COMT Activity',
            'PNMT' => 'COMT Activity',
            'TH' => 'COMT Activity',
            'CBS' => 'Trans-Sulfuration Pathway',
            'GSTP1' => 'Liver Detox',
            'CYP2D6' => 'Liver Detox',
            'CYP2C19' => 'Liver Detox',
            'ACE2' => 'HLA',
            'TNF' => 'HLA',
            'BCMO1' => 'Eye Health',
            'GAD1' => 'Neurotransmitter Pathway: Glutamate & GABA',
            'GAD2' => 'Neurotransmitter Pathway: Glutamate & GABA',
            'GAD' => 'Neurotransmitter Pathway: Glutamate & GABA'
        );

        if (empty($gene)) return 'Primary SNPs';
        
        $gene_upper = strtoupper(trim($gene));

        // Exact match
        if (isset($gene_pathway_map[$gene_upper])) {
            return $gene_pathway_map[$gene_upper];
        }

        // Split composite names
        foreach (preg_split('/[\/,\s\-]+/', $gene_upper) as $part) {
            if ($part === '') continue;
            if (isset($gene_pathway_map[$part])) {
                return $gene_pathway_map[$part];
            }

            // Prefix match
            foreach ($gene_pathway_map as $needle => $pathway) {
                if (stripos($part, $needle) === 0) {
                    return $pathway;
                }
            }
        }

        // Substring fallback
        foreach ($gene_pathway_map as $needle => $pathway) {
            if (stripos($gene_upper, $needle) !== false) {
                return $pathway;
            }
        }

        return 'Primary SNPs';
    }

    /**
     * Create minimal fallback database for system functionality
     */
    private static function create_fallback_database() {
        error_log('MTHFR: Creating minimal fallback database');
        
        return array(
            'rs1801133' => array(
                array(
                    'RSID' => 'rs1801133',
                    'Gene' => 'MTHFR',
                    'SNP' => 'C677T',
                    'Risk' => 'T',
                    'rs10306114' => 'Methylation & Methionine/Homocysteine Pathways',
                    'Report Name' => 'MTHFRSupport Variant Report v2.5',
                    'Info' => 'MTHFR C677T variant affecting folate metabolism',
                    'Video' => null,
                    'Tags' => 'methylation,folate'
                )
            ),
            'rs1801131' => array(
                array(
                    'RSID' => 'rs1801131',
                    'Gene' => 'MTHFR',
                    'SNP' => 'A1298C',
                    'Risk' => 'C',
                    'rs10306114' => 'Methylation & Methionine/Homocysteine Pathways',
                    'Report Name' => 'MTHFRSupport Variant Report v2.5',
                    'Info' => 'MTHFR A1298C variant',
                    'Video' => null,
                    'Tags' => 'methylation,folate'
                )
            )
        );
    }
    
    /**
     * Read CSV file
     */
    private static function read_csv_file($file_path) {
        try {
            error_log("MTHFR: Reading CSV file: {$file_path}");
            
            $handle = fopen($file_path, 'r');
            
            if (!$handle) {
                error_log("MTHFR: Failed to open CSV file: {$file_path}");
                return self::create_fallback_database();
            }
            
            $headers = fgetcsv($handle);
            
            if (!$headers) {
                error_log('MTHFR: No headers found in CSV file');
                fclose($handle);
                return self::create_fallback_database();
            }
            
            $database = array();
            $processed_count = 0;
            
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[0]) || strpos($row[0], 'rs') !== 0) {
                    continue;
                }
                
                $row_data = array();
                
                foreach ($headers as $index => $header) {
                    $row_data[trim($header)] = isset($row[$index]) ? $row[$index] : '';
                }
                
                $variant = self::map_row_to_variant_structure($row_data);
                
                if ($variant && !empty($variant['RSID'])) {
                    if (!isset($database[$variant['RSID']])) {
                        $database[$variant['RSID']] = array();
                    }
                    $database[$variant['RSID']][] = $variant;
                    $processed_count++;
                }
            }
            
            fclose($handle);
            error_log("MTHFR: Loaded {$processed_count} variants from CSV file");
            
            return $database;
            
        } catch (Exception $e) {
            error_log("MTHFR: Error reading CSV file: " . $e->getMessage());
            return self::create_fallback_database();
        }
    }
    
    /**
     * Get methylation database
     */
    public static function get_meth_database() {
        if (self::$meth_database_cache !== null) {
            return self::$meth_database_cache;
        }
        
        $possible_paths = array(
            MTHFR_PLUGIN_PATH . 'data/meth_database.xlsx',
            MTHFR_PLUGIN_PATH . 'data/methylation_database.xlsx',
            MTHFR_PLUGIN_PATH . 'data/meth_database.csv'
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                if (pathinfo($path, PATHINFO_EXTENSION) === 'csv') {
                    self::$meth_database_cache = self::read_meth_csv_file($path);
                } else {
                    self::$meth_database_cache = self::read_meth_excel_file($path);
                }
                
                if (!empty(self::$meth_database_cache)) {
                    return self::$meth_database_cache;
                }
            }
        }
        
        self::$meth_database_cache = array();
        return self::$meth_database_cache;
    }
    
    /**
     * Read methylation Excel file
     */
    private static function read_meth_excel_file($file_path) {
        // Implement similar to read_excel_file if needed
        return array();
    }
    
    /**
     * Read methylation CSV file
     */
    private static function read_meth_csv_file($file_path) {
        // Implement similar to read_csv_file if needed
        return array();
    }
    
    /**
     * Match user genetic data with database
     */
    public static function match_user_data($user_genetic_data) {
        $matched_data = array();
        $database = self::get_database();
        
        foreach ($user_genetic_data as $variant) {
            $rsid = $variant['rsid'] ?? '';
            
            if (isset($database[$rsid])) {
                $db_entries = $database[$rsid];
                
                // Handle both single and multi-category formats
                if (!isset($db_entries[0]) || !is_array($db_entries[0])) {
                    $db_entries = array($db_entries);
                }
                
                foreach ($db_entries as $db_info) {
                    $category = $db_info['rs10306114'] ?? 'Primary SNPs';
                    
                    if (!isset($matched_data[$category])) {
                        $matched_data[$category] = array();
                    }
                    
                    $matched_data[$category][] = array_merge($variant, $db_info);
                }
            }
        }
        
        return $matched_data;
    }
    
    /**
     * Match user data with category filtering
     */
    public static function match_user_data_with_categories($user_genetic_data, $category_filters = null) {
        $matched_data = array();
        $database = self::get_database();
        
        foreach ($user_genetic_data as $variant) {
            $rsid = $variant['rsid'] ?? '';
            
            if (isset($database[$rsid])) {
                $db_entries = $database[$rsid];
                
                // Handle both single and multi-category formats
                if (!isset($db_entries[0]) || !is_array($db_entries[0])) {
                    $db_entries = array($db_entries);
                }
                
                foreach ($db_entries as $db_info) {
                    $category = $db_info['rs10306114'] ?? 'Primary SNPs';
                    
                    // Apply category filtering
                    if ($category_filters && !in_array($category, $category_filters)) {
                        continue;
                    }
                    
                    if (!isset($matched_data[$category])) {
                        $matched_data[$category] = array();
                    }
                    
                    $merged_variant = array_merge($variant, $db_info);
                    $merged_variant['rs10306114'] = $category;
                    
                    $matched_data[$category][] = $merged_variant;
                }
            }
        }
        
        return $matched_data;
    }
    
    /**
     * Get report categories
     */
    public static function get_report_categories($report_type) {
        $database = self::get_database();
        $categories = array();
        
        foreach ($database as $rsid => $entries) {
            if (!is_array($entries)) continue;
            
            if (isset($entries[0]) && is_array($entries[0])) {
                // Multi-category format
                foreach ($entries as $variant) {
                    $category = $variant['rs10306114'] ?? 'Primary SNPs';
                    if (!in_array($category, $categories)) {
                        $categories[] = $category;
                    }
                }
            } else {
                // Single format
                $category = $entries['rs10306114'] ?? 'Primary SNPs';
                if (!in_array($category, $categories)) {
                    $categories[] = $category;
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Get all available categories
     */
    public static function get_all_categories() {
        $database = self::get_database();
        $categories = array();
        
        foreach ($database as $entries) {
            if (!is_array($entries)) continue;
            
            if (isset($entries[0]) && is_array($entries[0])) {
                // Multi-category format
                foreach ($entries as $variant) {
                    $category = $variant['rs10306114'] ?? 'Unknown';
                    if (!in_array($category, $categories)) {
                        $categories[] = $category;
                    }
                }
            } else {
                // Single format
                $category = $entries['rs10306114'] ?? 'Unknown';
                if (!in_array($category, $categories)) {
                    $categories[] = $category;
                }
            }
        }
        
        return $categories;
    }
    
    /**
     * Get database statistics
     */
    public static function get_database_statistics() {
        $database = self::get_database();
        $stats = array(
            'total_rsids' => count($database),
            'multi_category_rsids' => 0,
            'single_category_rsids' => 0,
            'categories' => array(),
            'total_entries' => 0
        );
        
        foreach ($database as $rsid => $entries) {
            if (!is_array($entries)) {
                continue;
            }
            
            if (isset($entries[0]) && is_array($entries[0])) {
                // Multi-category format
                $entry_count = count($entries);
                $stats['total_entries'] += $entry_count;
                
                if ($entry_count > 1) {
                    $stats['multi_category_rsids']++;
                } else {
                    $stats['single_category_rsids']++;
                }
                
                foreach ($entries as $variant) {
                    $category = $variant['rs10306114'] ?? 'Unknown';
                    if (!isset($stats['categories'][$category])) {
                        $stats['categories'][$category] = 0;
                    }
                    $stats['categories'][$category]++;
                }
            } else {
                // Legacy single format
                $stats['single_category_rsids']++;
                $stats['total_entries']++;
                
                $category = $entries['rs10306114'] ?? 'Unknown';
                if (!isset($stats['categories'][$category])) {
                    $stats['categories'][$category] = 0;
                }
                $stats['categories'][$category]++;
            }
        }
        
        return $stats;
    }
}
?>