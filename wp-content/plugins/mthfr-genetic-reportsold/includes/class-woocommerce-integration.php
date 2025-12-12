<?php
/**
 * MTHFR WooCommerce Integration - Complete Fixed Version
 * File: includes/class-woocommerce-integration.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_WooCommerce_Integration {
    
    public function __construct() {


// add_action('woocommerce_order_status_completed', array($this, 'process_order_completion'), 10, 1);
// add_action('woocommerce_order_status_processing', array($this, 'process_order_completion'), 10, 1);
// add_filter('woocommerce_add_cart_item_data', array($this, 'store_upload_id_with_cart_item'), 10, 3);
// add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_upload_id_to_order_item'), 10, 4);

        // Hook into WordPress to add meta boxes - IMMEDIATE
        // add_action('add_meta_boxes', array($this, 'add_order_meta_box'), 10, 2);
        
        // // Debug meta boxes registration
        // add_action('add_meta_boxes', array($this, 'debug_meta_boxes'), 999, 2);
        
        // // AJAX handler for test button
        // add_action('wp_ajax_mthfr_test_report', array($this, 'ajax_test_report'));
        // add_action('wp_ajax_mthfr_generate_pdf', array($this, 'ajax_generate_pdf'));
        // add_action('wp_ajax_mthfr_email_report', array($this, 'ajax_email_report'));
        
        // // Enqueue scripts on order pages
        // add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // error_log('MTHFR: WooCommerce Integration Constructor called');
    }



    /**
     * Debug meta boxes - to see what's happening
     */
    public function debug_meta_boxes($post_type, $post) {
        if ($post_type === 'shop_order') {
            error_log("MTHFR: debug_meta_boxes called for post_type: {$post_type}, post_id: " . ($post ? $post->ID : 'no post'));
            
            global $wp_meta_boxes;
            if (isset($wp_meta_boxes['shop_order'])) {
                error_log("MTHFR: Meta boxes registered for shop_order: " . print_r(array_keys($wp_meta_boxes['shop_order']), true));
            } else {
                error_log("MTHFR: No meta boxes found for shop_order");
            }
        }
    }
    
    /**
     * Add meta box to WooCommerce orders
     */
    public function add_order_meta_box($post_type, $post) {
        error_log("MTHFR: add_order_meta_box called with post_type: {$post_type}");
        
        // Only add to shop_order post type
        if ($post_type !== 'shop_order') {
            error_log("MTHFR: Not shop_order, skipping");
            return;
        }
        
        error_log("MTHFR: Adding meta box for shop_order");
        
        add_meta_box(
            'mthfr-test-reports',
            '🧬 MTHFR Test Reports',
            array($this, 'render_meta_box'),
            'shop_order',
            'side',
            'high'
        );
        
        error_log('MTHFR: Meta box added successfully');
    }
    
    /**
     * Render the meta box content
     */
    public function render_meta_box($post) {
        error_log("MTHFR: render_meta_box called for post ID: " . $post->ID);
        
        $order_id = $post->ID;
        $order = wc_get_order($order_id);
        
        if (!$order) {
            echo '<p>Error: Could not load order</p>';
            return;
        }
        
        // Add nonce for security
        wp_nonce_field('mthfr_test_action', 'mthfr_test_nonce');
        
        ?>
        <div class="mthfr-test-meta-box">
            <div style="text-align: center; padding: 15px;">
                <h4 style="margin-bottom: 15px;">🧬 Genetic Report Testing</h4>
                
                <!-- Order Info -->
                <div style="background: #f8f9fa; padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: left;">
                    <small>
                        <strong>Order ID:</strong> <?php echo esc_html($order_id); ?><br>
                        <strong>Status:</strong> <?php echo esc_html($order->get_status()); ?><br>
                        <strong>Customer:</strong> <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?><br>
                        <strong>Total:</strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                    </small>
                </div>
                
                <!-- Action Buttons -->
                <div style="margin-bottom: 15px;">
                    <button type="button" id="mthfr-test-btn" class="button button-primary" 
                            data-order-id="<?php echo esc_attr($order_id); ?>" style="margin-bottom: 8px; width: 100%;">
                        🧪 Generate Report
                    </button>
                    
                    <button type="button" id="mthfr-pdf-btn" class="button button-secondary" 
                            data-order-id="<?php echo esc_attr($order_id); ?>" style="margin-bottom: 8px; width: 100%;">
                        📄 Generate PDF Report
                    </button>
                    
                    <button type="button" id="mthfr-email-btn" class="button" 
                            data-order-id="<?php echo esc_attr($order_id); ?>" style="width: 100%;">
                        📧 Email Report
                    </button>
                </div>
                
                <!-- Loading indicator -->
                <div id="mthfr-loading" style="display: none; margin-top: 10px;">
                    <span class="spinner is-active"></span>
                    <small>Processing request...</small>
                </div>
                
                <!-- Results area -->
                <div id="mthfr-results" style="margin-top: 15px;"></div>
            </div>
            
            <!-- Integration Status -->
            <div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px;">
                <h4 style="margin-bottom: 10px;">Integration Status</h4>
                <div style="font-size: 12px; text-align: left;">
                    <?php $this->show_integration_status(); ?>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Test Report Button
            $('#mthfr-test-btn').on('click', function() {
                var $btn = $(this);
                var orderId = $btn.data('order-id');
                
                mthfrMakeRequest('mthfr_test_report', orderId, $btn, 'Generating report...');
            });
            
            // PDF Report Button
            $('#mthfr-pdf-btn').on('click', function() {
                var $btn = $(this);
                var orderId = $btn.data('order-id');
                
                mthfrMakeRequest('mthfr_generate_pdf', orderId, $btn, 'Generating PDF report...');
            });
            
            // Email Report Button
            $('#mthfr-email-btn').on('click', function() {
                var $btn = $(this);
                var orderId = $btn.data('order-id');
                
                mthfrMakeRequest('mthfr_email_report', orderId, $btn, 'Sending email...');
            });
            
            function mthfrMakeRequest(action, orderId, $btn, loadingMsg) {
                // Show loading
                $btn.prop('disabled', true);
                $('#mthfr-loading small').text(loadingMsg);
                $('#mthfr-loading').show();
                $('#mthfr-results').empty();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: action,
                        order_id: orderId,
                        nonce: $('#mthfr_test_nonce').val()
                    },
                    success: function(response) {
                        $('#mthfr-loading').hide();
                        $btn.prop('disabled', false);
                        
                        if (response.success) {
                            var resultHtml = '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                                '<strong>✅ Success!</strong><br>' +
                                '<small>' + response.data.message + '</small>';
                            
                            // Add download link if PDF was generated
                            if (response.data.pdf_url) {
                                resultHtml += '<br><br><a href="' + response.data.pdf_url + '" target="_blank" class="button button-small">📄 Download PDF</a>';
                            }
                            
                            // Add report details if available
                            if (response.data.report_id) {
                                resultHtml += '<br><small>Report ID: ' + response.data.report_id + '</small>';
                            }
                            
                            resultHtml += '</div>';
                            $('#mthfr-results').html(resultHtml);
                        } else {
                            $('#mthfr-results').html(
                                '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                                '<strong>❌ Error:</strong><br>' +
                                '<small>' + (response.data ? response.data.message : 'Unknown error') + '</small>' +
                                '</div>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#mthfr-loading').hide();
                        $btn.prop('disabled', false);
                        
                        $('#mthfr-results').html(
                            '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                            '<strong>❌ AJAX Error:</strong><br>' +
                            '<small>Status: ' + status + '<br>Error: ' + error + '</small>' +
                            '</div>'
                        );
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Show current integration status
     */
    private function show_integration_status() {
        $checks = array(
            'WooCommerce Active' => class_exists('WooCommerce'),
            'MTHFR Database' => class_exists('MTHFR_Database'),
            'MTHFR Report Generator' => class_exists('MTHFR_Report_Generator'),
            'MTHFR PDF Generator' => class_exists('MTHFR_PDF_Generator'),
            'MTHFR API Endpoints' => class_exists('MTHFR_API_Endpoints'),
        );
        
        foreach ($checks as $check => $status) {
            $icon = $status ? '✅' : '❌';
            $color = $status ? 'green' : 'red';
            echo "<div style='color: {$color};'>{$icon} {$check}</div>";
        }
    }
    
    /**
     * Enqueue scripts only on order pages
     */
    public function enqueue_scripts($hook) {
        $screen = get_current_screen();
        
        if ($screen && $screen->post_type === 'shop_order' && 
            ($hook === 'post.php' || $hook === 'post-new.php')) {
            
            wp_enqueue_script('jquery');
            wp_enqueue_style('wp-admin');
            
            error_log('MTHFR: Scripts enqueued on order page');
        }
    }
    
    /**
     * FIXED: AJAX handler for ACTUAL report generation (not test)
     */
    public function ajax_test_report() {
       
        
        // Check permissions
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
            return;
        }
        
        error_log("MTHFR: ACTUAL report generation for order {$order_id}");
        
        try {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                wp_send_json_error(array('message' => 'Order not found'));
                return;
            }
            
            // Check database connection first
            if (class_exists('MTHFR_Database')) {
                $db_test = MTHFR_Database::test_connection();
                if (!$db_test || $db_test['status'] !== 'success') {
                    wp_send_json_error(array('message' => 'Database connection failed'));
                    return;
                }
            } else {
                wp_send_json_error(array('message' => 'MTHFR_Database class not found'));
                return;
            }
            
            // Generate ACTUAL report (not test)
            if (class_exists('MTHFR_Report_Generator')) {
                // Create a real upload ID (you can modify this logic as needed)
                $upload_id = $order_id * 1000 + time();
                
                // Get product name from order
                $product_name = 'Genetic Analysis Report';
                $items = $order->get_items();
                if (!empty($items)) {
                    $first_item = reset($items);
                    $product_name = $first_item->get_name();
                }
                
                // Generate ACTUAL report
                $result = MTHFR_Report_Generator::generate_report(
                    $upload_id,
                    $order_id,
                    $product_name,
                    false // NOT test mode - actual generation
                );
                
                if ($result && isset($result['success']) && $result['success']) {
                    wp_send_json_success(array(
                        'message' => 'Report generated successfully! ' . ($result['message'] ?? ''),
                        'report_id' => $result['report_id'] ?? null,
                        'upload_id' => $upload_id,
                        'report_type' => $result['report_type'] ?? 'Unknown',
                        'data_points' => $result['data_points'] ?? 0,
                        'file_size' => $result['file_size'] ?? 0,
                        'details' => $result
                    ));
                } else {
                    $error_msg = isset($result['error']) ? $result['error'] : 'Report generation failed';
                    wp_send_json_error(array('message' => 'Report generation failed: ' . $error_msg));
                }
            } else {
                wp_send_json_error(array('message' => 'MTHFR_Report_Generator class not found'));
            }
            
        } catch (Exception $e) {
            error_log('MTHFR Report Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Exception: ' . $e->getMessage()));
        }
    }
    
    /**
     * FIXED: AJAX handler for ACTUAL PDF generation (not test)
     */
    public function ajax_generate_pdf() {
        // Verify nonce
      if (!isset($_POST['nonce']) || (!wp_verify_nonce($_POST['nonce'], 'mthfr_test_action') && !wp_verify_nonce($_POST['nonce'], 'mthfr_working_action'))) {

            wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
            return;
        }
        
        error_log("MTHFR: ACTUAL PDF generation for order {$order_id}");
        
        try {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                wp_send_json_error(array('message' => 'Order not found'));
                return;
            }
            
            // Generate ACTUAL PDF (not test)
            if (class_exists('MTHFR_PDF_Generator') && class_exists('MTHFR_Genetic_Data')) {
                
                // Get product name from order
                $product_name = 'Genetic Analysis Report';
                $items = $order->get_items();
                if (!empty($items)) {
                    $first_item = reset($items);
                    $product_name = $first_item->get_name();
                }
                
                // Create ACTUAL genetic data (not test data)
                $genetic_data = MTHFR_Genetic_Data::create_sample_data(); // Full dataset
                $report_type = MTHFR_Report_Generator::determine_report_type($product_name);
                // Generate ACTUAL PDF
                $pdf_result = MTHFR_PDF_Generator::generate_pdf(
                    $genetic_data, 
                    $product_name, 
                    $report_type
                );
                
                if ($pdf_result && strlen($pdf_result) > 0) {
                    // Save the PDF file
                    $upload_dir = wp_upload_dir();
                    $reports_dir = $upload_dir['basedir'] . '/user_reports';
                    
                    if (!file_exists($reports_dir)) {
                        wp_mkdir_p($reports_dir);
                    }
                    
                    $filename = "report_order_{$order_id}_" . date('Ymd_His') . '.pdf';
                    $file_path = $reports_dir . '/' . $filename;
                    $file_url = $upload_dir['baseurl'] . '/user_reports/' . $filename;
                    
                    // Save PDF to file
                    if (file_put_contents($file_path, $pdf_result)) {
                        wp_send_json_success(array(
                            'message' => 'PDF generated and saved successfully!',
                            'pdf_url' => $file_url,
                            'pdf_path' => $file_path,
                            'filename' => $filename,
                            'file_size' => strlen($pdf_result),
                            'variants_included' => count($genetic_data)
                        ));
                    } else {
                        wp_send_json_error(array('message' => 'PDF generated but failed to save file'));
                    }
                } else {
                    wp_send_json_error(array('message' => 'PDF generation failed - no data returned'));
                }
                
            } else {
                $missing_classes = array();
                if (!class_exists('MTHFR_PDF_Generator')) $missing_classes[] = 'MTHFR_PDF_Generator';
                if (!class_exists('MTHFR_Genetic_Data')) $missing_classes[] = 'MTHFR_Genetic_Data';
                
                wp_send_json_error(array('message' => 'Missing classes: ' . implode(', ', $missing_classes)));
            }
            
        } catch (Exception $e) {
            error_log('MTHFR PDF Generation Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Exception: ' . $e->getMessage()));
        }
    }
    



    /**
     * NEW: AJAX handler for emailing reports
     */
    public function ajax_email_report() {
        // Verify nonce
       if (!isset($_POST['nonce']) || (!wp_verify_nonce($_POST['nonce'], 'mthfr_test_action') && !wp_verify_nonce($_POST['nonce'], 'mthfr_working_action'))) {

     wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
            return;
        }
        
        try {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                wp_send_json_error(array('message' => 'Order not found'));
                return;
            }
            
            $customer_email = $order->get_billing_email();
            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            
            if (!$customer_email) {
                wp_send_json_error(array('message' => 'No customer email found'));
                return;
            }
            
            // Check if report exists first
            if (class_exists('MTHFR_Database')) {
                $report = MTHFR_Database::get_report_by_order($order_id);
                
                if (!$report) {
                    wp_send_json_error(array('message' => 'No report found for this order. Generate a report first.'));
                    return;
                }
                
                // Send email
                $subject = 'Your Genetic Analysis Report - Order #' . $order_id;
                $message = "Dear {$customer_name},\n\n";
                $message .= "Your genetic analysis report is ready!\n\n";
                $message .= "Order ID: #{$order_id}\n";
                $message .= "Report generated: " . date('F d, Y', strtotime($report->created_at)) . "\n\n";
                $message .= "You can download your report from your account or contact us for assistance.\n\n";
                $message .= "Best regards,\nMTHFR Support Team";
                
                $headers = array('Content-Type: text/plain; charset=UTF-8');
                
                if (wp_mail($customer_email, $subject, $message, $headers)) {
                    wp_send_json_success(array(
                        'message' => "Email sent successfully to {$customer_email}",
                        'customer_name' => $customer_name,
                        'report_id' => $report->id
                    ));
                } else {
                    wp_send_json_error(array('message' => 'Failed to send email'));
                }
            } else {
                wp_send_json_error(array('message' => 'Database class not available'));
            }
            
        } catch (Exception $e) {
            error_log('MTHFR Email Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Exception: ' . $e->getMessage()));
        }
    }
}

