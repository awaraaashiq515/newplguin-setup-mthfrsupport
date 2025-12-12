<?php
/**
 * Plugin Name: MTHFR Genetic Reports old
 * Plugin URI: https://mthfrsupport.org
 * Description: Generate genetic analysis reports with PDF output and REST API endpoints
 * Version: 1.0.5
 * Author: Tarun botdigit
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MTHFR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MTHFR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MTHFR_PLUGIN_VERSION', '1.0.5');

// IMMEDIATE LOADING - Load files right away, not on hooks
function mthfr_load_dependencies() {
    $required_files = array(
        'includes/class-database.php',
        'includes/class-genetic-data.php',
        'includes/class-pdf-generator.php', 
        'includes/class-report-generator.php',
        'includes/class-woocommerce-integration.php',
        'includes/class-admin-handler.php',
         'includes/class-zip-processor.php',
        'includes/class-excel-database.php',
        'api/class-api-endpoints.php'
    );
    
    $loaded_classes = array();
    $errors = array();
    
    foreach ($required_files as $file) {
        $file_path = MTHFR_PLUGIN_PATH . $file;
        
        if (!file_exists($file_path)) {
            $errors[] = "Missing file: {$file}";
            continue;
        }
        
        try {
            require_once $file_path;
            error_log("MTHFR: Loaded {$file}");
        } catch (Exception $e) {
            $errors[] = "Error loading {$file}: " . $e->getMessage();
            error_log("MTHFR: Error loading {$file} - " . $e->getMessage());
        }
    }
    
    // Check which classes are now available
    $expected_classes = array(
        'MTHFR_Database',
        'MTHFR_Genetic_Data', 
        'MTHFR_PDF_Generator',
        'MTHFR_Report_Generator',
        'MTHFR_WooCommerce_Integration',
        'MTHFR_Admin',
        'MTHFR_ZIP_Processor',
        'MTHFR_Excel_Database',
        'MTHFR_API_Endpoints'
    );
    
    foreach ($expected_classes as $class) {
        if (class_exists($class)) {
            $loaded_classes[] = $class;
            
        } else {
            $errors[] = "Class {$class} not found after loading";
            error_log("MTHFR: Class {$class} NOT available");
        }
    }
    
    return array(
        'success' => empty($errors),
        'loaded_classes' => $loaded_classes,
        'errors' => $errors
    );
}

// Load dependencies immediately when this file is included
$mthfr_load_result = mthfr_load_dependencies();

class MTHFR_Genetic_Reports {
    
    private static $instance = null;
    private static $loading_result = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $mthfr_load_result;
        self::$loading_result = $mthfr_load_result;
        
        // Initialize immediately, not on hooks
        $this->init_immediately();
        
        // Admin interface
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_notices', array($this, 'show_admin_notices'));
        }
        
        // AJAX handlers
        add_action('wp_ajax_mthfr_test_loading', array($this, 'ajax_test_loading'));
        
        // WordPress hooks
        add_action('init', array($this, 'late_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    private function init_immediately() {
    
        
        // Initialize components right away
        try {
            if (class_exists('MTHFR_Database')) {
                new MTHFR_Database();
         
            } else {
                error_log('MTHFR: Database class NOT available for immediate init');
            }
            
            if (class_exists('MTHFR_API_Endpoints')) {
                // Initialize API endpoints immediately
                add_action('rest_api_init', function() {
                    if (class_exists('MTHFR_API_Endpoints')) {
                        new MTHFR_API_Endpoints();
                       
                    }
                });
                
                // Also try to initialize on init as backup
                add_action('init', function() {
                    if (class_exists('MTHFR_API_Endpoints')) {
                        new MTHFR_API_Endpoints();
                    
                    }
                });
                
             
            } else {
                error_log('MTHFR: API Endpoints class NOT available for immediate init');
            }
            
        } catch (Exception $e) {
            error_log('MTHFR: Error during immediate initialization: ' . $e->getMessage());
        }
        
       
    }
    
    public function late_init() {
        // This runs on WordPress 'init' hook as a backup
      
        
        if (class_exists('MTHFR_API_Endpoints')) {
           
        } else {
            error_log('MTHFR: API Endpoints NOT available on late init');
        }
    }
    
    public function show_admin_notices() {
        if (current_user_can('manage_options')) {
            $result = self::$loading_result;
            
            if (!$result['success']) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>MTHFR Plugin Loading Errors:</strong></p>';
                echo '<ul>';
                foreach ($result['errors'] as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>MTHFR Plugin:</strong> All classes loaded successfully! ';
                echo 'Loaded: ' . implode(', ', $result['loaded_classes']);
                echo '</p></div>';
            }
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'MTHFR Reports',
            'MTHFR Reports',
            'manage_options',
            'mthfr-reports',
            array($this, 'admin_page'),
            'dashicons-analytics',
            30
        );
        
        add_submenu_page(
            'mthfr-reports',
            'Status',
            'Status',
            'manage_options',
            'mthfr-status',
            array($this, 'status_page')
        );
        
        add_submenu_page(
            'mthfr-reports',
            'API Test',
            'API Test',
            'manage_options',
            'mthfr-api-test',
            array($this, 'api_test_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>MTHFR Genetic Reports</h1>
            
            <div class="card">
                <h2>Current Status</h2>
                <?php $this->show_current_status(); ?>
            </div>
            
            <div class="card">
                <h2>Loading Results</h2>
                <?php $this->show_loading_results(); ?>
            </div>
            
            <div class="card">
                <h2>Quick Actions</h2>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=mthfr-status'); ?>" class="button">Detailed Status</a>
                    <a href="<?php echo admin_url('admin.php?page=mthfr-api-test'); ?>" class="button button-primary">Test API</a>
                    <a href="<?php echo rest_url('mthfr/v1/health'); ?>" target="_blank" class="button">Open API Health</a>
                </p>
            </div>
        </div>
        <?php
    }
    
    public function status_page() {
        ?>
        <div class="wrap">
            <h1>Plugin Status</h1>
            
            <div class="card">
                <h2>Component Status</h2>
                <?php $this->show_component_status(); ?>
            </div>
            
            <div class="card">
                <h2>API Endpoints Test</h2>
                <?php $this->test_api_endpoints(); ?>
            </div>
            
            <div class="card">
                <h2>Database Test</h2>
                <?php $this->test_database(); ?>
            </div>
        </div>
        <?php
    }
    
    public function api_test_page() {
        ?>
        <div class="wrap">
            <h1>API Testing</h1>
            
            <div class="card">
                <h2>Live API Tests</h2>
                <div id="api-test-results">
                    <p><button id="test-health" class="button">Test Health Endpoint</button> <span id="health-result"></span></p>
                    <p><button id="test-debug" class="button">Test Debug Endpoint</button> <span id="debug-result"></span></p>
                    <p><button id="test-generate" class="button">Test Generate Report</button> <span id="generate-result"></span></p>
                </div>
                
                <script>
                document.getElementById('test-health').addEventListener('click', function() {
                    testEndpoint('<?php echo rest_url('mthfr/v1/health'); ?>', 'health-result');
                });
                
                document.getElementById('test-debug').addEventListener('click', function() {
                    testEndpoint('<?php echo rest_url('mthfr/v1/debug'); ?>', 'debug-result');
                });
                
                document.getElementById('test-generate').addEventListener('click', function() {
                    testEndpointPost('<?php echo rest_url('mthfr/v1/generate-report'); ?>', {
                        upload_id: 1,
                        order_id: 123,
                        product_name: 'Test Product'
                    }, 'generate-result');
                });
                
                function testEndpoint(url, resultId) {
                    var resultSpan = document.getElementById(resultId);
                    resultSpan.innerHTML = 'Testing...';
                    
                    fetch(url)
                    .then(response => {
                        return response.json().then(data => {
                            resultSpan.innerHTML = response.ok ? '✅ Success' : '❌ Failed (HTTP ' + response.status + ')';
                        });
                    })
                    .catch(error => {
                        resultSpan.innerHTML = '❌ Error: ' + error.message;
                    });
                }
                
                function testEndpointPost(url, data, resultId) {
                    var resultSpan = document.getElementById(resultId);
                    resultSpan.innerHTML = 'Testing...';
                    
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        return response.json().then(data => {
                            resultSpan.innerHTML = response.ok ? '✅ Success' : '❌ Failed (HTTP ' + response.status + ')';
                        });
                    })
                    .catch(error => {
                        resultSpan.innerHTML = '❌ Error: ' + error.message;
                    });
                }
                </script>
            </div>
        </div>
        <?php
    }
    
    private function show_current_status() {
        echo '<table class="form-table">';
        echo '<tr><th>Plugin Version</th><td>' . MTHFR_PLUGIN_VERSION . '</td></tr>';
        echo '<tr><th>WordPress Version</th><td>' . get_bloginfo('version') . '</td></tr>';
        echo '<tr><th>PHP Version</th><td>' . PHP_VERSION . '</td></tr>';
        
        $classes = array(
            'MTHFR_Database' => 'Database operations',
            'MTHFR_API_Endpoints' => 'REST API endpoints', 
            'MTHFR_Report_Generator' => 'Report generation',
            'MTHFR_PDF_Generator' => 'PDF creation',
            'MTHFR_Genetic_Data' => 'Genetic data processing'
        );
        
        foreach ($classes as $class => $desc) {
            $status = class_exists($class) ? '✅ Loaded' : '❌ Missing';
            echo '<tr><th>' . esc_html($class) . '</th><td>' . $status . '</td></tr>';
        }
        
        echo '</table>';
    }
    
    private function show_loading_results() {
        $result = self::$loading_result;
        
        if ($result['success']) {
            echo '<p style="color: green;"><strong>✅ All files loaded successfully!</strong></p>';
            echo '<p><strong>Loaded classes:</strong> ' . implode(', ', $result['loaded_classes']) . '</p>';
        } else {
            echo '<p style="color: red;"><strong>❌ Loading errors detected:</strong></p>';
            echo '<ul>';
            foreach ($result['errors'] as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
        }
    }
    
    private function show_component_status() {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Component</th><th>Status</th><th>Details</th></tr></thead>';
        echo '<tbody>';
        
        $components = array(
            'MTHFR_Database' => 'Database operations',
            'MTHFR_API_Endpoints' => 'REST API endpoints', 
            'MTHFR_Report_Generator' => 'Report generation',
            'MTHFR_PDF_Generator' => 'PDF creation',
            'MTHFR_WooCommerce_Integration' => 'Woo integration',
            'MTHFR_Genetic_Data' => 'Genetic data processing'
        );
        
        foreach ($components as $class => $desc) {
            $exists = class_exists($class);
            $status = $exists ? '✅ Available' : '❌ Missing';
            $details = $exists ? 'Class loaded and ready' : 'Class not found';
            
            echo '<tr>';
            echo '<td><code>' . esc_html($class) . '</code></td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . esc_html($details) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function test_api_endpoints() {
        $endpoints = array(
            '/health' => 'Health check endpoint',
            '/debug' => 'Debug information endpoint'
        );
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Endpoint</th><th>URL</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($endpoints as $endpoint => $desc) {
            $url = rest_url('mthfr/v1' . $endpoint);
            
            // Test the endpoint
            $response = wp_remote_get($url, array('timeout' => 10));
            
            if (is_wp_error($response)) {
                $status = '❌ Error: ' . $response->get_error_message();
            } else {
                $code = wp_remote_retrieve_response_code($response);
                $status = ($code == 200) ? '✅ Working' : "❌ HTTP {$code}";
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($desc) . '</td>';
            echo '<td><a href="' . esc_url($url) . '" target="_blank">' . esc_html($endpoint) . '</a></td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function test_database() {
        if (class_exists('MTHFR_Database')) {
            $db_test = MTHFR_Database::test_connection();
            $upload_count = MTHFR_Database::get_upload_count();
            $report_count = MTHFR_Database::get_report_count();
            
            echo '<table class="form-table">';
            echo '<tr><th>Database Connection</th><td>' . ($db_test['status'] === 'success' ? '✅ Connected' : '❌ Failed') . '</td></tr>';
            echo '<tr><th>Upload Records</th><td>' . esc_html($upload_count) . '</td></tr>';
            echo '<tr><th>Report Records</th><td>' . esc_html($report_count) . '</td></tr>';
            echo '</table>';
        } else {
            echo '<p style="color: red;">Database class not available for testing</p>';
        }
    }
    
    public function ajax_test_loading() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $status = array();
        $classes = array('MTHFR_Database', 'MTHFR_API_Endpoints', 'MTHFR_Report_Generator');
        
        foreach ($classes as $class) {
            $status[] = $class . ': ' . (class_exists($class) ? 'LOADED' : 'MISSING');
        }
        
        wp_send_json_success(implode(', ', $status));
    }
    
    public function activate() {
        
        
        if (class_exists('MTHFR_Database')) {
            MTHFR_Database::create_tables();
       
        }
        
        // Flush rewrite rules for API endpoints
        flush_rewrite_rules();
        
        error_log('MTHFR: Plugin activated successfully');
    }
    
    public function deactivate() {
        flush_rewrite_rules();
        error_log('MTHFR: Plugin deactivated');
    }
}

// Initialize plugin immediately
MTHFR_Genetic_Reports::get_instance();

// Debug function for direct access
if (isset($_GET['mthfr_debug_direct']) && current_user_can('manage_options')) {
    echo '<pre>';
    echo "MTHFR Debug Direct\n";
    echo "==================\n";
    echo "Timestamp: " . current_time('c') . "\n\n";
    
    $classes = array('MTHFR_Database', 'MTHFR_API_Endpoints', 'MTHFR_Report_Generator', 'MTHFR_Genetic_Data', 'MTHFR_PDF_Generator');
    foreach ($classes as $class) {
        echo "{$class}: " . (class_exists($class) ? 'LOADED' : 'NOT LOADED') . "\n";
    }
    
    echo "\nHealth endpoint test:\n";
    $health_url = rest_url('mthfr/v1/health');
    echo "URL: {$health_url}\n";
    
    $response = wp_remote_get($health_url, array('timeout' => 5));
    if (is_wp_error($response)) {
        echo "Error: " . $response->get_error_message() . "\n";
    } else {
        echo "HTTP Code: " . wp_remote_retrieve_response_code($response) . "\n";
        $body = wp_remote_retrieve_body($response);
        echo "Response: " . substr($body, 0, 200) . "...\n";
    }
    
    echo '</pre>';
    exit;
}

