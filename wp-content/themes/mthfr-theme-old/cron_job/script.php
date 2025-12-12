<?php

require_once(dirname(__FILE__, 5) . '/wp-load.php');

function queue_reminders_by_level() {
    global $wpdb;
    $subs_table = $wpdb->prefix . 'user_reports';
    $reminders_table = $wpdb->prefix . 'report_subscription_reminders';

    $reminder_configs = [
        0 => ['type' => '2_weeks', 'days_before' => 14],
        1 => ['type' => '1_week',  'days_before' => 7],
        2 => ['type' => '1_day',   'days_before' => 1],
    ];

    foreach ($reminder_configs as $level => $config) {
        // Set a fixed target date for testing
        $target_date = '2025-09-28';

        $users = $wpdb->get_results($wpdb->prepare("
            SELECT ur.id, ur.upload_id, uu.user_id, uu.file_name, ur.report_name
            FROM $subs_table AS ur
            JOIN wpub_user_uploads AS uu ON ur.upload_id = uu.id
            WHERE DATE(ur.expires_at) IS NOT NULL
              AND ur.reminders = %d
              AND uu.user_id IS NOT NULL
        ", $level));

        foreach ($users as $user) {
            // Avoid inserting duplicates
            $exists = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM $reminders_table
                WHERE user_id = %d AND reminder_type = %s
            ", $user->user_id, $config['type']));

            if (!$exists) {
                $wpdb->insert($reminders_table, [
                    'user_id'       => $user->user_id,
                    'report_id'     => $user->id,
                    'reminder_type' => $config['type'],
                    'status'        => 'pending',
                    'report_name'   => $user->report_name,
                    'file_name'     => $user->file_name,
                    'created_at'    => current_time('mysql'),
                ]);

                // Update reminders column to next level
                $wpdb->update($subs_table, [
                    'reminders' => $level + 1
                ], ['id' => $user->id]);
            }
        }
    }
}

try {
    $logFile = __DIR__ . '/cron-log.txt';
    $logEntry = date('Y-m-d H:i:s') . " - Cron job ran\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    queue_reminders_by_level();
} catch (Exception $e) {
    $logFile = __DIR__ . '/cron-log.txt';
    $logEntry = date('Y-m-d H:i:s') . " - Cron job ran\n";
    file_put_contents($logFile, $e->getMessage(), FILE_APPEND);
}