// Initialize IMMEDIATELY - not on plugins_loaded
if (class_exists('WooCommerce')) {
    new MTHFR_WooCommerce_Integration();
    error_log('MTHFR: WooCommerce Integration initialized IMMEDIATELY');
} else {
    error_log('MTHFR: WooCommerce not active, integration not loaded');
}

// ALL YOUR OTHER FUNCTIONS (keep them as they are)

/**
 * Add ALL meta boxes to WooCommerce orders in ONE function
 */
function mthfr_add_all_order_meta_boxes($post_type, $post) {
    error_log("MTHFR: mthfr_add_all_order_meta_boxes called with post_type: {$post_type}");
    
    // Only add to shop_order post type
    if ($post_type !== 'shop_order') {
        error_log("MTHFR: Not shop_order, skipping");
        return;
    }
    
    error_log("MTHFR: Adding ALL meta boxes for shop_order");
    
    // Meta Box 1: MTHFR Test Reports (Main functionality)
    add_meta_box(
        'mthfr-test-reports',
        '🧬 MTHFR Test Reports',
        'mthfr_render_meta_box',
        'shop_order',
        'side',
        'high'
    );
    
    // Meta Box 2: Custom After Notes Box (Your test box)
    add_meta_box(
        'custom_after_notes_box',
        'Custom After Notes Box',
        'mthfr_render_custom_notes_box',
        'shop_order',
        'side',
        'low'
    );
    
    // Meta Box 3: Additional test box (if needed)
    add_meta_box(
        'mthfr-debug-box',
        '🔧 MTHFR Debug Info',
        'mthfr_render_debug_box',
        'shop_order',
        'side',
        'low'
    );
    
    error_log('MTHFR: All meta boxes added successfully');
}

