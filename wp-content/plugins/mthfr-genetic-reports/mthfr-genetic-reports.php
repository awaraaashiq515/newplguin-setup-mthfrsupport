<?php
/**
 * Plugin Name: MTHFR Genetic Reports
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
define('MTHFR_DB_VERSION', '1.1');

// Load Composer dependencies
require_once MTHFR_PLUGIN_PATH . 'vendor/autoload.php';

// Load Composer autoloader for mPDF dependencies
if (file_exists(MTHFR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once MTHFR_PLUGIN_PATH . 'vendor/autoload.php';
}

// IMMEDIATE LOADING - Load files right away, not on hooks
function mthfr_load_dependencies() {
    $required_files = array(
        'src/Core/Database/Database.php',
        'includes/class-genetic-data.php',
        'src/Core/Database/ExcelDatabase.php',
        'includes/class-report-utils.php',
        'src/Core/Report/ReportGenerator.php',
        'src/Core/PDF/PdfGenerator.php',
        'src/Core/PDF/PdfContentGenerator.php',
        'src/Core/PDF/PdfBookmarkGenerator.php',
        'src/Core/PDF/PdfUtils.php',
        'src/Core/PDF/PdfHtmlHeadGenerator.php',
        'src/Core/PDF/PdfHeaderGenerator.php',
        'src/Core/PDF/PdfStatsSummaryGenerator.php',
        'src/Core/PDF/PdfCategoriesContentGenerator.php',
        'src/Core/PDF/PdfFiguresContentGenerator.php',
        'src/Core/PDF/PdfDisclaimerGenerator.php',
        'includes/class-woocommerce-integration.php',
        'includes/class-admin-handler.php',
        'includes/class-zip-processor.php',
        'includes/class-async-report-generator.php',
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
        'Genetic_Database',
        'MTHFR\Core\Database\Database',
        'MTHFR_Genetic_Data',
        'MTHFR\Core\Database\ExcelDatabase',
        'MTHFR\PDF\PdfGenerator',
        'MTHFR_Report_Generator',
        'MTHFR_WooCommerce_Integration',
        'MTHFR_Admin',
        'MTHFR_ZIP_Processor',
        'MTHFR\PDF\PdfUtils',
        'MTHFR_Report_Utils',
        'MTHFR\PDF\PdfContentGenerator',
        'MTHFR\PDF\PdfBookmarkGenerator',
        'MTHFR\PDF\PdfHtmlHeadGenerator',
        'MTHFR\PDF\PdfHeaderGenerator',
        'MTHFR\PDF\PdfStatsSummaryGenerator',
        'MTHFR\PDF\PdfCategoriesContentGenerator',
        'MTHFR\PDF\PdfFiguresContentGenerator',
        'MTHFR\PDF\PdfDisclaimerGenerator',
        'MTHFR_Async_Report_Generator',
        'MTHFR_API_Endpoints'
    );

    // Create class aliases for backward compatibility before validation
    if (class_exists('MTHFR\\PDF\\PdfGenerator') && !class_exists('MTHFR_PDF_Generator')) {
        class_alias('MTHFR\\PDF\\PdfGenerator', 'MTHFR_PDF_Generator');
        error_log('MTHFR: Created class alias MTHFR_PDF_Generator -> MTHFR\\PDF\\PdfGenerator');
    }

    // Create class alias for ReportGenerator backward compatibility
    if (class_exists('MTHFR\\Core\\Report\\ReportGenerator') && !class_exists('MTHFR_Report_Generator')) {
        class_alias('MTHFR\\Core\\Report\\ReportGenerator', 'MTHFR_Report_Generator');
        error_log('MTHFR: Created class alias MTHFR_Report_Generator -> MTHFR\\Core\\Report\\ReportGenerator');
    }

    // Create class alias for Database backward compatibility
    if (class_exists('MTHFR\\Core\\Database\\Database') && !class_exists('MTHFR_Database')) {
        class_alias('MTHFR\\Core\\Database\\Database', 'MTHFR_Database');
        error_log('MTHFR: Created class alias MTHFR_Database -> MTHFR\\Core\\Database\\Database');
    }

    // Create class alias for ExcelDatabase backward compatibility
    if (class_exists('MTHFR\\Core\\Database\\ExcelDatabase') && !class_exists('Genetic_Database')) {
        class_alias('MTHFR\\Core\\Database\\ExcelDatabase', 'Genetic_Database');
        error_log('MTHFR: Created class alias Genetic_Database -> MTHFR\\Core\\Database\\ExcelDatabase');
    }

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
        add_action('wp_ajax_mthfr_import_upload', array($this, 'ajax_import_upload'));
        add_action('wp_ajax_mthfr_import_reimport', array($this, 'ajax_import_reimport'));

        // WordPress hooks
        add_action('init', array($this, 'late_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    private function init_immediately() {


        // Initialize components right away
        try {
            if (class_exists('MTHFR\\Core\\Database\\Database')) {
                new \MTHFR\Core\Database\Database();

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

        add_submenu_page(
            'mthfr-reports',
            'Data Import',
            'Data Import',
            'manage_options',
            'mthfr-data-import',
            array($this, 'data_import_page')
        );

        add_submenu_page(
            'mthfr-reports',
            'Cache Test',
            'Cache Test',
            'manage_options',
            'mthfr-cache-test',
            array($this, 'cache_test_page')
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

    public function data_import_page() {
        // Handle form submissions
        if (isset($_POST['action'])) {
            $this->handle_import_actions();
        }

        ?>
        <div class="wrap">
            <h1>Data Import Tool</h1>

            <div class="card">
                <h2>Database Statistics</h2>
                <?php $this->show_database_statistics(); ?>
            </div>

            <div class="card">
                <h2>Upload XLSX File</h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('mthfr_import_upload', 'mthfr_import_nonce'); ?>
                    <input type="hidden" name="action" value="upload_import">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Select XLSX File</th>
                            <td>
                                <input type="file" name="xlsx_file" accept=".xlsx,.xls" required>
                                <p class="description">Upload an XLSX file containing genetic variant data from the old plugin.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Upload and Import', 'primary', 'submit', false); ?>
                </form>
            </div>

            <div class="card">
                <h2>Re-import from Old Plugin Directory</h2>
                <form method="post">
                    <?php wp_nonce_field('mthfr_import_reimport', 'mthfr_import_nonce'); ?>
                    <input type="hidden" name="action" value="reimport_old">
                    <p>Re-import data from existing XLSX files in the old plugin directory.</p>
                    <p class="description">This will process: Database.xlsx, Database_0.xlsx, current_Database.xlsx</p>
                    <?php submit_button('Re-import Old Data', 'secondary', 'submit', false); ?>
                </form>
            </div>

            <div class="card">
                <h2>Database Management</h2>
                <form method="post" onsubmit="return confirm('Are you sure you want to clear all data? This action cannot be undone.');">
                    <?php wp_nonce_field('mthfr_clear_database', 'mthfr_import_nonce'); ?>
                    <input type="hidden" name="action" value="clear_database">
                    <p><strong>Warning:</strong> This will permanently delete all genetic variant data, categories, and tags from the database.</p>
                    <?php submit_button('Clear All Data', 'delete button-primary', 'submit', false); ?>
                </form>
            </div>

            <div id="import-progress" style="display: none;">
                <div class="card">
                    <h2>Import Progress</h2>
                    <div id="progress-content">
                        <p>Processing import... Please wait.</p>
                        <div class="spinner is-active" style="float: none; margin: 10px auto;"></div>
                    </div>
                </div>
            </div>

            <div id="import-results" style="display: none;">
                <div class="card">
                    <h2>Import Results</h2>
                    <div id="results-content"></div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('form').on('submit', function(e) {
                var $form = $(this);
                var action = $form.find('input[name="action"]').val();

                if (action === 'upload_import' || action === 'reimport_old') {
                    e.preventDefault();
                    $('#import-progress').show();
                    $('#import-results').hide();

                    var formData = new FormData(this);

                    var ajax_action = (action === 'upload_import') ? 'mthfr_import_upload' : 'mthfr_import_reimport';
                    formData.append('action', ajax_action);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#import-progress').hide();
                            $('#import-results').show();
                            if (response.success) {
                                $('#results-content').html(response.data);
                                // Refresh statistics after a short delay
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                $('#results-content').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#import-progress').hide();
                            $('#import-results').show();
                            $('#results-content').html('<div class="notice notice-error"><p>Error: ' + error + '</p></div>');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    public function cache_test_page() {
        if (isset($_GET['run_cache_test'])) {
            require_once plugin_dir_path(__FILE__) . 'test-caching.php';
        } else {
            ?>
            <div class="wrap">
                <h1>MTHFR Caching Performance Test</h1>

                <div class="card">
                    <h2>Test Caching Performance</h2>
                    <p>This test will measure the performance difference between cached and uncached database queries.</p>
                    <p><a href="<?php echo admin_url('admin.php?page=mthfr-cache-test&run_cache_test=1'); ?>" class="button button-primary">Run Cache Performance Test</a></p>
                    <p><em>Note: This will temporarily clear caches to test performance accurately.</em></p>
                </div>
            </div>
            <?php
        }
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

    private function show_database_statistics() {
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

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Data Type</th><th>Count</th><th>Description</th></tr></thead>';
        echo '<tbody>';
        echo '<tr><td><strong>Genetic Variants</strong></td><td>' . esc_html($stats['variants']) . '</td><td>Unique RSID entries</td></tr>';
        echo '<tr><td><strong>Categories</strong></td><td>' . esc_html($stats['categories']) . '</td><td>Unique category names</td></tr>';
        echo '<tr><td><strong>Tags</strong></td><td>' . esc_html($stats['tags']) . '</td><td>Unique tag names</td></tr>';
        echo '<tr><td><strong>Pathways</strong></td><td>' . esc_html($stats['pathways']) . '</td><td>Pathway definitions</td></tr>';
        echo '<tr><td><strong>Variant-Category Links</strong></td><td>' . esc_html($stats['category_relationships']) . '</td><td>RSID to category associations</td></tr>';
        echo '<tr><td><strong>Variant-Tag Links</strong></td><td>' . esc_html($stats['tag_relationships']) . '</td><td>RSID to tag associations</td></tr>';
        echo '</tbody></table>';

        // Check for old plugin directory
        $old_plugin_dir = plugin_dir_path(__FILE__) . '../mthfr-genetic-reportsold/data';
        $old_files_exist = false;
        $old_files = array();

        if (file_exists($old_plugin_dir)) {
            $files = glob($old_plugin_dir . '/*.xlsx');
            if (!empty($files)) {
                $old_files_exist = true;
                foreach ($files as $file) {
                    $old_files[] = basename($file);
                }
            }
        }

        if ($old_files_exist) {
            echo '<div class="notice notice-info" style="margin-top: 15px;">';
            echo '<p><strong>Old Plugin Data Found:</strong> ' . implode(', ', $old_files) . '</p>';
            echo '<p>These files can be re-imported using the "Re-import Old Data" option below.</p>';
            echo '</div>';
        }
    }

    private function handle_import_actions() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $action = sanitize_text_field($_POST['action']);

        switch ($action) {
            case 'upload_import':
                $this->handle_file_upload();
                break;
            case 'reimport_old':
                $this->handle_reimport_old();
                break;
            case 'clear_database':
                $this->handle_clear_database();
                break;
        }
    }

    private function handle_file_upload() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['mthfr_import_nonce'], 'mthfr_import_upload')) {
            wp_die('Security check failed');
        }

        if (empty($_FILES['xlsx_file']['tmp_name'])) {
            wp_die('No file uploaded');
        }

        // Validate file type
        $file_type = wp_check_filetype($_FILES['xlsx_file']['name']);
        if (!in_array($file_type['ext'], array('xlsx', 'xls'))) {
            wp_die('Invalid file type. Only XLSX and XLS files are allowed.');
        }

        // Move uploaded file to temp location
        $temp_file = $_FILES['xlsx_file']['tmp_name'];
        $file_name = sanitize_file_name($_FILES['xlsx_file']['name']);

        // Import the file
        require_once plugin_dir_path(__FILE__) . 'classes/class-database-importer.php';
        $importer = new MTHFR_Database_Importer();
        $result = $importer->import_from_xlsx(array($temp_file));

        // Display results
        $this->display_import_results($result);
    }

    private function handle_reimport_old() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['mthfr_import_nonce'], 'mthfr_import_reimport')) {
            wp_die('Security check failed');
        }

        require_once plugin_dir_path(__FILE__) . 'classes/class-database-importer.php';
        $importer = new MTHFR_Database_Importer();
        $result = $importer->import_from_xlsx();

        // Display results
        $this->display_import_results($result);
    }

    private function handle_clear_database() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['mthfr_import_nonce'], 'mthfr_clear_database')) {
            wp_die('Security check failed');
        }

        global $wpdb;

        // Clear tables in correct order (respecting foreign keys)
        $tables_to_clear = array(
            'variant_tag_relationships',
            'variant_tags',
            'variant_categories',
            'pathways',
            'genetic_variants'
        );

        $cleared_tables = 0;
        $errors = array();

        foreach ($tables_to_clear as $table) {
            $table_name = $wpdb->prefix . $table;
            $result = $wpdb->query("DELETE FROM {$table_name}");

            if ($result === false) {
                $errors[] = $table;
            } else {
                $cleared_tables++;
            }
        }

        // Reset auto-increment counters
        foreach ($tables_to_clear as $table) {
            $table_name = $wpdb->prefix . $table;
            $wpdb->query("ALTER TABLE {$table_name} AUTO_INCREMENT = 1");
        }

        echo '<div class="notice notice-success">';
        echo '<p><strong>Database cleared successfully!</strong></p>';
        echo '<p>Tables cleared: ' . $cleared_tables . '/' . count($tables_to_clear) . '</p>';
        if (!empty($errors)) {
            echo '<p style="color: red;">Errors clearing tables: ' . implode(', ', $errors) . '</p>';
        }
        echo '</div>';
    }

    private function display_import_results($result) {
        echo '<div class="notice notice-success">';
        echo '<p><strong>Import completed!</strong></p>';
        echo '<ul>';
        echo '<li><strong>Files processed:</strong> ' . esc_html($result['files_processed']) . '</li>';
        echo '<li><strong>Total rows processed:</strong> ' . esc_html($result['total_rows']) . '</li>';
        echo '<li><strong>New variants inserted:</strong> ' . esc_html($result['inserted_variants']) . '</li>';
        echo '<li><strong>Categories linked:</strong> ' . esc_html($result['inserted_categories']) . '</li>';
        echo '<li><strong>Rows skipped:</strong> ' . esc_html($result['skipped_rows']) . '</li>';
        echo '<li><strong>Errors:</strong> ' . esc_html($result['errors']) . '</li>';
        echo '</ul>';
        echo '<p><em>' . esc_html($result['message']) . '</em></p>';
        echo '</div>';
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

    public function ajax_import_upload() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['mthfr_import_nonce'], 'mthfr_import_upload')) {
            wp_send_json_error('Security check failed');
        }

        if (empty($_FILES['xlsx_file']['tmp_name'])) {
            wp_send_json_error('No file uploaded');
        }

        // Validate file type
        $file_type = wp_check_filetype($_FILES['xlsx_file']['name']);
        if (!in_array($file_type['ext'], array('xlsx', 'xls'))) {
            wp_send_json_error('Invalid file type. Only XLSX and XLS files are allowed.');
        }

        // Move uploaded file to temp location
        $temp_file = $_FILES['xlsx_file']['tmp_name'];

        // Import the file
        require_once plugin_dir_path(__FILE__) . 'classes/class-database-importer.php';
        $importer = new MTHFR_Database_Importer();
        $result = $importer->import_from_xlsx(array($temp_file));

        // Return results
        ob_start();
        $this->display_import_results($result);
        $output = ob_get_clean();

        wp_send_json_success($output);
    }

    public function ajax_import_reimport() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['mthfr_import_nonce'], 'mthfr_import_reimport')) {
            wp_send_json_error('Security check failed');
        }

        require_once plugin_dir_path(__FILE__) . 'src/Core/Database/DatabaseImporter.php';
        $importer = new MTHFR_Database_Importer();
        $result = $importer->import_from_xlsx();

        // Return results
        ob_start();
        $this->display_import_results($result);
        $output = ob_get_clean();

        wp_send_json_success($output);
    }

    public function activate() {
        error_log('MTHFR: Plugin activation started');

        if (class_exists('MTHFR_Database')) {
            error_log('MTHFR: MTHFR_Database class available, creating tables');
            MTHFR_Database::create_tables();
            error_log('MTHFR: Table creation completed');
        } else {
            error_log('MTHFR: ERROR - MTHFR_Database class not available during activation');
        }

        // Update database version
        update_option('mthfr_db_version', MTHFR_DB_VERSION);

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
