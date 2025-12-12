<?php
/**
 * MTHFR Database Class - FIXED VERSION
 * Handles all database operations for the MTHFR plugin
 */

namespace MTHFR\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Database {

    /**
     * Clear MTHFR-related caches
     */
    private static function clear_mthfr_caches($keys = array()) {
        if (empty($keys)) {
            // Clear all MTHFR caches
            wp_cache_flush_group('mthfr');
        } else {
            foreach ($keys as $key) {
                wp_cache_delete($key, 'mthfr');
            }
        }
    }

    public function __construct() {
        // Database initialization
    }

    /**
     * Create database tables with CORRECT structure
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // User uploads table
        $uploads_table = $wpdb->prefix . 'user_uploads';
        $uploads_sql = "CREATE TABLE $uploads_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size int(11) DEFAULT 0,
            status varchar(50) DEFAULT 'pending',
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id)
        ) $charset_collate;";

        // User reports table - FIXED with all required fields
        $reports_table = $wpdb->prefix . 'user_reports';
        $reports_sql = "CREATE TABLE $reports_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            upload_id int(11) NOT NULL,
            order_id int(11) NOT NULL,
            report_type varchar(100) DEFAULT NULL,
            report_name varchar(255) DEFAULT NULL,
            report_path varchar(500) DEFAULT NULL,
            pdf_report varchar(500) DEFAULT NULL,
            json_url varchar(500) DEFAULT NULL,
            pdf_url varchar(500) DEFAULT NULL,
            report_data longtext,
            status varchar(50) DEFAULT 'processing',
            error_message text DEFAULT NULL,
            is_subscribed tinyint(1) DEFAULT 0,
            subscription_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            is_deleted tinyint(1) DEFAULT 0,
            reminders int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY upload_id (upload_id),
            KEY order_id (order_id),
            KEY status (status),
            KEY report_type (report_type)
        ) $charset_collate;";

        // Genetic variants table
        $variants_table = $wpdb->prefix . 'genetic_variants';
        $variants_sql = "CREATE TABLE $variants_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            rsid varchar(20) NOT NULL,
            gene varchar(50) NOT NULL,
            snp_name varchar(50) DEFAULT NULL,
            risk_allele varchar(10) DEFAULT NULL,
            info text,
            video varchar(500) DEFAULT NULL,
            report_name varchar(255) DEFAULT NULL,
            tags text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY rsid (rsid),
            KEY gene (gene)
        ) $charset_collate;";

        // Variant categories table (many-to-many relationship)
        $categories_table = $wpdb->prefix . 'variant_categories';
        $categories_sql = "CREATE TABLE $categories_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            variant_id int(11) NOT NULL,
            category_name varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY variant_id (variant_id),
            KEY category_name (category_name),
            FOREIGN KEY (variant_id) REFERENCES $variants_table(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Variant tags table (many-to-many relationship)
        $tags_table = $wpdb->prefix . 'variant_tags';
        $tags_sql = "CREATE TABLE $tags_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            variant_id int(11) NOT NULL,
            tag_name varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY variant_id (variant_id),
            KEY tag_name (tag_name),
            FOREIGN KEY (variant_id) REFERENCES $variants_table(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Pathways table
        $pathways_table = $wpdb->prefix . 'pathways';
        $pathways_sql = "CREATE TABLE $pathways_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            pathway_name varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pathway_name (pathway_name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($uploads_sql);
        dbDelta($reports_sql);
        dbDelta($variants_sql);
        $wpdb->query("ALTER TABLE wp_genetic_variants DROP INDEX IF EXISTS rsid_gene;");
        dbDelta($categories_sql);
        dbDelta($tags_sql);
        dbDelta($pathways_sql);

        // Ensure indexes exist on frequently queried columns
        self::ensure_indexes();

        // Run migrations for existing installations
        self::run_migrations();

        error_log('MTHFR: Database tables created/updated with correct structure');
    }

    /**
     * Run database migrations for existing installations
     */
    public static function run_migrations() {
        global $wpdb;

        $reports_table = $wpdb->prefix . 'user_reports';

        // Check if json_url column exists, add if not
        $json_url_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$reports_table} LIKE 'json_url'"
        );

        if (empty($json_url_exists)) {
            $result = $wpdb->query(
                "ALTER TABLE {$reports_table} ADD COLUMN json_url varchar(500) DEFAULT NULL AFTER pdf_report"
            );

            if ($result !== false) {
                error_log('MTHFR: Successfully added json_url column to user_reports table');

                // Populate existing records with URLs
                self::populate_existing_urls();
            } else {
                error_log('MTHFR: Failed to add json_url column: ' . $wpdb->last_error);
            }
        }

        // Check if pdf_url column exists, add if not
        $pdf_url_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$reports_table} LIKE 'pdf_url'"
        );

        if (empty($pdf_url_exists)) {
            $result = $wpdb->query(
                "ALTER TABLE {$reports_table} ADD COLUMN pdf_url varchar(500) DEFAULT NULL AFTER json_url"
            );

            if ($result !== false) {
                error_log('MTHFR: Successfully added pdf_url column to user_reports table');

                // Populate existing records with URLs
                self::populate_existing_urls();
            } else {
                error_log('MTHFR: Failed to add pdf_url column: ' . $wpdb->last_error);
            }
        }
    }

    /**
     * Populate URL columns for existing records
     */
    private static function populate_existing_urls() {
        global $wpdb;

        $upload_dir = wp_upload_dir();
        $reports_table = $wpdb->prefix . 'user_reports';

        // Get all reports that have file paths but no URLs
        $reports = $wpdb->get_results(
            "SELECT id, report_path, pdf_report FROM {$reports_table}
             WHERE (json_url IS NULL OR pdf_url IS NULL)
             AND (report_path IS NOT NULL OR pdf_report IS NOT NULL)"
        );

        foreach ($reports as $report) {
            $json_url = null;
            $pdf_url = null;

            if (!empty($report->report_path) && file_exists($report->report_path)) {
                $json_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $report->report_path);
            }

            if (!empty($report->pdf_report) && file_exists($report->pdf_report)) {
                $pdf_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $report->pdf_report);
            }

            if ($json_url || $pdf_url) {
                $wpdb->update(
                    $reports_table,
                    array(
                        'json_url' => $json_url,
                        'pdf_url' => $pdf_url,
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $report->id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
            }
        }

        error_log('MTHFR: Populated URL columns for existing reports');
    }

    /**
     * Ensure indexes exist on frequently queried columns
     */
    public static function ensure_indexes() {
        global $wpdb;

        $indexes = array(
            array(
                'table' => $wpdb->prefix . 'genetic_variants',
                'column' => 'rsid',
                'index_name' => 'rsid'
            ),
            array(
                'table' => $wpdb->prefix . 'genetic_variants',
                'column' => 'gene',
                'index_name' => 'gene'
            ),
            array(
                'table' => $wpdb->prefix . 'genetic_variants',
                'column' => 'rsid,gene',
                'index_name' => 'rsid_gene'
            ),
            array(
                'table' => $wpdb->prefix . 'variant_categories',
                'column' => 'category_name',
                'index_name' => 'category_name'
            ),
            array(
                'table' => $wpdb->prefix . 'variant_tags',
                'column' => 'tag_name',
                'index_name' => 'tag_name'
            )
        );

        foreach ($indexes as $index) {
            // Check if index exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                 AND table_name = %s
                 AND index_name = %s",
                $index['table'],
                $index['index_name']
            ));

            if (!$exists) {
                // Create the index
                $sql = "ALTER TABLE {$index['table']} ADD INDEX {$index['index_name']} ({$index['column']})";
                $result = $wpdb->query($sql);

                if ($result === false) {
                    error_log("MTHFR: Failed to create index {$index['index_name']} on {$index['table']}: " . $wpdb->last_error);
                } else {
                    error_log("MTHFR: Created index {$index['index_name']} on {$index['table']}");
                }
            } else {
                error_log("MTHFR: Index {$index['index_name']} already exists on {$index['table']}");
            }
        }
    }

    /**
     * Test database connection
     */
    public static function test_connection() {

        global $wpdb;

        try {
            $result = $wpdb->get_var("SELECT 1");

            if ($result == 1) {
                return array(
                    'status' => 'success',
                    'message' => 'Database connection successful'
                );
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'Database connection failed'
                );
            }
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            );
        }
    }

    /**
     * UPLOAD METHODS
     */
    public static function get_upload($upload_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'user_uploads';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $upload_id
        ));
    }

    public static function get_upload_data($upload_id) {
        return self::get_upload($upload_id);
    }

    public static function create_upload($order_id, $file_name, $file_path, $file_size = 0) {
        global $wpdb;

        $table = $wpdb->prefix . 'user_uploads';

        $result = $wpdb->insert(
            $table,
            array(
                'order_id' => $order_id,
                'file_name' => $file_name,
                'file_path' => $file_path,
                'file_size' => $file_size,
                'status' => 'uploaded',
                'uploaded_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s')
        );

        if ($result !== false) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * REPORT METHODS
     */
    public static function save_report($upload_id, $order_id, $report_type, $report_name, $json_report_path, $pdf_report_path, $json_data, $status, $is_subscribed = false, $error_message = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'user_reports';

        // Check if a report already exists for this order_id
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE order_id = %d LIMIT 1", $order_id
        ));

        // Prepare the JSON data - handle both string and array formats
        $json_data_to_store = '';
        if (is_string($json_data)) {
            $json_data_to_store = $json_data;
        } elseif (is_array($json_data)) {
            $json_data_to_store = json_encode($json_data, JSON_UNESCAPED_SLASHES);
        }
        $json_data_to_store=null;

        // Generate URLs from file paths
        $upload_dir = wp_upload_dir();
        $json_url = null;
        $pdf_url = null;

        if (!empty($json_report_path) && file_exists($json_report_path)) {
            $json_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $json_report_path);
        }

        if (!empty($pdf_report_path) && file_exists($pdf_report_path)) {
            $pdf_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $pdf_report_path);
        }

        $data = array(
            'upload_id'         => $upload_id,
            'order_id'          => $order_id,
            'report_type'       => $report_type,
            'report_name'       => $report_name,
            'report_path'       => $json_report_path,  // JSON report path
            'pdf_report'        => $pdf_report_path,   // PDF report path
            'json_url'          => $json_url,          // JSON report URL
            'pdf_url'           => $pdf_url,           // PDF report URL
            'report_data'       => $json_data_to_store, // JSON data as string
            'status'            => $status,
            'error_message'     => $error_message,
            'is_subscribed'     => $is_subscribed ? 1 : 0,
            'subscription_date' => $is_subscribed ? current_time('mysql') : null,
            'updated_at'        => current_time('mysql'),
            'expires_at'        => date('Y-m-d H:i:s', strtotime('+1 year')),
            'is_deleted'        => 0,
            'reminders'         => 0
        );

        $formats = array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d');

        if ($existing_id) {
            // Update existing row
            $result = $wpdb->update(
                $table,
                $data,
                array('order_id' => $order_id),
                $formats,
                array('%d')
            );
            if ($result !== false) {
                $message = "Updated existing report (order_id: $order_id)";
                if ($json_report_path) $message .= " with JSON: " . basename($json_report_path);
                if ($pdf_report_path) $message .= " and PDF: " . basename($pdf_report_path);
                error_log("MTHFR: " . $message);

                // Clear related caches
                self::clear_mthfr_caches(array('mthfr_report_data_' . $order_id));

                return $existing_id;
            }
        } else {
            // Insert new row - add created_at for new records
            $data['created_at'] = current_time('mysql');
            $formats[] = '%s';

            $result = $wpdb->insert($table, $data, $formats);
            if ($result !== false) {
                $message = "Inserted new report (ID: " . $wpdb->insert_id . ")";
                if ($json_report_path) $message .= " with JSON: " . basename($json_report_path);
                if ($pdf_report_path) $message .= " and PDF: " . basename($pdf_report_path);
                error_log("MTHFR: " . $message);

                // Clear related caches
                self::clear_mthfr_caches(array('mthfr_report_data_' . $order_id));

                return $wpdb->insert_id;
            }
        }

        error_log("MTHFR: Failed to save report: " . $wpdb->last_error);
        return false;
    }

    public static function get_report($report_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'user_reports';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $report_id
        ));
    }

    public static function get_report_by_order($order_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'user_reports';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d AND is_deleted = 0 ORDER BY created_at DESC LIMIT 1",
            $order_id
        ));
    }

    public static function get_report_json_data($order_id) {
        $cache_key = 'mthfr_report_data_' . $order_id;
        $cached_result = wp_cache_get($cache_key, 'mthfr');

        if ($cached_result !== false) {
            return $cached_result;
        }

        $report = self::get_report_by_order($order_id);

        if (!$report || empty($report->report_path) || !file_exists($report->report_path)) {
            wp_cache_set($cache_key, null, 'mthfr', 3600); // Cache null results for 1 hour
            return null;
        }

        try {
            $json_content = file_get_contents($report->report_path);
            $data = json_decode($json_content, true);
            wp_cache_set($cache_key, $data, 'mthfr', 3600); // Cache for 1 hour
            return $data;
        } catch (Exception $e) {
            error_log("MTHFR: Error reading JSON data: " . $e->getMessage());
            wp_cache_set($cache_key, null, 'mthfr', 3600); // Cache null results for 1 hour
            return null;
        }
    }

    /**
     * LEGACY METHOD - for backward compatibility
     */
    public static function create_report($upload_id, $order_id, $report_data, $pdf_path = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'user_reports';
        $report_data=null;

        $result = $wpdb->insert(
            $table,
            array(
                'upload_id' => $upload_id,
                'order_id' => $order_id,
                'report_data' => is_array($report_data) ? json_encode($report_data) : $report_data,
                'pdf_report' => $pdf_path,
                'status' => 'completed',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );

        if ($result !== false) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * UTILITY METHODS
     */
    public static function get_recent_reports($limit = 10) {
        global $wpdb;

        $table = $wpdb->prefix . 'user_reports';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }

    public static function get_upload_count() {
        global $wpdb;

        $table = $wpdb->prefix . 'user_uploads';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table") ?: 0;
    }

    public static function get_report_count() {
        global $wpdb;

        $table = $wpdb->prefix . 'user_reports';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table") ?: 0;
    }

    public static function delete_report($report_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'user_reports';
        return $wpdb->delete($table, array('id' => $report_id), array('%d'));
    }

    public static function update_report_status($report_id, $status) {
        global $wpdb;

        $table = $wpdb->prefix . 'user_reports';
        return $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $report_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Get report statistics
     */
    public static function get_report_stats($order_id) {
        $json_data = self::get_report_json_data($order_id);

        if (!$json_data) {
            return array(
                'total_variants' => 0,
                'risk_variants' => 0,
                'genes_analyzed' => 0
            );
        }

        $total = count($json_data);
        $risk_variants = 0;
        $genes = array();

        foreach ($json_data as $variant) {
            if (isset($variant['Result']) && in_array($variant['Result'], array('+/-', '+/+'))) {
                $risk_variants++;
            }

            if (isset($variant['Gene']) && !in_array($variant['Gene'], $genes)) {
                $genes[] = $variant['Gene'];
            }
        }

        return array(
            'total_variants' => $total,
            'risk_variants' => $risk_variants,
            'genes_analyzed' => count($genes),
            'genes' => $genes
        );
    }

    /**
     * GENETIC VARIANTS DATABASE METHODS
     */

    /**
     * Get all genetic variants
     */
    public static function get_all_variants() {
        global $wpdb;
        $table = $wpdb->prefix . 'genetic_variants';

        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY rsid, gene", ARRAY_A);

        $variants = array();
        foreach ($results as $row) {
            $rsid = $row['rsid'];
            if (!isset($variants[$rsid])) {
                $variants[$rsid] = array();
            }

            // Get categories and tags for this variant
            $categories = self::get_variant_categories($row['id']);
            $tags = self::get_variant_tags($row['id']);

            $variant_data = array(
                'RSID' => $row['rsid'],
                'Gene' => $row['gene'],
                'SNP' => $row['snp_name'],
                'Risk' => $row['risk_allele'],
                'Category' => !empty($categories) ? $categories[0] : 'Primary SNPs', // Use first category as pathway
                'Report Name' => $row['report_name'],
                'Info' => $row['info'],
                'Video' => $row['video'],
                'Tags' => !empty($tags) ? implode(',', $tags) : null,
                'RawTags' => $row['tags']
            );

            $variants[$rsid][] = $variant_data;
        }

        return $variants;
    }

    /**
     * Get genetic variants with pagination
     */
    public static function get_variants_paginated($limit = 100, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'genetic_variants';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY rsid, gene LIMIT %d OFFSET %d",
            $limit, $offset
        ), ARRAY_A);

        $variants = array();
        foreach ($results as $row) {
            $rsid = $row['rsid'];
            if (!isset($variants[$rsid])) {
                $variants[$rsid] = array();
            }

            // Get categories and tags for this variant
            $categories = self::get_variant_categories($row['id']);
            $tags = self::get_variant_tags($row['id']);

            $variant_data = array(
                'RSID' => $row['rsid'],
                'Gene' => $row['gene'],
                'SNP' => $row['snp_name'],
                'Risk' => $row['risk_allele'],
                'Category' => !empty($categories) ? $categories[0] : 'Primary SNPs', // Use first category as pathway
                'Report Name' => $row['report_name'],
                'Info' => $row['info'],
                'Video' => $row['video'],
                'Tags' => !empty($tags) ? implode(',', $tags) : null,
                'RawTags' => $row['tags']
            );

            $variants[$rsid][] = $variant_data;
        }

        return $variants;
    }

    /**
     * Get total count of genetic variants
     */
    public static function get_variants_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'genetic_variants';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table") ?: 0;
    }

    /**
      * Get variant by RSID
      */
     public static function get_variant_by_rsid($rsid) {
         $cache_key = 'mthfr_variant_' . $rsid;
         $cached_result = wp_cache_get($cache_key, 'mthfr');

         if ($cached_result !== false) {
             return $cached_result;
         }

         global $wpdb;
         $table = $wpdb->prefix . 'genetic_variants';

         $results = $wpdb->get_results($wpdb->prepare(
             "SELECT * FROM $table WHERE rsid = %s ORDER BY gene",
             $rsid
         ), ARRAY_A);

         if (empty($results)) {
             wp_cache_set($cache_key, null, 'mthfr', 3600); // Cache null results for 1 hour
             return null;
         }

         $variants = array();
         foreach ($results as $row) {
             $categories = self::get_variant_categories($row['id']);
             $tags = self::get_variant_tags($row['id']);

             $variants[] = array(
                 'RSID' => $row['rsid'],
                 'Gene' => $row['gene'],
                 'SNP' => $row['snp_name'],
                 'Risk' => $row['risk_allele'],
                 'Category' => !empty($categories) ? $categories[0] : 'Primary SNPs',
                 'Report Name' => $row['report_name'],
                 'Info' => $row['info'],
                 'Video' => $row['video'],
                 'Tags' => !empty($tags) ? implode(',', $tags) : null,
                 'RawTags' => $row['tags']
             );
         }

         wp_cache_set($cache_key, $variants, 'mthfr', 3600); // Cache for 1 hour
         return $variants;
     }

    /**
      * Get variant by RSID with lazy loading (for matching user data)
      */
     public static function get_variant_by_rsid_lazy($rsid) {
         $cache_key = 'mthfr_variant_lazy_' . $rsid;
         $cached_result = wp_cache_get($cache_key, 'mthfr');

         if ($cached_result !== false) {
             return $cached_result;
         }

         global $wpdb;
         $table = $wpdb->prefix . 'genetic_variants';

         $results = $wpdb->get_results($wpdb->prepare(
             "SELECT v.*, GROUP_CONCAT(DISTINCT c.category_name) as categories, GROUP_CONCAT(DISTINCT t.tag_name) as tags
              FROM $table v
              LEFT JOIN {$wpdb->prefix}variant_categories c ON v.id = c.variant_id
              LEFT JOIN {$wpdb->prefix}variant_tags t ON v.id = t.variant_id
              WHERE v.rsid = %s
              GROUP BY v.id
              ORDER BY v.gene",
             $rsid
         ), ARRAY_A);

         if (empty($results)) {
             wp_cache_set($cache_key, null, 'mthfr', 3600); // Cache null results for 1 hour
             return null;
         }

         $variants = array();
         foreach ($results as $row) {
             $categories = !empty($row['categories']) ? explode(',', $row['categories']) : array();
             $tags = !empty($row['tags']) ? explode(',', $row['tags']) : array();

             $variants[] = array(
                 'RSID' => $row['rsid'],
                 'Gene' => $row['gene'],
                 'SNP' => $row['snp_name'],
                 'Risk' => $row['risk_allele'],
                 'Category' => !empty($categories) ? $categories[0] : 'Primary SNPs',
                 'Report Name' => $row['report_name'],
                 'Info' => $row['info'],
                 'Video' => $row['video'],
                 'Tags' => !empty($tags) ? implode(',', $tags) : null,
                 'RawTags' => $row['tags']
             );
         }

         wp_cache_set($cache_key, $variants, 'mthfr', 3600); // Cache for 1 hour
         return $variants;
     }

    /**
      * Get variants by multiple RSIDs with lazy loading (for optimized report generation)
      */
     public static function get_variants_by_rsids($rsids) {
         if (empty($rsids)) {
             return array();
         }

         // Create cache key from sorted rsids for consistency
         sort($rsids);
         $cache_key = 'mthfr_variants_by_rsids_' . md5(implode(',', $rsids));
         $cached_result = wp_cache_get($cache_key, 'mthfr');

         if ($cached_result !== false) {
             return $cached_result;
         }

         global $wpdb;
         $table = $wpdb->prefix . 'genetic_variants';

         // Chunk rsids to avoid MySQL IN clause limits (typically 1000 items)
         $chunk_size = 500;
         $all_results = array();

         foreach (array_chunk($rsids, $chunk_size) as $chunk) {
             // Prepare IN clause placeholders for this chunk
             $placeholders = array_fill(0, count($chunk), '%s');
             $in_clause = implode(', ', $placeholders);

             $query = "SELECT v.*, GROUP_CONCAT(DISTINCT c.category_name) as categories, GROUP_CONCAT(DISTINCT t.tag_name) as tags
                      FROM $table v
                      LEFT JOIN {$wpdb->prefix}variant_categories c ON v.id = c.variant_id
                      LEFT JOIN {$wpdb->prefix}variant_tags t ON v.id = t.variant_id
                      WHERE v.rsid IN ($in_clause)
                      GROUP BY v.id
                      ORDER BY v.rsid, v.gene";

             $results = $wpdb->get_results($wpdb->prepare($query, $chunk), ARRAY_A);
             if (!empty($results)) {
                 $all_results = array_merge($all_results, $results);
             }
         }

         if (empty($all_results)) {
             wp_cache_set($cache_key, array(), 'mthfr', 3600); // Cache empty results for 1 hour
             return array();
         }

         $variants = array();
         foreach ($all_results as $row) {
             $rsid = $row['rsid'];
             if (!isset($variants[$rsid])) {
                 $variants[$rsid] = array();
             }

             $categories = !empty($row['categories']) ? explode(',', $row['categories']) : array();
             $tags = !empty($row['tags']) ? explode(',', $row['tags']) : array();

             $variant_data = array(
                 'RSID' => $row['rsid'],
                 'Gene' => $row['gene'],
                 'SNP' => $row['snp_name'],
                 'Risk' => $row['risk_allele'],
                 'Category' => !empty($categories) ? $categories[0] : 'Primary SNPs',
                 'Report Name' => $row['report_name'],
                 'Info' => $row['info'],
                 'Video' => $row['video'],
                 'Tags' => !empty($tags) ? implode(',', $tags) : null,
                 'RawTags' => $row['tags']
             );

             $variants[$rsid][] = $variant_data;
         }

         wp_cache_set($cache_key, $variants, 'mthfr', 3600); // Cache for 1 hour
         return $variants;
     }

    /**
      * Get variant categories
      */
     public static function get_variant_categories($variant_id) {
         $cache_key = 'mthfr_categories_' . $variant_id;
         $cached_result = wp_cache_get($cache_key, 'mthfr');

         if ($cached_result !== false) {
             return $cached_result;
         }

         global $wpdb;
         $table = $wpdb->prefix . 'variant_categories';

         $results = $wpdb->get_col($wpdb->prepare(
             "SELECT category_name FROM $table WHERE variant_id = %d",
             $variant_id
         ));

         wp_cache_set($cache_key, $results, 'mthfr', 3600); // Cache for 1 hour
         return $results;
     }

    /**
      * Get variant tags
      */
     public static function get_variant_tags($variant_id) {
         $cache_key = 'mthfr_tags_' . $variant_id;
         $cached_result = wp_cache_get($cache_key, 'mthfr');

         if ($cached_result !== false) {
             return $cached_result;
         }

         global $wpdb;
         $table = $wpdb->prefix . 'variant_tags';

         $results = $wpdb->get_col($wpdb->prepare(
             "SELECT tag_name FROM $table WHERE variant_id = %d",
             $variant_id
         ));

         wp_cache_set($cache_key, $results, 'mthfr', 3600); // Cache for 1 hour
         return $results;
     }

    /**
      * Get all categories
      */
     public static function get_all_categories() {
         $cache_key = 'mthfr_all_categories';
         $cached_result = wp_cache_get($cache_key, 'mthfr');

         if ($cached_result !== false) {
             return $cached_result;
         }

         global $wpdb;
         $table = $wpdb->prefix . 'variant_categories';

         $results = $wpdb->get_col("SELECT DISTINCT category_name FROM $table ORDER BY category_name");

         wp_cache_set($cache_key, $results, 'mthfr', 86400); // Cache for 24 hours
         return $results;
     }

    /**
     * Get categories with pagination
     */
    public static function get_categories_paginated($limit = 50, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'variant_categories';

        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT category_name FROM $table ORDER BY category_name LIMIT %d OFFSET %d",
            $limit, $offset
        ));

        return $results;
    }

    /**
     * Get total count of distinct categories
     */
    public static function get_categories_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'variant_categories';
        return $wpdb->get_var("SELECT COUNT(DISTINCT category_name) FROM $table") ?: 0;
    }

    /**
      * Get all tags
      */
     public static function get_all_tags() {
         $cache_key = 'mthfr_all_tags';
         $cached_result = wp_cache_get($cache_key, 'mthfr');

         if ($cached_result !== false) {
             return $cached_result;
         }

         global $wpdb;
         $table = $wpdb->prefix . 'variant_tags';

         $results = $wpdb->get_col("SELECT DISTINCT tag_name FROM $table ORDER BY tag_name");

         wp_cache_set($cache_key, $results, 'mthfr', 86400); // Cache for 24 hours
         return $results;
     }

    /**
     * Get tags with pagination
     */
    public static function get_tags_paginated($limit = 50, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'variant_tags';

        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT tag_name FROM $table ORDER BY tag_name LIMIT %d OFFSET %d",
            $limit, $offset
        ));

        return $results;
    }

    /**
     * Get total count of distinct tags
     */
    public static function get_tags_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'variant_tags';
        return $wpdb->get_var("SELECT COUNT(DISTINCT tag_name) FROM $table") ?: 0;
    }

    /**
     * Get all pathways
     */
    public static function get_all_pathways() {
        global $wpdb;
        $table = $wpdb->prefix . 'pathways';

        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY pathway_name", ARRAY_A);

        return $results;
    }

    /**
     * Insert genetic variant
     */
    public static function insert_variant($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'genetic_variants';

        $result = $wpdb->insert(
            $table,
            array(
                'rsid' => $data['rsid'],
                'gene' => $data['gene'],
                'snp_name' => $data['snp_name'] ?? null,
                'risk_allele' => $data['risk_allele'] ?? null,
                'info' => $data['info'] ?? null,
                'video' => $data['video'] ?? null,
                'report_name' => $data['report_name'] ?? null,
                'tags' => $data['tags'] ?? null
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return false;
        }

        $variant_id = $wpdb->insert_id;

        // Clear related caches
        self::clear_mthfr_caches(array(
            'mthfr_variant_' . $data['rsid'],
            'mthfr_variant_lazy_' . $data['rsid']
        ));

        return $variant_id;
    }

    /**
     * Insert variant category
     */
    public static function insert_variant_category($variant_id, $category_name) {
        global $wpdb;
        $table = $wpdb->prefix . 'variant_categories';


        $result = $wpdb->insert(
            $table,
            array(
                'variant_id' => $variant_id,
                'category_name' => $category_name
            ),
            array('%d', '%s')
        );

        if ($result !== false) {
            // Clear related caches
            self::clear_mthfr_caches(array(
                'mthfr_categories_' . $variant_id,
                'mthfr_all_categories'
            ));
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Insert variant tag
     */
    public static function insert_variant_tag($variant_id, $tag_name) {
        global $wpdb;
        $table = $wpdb->prefix . 'variant_tags';

        $result = $wpdb->insert(
            $table,
            array(
                'variant_id' => $variant_id,
                'tag_name' => $tag_name
            ),
            array('%d', '%s')
        );

        if ($result !== false) {
            // Clear related caches
            self::clear_mthfr_caches(array(
                'mthfr_tags_' . $variant_id,
                'mthfr_all_tags'
            ));
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Insert pathway
     */
    public static function insert_pathway($pathway_name, $description = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'pathways';

        // Check if already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE pathway_name = %s",
            $pathway_name
        ));

        if ($exists) {
            return $exists;
        }

        $result = $wpdb->insert(
            $table,
            array(
                'pathway_name' => $pathway_name,
                'description' => $description
            ),
            array('%s', '%s')
        );

        return $result !== false ? $wpdb->insert_id : false;
    }

    /**
     * Batch insert genetic variants
     */
    public static function batch_insert_variants($variants_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'genetic_variants';

        if (empty($variants_data)) {
            error_log('MTHFR: batch_insert_variants called with empty data');
            return array();
        }

        error_log('MTHFR: Attempting to batch insert ' . count($variants_data) . ' variants');

        $values = array();
        $placeholders = array();

        foreach ($variants_data as $variant) {
            $values = array_merge($values, array(
                $variant['rsid'],
                $variant['gene'],
                $variant['snp_name'] ?? null,
                $variant['risk_allele'] ?? null,
                $variant['info'] ?? null,
                $variant['video'] ?? null,
                $variant['report_name'] ?? null,
                $variant['tags'] ?? null
            ));
            $placeholders[] = "(%s, %s, %s, %s, %s, %s, %s, %s)";
        }

        $query = "INSERT INTO $table
                  (rsid, gene, snp_name, risk_allele, info, video, report_name, tags)
                  VALUES " . implode(', ', $placeholders);

        $result = $wpdb->query($wpdb->prepare($query, $values));

        if ($result === false) {
            error_log("MTHFR: Batch insert variants failed: " . $wpdb->last_error);
            error_log("MTHFR: Query: " . $query);
            error_log("MTHFR: Values count: " . count($values));
            return array();
        }

        error_log('MTHFR: Batch insert variants succeeded, affected rows: ' . $result);

        // Get the inserted IDs (this is approximate since INSERT IGNORE doesn't return IDs for duplicates)
        $inserted_ids = array();
        $last_id = $wpdb->insert_id;
        if ($last_id) {
            for ($i = 0; $i < $result; $i++) {
                $inserted_ids[] = $last_id + $i;
            }
        }

        error_log('MTHFR: Generated ' . count($inserted_ids) . ' insert IDs starting from ' . $last_id);

        // Clear caches for inserted variants
        if (!empty($inserted_ids)) {
            $cache_keys_to_clear = array();
            foreach ($variants_data as $variant) {
                $cache_keys_to_clear[] = 'mthfr_variant_' . $variant['rsid'];
                $cache_keys_to_clear[] = 'mthfr_variant_lazy_' . $variant['rsid'];
            }
            self::clear_mthfr_caches($cache_keys_to_clear);
        }

        return $inserted_ids;
    }

    /**
     * Batch insert variant categories
     */
    public static function batch_insert_variant_categories($categories_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'variant_categories';

        if (empty($categories_data)) {
            return 0;
        }

        $values = array();
        $placeholders = array();

        foreach ($categories_data as $category) {
            $values = array_merge($values, array(
                $category['variant_id'],
                $category['category_name']
            ));
            $placeholders[] = "(%d, %s)";
        }

        $query = "INSERT INTO $table
                  (variant_id, category_name)
                  VALUES " . implode(', ', $placeholders);

        $result = $wpdb->query($wpdb->prepare($query, $values));

        if ($result === false) {
            error_log("MTHFR: Batch insert categories failed: " . $wpdb->last_error);
            return 0;
        }

        // Clear related caches
        if ($result > 0) {
            $cache_keys_to_clear = array('mthfr_all_categories');
            foreach ($categories_data as $category) {
                $cache_keys_to_clear[] = 'mthfr_categories_' . $category['variant_id'];
            }
            self::clear_mthfr_caches($cache_keys_to_clear);
        }

        return $result;
    }

    /**
     * Batch insert variant tags
     */
    public static function batch_insert_variant_tags($tags_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'variant_tags';

        if (empty($tags_data)) {
            return 0;
        }

        $values = array();
        $placeholders = array();

        foreach ($tags_data as $tag) {
            $values = array_merge($values, array(
                $tag['variant_id'],
                $tag['tag_name']
            ));
            $placeholders[] = "(%d, %s)";
        }

        $query = "INSERT INTO $table
                  (variant_id, tag_name)
                  VALUES " . implode(', ', $placeholders);

        $result = $wpdb->query($wpdb->prepare($query, $values));

        if ($result === false) {
            error_log("MTHFR: Batch insert tags failed: " . $wpdb->last_error);
            return 0;
        }

        // Clear related caches
        if ($result > 0) {
            $cache_keys_to_clear = array('mthfr_all_tags');
            foreach ($tags_data as $tag) {
                $cache_keys_to_clear[] = 'mthfr_tags_' . $tag['variant_id'];
            }
            self::clear_mthfr_caches($cache_keys_to_clear);
        }

        return $result;
    }

    /**
     * Get database statistics for genetic data
     */
    public static function get_genetic_database_stats() {
        global $wpdb;

        $stats = array();

        // Count variants
        $stats['variants'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}genetic_variants");

        // Count categories
        $stats['categories'] = $wpdb->get_var("SELECT COUNT(DISTINCT category_name) FROM {$wpdb->prefix}variant_categories");

        // Count tags
        $stats['tags'] = $wpdb->get_var("SELECT COUNT(DISTINCT tag_name) FROM {$wpdb->prefix}variant_tags");

        // Count pathways
        $stats['pathways'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pathways");

        // Count category relationships
        $stats['category_relationships'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}variant_categories");

        // Count tag relationships
        $stats['tag_relationships'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}variant_tags");

        return $stats;
    }

}