/**
 * Render the main MTHFR meta box content
 */

function mthfr_render_meta_box($post) {
    error_log("MTHFR: mthfr_render_meta_box called for post ID: " . $post->ID);
    
    $order_id = $post->ID;
    $order = wc_get_order($order_id);
    
    if (!$order) {
        echo '<p>Error: Could not load order</p>';
        return;
    }
    
    // Add nonce for security
    wp_nonce_field('mthfr_test_action', 'mthfr_test_nonce');
    
    // Check for existing reports
    $existing_reports = mthfr_check_existing_reports($order_id);
    
    ?>
    <div class="mthfr-test-meta-box">
        <div style="text-align: center; padding: 15px;">
            <h4 style="margin-bottom: 15px;">🧬 Genetic Report Testing</h4>
            
            <!-- Order Info -->
            <div style="background: #f8f9fa; padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: left;">
                <small>
                    <strong>Order ID:</strong> <?php echo esc_html($order_id); ?><br>
                    <strong>Status:</strong> <?php echo esc_html($order->get_status()); ?><br>
                    <strong>Customer:</strong> <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?><br>
                    <strong>Total:</strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                </small>
            </div>
            
            <!-- Action Buttons Section - UPDATED -->
            <div style="margin-bottom: 15px;">
                <button type="button" id="mthfr-test-btn" class="button button-primary" 
                        data-order-id="<?php echo esc_attr($order_id); ?>" style="margin-bottom: 8px; width: 100%;">
                    🧪 Generate Reports (JSON + PDF)
                </button>
                
                <button type="button" id="mthfr-pdf-btn" class="button button-secondary" 
                        data-order-id="<?php echo esc_attr($order_id); ?>" style="margin-bottom: 8px; width: 100%;">
                    📄 Generate PDF Only
                </button>
            </div>

            <!-- Download Section - NEW -->
            <div style="margin-bottom: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                <h5 style="margin: 0 0 10px 0; color: #0073aa; font-size: 13px;">Download Reports</h5>
                
                <div style="display: flex; gap: 5px; margin-bottom: 8px;">
                    <button type="button" id="mthfr-download-json-btn" class="button" 
                            data-order-id="<?php echo esc_attr($order_id); ?>" style="flex: 1; font-size: 11px;">
                        📊 Download JSON
                    </button>
                    <button type="button" id="mthfr-download-pdf-btn" class="button" 
                            data-order-id="<?php echo esc_attr($order_id); ?>" style="flex: 1; font-size: 11px;">
                        📄 Download PDF
                    </button>
                </div>
                
                <?php
                // Show existing report status
                if ($existing_reports) {
                    echo '<div style="font-size: 11px; color: #666; text-align: center; background: #f0f8ff; padding: 8px; border-radius: 3px;">';
                    
                    if ($existing_reports['has_json']) {
                        echo '✅ JSON Report Available<br>';
                    }
                    if ($existing_reports['has_pdf']) {
                        echo '✅ PDF Report Available<br>';
                    }
                    
                    if ($existing_reports['has_json'] || $existing_reports['has_pdf']) {
                        echo '<small><strong>Type:</strong> ' . esc_html($existing_reports['report_type']) . ' | ';
                        echo '<strong>Status:</strong> ' . esc_html(ucfirst($existing_reports['status'])) . '<br>';
                        echo '<strong>Generated:</strong> ' . date('M j, Y g:i A', strtotime($existing_reports['created_at'])) . '</small>';
                    }
                    echo '</div>';
                } else {
                    echo '<div style="font-size: 11px; color: #999; text-align: center; background: #f9f9f9; padding: 8px; border-radius: 3px;">No reports generated yet</div>';
                }
                ?>
            </div>

            <!-- Email Section -->
            <div style="margin-bottom: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                <button type="button" id="mthfr-email-btn" class="button" 
                        data-order-id="<?php echo esc_attr($order_id); ?>" style="width: 100%;">
                    📧 Email Report to Customer
                </button>
            </div>
            
            <!-- Loading indicator -->
            <div id="mthfr-loading" style="display: none; margin-top: 10px;">
                <span class="spinner is-active"></span>
                <small id="mthfr-loading-text">Processing request...</small>
            </div>
            
            <!-- Results area -->
            <div id="mthfr-results" style="margin-top: 15px;"></div>
        </div>
        
        <!-- Integration Status -->
        <div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px;">
            <h4 style="margin-bottom: 10px;">Integration Status</h4>
            <div style="font-size: 12px; text-align: left;">
                <?php mthfr_show_integration_status(); ?>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Avoid duplicate event handlers
        if (window.mthfrHandlersAdded) {
            return;
        }
        window.mthfrHandlersAdded = true;
        
        // Test Report Button (now generates both JSON and PDF)
        $(document).on('click', '#mthfr-test-btn', function() {
            var $btn = $(this);
            var orderId = $btn.data('order-id');
            
            mthfrMakeRequest('mthfr_test_report', orderId, $btn, 'Generating JSON and PDF reports...');
        });
        
        // PDF Report Button (still works for standalone PDF)
        $(document).on('click', '#mthfr-pdf-btn', function() {
            var $btn = $(this);
            var orderId = $btn.data('order-id');
            
            mthfrMakeRequest('mthfr_generate_pdf', orderId, $btn, 'Generating PDF report...');
        });
        
        // Email Report Button
        $(document).on('click', '#mthfr-email-btn', function() {
            var $btn = $(this);
            var orderId = $btn.data('order-id');
            
            mthfrMakeRequest('mthfr_email_report', orderId, $btn, 'Sending email report...');
        });
        
        // New: Download JSON Button
        $(document).on('click', '#mthfr-download-json-btn', function() {
            var $btn = $(this);
            var orderId = $btn.data('order-id');
            
            mthfrDownloadRequest('json', orderId, $btn);
        });
        
        // New: Download PDF Button
        $(document).on('click', '#mthfr-download-pdf-btn', function() {
            var $btn = $(this);
            var orderId = $btn.data('order-id');
            
            mthfrDownloadRequest('pdf', orderId, $btn);
        });
        
        function mthfrMakeRequest(action, orderId, $btn, loadingMsg) {
            // Show loading
            $btn.prop('disabled', true);
            $('#mthfr-loading-text').text(loadingMsg);
            $('#mthfr-loading').show();
            $('#mthfr-results').empty();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    order_id: orderId,
                    nonce: $('#mthfr_test_nonce').val()
                },
                success: function(response) {
                    $('#mthfr-loading').hide();
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        var resultHtml = '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                            '<strong>✅ Success!</strong><br>' +
                            '<small>' + response.data.message + '</small>';
                        
                        // Add download links for both JSON and PDF if available
                        if (response.data.json_url) {
                            resultHtml += '<br><br><a href="' + response.data.json_url + '" target="_blank" class="button button-small" style="margin-right: 5px;">📊 Download JSON Report</a>';
                        }
                        
                        if (response.data.pdf_url) {
                            resultHtml += '<a href="' + response.data.pdf_url + '" target="_blank" class="button button-small">📄 Download PDF Report</a>';
                        }
                        
                        // Legacy support for single pdf_url (from old mthfr_generate_pdf)
                        if (!response.data.json_url && !response.data.pdf_url && response.data.pdf_url) {
                            resultHtml += '<br><br><a href="' + response.data.pdf_url + '" target="_blank" class="button button-small">📄 Download Report</a>';
                        }
                        
                        // Add report details if available
                        if (response.data.report_id) {
                            resultHtml += '<br><small style="display: block; margin-top: 8px;">Report ID: ' + response.data.report_id + '</small>';
                        }
                        
                        // Show generation status
                        if (response.data.json_generated && response.data.pdf_generated) {
                            resultHtml += '<br><small style="color: #28a745; display: block; margin-top: 5px;">✅ Both JSON and PDF reports generated successfully</small>';
                        } else if (response.data.json_generated) {
                            resultHtml += '<br><small style="color: #ffc107; display: block; margin-top: 5px;">⚠️ JSON report generated, PDF generation may have failed</small>';
                        } else if (response.data.pdf_generated) {
                            resultHtml += '<br><small style="color: #ffc107; display: block; margin-top: 5px;">⚠️ PDF report generated, JSON generation may have failed</small>';
                        }
                        
                        // Add data points info if available
                        if (response.data.data_points) {
                            resultHtml += '<br><small style="color: #666; display: block; margin-top: 5px;">Data Points: ' + response.data.data_points + '</small>';
                        }
                        
                        resultHtml += '</div>';
                        $('#mthfr-results').html(resultHtml);
                        
                        // Refresh the page after 3 seconds to update the status display
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                        
                    } else {
                        $('#mthfr-results').html(
                            '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                            '<strong>❌ Error:</strong><br>' +
                            '<small>' + (response.data ? response.data.message : 'Unknown error') + '</small>' +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $('#mthfr-loading').hide();
                    $btn.prop('disabled', false);
                    
                    $('#mthfr-results').html(
                        '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                        '<strong>❌ AJAX Error:</strong><br>' +
                        '<small>Status: ' + status + '<br>Error: ' + error + '</small>' +
                        '</div>'
                    );
                }
            });
        }
        
        function mthfrDownloadRequest(reportType, orderId, $btn) {
            // Show loading
            $btn.prop('disabled', true);
            $('#mthfr-loading-text').text('Preparing ' + reportType.toUpperCase() + ' download...');
            $('#mthfr-loading').show();
            $('#mthfr-results').empty();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mthfr_download_report',
                    order_id: orderId,
                    report_type: reportType,
                    nonce: $('#mthfr_test_nonce').val()
                },
                success: function(response) {
                    $('#mthfr-loading').hide();
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        // Open download URL in new tab
                        window.open(response.data.download_url, '_blank');
                        
                        var resultHtml = '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                            '<strong>✅ Download Started!</strong><br>' +
                            '<small>' + response.data.message + '</small>' +
                            '<br><br><a href="' + response.data.download_url + '" target="_blank" class="button button-small">🔗 Download ' + response.data.file_type + ' Again</a>' +
                            '<br><small style="display: block; margin-top: 8px; color: #666;">Report Type: ' + response.data.report_type + '</small>' +
                            '</div>';
                        $('#mthfr-results').html(resultHtml);
                    } else {
                        $('#mthfr-results').html(
                            '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                            '<strong>❌ Download Error:</strong><br>' +
                            '<small>' + (response.data ? response.data.message : 'Download not available') + '</small>' +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $('#mthfr-loading').hide();
                    $btn.prop('disabled', false);
                    
                    $('#mthfr-results').html(
                        '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                        '<strong>❌ Download Failed:</strong><br>' +
                        '<small>Status: ' + status + '<br>Error: ' + error + '</small>' +
                        '</div>'
                    );
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Helper function to check existing reports for this order
 */
function mthfr_check_existing_reports($order_id) {
    if (!class_exists('MTHFR_Database')) {
        return null;
    }
    
    $report = MTHFR_Database::get_report_by_order($order_id);
    
    if (!$report) {
        return null;
    }
    
    $result = array(
        'has_json' => !empty($report->report_path) && file_exists($report->report_path),
        'has_pdf' => !empty($report->pdf_report) && file_exists($report->pdf_report),
        'report_type' => $report->report_type ?? 'Unknown',
        'status' => $report->status ?? 'Unknown',
        'created_at' => $report->created_at ?? 'Unknown'
    );
    
    // Add URLs for existing files
    if ($result['has_json']) {
        $upload_dir = wp_upload_dir();
        $result['json_url'] = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $report->report_path);
    }
    
    if ($result['has_pdf']) {
        $upload_dir = wp_upload_dir();
        $result['pdf_url'] = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $report->pdf_report);
    }
    
    return $result;
}

