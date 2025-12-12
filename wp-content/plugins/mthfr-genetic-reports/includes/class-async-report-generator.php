<?php
/**
 * MTHFR Async Report Generator
 * Background job system for generating reports asynchronously using Action Scheduler
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_Async_Report_Generator {

    const HOOK_GENERATE_REPORT = 'mthfr_generate_report_async';
    const HOOK_RETRY_FAILED_REPORT = 'mthfr_retry_failed_report';
    const MAX_RETRIES = 3;
    const RETRY_DELAY_MINUTES = 5;

    /**
     * Initialize the async report generator
     */
    public static function init() {
        // Register Action Scheduler hooks
        add_action(self::HOOK_GENERATE_REPORT, array(__CLASS__, 'process_report_generation'), 10, 1);
        add_action(self::HOOK_RETRY_FAILED_REPORT, array(__CLASS__, 'process_retry_failed_report'), 10, 1);

        // Add admin interface for managing async jobs
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));

        // AJAX handlers for status checking
        add_action('wp_ajax_mthfr_check_report_status', array(__CLASS__, 'ajax_check_report_status'));
        add_action('wp_ajax_mthfr_cancel_report_job', array(__CLASS__, 'ajax_cancel_report_job'));

        error_log('MTHFR: Async Report Generator initialized');
    }

    /**
     * Schedule a report generation job
     *
     * @param int $upload_id
     * @param int $order_id
     * @param string $product_name
     * @param bool $has_subscription
     * @param array $options Additional options
     * @return int Action ID
     */
    public static function schedule_report_generation($upload_id, $order_id, $product_name, $has_subscription = false, $options = array()) {
        $job_data = array(
            'upload_id' => $upload_id,
            'order_id' => $order_id,
            'product_name' => $product_name,
            'has_subscription' => $has_subscription,
            'options' => $options,
            'scheduled_at' => current_time('mysql'),
            'attempt' => 1
        );

        // Schedule the job with a 10-second delay to avoid interfering with checkout API response
        $action_id = as_schedule_single_action(
            time() + 10,
            self::HOOK_GENERATE_REPORT,
            $job_data,
            'mthfr-reports'
        );

        if ($action_id) {
            // Store job tracking information
            self::create_job_tracking_record($action_id, $job_data);

            error_log("MTHFR: Scheduled async report generation for order {$order_id}, upload {$upload_id}, action ID: {$action_id}");
        } else {
            error_log("MTHFR: Failed to schedule async report generation for order {$order_id}");
        }

        return $action_id;
    }

    /**
     * Process the report generation job
     *
     * @param array $job_data
     */
    public static function process_report_generation($job_data) {
        $upload_id = $job_data['upload_id'];
        $order_id = $job_data['order_id'];
        $product_name = $job_data['product_name'];
        $has_subscription = $job_data['has_subscription'];
        $attempt = $job_data['attempt'] ?? 1;

        error_log("MTHFR: Processing async report generation - Order: {$order_id}, Upload: {$upload_id}, Attempt: {$attempt}");

        try {
            // Update job status to processing
            self::update_job_status($job_data, 'processing');

            // Generate the report using existing logic
            if (!class_exists('MTHFR_Report_Generator')) {
                throw new Exception('MTHFR_Report_Generator class not found');
            }

            $result = MTHFR_Report_Generator::generate_report(
                $upload_id,
                $order_id,
                $product_name,
                $has_subscription
            );

            if ($result && isset($result['success']) && $result['success']) {
                // Success - update job status
                self::update_job_status($job_data, 'completed', $result);

                // Send completion notification if email is enabled
                if (!empty($job_data['options']['notify_email'])) {
                    self::send_completion_notification($order_id, $result);
                }

                error_log("MTHFR: Async report generation completed successfully for order {$order_id}");
            } else {
                // Handle failure
                $error_message = isset($result['error']) ? $result['error'] : 'Unknown error during report generation';
                self::handle_job_failure($job_data, $error_message, $attempt);
            }

        } catch (Exception $e) {
            error_log("MTHFR: Exception during async report generation for order {$order_id}: " . $e->getMessage());
            self::handle_job_failure($job_data, $e->getMessage(), $attempt);
        }
    }

    /**
     * Handle job failure with retry logic
     *
     * @param array $job_data
     * @param string $error_message
     * @param int $attempt
     */
    private static function handle_job_failure($job_data, $error_message, $attempt) {
        $max_retries = self::MAX_RETRIES;

        if ($attempt < $max_retries) {
            // Schedule retry
            $retry_data = $job_data;
            $retry_data['attempt'] = $attempt + 1;
            $retry_data['last_error'] = $error_message;

            $retry_delay = self::RETRY_DELAY_MINUTES * 60 * $attempt; // Exponential backoff

            $retry_action_id = as_schedule_single_action(
                time() + $retry_delay,
                self::HOOK_GENERATE_REPORT,
                $retry_data,
                'mthfr-reports'
            );

            if ($retry_action_id) {
                self::update_job_status($job_data, 'retry_scheduled', array(
                    'error' => $error_message,
                    'next_attempt' => $attempt + 1,
                    'retry_action_id' => $retry_action_id,
                    'retry_at' => date('Y-m-d H:i:s', time() + $retry_delay)
                ));

                error_log("MTHFR: Scheduled retry {$retry_data['attempt']} for order {$job_data['order_id']} in {$retry_delay} seconds");
            } else {
                self::update_job_status($job_data, 'failed', array(
                    'error' => $error_message,
                    'final_attempt' => $attempt
                ));
                error_log("MTHFR: Failed to schedule retry for order {$job_data['order_id']}");
            }
        } else {
            // Max retries reached - mark as permanently failed
            self::update_job_status($job_data, 'failed', array(
                'error' => $error_message,
                'final_attempt' => $attempt,
                'max_retries_reached' => true
            ));

            // Send failure notification
            self::send_failure_notification($job_data['order_id'], $error_message);

            error_log("MTHFR: Report generation permanently failed for order {$job_data['order_id']} after {$attempt} attempts");
        }
    }

    /**
     * Process retry for failed reports
     *
     * @param array $job_data
     */
    public static function process_retry_failed_report($job_data) {
        // This is a hook for manual retries - just call the main processing function
        self::process_report_generation($job_data);
    }

    /**
     * Create job tracking record in database
     *
     * @param int $action_id
     * @param array $job_data
     */
    private static function create_job_tracking_record($action_id, $job_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mthfr_async_jobs';

        // Ensure table exists
        self::create_jobs_table();

        $wpdb->insert(
            $table_name,
            array(
                'action_id' => $action_id,
                'order_id' => $job_data['order_id'],
                'upload_id' => $job_data['upload_id'],
                'product_name' => $job_data['product_name'],
                'status' => 'scheduled',
                'attempt' => 1,
                'scheduled_at' => current_time('mysql'),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s')
        );
    }

    /**
     * Update job status
     *
     * @param array $job_data
     * @param string $status
     * @param array $additional_data
     */
    private static function update_job_status($job_data, $status, $additional_data = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mthfr_async_jobs';

        $update_data = array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        );

        $format = array('%s', '%s');

        // Add additional data based on status
        if ($status === 'processing') {
            $update_data['processing_started_at'] = current_time('mysql');
            $format[] = '%s';
        } elseif ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
            $update_data['result_data'] = json_encode($additional_data);
            $format[] = '%s';
            $format[] = '%s';
        } elseif ($status === 'failed') {
            $update_data['failed_at'] = current_time('mysql');
            $update_data['error_message'] = $additional_data['error'] ?? '';
            $update_data['attempt'] = $additional_data['final_attempt'] ?? $job_data['attempt'];
            $format[] = '%s';
            $format[] = '%s';
            $format[] = '%d';
        } elseif ($status === 'retry_scheduled') {
            $update_data['last_error'] = $additional_data['error'] ?? '';
            $update_data['retry_scheduled_at'] = current_time('mysql');
            $update_data['next_attempt'] = $additional_data['next_attempt'] ?? 1;
            $format[] = '%s';
            $format[] = '%s';
            $format[] = '%d';
        }

        $wpdb->update(
            $table_name,
            $update_data,
            array('order_id' => $job_data['order_id'], 'upload_id' => $job_data['upload_id']),
            $format,
            array('%d', '%d')
        );
    }

    /**
     * Create the async jobs tracking table
     */
    private static function create_jobs_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mthfr_async_jobs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            action_id bigint(20) NOT NULL,
            order_id int(11) NOT NULL,
            upload_id int(11) NOT NULL,
            product_name varchar(255) NOT NULL,
            status enum('scheduled','processing','completed','failed','retry_scheduled','cancelled') DEFAULT 'scheduled',
            attempt int(11) DEFAULT 1,
            scheduled_at datetime DEFAULT NULL,
            processing_started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            failed_at datetime DEFAULT NULL,
            retry_scheduled_at datetime DEFAULT NULL,
            next_attempt int(11) DEFAULT NULL,
            last_error text,
            error_message text,
            result_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_order_upload (order_id, upload_id),
            KEY action_id (action_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get job status for an order
     *
     * @param int $order_id
     * @param int $upload_id
     * @return array|null
     */
    public static function get_job_status($order_id, $upload_id = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mthfr_async_jobs';

        $where = array('order_id' => $order_id);
        $format = array('%d');

        if ($upload_id) {
            $where['upload_id'] = $upload_id;
            $format[] = '%d';
        }

        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE order_id = %d" .
                ($upload_id ? " AND upload_id = %d" : "") .
                " ORDER BY created_at DESC LIMIT 1",
                $where
            ),
            ARRAY_A
        );

        return $job;
    }

    /**
     * Send completion notification email
     *
     * @param int $order_id
     * @param array $result
     */
    private static function send_completion_notification($order_id, $result) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

        if (!$customer_email) return;

        $subject = 'Your Genetic Analysis Report is Ready - Order #' . $order_id;
        $message = "Dear {$customer_name},\n\n";
        $message .= "Your genetic analysis report has been generated and is now ready for download!\n\n";
        $message .= "Order ID: #{$order_id}\n";
        $message .= "Generated: " . date('F d, Y \a\t g:i A') . "\n\n";
        $message .= "You can access your report from your account dashboard.\n\n";
        $message .= "Best regards,\nMTHFR Support Team";

        wp_mail($customer_email, $subject, $message);
    }

    /**
     * Send failure notification email
     *
     * @param int $order_id
     * @param string $error_message
     */
    private static function send_failure_notification($order_id, $error_message) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

        if (!$customer_email) return;

        $subject = 'Report Generation Issue - Order #' . $order_id;
        $message = "Dear {$customer_name},\n\n";
        $message .= "We encountered an issue while generating your genetic analysis report.\n\n";
        $message .= "Order ID: #{$order_id}\n";
        $message .= "Issue: {$error_message}\n\n";
        $message .= "Our team has been notified and will resolve this issue shortly. ";
        $message .= "You will receive another email once your report is ready.\n\n";
        $message .= "Best regards,\nMTHFR Support Team";

        wp_mail($customer_email, $subject, $message);
    }

    /**
     * Add admin menu for managing async jobs
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'mthfr-reports',
            'Async Jobs',
            'Async Jobs',
            'manage_options',
            'mthfr-async-jobs',
            array(__CLASS__, 'admin_page')
        );
    }

    /**
     * Admin page for managing async jobs
     */
    public static function admin_page() {
        ?>
        <div class="wrap">
            <h1>MTHFR Async Report Jobs</h1>

            <div class="card">
                <h2>Job Queue Status</h2>
                <?php self::show_queue_status(); ?>
            </div>

            <div class="card">
                <h2>Recent Jobs</h2>
                <?php self::show_recent_jobs(); ?>
            </div>

            <div class="card">
                <h2>Failed Jobs</h2>
                <?php self::show_failed_jobs(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Show queue status
     */
    private static function show_queue_status() {
        if (!function_exists('as_get_scheduled_actions')) {
            echo '<p>Action Scheduler not available</p>';
            return;
        }

        $scheduled = as_get_scheduled_actions(array(
            'hook' => self::HOOK_GENERATE_REPORT,
            'status' => 'pending'
        ));

        $running = as_get_scheduled_actions(array(
            'hook' => self::HOOK_GENERATE_REPORT,
            'status' => 'in-progress'
        ));

        echo '<table class="form-table">';
        echo '<tr><th>Scheduled Jobs:</th><td>' . count($scheduled) . '</td></tr>';
        echo '<tr><th>Running Jobs:</th><td>' . count($running) . '</td></tr>';
        echo '<tr><th>Total Queue:</th><td>' . (count($scheduled) + count($running)) . '</td></tr>';
        echo '</table>';
    }

    /**
     * Show recent jobs
     */
    private static function show_recent_jobs() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mthfr_async_jobs';

        $jobs = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 20"
        );

        if (empty($jobs)) {
            echo '<p>No jobs found</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Order ID</th><th>Product</th><th>Status</th><th>Attempt</th><th>Created</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        foreach ($jobs as $job) {
            $status_class = 'status-' . str_replace('_', '-', $job->status);
            echo '<tr>';
            echo '<td><a href="' . admin_url('post.php?post=' . $job->order_id . '&action=edit') . '">' . $job->order_id . '</a></td>';
            echo '<td>' . esc_html($job->product_name) . '</td>';
            echo '<td><span class="' . $status_class . '">' . ucfirst(str_replace('_', ' ', $job->status)) . '</span></td>';
            echo '<td>' . $job->attempt . '</td>';
            echo '<td>' . date('M j, g:i A', strtotime($job->created_at)) . '</td>';
            echo '<td>';
            if ($job->status === 'failed') {
                echo '<button class="button button-small" onclick="retryJob(' . $job->id . ')">Retry</button>';
            }
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Show failed jobs
     */
    private static function show_failed_jobs() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mthfr_async_jobs';

        $jobs = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE status = 'failed' ORDER BY failed_at DESC LIMIT 10"
        );

        if (empty($jobs)) {
            echo '<p>No failed jobs</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Order ID</th><th>Product</th><th>Error</th><th>Failed At</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        foreach ($jobs as $job) {
            echo '<tr>';
            echo '<td><a href="' . admin_url('post.php?post=' . $job->order_id . '&action=edit') . '">' . $job->order_id . '</a></td>';
            echo '<td>' . esc_html($job->product_name) . '</td>';
            echo '<td>' . esc_html(substr($job->error_message, 0, 100)) . '...</td>';
            echo '<td>' . date('M j, g:i A', strtotime($job->failed_at)) . '</td>';
            echo '<td><button class="button button-small" onclick="retryJob(' . $job->id . ')">Retry</button></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * AJAX handler for checking report status
     */
    public static function ajax_check_report_status() {
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $upload_id = isset($_POST['upload_id']) ? intval($_POST['upload_id']) : null;

        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }

        $job = self::get_job_status($order_id, $upload_id);

        if (!$job) {
            wp_send_json_error('No job found for this order');
            return;
        }

        wp_send_json_success(array(
            'status' => $job['status'],
            'attempt' => $job['attempt'],
            'created_at' => $job['created_at'],
            'updated_at' => $job['updated_at'],
            'error_message' => $job['error_message'] ?? null,
            'result_data' => $job['result_data'] ? json_decode($job['result_data'], true) : null
        ));
    }

    /**
     * AJAX handler for canceling report job
     */
    public static function ajax_cancel_report_job() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $upload_id = isset($_POST['upload_id']) ? intval($_POST['upload_id']) : null;

        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
            return;
        }

        // Cancel Action Scheduler action
        $job = self::get_job_status($order_id, $upload_id);
        if ($job && $job['action_id']) {
            as_unschedule_action(self::HOOK_GENERATE_REPORT, array(
                'upload_id' => $upload_id,
                'order_id' => $order_id
            ));
        }

        // Update job status
        self::update_job_status(
            array('order_id' => $order_id, 'upload_id' => $upload_id),
            'cancelled'
        );

        wp_send_json_success('Job cancelled successfully');
    }

    /**
     * Manually retry a failed job
     *
     * @param int $job_id
     */
    public static function retry_failed_job($job_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mthfr_async_jobs';
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $job_id));

        if (!$job) {
            return false;
        }

        $job_data = array(
            'upload_id' => $job->upload_id,
            'order_id' => $job->order_id,
            'product_name' => $job->product_name,
            'has_subscription' => false, // Could be stored in job data
            'attempt' => ($job->attempt ?? 0) + 1
        );

        // Schedule retry
        $action_id = as_schedule_single_action(
            time(),
            self::HOOK_GENERATE_REPORT,
            $job_data,
            'mthfr-reports'
        );

        if ($action_id) {
            // Update job record
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'retry_scheduled',
                    'action_id' => $action_id,
                    'attempt' => $job_data['attempt'],
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $job_id),
                array('%s', '%d', '%d', '%s'),
                array('%d')
            );

            return true;
        }

        return false;
    }
}

// Initialize the async report generator
MTHFR_Async_Report_Generator::init();