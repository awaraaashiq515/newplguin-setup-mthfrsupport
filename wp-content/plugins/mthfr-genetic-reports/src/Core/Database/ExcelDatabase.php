<?php
/**
 * MTHFR Excel Database Handler - Enhanced Production Version
 * Reads and processes genetic data from Excel database files
 */

namespace MTHFR\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

// Include PHPSpreadsheet if available
if (file_exists(MTHFR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once MTHFR_PLUGIN_PATH . 'vendor/autoload.php';
}

class ExcelDatabase {

    private static $database_cache = null;
    private static $meth_database_cache = null;

    /**
     * Initialize databases method - now uses database instead of XLSX
     */
    public static function initialize_databases() {
        error_log('MTHFR: Initializing database-backed genetic data...');

        // Check if database has data, if not, try to import from XLSX
        $stats = Database::get_genetic_database_stats();
        if ($stats['variants'] == 0) {
            error_log('MTHFR: No genetic data in database, attempting import from XLSX files');
            self::import_legacy_data();
        }

        // Initialize main database (now from database)
        self::get_database();

        // Initialize methylation database
        self::get_meth_database();

        error_log('MTHFR: Database-backed genetic data initialization complete');
    }

    /**
     * Import legacy XLSX data to database
     */
    private static function import_legacy_data() {
        if (!class_exists('MTHFR_Database_Importer')) {
            require_once MTHFR_PLUGIN_PATH . 'classes/class-database-importer.php';
        }

        $importer = new MTHFR_Database_Importer();
        $result = $importer->import_from_xlsx();

        if ($result['success']) {
            error_log('MTHFR: Legacy data import completed: ' . $result['message']);
        } else {
            error_log('MTHFR: Legacy data import failed: ' . $result['message']);
        }
    }

    /**
     * Clear existing genetic database data
     */
    private static function clear_genetic_data() {
        global $wpdb;

        error_log('MTHFR: Clearing existing genetic database data for reimport');

        try {
            // Clear in order due to foreign key constraints
            $wpdb->query("DELETE FROM {$wpdb->prefix}variant_tags");
            $wpdb->query("DELETE FROM {$wpdb->prefix}variant_categories");
            $wpdb->query("DELETE FROM {$wpdb->prefix}genetic_variants");
            $wpdb->query("DELETE FROM {$wpdb->prefix}pathways");

            // Reset auto-increment counters
            $wpdb->query("ALTER TABLE {$wpdb->prefix}genetic_variants AUTO_INCREMENT = 1");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}variant_categories AUTO_INCREMENT = 1");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}variant_tags AUTO_INCREMENT = 1");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}pathways AUTO_INCREMENT = 1");

            error_log('MTHFR: Genetic database data cleared successfully');
            return true;
        } catch (Exception $e) {
            error_log('MTHFR: Error clearing genetic database data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Re-import legacy XLSX data to database (allows re-importing even if data exists)
     */
    public static function reimport_legacy_data() {
        error_log('MTHFR: Starting reimport of legacy XLSX data');

        // Clear existing data first
        if (!self::clear_genetic_data()) {
            error_log('MTHFR: Failed to clear existing data for reimport');
            return array(
                'success' => false,
                'message' => 'Failed to clear existing genetic data'
            );
        }

        // Import fresh data
        if (!class_exists('MTHFR_Database_Importer')) {
            require_once MTHFR_PLUGIN_PATH . 'classes/class-database-importer.php';
        }

        try {
            $importer = new MTHFR_Database_Importer();
            $result = $importer->import_from_xlsx();

            if ($result['success']) {
                error_log('MTHFR: Legacy data reimport completed: ' . $result['message']);

                // Clear cache to force reload
                self::$database_cache = null;
                self::$meth_database_cache = null;

                return array(
                    'success' => true,
                    'message' => 'Reimport completed successfully: ' . $result['message'],
                    'details' => $result
                );
            } else {
                error_log('MTHFR: Legacy data reimport failed: ' . $result['message']);
                return array(
                    'success' => false,
                    'message' => 'Reimport failed: ' . $result['message'],
                    'details' => $result
                );
            }
        } catch (Exception $e) {
            error_log('MTHFR: Exception during reimport: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Reimport failed with exception: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get main database data from database tables
     */
    public static function get_database() {
        if (self::$database_cache !== null) {
            return self::$database_cache;
        }

        error_log('MTHFR: Loading genetic data from database...');

        try {
            $database = Database::get_all_variants();

            if (!empty($database)) {
                error_log("MTHFR: Successfully loaded " . count($database) . " RSIDs from database");
                self::$database_cache = $database;
                return self::$database_cache;
            } else {
                error_log('MTHFR: No data found in database, using fallback database');
                $database = self::create_fallback_database();
            }
        } catch (Exception $e) {
            error_log("MTHFR: Error loading from database: " . $e->getMessage());
            $database = self::create_fallback_database();
        }

        self::$database_cache = $database;
        return self::$database_cache;
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
     * Get methylation database - placeholder for future implementation
     */
    public static function get_meth_database() {
        if (self::$meth_database_cache !== null) {
            return self::$meth_database_cache;
        }

        // For now, return empty array since methylation data is not implemented in database
        self::$meth_database_cache = array();
        return self::$meth_database_cache;
    }

    /**
     * Match user genetic data with database (lazy loading version)
     */
    public static function match_user_data($user_genetic_data) {
        $matched_data = array();

        foreach ($user_genetic_data as $variant) {
            $rsid = $variant['rsid'] ?? '';

            if (empty($rsid)) {
                continue;
            }

            // Use lazy loading - query database directly for this RSID
            $db_entries = Database::get_variant_by_rsid_lazy($rsid);

            if ($db_entries) {
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
     * Match user data with category filtering (lazy loading version)
     */
    public static function match_user_data_with_categories($user_genetic_data, $category_filters = null) {
        $matched_data = array();

        foreach ($user_genetic_data as $variant) {
            $rsid = $variant['rsid'] ?? '';

            if (empty($rsid)) {
                continue;
            }

            // Use lazy loading - query database directly for this RSID
            $db_entries = Database::get_variant_by_rsid_lazy($rsid);

            if ($db_entries) {
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
     * Get report categories (optimized version)
     */
    public static function get_report_categories($report_type) {
        // Use database query instead of loading all variants
        global $wpdb;

        $results = $wpdb->get_col("
            SELECT DISTINCT c.category_name
            FROM {$wpdb->prefix}variant_categories c
            INNER JOIN {$wpdb->prefix}genetic_variants v ON c.variant_id = v.id
            ORDER BY c.category_name
        ");

        return $results ?: array();
    }

    /**
     * Get all available categories
     */
    public static function get_all_categories() {
        return Database::get_all_categories();
    }

    /**
     * Get database statistics
     */
    public static function get_database_statistics() {
        $db_stats = Database::get_genetic_database_stats();

        // Transform to match expected format
        $stats = array(
            'total_rsids' => $db_stats['variants'],
            'multi_category_rsids' => 0, // We'll calculate this differently now
            'single_category_rsids' => $db_stats['variants'],
            'categories' => array(),
            'total_entries' => $db_stats['variants']
        );

        // Get category distribution (limit to avoid loading too much data)
        global $wpdb;
        $category_counts = $wpdb->get_results("
            SELECT category_name, COUNT(*) as count
            FROM {$wpdb->prefix}variant_categories
            GROUP BY category_name
            ORDER BY count DESC
            LIMIT 100
        ", ARRAY_A);

        foreach ($category_counts as $row) {
            $stats['categories'][$row['category_name']] = intval($row['count']);
        }

        return $stats;
    }
}