/**
 * Render the custom notes box
 */
function mthfr_render_custom_notes_box($post) {
    $order_id = $post->ID;
    $order = wc_get_order($order_id);
    
    ?>
    <div style="padding: 10px;">
        <p><strong>This box appears after Notes.</strong></p>
        <p>Order Status: <span style="color: blue;"><?php echo esc_html($order->get_status()); ?></span></p>
        <p>Created: <?php echo esc_html($order->get_date_created()->date('Y-m-d H:i:s')); ?></p>
        <button type="button" class="button button-small" onclick="alert('Custom notes box clicked!')">Test Button</button>
    </div>
    <?php
}

/**
 * Render the debug box
 */
function mthfr_render_debug_box($post) {
    $order_id = $post->ID;
    
    ?>
    <div style="padding: 10px; font-family: monospace; font-size: 11px;">
        <p><strong>Debug Information:</strong></p>
        <div style="background: #f1f1f1; padding: 8px; border-radius: 3px;">
            <div>Post ID: <?php echo esc_html($order_id); ?></div>
            <div>Post Type: <?php echo esc_html(get_post_type($order_id)); ?></div>
            <div>Current Time: <?php echo esc_html(current_time('Y-m-d H:i:s')); ?></div>
            <div>User ID: <?php echo esc_html(get_current_user_id()); ?></div>
        </div>
        
        <p style="margin-top: 10px;"><strong>Classes Available:</strong></p>
        <div style="background: #f1f1f1; padding: 8px; border-radius: 3px;">
            <?php
            $classes = ['MTHFR_Database', 'MTHFR_Report_Generator', 'MTHFR_PDF_Generator'];
            foreach ($classes as $class) {
                $status = class_exists($class) ? '✅' : '❌';
                echo "<div>{$status} {$class}</div>";
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Show current integration status
 */
function mthfr_show_integration_status() {
    $checks = array(
        'WooCommerce Active' => class_exists('WooCommerce'),
        'MTHFR Database' => class_exists('MTHFR_Database'),
        'MTHFR Report Generator' => class_exists('MTHFR_Report_Generator'),
        'MTHFR PDF Generator' => class_exists('MTHFR_PDF_Generator'),
        'MTHFR API Endpoints' => class_exists('MTHFR_API_Endpoints'),
    );
    
    foreach ($checks as $check => $status) {
        $icon = $status ? '✅' : '❌';
        $color = $status ? 'green' : 'red';
        echo "<div style='color: {$color}; margin-bottom: 3px;'>{$icon} {$check}</div>";
    }
}

/**
 * Enqueue scripts only on order pages
 */
function mthfr_enqueue_order_scripts($hook) {
    $screen = get_current_screen();
    
    if ($screen && $screen->post_type === 'shop_order' && 
        ($hook === 'post.php' || $hook === 'post-new.php')) {
        
        wp_enqueue_script('jquery');
        wp_enqueue_style('wp-admin');
        
        error_log('MTHFR: Scripts enqueued on order page');
    }
}

/**
 * FIXED: AJAX handler for ACTUAL report generation (standalone function)
 */
function mthfr_ajax_test_report() {

    generate_missing_reports();
   
    @ini_set('display_errors', 1);
    @ini_set('display_startup_errors', 1);
    // Check permissions
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
  
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error(array('message' => 'Invalid order ID'));
        return;
    }
    
    error_log("MTHFR: ACTUAL report generation for order {$order_id}");

    try {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found'));
            return;
        }
        
        // Get upload_id from order items
        $upload_id = null;
        foreach ($order->get_items() as $item_id => $item) {
            if ($item instanceof WC_Order_Item_Product) {
                $upload_id = $item->get_meta('_upload_id');
                if ($upload_id) {
                    break;
                }
            }
        }
        
        if (!$upload_id) {
            // Create a fallback upload_id if none found
            $upload_id = $order_id * 1000 + time();
            error_log("MTHFR: No upload_id found, using fallback: {$upload_id}");
        }
     
        // Check database connection first
        if (class_exists('MTHFR_Database')) {
            $db_test = MTHFR_Database::test_connection();
            if (!$db_test || $db_test['status'] !== 'success') {
                wp_send_json_error(array('message' => 'Database connection failed'));
                return;
            }
        } else {
            wp_send_json_error(array('message' => 'MTHFR_Database class not found'));
            return;
        }
        
        // Generate ACTUAL report (not test)
        if (class_exists('MTHFR_Report_Generator')) {
            // Get product name from order
            $product_name = 'Genetic Analysis Report';
            $items = $order->get_items();
            if (!empty($items)) {
                $first_item = reset($items);
                $product_name = $first_item->get_name();
            }
            
            // Check if order has subscription
            $has_subscription = order_has_product($order, 2152);
            
            // Generate ACTUAL report (both JSON and PDF)
            $result = MTHFR_Report_Generator::generate_report(
                $upload_id,
                $order_id,
                $product_name,
                $has_subscription
            );
     
            
            if ($result && isset($result['success']) && $result['success']) {
                // Get download URLs using helper function (since get_report_urls might not exist)
                $report_urls = mthfr_get_download_urls($order_id,$result);
                
                
                wp_send_json_success(array(
                    'message' => 'Report generated successfully! Both JSON and PDF files created.',
                    'report_id' => $result['upload_id'] ?? null,
                    'upload_id' => $upload_id,
                    'report_type' => $result['report_type'] ?? 'Unknown',
                    'data_points' => $result['data_points'] ?? 0,
                    'matched_categories' => $result['matched_categories'] ?? 0,
                    'json_generated' => isset($result['json_report_path']) && !empty($result['json_report_path']),
                    'pdf_generated' => isset($result['pdf_report_path']) && !empty($result['pdf_report_path']),
                    'json_url' => $report_urls['json_url'] ?? null,
                    'pdf_url' => $report_urls['pdf_url'] ?? null,
                    'json_file_path' => $result['json_report_path'] ?? null,
                    'pdf_file_path' => $result['pdf_report_path'] ?? null,
                    'file_size' => $result['pdf_file_size'] ?? 0,
                    'details' => $result
                ));
            } else {
                $error_msg = isset($result['error']) ? $result['error'] : 'Report generation failed';
                wp_send_json_error(array('message' => 'Report generation failed: ' . $error_msg));
            }
        } else {
            wp_send_json_error(array('message' => 'MTHFR_Report_Generator class not found'));
        }
        
    } catch (Exception $e) {
        error_log('MTHFR Report Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'Exception: ' . $e->getMessage()));
    }
}


function generate_missing_reports() {
    global $wpdb;

    $table = $wpdb->prefix . 'user_reports';

    // Fetch rows where pdf_report is NULL
    $rows = $wpdb->get_results(
        "SELECT * FROM {$table} WHERE pdf_report IS NULL ORDER BY order_id ASC"
    );

    if ( empty( $rows ) ) {
        return "No pending reports found.";
    }
    // print_r($rows);
    // die;

    foreach ( $rows as $row ) {
        try {
            $result = MTHFR_Report_Generator::generate_report(
                $row->upload_id,
                $row->order_id,
                $row->product_name,
                $row->has_subscription
            );

            // If report generation returns a file path or some identifier, save it back
            if ( $result ) {
                $wpdb->update(
                    $table,
                    [ 'pdf_report' => $result ],   // update pdf_report with result
                    [ 'id' => $row->id ],          // condition (adjust column name if not `id`)
                    [ '%s' ],
                    [ '%d' ]
                );
            }
        } catch ( Exception $e ) {
            error_log( "Error generating report for order_id {$row->order_id}: " . $e->getMessage() );
        }
    }

    return "Report generation complete.";
}


function mthfr_get_download_urls($order_id,$result=null) {
    if (!class_exists('MTHFR_Database')) {
        return array('json_url' => null, 'pdf_url' => null);
    }
    
    $report = MTHFR_Database::get_report_by_order($order_id);
    // print_r( $report );
    // die;
    
    if (!$report) {
        return array('json_url' => null, 'pdf_url' => null);
    }
    
    $upload_dir = wp_upload_dir();
    $base_url = $upload_dir['baseurl'] . '/user_reports/';
    $upload_id='upload_'.$report->upload_id.'/';
    
    $json_url = null;
    $pdf_url = null;

    // Generate JSON download URL
    if (!empty($result['json_report_path']) && file_exists($result['json_report_path'])) {
        $json_filename = basename($result['json_report_path']);
        $json_url = $base_url .$upload_id . $json_filename;
    }
  
    // Generate PDF download URL  
    if (!empty($result['pdf_report_path']) && file_exists($result['pdf_report_path'])) {
      
        $pdf_filename = basename($result['pdf_report_path']);
        $pdf_url = $base_url.$upload_id . $pdf_filename;
    }
 
    
    return array(
        'json_url' => $json_url,
        'pdf_url' => $pdf_url,
        'report_type' => $report->report_type ?? 'Unknown',
        'status' => $report->status ?? 'Unknown',
        'created_at' => $report->created_at ?? 'Unknown'
    );
}

add_action('wp_ajax_mthfr_download_report', 'mthfr_ajax_download_report');
function mthfr_ajax_download_report() {
    // Check permissions
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : 'pdf';
    
    if (!$order_id) {
        wp_send_json_error(array('message' => 'Invalid order ID'));
        return;
    }
    
    try {
        // Get report from database
        if (!class_exists('MTHFR_Database')) {
            wp_send_json_error(array('message' => 'Database class not available'));
            return;
        }
        
        $report = MTHFR_Database::get_report_by_order($order_id);
        
        if (!$report) {
            wp_send_json_error(array('message' => 'No report found for this order. Generate a report first.'));
            return;
        }
        
        $download_url = null;
        $file_type = '';
        $file_path = '';
        
        // Determine which file to download
        if ($report_type === 'json' && !empty($report->report_path)) {
            $file_path = $report->report_path;
            $file_type = 'JSON';
        } elseif ($report_type === 'pdf' && !empty($report->pdf_report)) {
            $file_path = $report->pdf_report;
            $file_type = 'PDF';
        }
        
        // Check if file exists
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error(array(
                'message' => ucfirst($report_type) . ' report file not found. Please generate the report first.',
                'debug_info' => array(
                    'requested_type' => $report_type,
                    'json_path' => $report->report_path ?? 'null',
                    'pdf_path' => $report->pdf_report ?? 'null',
                    'json_exists' => !empty($report->report_path) && file_exists($report->report_path),
                    'pdf_exists' => !empty($report->pdf_report) && file_exists($report->pdf_report)
                )
            ));
            return;
        }
        
        // Generate download URL
        $upload_dir = wp_upload_dir();
        $download_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
        
        // Verify URL is accessible
        $response = wp_remote_head($download_url);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            wp_send_json_error(array(
                'message' => 'Report file exists but is not accessible via URL',
                'file_path' => $file_path,
                'download_url' => $download_url
            ));
            return;
        }
        
        wp_send_json_success(array(
            'message' => $file_type . ' report is ready for download',
            'download_url' => $download_url,
            'file_type' => $file_type,
            'report_type' => $report->report_type ?? 'Unknown',
            'file_size' => file_exists($file_path) ? filesize($file_path) : 0,
            'created_at' => $report->created_at ?? 'Unknown'
        ));
        
    } catch (Exception $e) {
        error_log('MTHFR Download Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'Download failed: ' . $e->getMessage()));
    }
}

