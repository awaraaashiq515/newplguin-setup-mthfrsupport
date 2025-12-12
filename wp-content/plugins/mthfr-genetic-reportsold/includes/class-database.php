<?php
/**
 * MTHFR Database Class - FIXED VERSION
 * Handles all database operations for the MTHFR plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_Database {
    
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($uploads_sql);
        dbDelta($reports_sql);
        
        error_log('MTHFR: Database tables created/updated with correct structure');
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

        $data = array(
            'upload_id'         => $upload_id,
            'order_id'          => $order_id,
            'report_type'       => $report_type,
            'report_name'       => $report_name,
            'report_path'       => $json_report_path,  // JSON report path
            'pdf_report'        => $pdf_report_path,   // PDF report path  
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

        $formats = array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d');

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
        $report = self::get_report_by_order($order_id);
        
        if (!$report || empty($report->report_path) || !file_exists($report->report_path)) {
            return null;
        }
        
        try {
            $json_content = file_get_contents($report->report_path);
            return json_decode($json_content, true);
        } catch (Exception $e) {
            error_log("MTHFR: Error reading JSON data: " . $e->getMessage());
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

}