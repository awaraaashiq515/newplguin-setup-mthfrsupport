<?php
/**
 * MTHFR Admin Handler Class - Clean Version
 * Handles only plugin admin pages and settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_Admin {
    
    public function __construct() {
        // Admin hooks only
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        error_log('MTHFR: Admin class initialized');
    }
    
    /**
     * Add main admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('MTHFR Reports', 'mthfr'),
            __('MTHFR Reports', 'mthfr'),
            'manage_options',
            'mthfr-reports',
            array($this, 'admin_main_page'),
            'dashicons-analytics',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'mthfr-reports',
            __('All Reports', 'mthfr'),
            __('All Reports', 'mthfr'),
            'manage_options',
            'mthfr-all-reports',
            array($this, 'admin_reports_page')
        );
        
        add_submenu_page(
            'mthfr-reports',
            __('Settings', 'mthfr'),
            __('Settings', 'mthfr'),
            'manage_options',
            'mthfr-settings',
            array($this, 'admin_settings_page')
        );
        
        add_submenu_page(
            'mthfr-reports',
            __('Status', 'mthfr'),
            __('Status', 'mthfr'),
            'manage_options',
            'mthfr-status',
            array($this, 'admin_status_page')
        );
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        register_setting('mthfr_settings', 'mthfr_genetic_categories');
        register_setting('mthfr_settings', 'mthfr_enable_auto_generation');
        register_setting('mthfr_settings', 'mthfr_default_report_template');
        
        // Add settings sections
        add_settings_section(
            'mthfr_general_settings',
            __('General Settings', 'mthfr'),
            array($this, 'settings_section_callback'),
            'mthfr_settings'
        );
        
        add_settings_field(
            'mthfr_genetic_categories',
            __('Genetic Product Categories', 'mthfr'),
            array($this, 'genetic_categories_callback'),
            'mthfr_settings',
            'mthfr_general_settings'
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_main_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('MTHFR Genetic Reports', 'mthfr'); ?></h1>
            
            <div class="mthfr-admin-dashboard">
                <div class="dashboard-widgets" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
                    
                    <div class="dashboard-widget" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3><?php _e('Statistics', 'mthfr'); ?></h3>
                        <?php $this->show_statistics(); ?>
                    </div>
                    
                    <div class="dashboard-widget" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3><?php _e('System Status', 'mthfr'); ?></h3>
                        <?php $this->show_system_status(); ?>
                    </div>
                    
                    <div class="dashboard-widget" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3><?php _e('Quick Actions', 'mthfr'); ?></h3>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=mthfr-all-reports'); ?>" class="button button-primary">View All Reports</a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=mthfr-settings'); ?>" class="button">Settings</a>
                        </p>
                        <p>
                            <a href="<?php echo rest_url('mthfr/v1/health'); ?>" target="_blank" class="button">Test API</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Reports listing page
     */
    public function admin_reports_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('All Genetic Reports', 'mthfr'); ?></h1>
            <?php $this->show_reports_table(); ?>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function admin_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('MTHFR Settings', 'mthfr'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('mthfr_settings');
                do_settings_sections('mthfr_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Status page
     */
    public function admin_status_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('System Status', 'mthfr'); ?></h1>
            
            <div class="mthfr-status-page" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">
                
                <div class="status-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h2><?php _e('Plugin Information', 'mthfr'); ?></h2>
                    <?php $this->show_plugin_info(); ?>
                </div>
                
                <div class="status-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h2><?php _e('Database Status', 'mthfr'); ?></h2>
                    <?php $this->show_database_status(); ?>
                </div>
                
                <div class="status-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h2><?php _e('API Status', 'mthfr'); ?></h2>
                    <?php $this->show_api_status(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on MTHFR admin pages
        if (strpos($hook, 'mthfr') !== false) {
            wp_enqueue_style(
                'mthfr-admin',
                MTHFR_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                MTHFR_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                'mthfr-admin',
                MTHFR_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                MTHFR_PLUGIN_VERSION,
                true
            );
            
            wp_localize_script('mthfr-admin', 'mthfr_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('mthfr/v1/'),
                'strings' => array(
                    'error' => __('Error', 'mthfr'),
                    'success' => __('Success', 'mthfr')
                )
            ));
        }
    }
    
    // Helper methods for admin pages
    
    private function show_statistics() {
        if (class_exists('MTHFR_Database')) {
            $upload_count = MTHFR_Database::get_upload_count();
            $report_count = MTHFR_Database::get_report_count();
            
            echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: center;">';
            echo '<div style="padding: 15px; background: #f0f8ff; border-radius: 4px;">';
            echo '<div style="font-size: 24px; font-weight: bold; color: #007cba;">' . esc_html($upload_count) . '</div>';
            echo '<div style="font-size: 12px; color: #666;">Total Uploads</div>';
            echo '</div>';
            echo '<div style="padding: 15px; background: #f0f8ff; border-radius: 4px;">';
            echo '<div style="font-size: 24px; font-weight: bold; color: #007cba;">' . esc_html($report_count) . '</div>';
            echo '<div style="font-size: 12px; color: #666;">Total Reports</div>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<p>Database not available</p>';
        }
    }
    
    private function show_system_status() {
        $classes = array(
            'MTHFR_Database' => 'Database',
            'MTHFR_API_Endpoints' => 'API Endpoints',
            'MTHFR_Report_Generator' => 'Report Generator',
            'MTHFR_PDF_Generator' => 'PDF Generator'
        );
        
        echo '<ul style="margin: 0; padding: 0; list-style: none;">';
        foreach ($classes as $class => $name) {
            $status = class_exists($class) ? '✅ Active' : '❌ Missing';
            echo '<li style="padding: 5px 0; display: flex; justify-content: space-between;">';
            echo '<span>' . esc_html($name) . ':</span>';
            echo '<span>' . $status . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
   private function show_reports_table() {
    if (!class_exists('MTHFR_Database')) {
        echo '<div class="notice notice-error"><p>Database class not available</p></div>';
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'user_reports';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
        echo '<div class="notice notice-warning"><p>Reports table not found. Please check database setup.</p></div>';
        return;
    }
    
    $reports = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 50");
    
    if (empty($reports)) {
        echo '<div class="notice notice-info"><p>No reports found.</p></div>';
        return;
    }
    
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th style="width: 80px;">Order</th>
                <th>Product</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 150px;">Created</th>
                <th style="width: 120px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $report): ?>
            <?php 
            $order = wc_get_order($report->order_id);
            $status_class = 'status-' . esc_attr($report->status);
            
            // Get product name safely
            $product_name = 'N/A';
            if (isset($report->product_name) && !empty($report->product_name)) {
                $product_name = $report->product_name;
            } elseif ($order && $order->get_items()) {
                // If product_name not in database, get from order items
                $items = $order->get_items();
                $first_item = reset($items);
                if ($first_item) {
                    $product_name = $first_item->get_name();
                }
            }
            ?>
            <tr>
                <td><?php echo esc_html($report->id); ?></td>
                <td>
                    <?php if ($order): ?>
                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">
                            #<?php echo esc_html($order->get_id()); ?>
                        </a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($product_name); ?></td>
                <td>
                    <span class="status-badge <?php echo $status_class; ?>" style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                        <?php echo esc_html(ucfirst($report->status)); ?>
                    </span>
                </td>
                <td><?php echo esc_html(date('M j, Y g:i A', strtotime($report->created_at))); ?></td>
                <td>
                    <?php if ($report->status === 'completed'): ?>
                        <?php if (isset($report->pdf_path) && !empty($report->pdf_path) && file_exists($report->pdf_path)): ?>
                            <a href="<?php echo esc_url($this->get_report_file_url($report->pdf_path)); ?>" 
                               class="button button-small" target="_blank">PDF</a>
                        <?php endif; ?>
                        <?php if (isset($report->json_report) && !empty($report->json_report)): ?>
                            <a href="<?php echo esc_url($this->get_report_view_url($report->id, 'json')); ?>" 
                               class="button button-small" target="_blank">View</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <style>
    .status-completed { background: #d4edda; color: #155724; }
    .status-processing { background: #fff3cd; color: #856404; }
    .status-error { background: #f8d7da; color: #721c24; }
    .status-pending { background: #e2e3e5; color: #495057; }
    </style>
    <?php
}
    private function show_plugin_info() {
        ?>
        <table class="form-table" style="margin: 0;">
            <tr>
                <th style="width: 150px;">Plugin Version</th>
                <td><?php echo defined('MTHFR_PLUGIN_VERSION') ? MTHFR_PLUGIN_VERSION : 'Unknown'; ?></td>
            </tr>
            <tr>
                <th>WordPress</th>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <th>PHP Version</th>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <th>WooCommerce</th>
                <td><?php echo class_exists('WooCommerce') ? '✅ Active' : '❌ Not Active'; ?></td>
            </tr>
        </table>
        <?php
    }
    
    private function show_database_status() {
        if (class_exists('MTHFR_Database')) {
            $db_test = MTHFR_Database::test_connection();
            
            echo '<table class="form-table" style="margin: 0;">';
            echo '<tr><th style="width: 150px;">Connection</th><td>' . ($db_test['status'] === 'success' ? '✅ Connected' : '❌ Failed') . '</td></tr>';
            
            if ($db_test['status'] === 'success') {
                echo '<tr><th>Upload Records</th><td>' . esc_html(MTHFR_Database::get_upload_count()) . '</td></tr>';
                echo '<tr><th>Report Records</th><td>' . esc_html(MTHFR_Database::get_report_count()) . '</td></tr>';
            } else {
                echo '<tr><th>Error</th><td>' . esc_html($db_test['message']) . '</td></tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p>❌ Database class not available</p>';
        }
    }
    
    private function show_api_status() {
        $endpoints = array(
            '/health' => 'Health Check',
            '/debug' => 'Debug Info'
        );
        
        echo '<table class="form-table" style="margin: 0;">';
        
        foreach ($endpoints as $endpoint => $name) {
            $url = rest_url('mthfr/v1' . $endpoint);
            $response = wp_remote_get($url, array('timeout' => 5));
            
            if (is_wp_error($response)) {
                $status = '❌ Error';
            } else {
                $code = wp_remote_retrieve_response_code($response);
                $status = ($code == 200) ? '✅ Working' : "❌ HTTP {$code}";
            }
            
            echo '<tr>';
            echo '<th style="width: 150px;">' . esc_html($name) . '</th>';
            echo '<td>' . $status . ' <a href="' . esc_url($url) . '" target="_blank" class="button button-small">Test</a></td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    public function settings_section_callback() {
        echo '<p>' . __('Configure genetic report settings below.', 'mthfr') . '</p>';
    }
    
    public function genetic_categories_callback() {
        $categories = get_option('mthfr_genetic_categories', array());
        $product_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ));
        
        if (empty($product_categories)) {
            echo '<p>No product categories found. Make sure WooCommerce is active.</p>';
            return;
        }
        
        echo '<select name="mthfr_genetic_categories[]" multiple style="width: 100%; height: 150px;">';
        foreach ($product_categories as $category) {
            $selected = in_array($category->term_id, $categories) ? 'selected' : '';
            echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>';
            echo esc_html($category->name);
            echo '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select which product categories should be treated as genetic analysis products.', 'mthfr') . '</p>';
    }
    
    // Helper methods
    
    private function get_report_view_url($report_id, $format = 'json') {
        return add_query_arg(array(
            'mthfr_action' => 'view_report',
            'report_id' => $report_id,
            'format' => $format,
            'nonce' => wp_create_nonce('mthfr_view_report_' . $report_id)
        ), site_url());
    }
    
    private function get_report_file_url($file_path) {
        if (!file_exists($file_path)) {
            return '#';
        }
        
        $upload_dir = wp_upload_dir();
        return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
    }
}

// Initialize Admin class
if (!function_exists('mthfr_init_admin')) {
    function mthfr_init_admin() {
        new MTHFR_Admin();
    }
    add_action('plugins_loaded', 'mthfr_init_admin');
}

?>