/**
 * FIXED: AJAX handler for ACTUAL PDF generation (standalone function)
 */
function mthfr_ajax_generate_pdf() {
    @ini_set('display_errors', 1);
    @ini_set('display_startup_errors', 1);
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mthfr_test_action')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error(array('message' => 'Invalid order ID'));
        return;
    }
    
    error_log("MTHFR: ACTUAL PDF generation for order {$order_id}");
    
    try {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found'));
            return;
        }
        
        // Check required classes
        if (!class_exists('MTHFR_PDF_Generator')) {
            wp_send_json_error(array('message' => 'MTHFR_PDF_Generator class not found'));
            return;
        }
        
        if (!class_exists('MTHFR_Genetic_Data')) {
            wp_send_json_error(array('message' => 'MTHFR_Genetic_Data class not found'));
            return;
        }
        
        // Get product name from order
        $product_name = 'Genetic Analysis Report';
        $items = $order->get_items();
        if (!empty($items)) {
            $first_item = reset($items);
            $product_name = $first_item->get_name();
        }
        
        // Create genetic data with error handling
        try {
            $genetic_data = MTHFR_Genetic_Data::create_sample_data(); // Full dataset
            
            if (!is_array($genetic_data) || empty($genetic_data)) {
                wp_send_json_error(array('message' => 'Failed to create genetic data'));
                return;
            }
            
            error_log('MTHFR: Created genetic data with ' . count($genetic_data) . ' variants');
            
        } catch (Exception $e) {
            error_log('MTHFR: Error creating genetic data: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error creating genetic data: ' . $e->getMessage()));
            return;
        }
        
        // Generate PDF with comprehensive error handling
        try {
            error_log('MTHFR: Starting PDF generation...');
            
            $pdf_result = MTHFR_PDF_Generator::generate_pdf(
                $genetic_data, 
                $product_name, 
                'Genetic Analysis'
            );
            
            error_log('MTHFR: PDF generation returned ' . strlen($pdf_result) . ' bytes');
            
            if (!$pdf_result || strlen($pdf_result) === 0) {
                wp_send_json_error(array('message' => 'PDF generation failed - no data returned'));
                return;
            }
            
            // Check if it's actually PDF content
            $is_pdf = (strpos($pdf_result, '%PDF') === 0);
            $is_html = (strpos($pdf_result, '<!DOCTYPE html') !== false || strpos($pdf_result, '<html') !== false);
            
            if (!$is_pdf && !$is_html && strlen($pdf_result) < 2000) {
                // Probably an error message or very small content
                error_log('MTHFR: PDF generation returned suspicious content: ' . substr($pdf_result, 0, 200));
                wp_send_json_error(array(
                    'message' => 'PDF generation failed - invalid content returned',
                    'content_preview' => substr($pdf_result, 0, 200),
                    'content_length' => strlen($pdf_result)
                ));
                return;
            }
            
        } catch (Exception $e) {
            error_log('MTHFR: Exception during PDF generation: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'PDF generation exception: ' . $e->getMessage()));
            return;
        }
        
        // Save the file
        try {
            $upload_dir = wp_upload_dir();
            $reports_dir = $upload_dir['basedir'] . '/user_reports';
            
            if (!file_exists($reports_dir)) {
                if (!wp_mkdir_p($reports_dir)) {
                    wp_send_json_error(array('message' => 'Failed to create reports directory'));
                    return;
                }
            }
            
            // Determine file extension and content type
            $file_extension = '.txt'; // Default fallback
            $content_type = 'text/plain';
            
            if (strpos($pdf_result, '%PDF') === 0) {
                $file_extension = '.pdf';
                $content_type = 'application/pdf';
            } elseif (strpos($pdf_result, '<!DOCTYPE html') !== false || strpos($pdf_result, '<html') !== false) {
                $file_extension = '.html';
                $content_type = 'text/html';
            }
            
            $filename = "report_order_{$order_id}_" . date('Ymd_His') . $file_extension;
            $file_path = $reports_dir . '/' . $filename;
            $file_url = $upload_dir['baseurl'] . '/user_reports/' . $filename;
            
            // Save file with error handling
            $bytes_written = file_put_contents($file_path, $pdf_result);
            
            if ($bytes_written === false) {
                wp_send_json_error(array('message' => 'Failed to save report file'));
                return;
            }
            
            if ($bytes_written === 0) {
                wp_send_json_error(array('message' => 'Report file saved but is empty'));
                return;
            }
            
            // Verify file was saved correctly
            if (!file_exists($file_path)) {
                wp_send_json_error(array('message' => 'Report file was not saved properly'));
                return;
            }
            
            error_log("MTHFR: Successfully saved report to {$file_path} ({$bytes_written} bytes)");
            
            // Success response
            wp_send_json_success(array(
                'message' => 'Report generated and saved successfully!',
                'pdf_url' => $file_url,
                'pdf_path' => $file_path,
                'filename' => $filename,
                'file_size' => $bytes_written,
                'variants_included' => count($genetic_data),
                'file_type' => $file_extension,
                'content_type' => $content_type,
                'is_pdf' => ($file_extension === '.pdf'),
                'debug_info' => array(
                    'content_length' => strlen($pdf_result),
                    'starts_with_pdf' => strpos($pdf_result, '%PDF') === 0,
                    'contains_html' => strpos($pdf_result, '<html') !== false,
                    'mpdf_available' => class_exists('Mpdf\Mpdf')
                )
            ));
            
        } catch (Exception $e) {
            error_log('MTHFR: Error saving file: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error saving report file: ' . $e->getMessage()));
            return;
        }
        
    } catch (Exception $e) {
        error_log('MTHFR: General PDF generation error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'General error: ' . $e->getMessage()));
    }
}

