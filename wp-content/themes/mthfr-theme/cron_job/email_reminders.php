<?php

require_once(dirname(__FILE__, 5) . '/wp-load.php');

function send_pending_reminders() {
    global $wpdb;
    $reminders_table = $wpdb->prefix . 'report_subscription_reminders';
    $uploads_table   = $wpdb->prefix . 'user_uploads'; // adjust if it's wpub_user_uploads
    $users_table     = $wpdb->prefix . 'users';
    $users_report_table     = $wpdb->prefix . 'user_reports';

    // Fetch up to 10 pending reminders and join user & upload info
    $reminders = $wpdb->get_results("
        SELECT r.id AS reminder_id, r.user_id, r.reminder_type,
               u.user_email,
               r.file_name, r.report_name,
               r.report_id
        FROM {$reminders_table} r
        JOIN {$users_table} u ON r.user_id = u.ID
        WHERE r.status = 'pending' AND r.user_id = 3
        ORDER BY r.id ASC
        LIMIT 10
    ");
    file_put_contents(__DIR__ . '/cron-log.txt', "Found " . count($reminders) . " pending reminders\n", FILE_APPEND);


    foreach ($reminders as $reminder) {
        $file_name = esc_html($reminder->file_name);
        $report_name = esc_html($reminder->report_name);
        $report_link = "http://mthfrsupport.local/";
        $report_id = esc_html($reminder->report_id);

        // Email content per reminder type
        switch ($reminder->reminder_type) {
            case '2_weeks':
                $subject = "⏳ Reminder: '$report_name' expires in 2 weeks";
                $msg_note = "This report will expire in 14 days.";
                break;
            case '1_week':
                $subject = "⚠️ Reminder: '$report_name' expires in 1 week";
                $msg_note = "This report will expire in 7 days.";
                break;
            case '1_day':
                $subject = "🚨 Final Reminder: '$report_name' expires tomorrow";
                $msg_note = "This report will expire tomorrow.";
                break;
            default:
                $subject = "Reminder: Your report is expiring";
                $msg_note = "Your report is nearing expiration.";
                break;
        }
        $checkout_url = site_url("/checkout/?add-to-cart=2152&report_id={$report_id}");


        $message = "
            <p>Hi,</p>
            <p>{$msg_note}</p>
            <p><strong>Report:</strong> {$report_name}<br>
               <strong>File:</strong> {$file_name}</p>
            
            <p>
                <a href='{$checkout_url}' style='
                    display:inline-block;
                    background-color:#007cba;
                    color:white;
                    padding:10px 20px;
                    text-decoration:none;
                    border-radius:5px;
                    font-weight:bold;
                '>
                    Renew Now – Go to Checkout
                </a>
            </p>

            <p>Thank you,<br>Your Website Team</p>
        ";

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        if (wp_mail('joashj15@gmail.com', $subject, $message, $headers)) {
            $wpdb->update($reminders_table, [
                'status'  => 'sent',
                'sent_at' => current_time('mysql'),
            ], ['id' => $reminder->reminder_id]);

            try {
                file_put_contents(__DIR__ . '/cron-log.txt', "Reminder sent to {$reminder->user_email} for {$report_name}\n", FILE_APPEND);
            } catch (Exception $e) {
                error_log('Failed to write log: ' . $e->getMessage());
            }
        } else {
            error_log("❌ Failed to send to {$reminder->user_email}");
        }
    }
}

try {
    $logFile = __DIR__ . '/cron-log.txt';
    $logEntry = date('Y-m-d H:i:s') . " - Reminder email cron ranw\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    send_pending_reminders();
} catch (Exception $e) {
    file_put_contents($logFile, $e->getMessage(), FILE_APPEND);
}
