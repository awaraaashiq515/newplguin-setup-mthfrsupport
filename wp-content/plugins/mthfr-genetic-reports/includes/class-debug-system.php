<?php
/**
 * MTHFR Enhanced Debug and Testing System
 * File: includes/class-debug-system.php
 */

class MTHFR_Debug_System {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_mthfr_test_endpoint', array($this, 'ajax_test_endpoint'));
        add_action('wp_ajax_mthfr_refresh_debug', array($this, 'ajax_refresh_debug'));
        add_action('admin_init', array($this, 'handle_manual_actions'));
    }
    
    /**
     * Get comprehensive debug information
     */
    public static function get_comprehensive_debug_info() {
        global $wpdb;
        
        $debug_info = array(
            'timestamp' => current_time('c'),
            'plugin_status' => self::get_plugin_status(),
            'api_status' => self::get_api_status(),
            'database_status' => self::get_database_status(),
            'file_system' => self::get_file_system_status(),
            'wordpress_environment' => self::get_wp_environment(),
            'available_endpoints' => self::get_available_endpoints(),
            'recent_errors' => self::get_recent_errors(),
            'test_results' => self::run_comprehensive_tests()
        );
        
        return $debug_info;
    }
    
    /**
     * Get plugin status information
     */
    private static function get_plugin_status() {
        return array(
            'version' => MTHFR_PLUGIN_VERSION,
            'path' => MTHFR_PLUGIN_PATH,
            'url' => MTHFR_PLUGIN_URL,
            'classes_loaded' => array(
                'MTHFR_Genetic_Reports' => class_exists('MTHFR_Genetic_Reports'),
                'MTHFR_API_Endpoints' => class_exists('MTHFR_API_Endpoints'),
                'MTHFR_Database' => class_exists('MTHFR_Database'),
                'MTHFR_Genetic_Data' => class_exists('MTHFR_Genetic_Data'),
                'MTHFR_PDF_Generator' => class_exists('MTHFR_PDF_Generator'),
                'MTHFR_Report_Generator' => class_exists('MTHFR_Report_Generator')
            ),
            'hooks_registered' => array(
                'rest_api_init' => has_action('rest_api_init'),
                'admin_menu' => has_action('admin_menu'),
                'plugins_loaded' => has_action('plugins_loaded')
            )
        );
    }
    
    /**
     * Get API status and registered routes
     */
    private static function get_api_status() {
        global $wp_rest_server;
        
        $status = array(
            'rest_api_available' => function_exists('register_rest_route'),
            'rest_server_class' => class_exists('WP_REST_Server'),
            'rest_enabled' => !empty($GLOBALS['wp']->query_vars['rest_route']) || !empty($_GET['rest_route']),
            'registered_routes' => array(),
            'namespace_registered' => false
        );
        
        // Check if our routes are registered
        if (function_exists('rest_get_server')) {
            $server = rest_get_server();
            $routes = $server->get_routes();
            
            // Look for our namespace
            foreach ($routes as $route => $handlers) {
                if (strpos($route, '/mthfr/v1') === 0) {
                    $status['registered_routes'][$route] = array(
                        'methods' => array(),
                        'handlers' => count($handlers)
                    );
                    
                    foreach ($handlers as $handler) {
                        if (isset($handler['methods'])) {
                            $status['registered_routes'][$route]['methods'] = array_merge(
                                $status['registered_routes'][$route]['methods'],
                                array_keys($handler['methods'])
                            );
                        }
                    }
                    $status['namespace_registered'] = true;
                }
            }
        }
        
        return $status;
    }
    
    /**
     * Get database status
     */
    private static function get_database_status() {
        global $wpdb;
        
        $tables = array(
            'user_uploads' => $wpdb->prefix . 'user_uploads',
            'user_reports' => $wpdb->prefix . 'user_reports'
        );
        
        $status = array(
            'connection' => true,
            'tables' => array(),
            'table_counts' => array()
        );
        
        try {
            // Test connection
            $wpdb->get_var("SELECT 1");
            
            // Check tables
            foreach ($tables as $key => $table_name) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
                $status['tables'][$key] = array(
                    'name' => $table_name,
                    'exists' => $exists,
                    'count' => $exists ? (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") : 0
                );
            }
            
        } catch (Exception $e) {
            $status['connection'] = false;
            $status['error'] = $e->getMessage();
        }
        
        return $status;
    }
    
    /**
     * Get file system status
     */
    private static function get_file_system_status() {
        $required_files = array(
            'main' => 'mthfr-genetic-reports.php',
            'database' => 'includes/class-database.php',
            'genetic_data' => 'includes/class-genetic-data.php',
            'pdf_generator' => 'includes/class-pdf-generator.php',
            'report_generator' => 'includes/class-report-generator.php',
            'api_endpoints' => 'api/class-api-endpoints.php',
            'admin_template' => 'templates/admin-dashboard.php',
            'debug_template' => 'templates/debug-page.php'
        );
        
        $status = array(
            'plugin_directory_writable' => is_writable(MTHFR_PLUGIN_PATH),
            'files' => array()
        );
        
        foreach ($required_files as $key => $file) {
            $full_path = MTHFR_PLUGIN_PATH . $file;
            $status['files'][$key] = array(
                'path' => $file,
                'exists' => file_exists($full_path),
                'readable' => file_exists($full_path) && is_readable($full_path),
                'size' => file_exists($full_path) ? filesize($full_path) : 0
            );
        }
        
        return $status;
    }
    
    /**
     * Get WordPress environment info
     */
    private static function get_wp_environment() {
        return array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'mysql_version' => $GLOBALS['wpdb']->db_version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'rest_api_enabled' => !empty(get_option('permalink_structure')),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG
        );
    }
    
    /**
     * Get available endpoints with test results
     */
    private static function get_available_endpoints() {
        $endpoints = array(
            '/mthfr/v1/health' => array(
                'method' => 'GET',
                'description' => 'Health check endpoint',
                'test_url' => home_url('/wp-json/mthfr/v1/health')
            ),
            '/mthfr/v1/debug' => array(
                'method' => 'GET',
                'description' => 'Debug information endpoint',
                'test_url' => home_url('/wp-json/mthfr/v1/debug')
            ),
            '/mthfr/v1/generate-report' => array(
                'method' => 'POST',
                'description' => 'Generate genetic report',
                'test_url' => home_url('/wp-json/mthfr/v1/generate-report')
            ),
            '/mthfr/v1/test-db' => array(
                'method' => 'GET',
                'description' => 'Test database connection',
                'test_url' => home_url('/wp-json/mthfr/v1/test-db')
            ),
            '/mthfr/v1/test-pdf' => array(
                'method' => 'GET',
                'description' => 'Test PDF generation',
                'test_url' => home_url('/wp-json/mthfr/v1/test-pdf')
            )
        );
        
        // Test each endpoint
        foreach ($endpoints as $route => &$endpoint) {
            $endpoint['status'] = self::test_endpoint_availability($endpoint['test_url'], $endpoint['method']);
        }
        
        return $endpoints;
    }
    
    /**
     * Test endpoint availability
     */
    private static function test_endpoint_availability($url, $method = 'GET') {
        $args = array(
            'timeout' => 5,
            'method' => $method,
            'headers' => array(
                'User-Agent' => 'MTHFR-Plugin-Debug/1.0'
            )
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'available' => false,
                'error' => $response->get_error_message(),
                'tested_at' => current_time('c')
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        return array(
            'available' => $status_code !== 404,
            'status_code' => $status_code,
            'response_size' => strlen($response_body),
            'tested_at' => current_time('c')
        );
    }
    
    /**
     * Get recent errors from logs
     */
    private static function get_recent_errors() {
        $errors = array();
        
        // Try to read from WordPress debug log
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_file) && is_readable($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $recent_lines = array_slice($lines, -50); // Last 50 lines
            
            foreach ($recent_lines as $line) {
                if (strpos($line, 'MTHFR') !== false) {
                    $errors[] = $line;
                }
            }
        }
        
        return array_slice($errors, -10); // Last 10 MTHFR-related errors
    }
    
    /**
     * Run comprehensive tests
     */
    private static function run_comprehensive_tests() {
        $tests = array();
        
        // Test 1: Class loading
        $tests['class_loading'] = array(
            'name' => 'Class Loading Test',
            'status' => 'success',
            'details' => array(),
            'score' => 0,
            'max_score' => 6
        );
        
        $required_classes = array(
            'MTHFR_Genetic_Reports',
            'MTHFR_API_Endpoints', 
            'MTHFR_Database',
            'MTHFR_Genetic_Data',
            'MTHFR_PDF_Generator',
            'MTHFR_Report_Generator'
        );
        
        foreach ($required_classes as $class) {
            $loaded = class_exists($class);
            $tests['class_loading']['details'][$class] = $loaded ? 'loaded' : 'missing';
            if ($loaded) $tests['class_loading']['score']++;
        }
        
        if ($tests['class_loading']['score'] < $tests['class_loading']['max_score']) {
            $tests['class_loading']['status'] = 'warning';
        }
        
        // Test 2: Database connectivity
        $tests['database'] = array(
            'name' => 'Database Test',
            'status' => 'success',
            'details' => array(),
            'score' => 0,
            'max_score' => 3
        );
        
        try {
            global $wpdb;
            $wpdb->get_var("SELECT 1");
            $tests['database']['details']['connection'] = 'success';
            $tests['database']['score']++;
            
            // Test table existence
            $tables = array(
                $wpdb->prefix . 'user_uploads',
                $wpdb->prefix . 'user_reports'
            );
            
            foreach ($tables as $table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table;
                $tests['database']['details']['table_' . str_replace($wpdb->prefix, '', $table)] = $exists ? 'exists' : 'missing';
                if ($exists) $tests['database']['score']++;
            }
            
        } catch (Exception $e) {
            $tests['database']['status'] = 'error';
            $tests['database']['details']['error'] = $e->getMessage();
        }
        
        // Test 3: API Routes
        $tests['api_routes'] = array(
            'name' => 'API Routes Test',
            'status' => 'success',
            'details' => array(),
            'score' => 0,
            'max_score' => 5
        );
        
        if (function_exists('rest_get_server')) {
            $server = rest_get_server();
            $routes = $server->get_routes();
            
            $expected_routes = array(
                '/mthfr/v1/health',
                '/mthfr/v1/debug',
                '/mthfr/v1/generate-report',
                '/mthfr/v1/test-db',
                '/mthfr/v1/test-pdf'
            );
            
            foreach ($expected_routes as $route) {
                $registered = isset($routes[$route]);
                $tests['api_routes']['details'][$route] = $registered ? 'registered' : 'missing';
                if ($registered) $tests['api_routes']['score']++;
            }
        } else {
            $tests['api_routes']['status'] = 'error';
            $tests['api_routes']['details']['error'] = 'REST server not available';
        }
        
        if ($tests['api_routes']['score'] < $tests['api_routes']['max_score']) {
            $tests['api_routes']['status'] = 'warning';
        }
        
        return $tests;
    }
    
    /**
     * AJAX handler for testing individual endpoints
     */
    public function ajax_test_endpoint() {
        check_ajax_referer('mthfr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $endpoint_url = sanitize_url($_POST['endpoint_url']);
        $method = sanitize_text_field($_POST['method']);
        
        $result = self::test_endpoint_availability($endpoint_url, $method);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for refreshing debug info
     */
    public function ajax_refresh_debug() {
        check_ajax_referer('mthfr_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $debug_info = self::get_comprehensive_debug_info();
        
        wp_send_json_success($debug_info);
    }
    
    /**
     * Handle manual debug actions
     */
    public function handle_manual_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Force route registration
        if (isset($_GET['mthfr_action']) && $_GET['mthfr_action'] === 'force_register_routes') {
            if (class_exists('MTHFR_API_Endpoints')) {
                $api = new MTHFR_API_Endpoints();
                $api->register_routes();
                flush_rewrite_rules();
                
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>Routes registration forced and rewrite rules flushed.</p></div>';
                });
            }
        }
        
        // Test all endpoints
        if (isset($_GET['mthfr_action']) && $_GET['mthfr_action'] === 'test_all_endpoints') {
            $results = self::get_available_endpoints();
            set_transient('mthfr_endpoint_test_results', $results, 300); // 5 minutes
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info"><p>All endpoints tested. Check results in the debug panel.</p></div>';
            });
        }
    }
    
    /**
     * Generate diagnostic report
     */
    public static function generate_diagnostic_report() {
        $debug_info = self::get_comprehensive_debug_info();
        
        $report = "=== MTHFR Plugin Diagnostic Report ===\n";
        $report .= "Generated: " . $debug_info['timestamp'] . "\n\n";
        
        // Plugin Status
        $report .= "PLUGIN STATUS:\n";
        $report .= "Version: " . $debug_info['plugin_status']['version'] . "\n";
        $report .= "Classes Loaded: " . count(array_filter($debug_info['plugin_status']['classes_loaded'])) . "/" . count($debug_info['plugin_status']['classes_loaded']) . "\n\n";
        
        // API Status
        $report .= "API STATUS:\n";
        $report .= "REST API Available: " . ($debug_info['api_status']['rest_api_available'] ? 'YES' : 'NO') . "\n";
        $report .= "Namespace Registered: " . ($debug_info['api_status']['namespace_registered'] ? 'YES' : 'NO') . "\n";
        $report .= "Registered Routes: " . count($debug_info['api_status']['registered_routes']) . "\n\n";
        
        // Database Status
        $report .= "DATABASE STATUS:\n";
        $report .= "Connection: " . ($debug_info['database_status']['connection'] ? 'OK' : 'FAILED') . "\n";
        foreach ($debug_info['database_status']['tables'] as $table) {
            $report .= "Table {$table['name']}: " . ($table['exists'] ? "EXISTS ({$table['count']} records)" : 'MISSING') . "\n";
        }
        
        // Test Results
        $report .= "\nTEST RESULTS:\n";
        foreach ($debug_info['test_results'] as $test) {
            $report .= "{$test['name']}: {$test['status']} ({$test['score']}/{$test['max_score']})\n";
        }
        
        return $report;
    }
}