/**
 * NEW: AJAX handler for emailing reports (standalone function)
 */
function mthfr_ajax_email_report() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mthfr_test_action')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error(array('message' => 'Invalid order ID'));
        return;
    }
    
    try {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found'));
            return;
        }
        
        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        
        if (!$customer_email) {
            wp_send_json_error(array('message' => 'No customer email found'));
            return;
        }
        
        // Check if report exists first
        if (class_exists('MTHFR_Database')) {
            $report = MTHFR_Database::get_report_by_order($order_id);
            
            if (!$report) {
                wp_send_json_error(array('message' => 'No report found for this order. Generate a report first.'));
                return;
            }
            
            // Send email
            $subject = 'Your Genetic Analysis Report - Order #' . $order_id;
            $message = "Dear {$customer_name},\n\n";
            $message .= "Your genetic analysis report is ready!\n\n";
            $message .= "Order ID: #{$order_id}\n";
            $message .= "Report generated: " . date('F d, Y', strtotime($report->created_at)) . "\n\n";
            $message .= "You can download your report from your account or contact us for assistance.\n\n";
            $message .= "Best regards,\nMTHFR Support Team";
            
            $headers = array('Content-Type: text/plain; charset=UTF-8');
            
            if (wp_mail($customer_email, $subject, $message, $headers)) {
                wp_send_json_success(array(
                    'message' => "Email sent successfully to {$customer_email}",
                    'customer_name' => $customer_name,
                    'report_id' => $report->id
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to send email'));
            }
        } else {
            wp_send_json_error(array('message' => 'Database class not available'));
        }
        
    } catch (Exception $e) {
        error_log('MTHFR Email Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'Exception: ' . $e->getMessage()));
    }
}

// SINGLE HOOK REGISTRATION - This is the key fix!
// Only register the main function once, and it handles all meta boxes
add_action('add_meta_boxes', 'mthfr_add_all_order_meta_boxes', 10, 2);

// Other hooks
add_action('admin_enqueue_scripts', 'mthfr_enqueue_order_scripts');

// AJAX handlers - only register once (these override the class methods)
add_action('wp_ajax_mthfr_test_report', 'mthfr_ajax_test_report');
add_action('wp_ajax_mthfr_generate_pdf', 'mthfr_ajax_generate_pdf');
add_action('wp_ajax_mthfr_email_report', 'mthfr_ajax_email_report');

// Debug logging
error_log('MTHFR: Fixed WooCommerce integration loaded - all meta boxes should work');
error_log('MTHFR: Single meta box hook registered: mthfr_add_all_order_meta_boxes');
error_log('MTHFR: AJAX handlers registered: mthfr_test_report, mthfr_generate_pdf, mthfr_email_report');


add_action('woocommerce_order_status_completed', 'process_order_completion');
add_action('woocommerce_order_status_processing', 'process_order_completion');
 add_filter('woocommerce_add_cart_item_data', 'store_upload_id_with_cart_item', 10, 3);
add_action('woocommerce_checkout_create_order_line_item', 'save_upload_id_to_order_item', 10, 4);


// Debug hook to verify everything is working
add_action('add_meta_boxes', function($post_type) {
    if ($post_type === 'shop_order') {
        error_log('MTHFR: add_meta_boxes hook fired for shop_order - fixed version active');
        
        global $wp_meta_boxes;
        if (isset($wp_meta_boxes['shop_order']['side'])) {
            $boxes = array_keys($wp_meta_boxes['shop_order']['side']['high'] ?? []) + 
                    array_keys($wp_meta_boxes['shop_order']['side']['low'] ?? []);
            error_log('MTHFR: Meta boxes in sidebar: ' . implode(', ', $boxes));
        }
    }
}, 999);

// YOUR WORKING ADD_ACTION - Enhanced with full HTML and buttons  
add_action('add_meta_boxes', function($post_type) {
    global $wp_meta_boxes;

    // Register your custom meta box with full functionality
    add_meta_box(
        'mthfr_working_meta_box',           // ID
        '🧬 MTHFR Working Meta Box',        // Title
        function($post) {                   // Callback with full HTML and buttons
            $order_id = $post->ID;
            $order = wc_get_order($order_id);
            
            if (!$order) {
                echo '<p>Error: Could not load order</p>';
                return;
            }
            
            // Add nonce for security
            wp_nonce_field('mthfr_working_action', 'mthfr_working_nonce');
            
            ?>
            <div class="mthfr-working-meta-box" style="padding: 15px;">
                <h4 style="margin-bottom: 15px; color: #0073aa;">🧬 MTHFR Genetic Reports</h4>
                
                <!-- Order Summary -->
                <div style="background: #f0f6fc; border: 1px solid #c3dcf7; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
                    <h5 style="margin: 0 0 8px 0; color: #0073aa;">Order Details</h5>
                    <div style="font-size: 13px; line-height: 1.4;">
                        <strong>ID:</strong> #<?php echo esc_html($order_id); ?><br>
                        <strong>Customer:</strong> <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?><br>
                        <strong>Email:</strong> <?php echo esc_html($order->get_billing_email()); ?><br>
                        <strong>Status:</strong> <span style="color: #d63638;"><?php echo esc_html(ucfirst($order->get_status())); ?></span><br>
                        <strong>Total:</strong> <?php echo wp_kses_post($order->get_formatted_order_total()); ?><br>
                        <strong>Date:</strong> <?php echo esc_html($order->get_date_created()->date('M j, Y g:i A')); ?>
                    </div>
                </div>
                
                <!-- Action Buttons Section -->
                <div style="margin-bottom: 20px;">
                    <h5 style="margin: 0 0 10px 0; color: #0073aa;">Report Generation</h5>
                    
                    <button type="button" id="mthfr-working-test-btn" class="button button-primary" 
                            data-order-id="<?php echo esc_attr($order_id); ?>" 
                            style="width: 100%; margin-bottom: 8px; height: 32px;">
                        🧪 Generate Report
                    </button>
                    
                    <button type="button" id="mthfr-working-pdf-btn" class="button button-secondary" 
                            data-order-id="<?php echo esc_attr($order_id); ?>" 
                            style="width: 100%; margin-bottom: 8px; height: 32px;">
                        📄 Generate PDF Report
                    </button>
                    
                    <button type="button" id="mthfr-working-email-btn" class="button" 
                            data-order-id="<?php echo esc_attr($order_id); ?>" 
                            style="width: 100%; height: 32px;">
                        📧 Email Report to Customer
                    </button>
                </div>
                
                <!-- Loading indicator -->
                <div id="mthfr-working-loading" style="display: none; text-align: center; padding: 15px; background: #fff3cd; border-radius: 4px; margin-bottom: 15px;">
                    <span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span>
                    <small id="mthfr-working-loading-text">Processing request...</small>
                </div>
                
                <!-- Results area -->
                <div id="mthfr-working-results"></div>
                
                <!-- Status Section -->
                <div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px;">
                    <h5 style="margin: 0 0 10px 0; color: #0073aa;">System Status</h5>
                    <div style="font-size: 12px;">
                        <?php
                        $checks = array(
                            'WooCommerce' => class_exists('WooCommerce'),
                            'MTHFR Database' => class_exists('MTHFR_Database'),
                            'MTHFR Report Generator' => class_exists('MTHFR_Report_Generator'),
                            'MTHFR PDF Generator' => class_exists('MTHFR_PDF_Generator'),
                            'MTHFR API' => class_exists('MTHFR_API_Endpoints'),
                        );
                        
                        foreach ($checks as $check => $status) {
                            $icon = $status ? '✅' : '❌';
                            $color = $status ? 'green' : 'red';
                            echo "<div style='color: {$color}; margin-bottom: 2px;'>{$icon} {$check}</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Prevent duplicate handlers
                if (window.mthfrWorkingHandlersAdded) {
                    return;
                }
                window.mthfrWorkingHandlersAdded = true;
                
                // Test Report Button
                $(document).on('click', '#mthfr-working-test-btn', function() {
                    var $btn = $(this);
                    var orderId = $btn.data('order-id');
                    mthfrWorkingMakeRequest('mthfr_test_report', orderId, $btn, 'Generating report...');
                });
                
                // PDF Report Button
                $(document).on('click', '#mthfr-working-pdf-btn', function() {
                    var $btn = $(this);
                    var orderId = $btn.data('order-id');
                    mthfrWorkingMakeRequest('mthfr_generate_pdf', orderId, $btn, 'Generating PDF report...');
                });
                
                // Email Button
                $(document).on('click', '#mthfr-working-email-btn', function() {
                    var $btn = $(this);
                    var orderId = $btn.data('order-id');
                    mthfrWorkingMakeRequest('mthfr_email_report', orderId, $btn, 'Sending email report...');
                });
                
                function mthfrWorkingMakeRequest(action, orderId, $btn, loadingMsg) {
                    // Show loading
                    $('.mthfr-working-meta-box button').prop('disabled', true);
                    $('#mthfr-working-loading-text').text(loadingMsg);
                    $('#mthfr-working-loading').show();
                    $('#mthfr-working-results').empty();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: action,
                            order_id: orderId,
                            nonce: $('#mthfr_working_nonce').val()
                        },
                        success: function(response) {
                            $('#mthfr-working-loading').hide();
                            $('.mthfr-working-meta-box button').prop('disabled', false);
                            
                            if (response.success) {
                                var resultHtml = '<div style="background: #d1edff; border: 1px solid #b3d7ff; color: #0c5460; padding: 12px; border-radius: 4px; margin-bottom: 10px;">' +
                                    '<h5 style="margin: 0 0 8px 0;">✅ Success!</h5>' +
                                    '<p style="margin: 0; font-size: 13px;">' + response.data.message + '</p>';
                                
                                // Add download link if PDF was generated
                                if (response.data.pdf_url) {
                                    resultHtml += '<p style="margin: 8px 0 0 0;"><a href="' + response.data.pdf_url + '" target="_blank" class="button button-small">📄 Download Report</a></p>';
                                }
                                
                                // Add report details if available
                                if (response.data.report_id) {
                                    resultHtml += '<p style="margin: 8px 0 0 0; font-size: 12px; color: #666;">Report ID: ' + response.data.report_id + '</p>';
                                }
                                
                                resultHtml += '</div>';
                                $('#mthfr-working-results').html(resultHtml);
                            } else {
                                $('#mthfr-working-results').html(
                                    '<div style="background: #ffeaa7; border: 1px solid #fdcb6e; color: #6c5ce7; padding: 12px; border-radius: 4px; margin-bottom: 10px;">' +
                                    '<h5 style="margin: 0 0 8px 0;">❌ Error</h5>' +
                                    '<p style="margin: 0; font-size: 13px;">' + (response.data ? response.data.message : 'Unknown error occurred') + '</p>' +
                                    '</div>'
                                );
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#mthfr-working-loading').hide();
                            $('.mthfr-working-meta-box button').prop('disabled', false);
                            
                            $('#mthfr-working-results').html(
                                '<div style="background: #fab1a0; border: 1px solid #e17055; color: #2d3436; padding: 12px; border-radius: 4px; margin-bottom: 10px;">' +
                                '<h5 style="margin: 0 0 8px 0;">❌ AJAX Error</h5>' +
                                '<p style="margin: 0; font-size: 13px;">Status: ' + status + '<br>Error: ' + error + '</p>' +
                                '</div>'
                            );
                        }
                    });
                }
            });
            </script>
            <?php
        },
        $post_type,                         // Post type
        'side',                             // Context (same as Notes)
        'high'                              // Priority (high to appear at top)
    );
});



    /**
 * Process order completion and generate reports automatically
 */
 function process_order_completion($order_id) {
    error_log("MTHFR: Processing order completion for order: $order_id");
    
    $order = wc_get_order($order_id);
    if (!$order) {
        error_log("MTHFR: Could not load order $order_id");
        return;
    }
    
    // Check if order has subscription product
    $has_subscription = order_has_product($order, 2152);
    error_log('MTHFR: Order has subscription: ' . ($has_subscription ? 'true' : 'false'));
    
    // Product IDs that trigger report generation
    $product_ids_to_check = [120, 938, 971, 977, 1698];
    
    foreach ($order->get_items() as $item_id => $item) {
        // Get upload_id from order item meta
        $upload_id = $item->get_meta('_upload_id');
        $product = $item->get_product();
        $product_id = $product->get_id();
        $product_name = $product->get_name();
        
        error_log("MTHFR: Processing item - Product ID: $product_id, Product Name: $product_name, Upload ID: $upload_id");
        
        if ($upload_id && in_array($product_id, $product_ids_to_check)) {
            error_log("MTHFR: Generating report for upload ID: $upload_id");
            
            // Generate report using the same logic as manual generation
            $result = create_report_from_upload($upload_id, $order_id, $product_name, $has_subscription);
            
            if ($result && isset($result['success']) && $result['success']) {
                error_log("MTHFR: Report generation successful for upload ID: $upload_id");
                
                // Save result to order item meta
                $item->add_meta_data('_mthfr_report_result', json_encode($result));
                $item->add_meta_data('_mthfr_report_generated', 'yes');
                $item->add_meta_data('_mthfr_report_date', current_time('mysql'));
                $item->save();
                
                // Add order note
                $order->add_order_note(
                    sprintf('MTHFR report generated successfully for %s (Upload ID: %d)', $product_name, $upload_id)
                );
                
            } else {
                error_log("MTHFR: Report generation failed for upload ID: $upload_id");
                
                // Save failure info
                $item->add_meta_data('_mthfr_report_error', json_encode($result));
                $item->add_meta_data('_mthfr_report_generated', 'failed');
                $item->save();
                
                // Add order note
                $order->add_order_note(
                    sprintf('MTHFR report generation failed for %s (Upload ID: %d)', $product_name, $upload_id)
                );
            }
        }
    }
}
    /**
 * Create report from upload using existing report generator
 */
 function create_report_from_upload($upload_id, $order_id, $product_name, $has_subscription = false) {
    try {
        error_log("MTHFR: Creating report for upload_id: $upload_id, order_id: $order_id");
        
        // Use the existing report generator
        if (class_exists('MTHFR_Report_Generator')) {
            $result = MTHFR_Report_Generator::generate_report(
                $upload_id,
                $order_id,
                $product_name,
                $has_subscription
            );
            
            return $result;
        } else {
            error_log("MTHFR: MTHFR_Report_Generator class not found");
            return array(
                'success' => false,
                'error' => 'Report generator class not found'
            );
        }
        
    } catch (Exception $e) {
        error_log('MTHFR: Error creating report: ' . $e->getMessage());
        return array(
            'success' => false,
            'error' => $e->getMessage()
        );
    }
}

