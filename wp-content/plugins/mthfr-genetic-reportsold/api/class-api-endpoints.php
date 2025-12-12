<?php
/**
 * MTHFR API Endpoints Class
 * Handles REST API endpoints with proper JSON responses
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_API_Endpoints {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('init', array($this, 'register_routes_backup'));
        // error_log('MTHFR: API Endpoints constructor called');
    }
    
    public function register_routes_backup() {
        // Only register if rest_api_init hasn't fired yet
        if (did_action('rest_api_init') === 0) {
            $this->register_routes();
        }
    }
    
    public function register_routes() {
        // Only register routes if WordPress REST API is available
        if (!function_exists('register_rest_route')) {
            error_log('MTHFR: register_rest_route function not available');
            return;
        }
        
        error_log('MTHFR: Attempting to register REST routes');
        
        try {
            // Health check endpoint - completely public
            $health_registered = register_rest_route('mthfr/v1', '/health', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'health_check'),
                'permission_callback' => '__return_true',
                'show_in_index' => true
            ));
            
            error_log('MTHFR: Health route registered: ' . ($health_registered ? 'SUCCESS' : 'FAILED'));
            
            // Debug info endpoint - public for now to test
            $debug_registered = register_rest_route('mthfr/v1', '/debug', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'debug_info'),
                'permission_callback' => '__return_true', // Changed to public for testing
                'show_in_index' => true
            ));
            
            // error_log('MTHFR: Debug route registered: ' . ($debug_registered ? 'SUCCESS' : 'FAILED'));
            
            // Test endpoint for basic functionality
            register_rest_route('mthfr/v1', '/test', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'simple_test'),
                'permission_callback' => '__return_true'
            ));
            
            // Generate report endpoint
            $report_registered = register_rest_route('mthfr/v1', '/generate-report', array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'generate_report'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'upload_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param) && $param > 0;
                        }
                    ),
                    'order_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param) && $param > 0;
                        }
                    ),
                    'product_name' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'Unknown Product',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'has_subscription' => array(
                        'required' => false,
                        'type' => 'boolean',
                        'default' => false
                    )
                )
            ));
            
            error_log('MTHFR: Report route registered: ' . ($report_registered ? 'SUCCESS' : 'FAILED'));
            
            // Additional endpoints
            register_rest_route('mthfr/v1', '/test-db', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'test_database'),
                'permission_callback' => array($this, 'check_permissions')
            ));
            
            register_rest_route('mthfr/v1', '/test-pdf', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'test_pdf'),
                'permission_callback' => array($this, 'check_permissions')
            ));
            
            register_rest_route('mthfr/v1', '/report-status/(?P<order_id>\d+)', array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_report_status'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'order_id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param) && $param > 0;
                        }
                    )
                )
            ));
            
            // error_log('MTHFR: All REST routes registration completed');
            
        } catch (Exception $e) {
            error_log('MTHFR: Failed to register API routes - ' . $e->getMessage());
        }
    }
    
    public function check_permissions($request) {
        return current_user_can('manage_options');
    }
    
    public function simple_test($request) {
        // Ultra simple test endpoint
        return rest_ensure_response(array(
            'status' => 'success',
            'message' => 'API is working',
            'timestamp' => current_time('c'),
            'method' => $request->get_method()
        ));
    }
    
    public function health_check($request) {
        try {
            // error_log('MTHFR: Health check endpoint called');
            
            // Ensure we return proper JSON
            $response_data = array(
                'status' => 'healthy',
                'timestamp' => current_time('c'),
                'version' => defined('MTHFR_PLUGIN_VERSION') ? MTHFR_PLUGIN_VERSION : 'Unknown',
                'message' => 'All systems operational'
            );
            
            // Test basic functionality
            if (class_exists('MTHFR_Genetic_Data')) {
                $test_data = MTHFR_Genetic_Data::create_sample_data(3);
                $response_data['genetic_data_test'] = count($test_data) > 0 ? 'working' : 'error';
                $response_data['sample_data_count'] = count($test_data);
            } else {
                $response_data['genetic_data_test'] = 'class_missing';
            }
            
            // Test database connection
            if (class_exists('MTHFR_Database')) {
                $db_status = MTHFR_Database::test_connection();
                $response_data['database'] = $db_status['status'];
                $response_data['database_message'] = $db_status['message'];
            } else {
                $response_data['database'] = 'class_missing';
            }
            
if (class_exists('MTHFR_PDF_Generator') && method_exists('MTHFR_PDF_Generator', 'test_generation')) {
    $pdf_status = MTHFR_PDF_Generator::test_generation();
    $response_data['pdf_generation'] = $pdf_status['status'];
} elseif (class_exists('MTHFR_PDF_Generator')) {
    $response_data['pdf_generation'] = 'method_missing';
} else {
    $response_data['pdf_generation'] = 'class_missing';
}
            
            // Component status
            $response_data['components'] = array(
                'MTHFR_Database' => class_exists('MTHFR_Database'),
                'MTHFR_Genetic_Data' => class_exists('MTHFR_Genetic_Data'),
                'MTHFR_PDF_Generator' => class_exists('MTHFR_PDF_Generator'),
                'MTHFR_Report_Generator' => class_exists('MTHFR_Report_Generator'),
                'MTHFR_API_Endpoints' => class_exists('MTHFR_API_Endpoints')
            );
            
            // System info
            $response_data['system'] = array(
                'wordpress_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'plugin_path' => MTHFR_PLUGIN_PATH,
                'rest_url_base' => rest_url('mthfr/v1/')
            );
            
            // error_log('MTHFR: Health check data prepared');
            
            // Use rest_ensure_response to ensure proper JSON
            return rest_ensure_response($response_data);
            
        } catch (Exception $e) {
            error_log('MTHFR: Health check error: ' . $e->getMessage());
            
            return rest_ensure_response(array(
                'status' => 'unhealthy',
                'timestamp' => current_time('c'),
                'error' => $e->getMessage(),
                'message' => 'System experiencing issues'
            ));
        }
    }
    
    public function debug_info($request) {
        try {
            // error_log('MTHFR: Debug info endpoint called');
            
            $debug_data = array(
                'timestamp' => current_time('c'),
                'endpoint' => 'debug',
                'status' => 'success'
            );
            
            if (class_exists('MTHFR_Report_Generator')) {
                $debug_data['report_generator_debug'] = MTHFR_Report_Generator::get_debug_info();
            } else {
                $debug_data['error'] = 'MTHFR_Report_Generator class not available';
            }
            
            $debug_data['classes_available'] = array(
                'MTHFR_Database' => class_exists('MTHFR_Database'),
                'MTHFR_Genetic_Data' => class_exists('MTHFR_Genetic_Data'),
                'MTHFR_PDF_Generator' => class_exists('MTHFR_PDF_Generator'),
                'MTHFR_Report_Generator' => class_exists('MTHFR_Report_Generator')
            );
            
            // Request information
            $debug_data['request_info'] = array(
                'method' => $request->get_method(),
                'route' => $request->get_route(),
                'params' => $request->get_params(),
                'headers' => $request->get_headers()
            );
            
            return rest_ensure_response($debug_data);
            
        } catch (Exception $e) {
            error_log('MTHFR: Debug info error: ' . $e->getMessage());
            
            return rest_ensure_response(array(
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => current_time('c')
            ));
        }
    }
    
    public function generate_report($request) {
        try {
            // error_log('MTHFR: Generate report endpoint called');
            
            $upload_id = $request->get_param('upload_id');
            $order_id = $request->get_param('order_id');
            $product_name = $request->get_param('product_name') ?: 'Unknown Product';
            $has_subscription = $request->get_param('has_subscription') ?: false;
            
            // Log the request
            // error_log("MTHFR: API Report generation request - Upload ID: {$upload_id}, Order ID: {$order_id}, Product: {$product_name}");
            
            // Check if Report Generator class is available
            if (!class_exists('MTHFR_Report_Generator')) {
                throw new Exception('Report Generator class not available');
            }
            
            // Generate the report
            $result = MTHFR_Report_Generator::generate_report(
                $upload_id,
                $order_id,
                $product_name,
                $has_subscription
            );
            
            $status_code = $result['success'] ? 200 : 400;
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            error_log("MTHFR: API Report generation error - " . $e->getMessage());
            
            return rest_ensure_response(array(
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => current_time('c')
            ));
        }
    }
    
    public function get_report_status($request) {
        try {
            $order_id = $request->get_param('order_id');
            
            if (!class_exists('MTHFR_Report_Generator')) {
                throw new Exception('Report Generator class not available');
            }
            
            $result = MTHFR_Report_Generator::get_report_status($order_id);
            
            $status_code = $result['success'] ? 200 : 404;
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => current_time('c')
            ));
        }
    }
    
    public function test_database($request) {
        try {
            if (!class_exists('MTHFR_Database')) {
                throw new Exception('Database class not available');
            }
            
            $result = MTHFR_Database::test_connection();
            
            // Add additional database tests
            global $wpdb;
            $tables_test = array(
                'uploads_table_exists' => $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}user_uploads'") == $wpdb->prefix . 'user_uploads',
                'reports_table_exists' => $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}user_reports'") == $wpdb->prefix . 'user_reports'
            );
            
            $result['tables'] = $tables_test;
            $result['upload_count'] = MTHFR_Database::get_upload_count();
            $result['report_count'] = MTHFR_Database::get_report_count();
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => current_time('c')
            ));
        }
    }
    
    public function test_pdf($request) {
        try {
            if (!class_exists('MTHFR_PDF_Generator')) {
                throw new Exception('PDF Generator class not available');
            }
            
            $result = MTHFR_PDF_Generator::test_generation();
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => current_time('c')
            ));
        }
    }
}