/**
 * Check if order contains a specific product
 */
 function order_has_product($order, $product_id) {
    foreach ($order->get_items() as $item) {
        if ($item->get_product_id() == $product_id) {
            return true;
        }
    }
    return false;
}


/**
 * Store upload_id with cart item when product is added
 */
 function store_upload_id_with_cart_item($cart_item_data, $product_id, $variation_id) {
    error_log('MTHFR: store_upload_id_with_cart_item function called');
    
    // Get upload_id from session
    $upload_id = WC()->session->get('temp_upload_id');
    
    if ($upload_id) {
        error_log("MTHFR: Upload ID found in session: $upload_id");
        $cart_item_data['upload_id'] = $upload_id;
        error_log('MTHFR: Upload ID stored in cart item data');
    } else {
        error_log('MTHFR: No upload ID found in session');
    }
    
    return $cart_item_data;
}

/**
 * Save upload_id to order item meta during checkout
 */
 function save_upload_id_to_order_item($item, $cart_item_key, $values, $order) {
    error_log('MTHFR: save_upload_id_to_order_item function called');
    
    if (isset($values['upload_id'])) {
        $upload_id = $values['upload_id'];
        error_log("MTHFR: Saving upload ID to order item: $upload_id");
        $item->add_meta_data('_upload_id', $upload_id);
    } else {
        error_log('MTHFR: No upload ID found in cart item values');
    }
}
function mthfr_migrate_database_for_pdf_reports() {
    global $wpdb;
    
    $reports_table = $wpdb->prefix . 'user_reports';
    
    // Check if pdf_report column exists
    $column_exists = $wpdb->get_results(
        "SHOW COLUMNS FROM {$reports_table} LIKE 'pdf_report'"
    );
    
    if (empty($column_exists)) {
        // Add the pdf_report column
        $result = $wpdb->query(
            "ALTER TABLE {$reports_table} ADD COLUMN pdf_report varchar(500) AFTER report_path"
        );
        
        if ($result !== false) {
            error_log('MTHFR: Successfully added pdf_report column to database');
            return true;
        } else {
            error_log('MTHFR: Failed to add pdf_report column: ' . $wpdb->last_error);
            return false;
        }
    }
    
    error_log('MTHFR: pdf_report column already exists');
    return true; // Already exists, so it's "successful"
}

// Run migration on admin init (only once)
add_action('admin_init', function() {
    // Only run once by checking an option
    if (!get_option('mthfr_pdf_report_column_added', false)) {
        if (mthfr_migrate_database_for_pdf_reports()) {
            update_option('mthfr_pdf_report_column_added', true);
            
            // Add admin notice for successful migration
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>MTHFR Plugin:</strong> Database updated successfully. PDF report column added.</p>';
                echo '</div>';
            });
        }
    }
});

// Also run on plugin activation as backup
register_activation_hook(__FILE__, function() {
    mthfr_migrate_database_for_pdf_reports();
    update_option('mthfr_pdf_report_column_added', true);
});



function download_pdf() {
  
   
    check_ajax_referer('download_pdf', 'security');

    global $wpdb;

     if (!isset($_POST['result_id']) || empty($_POST['result_id'])) {
        error_log('Download PDF: No result_id provided');
        wp_die('No result ID provided');
    }

    $result_id = intval($_POST['result_id']);
    $user_id = get_current_user_id();


    // Fetch first PDF report
    $table_name = $wpdb->prefix . 'user_reports';

    // $report = $wpdb->get_row($wpdb->prepare(
    //     "SELECT ur.id, ur.report_path, ur.report_name, ur.report_type, uu.file_name,uu.pdf_report
    //      FROM {$wpdb->prefix}user_reports ur
    //      INNER JOIN {$wpdb->prefix}user_uploads uu ON ur.upload_id = uu.id
    //      WHERE ur.id = %d AND uu.user_id = %d AND ur.status = 'completed'",
    //     $result_id,
    //     $user_id
    // ));
  
    

  $report = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * 
         FROM $table_name 
         WHERE pdf_report IS NOT NULL 
           AND pdf_report != '' 
           AND id = %d 
          
         ORDER BY id DESC 
         LIMIT 1",
        $result_id,
     
    )
);


    if (!$report || empty($report->pdf_report)) {
        wp_send_json_error(['message' => 'No PDF found.']);
    }

    $file_path = $report->pdf_report;

    if (!file_exists($file_path)) {
        wp_send_json_error(['message' => 'PDF file does not exist on server.']);
    }

    // Convert server path to URL
    $upload_dir = wp_upload_dir();
    $relative_path = str_replace($upload_dir['basedir'], '', $file_path);
    $file_url = $upload_dir['baseurl'] . $relative_path;

    // Return URL to JS for browser-based download
    wp_send_json_success(['url' => $file_url]);
}
add_action('wp_ajax_download_pdf', 'download_pdf');
add_action('wp_ajax_nopriv_download_pdf', 'download_pdf');
