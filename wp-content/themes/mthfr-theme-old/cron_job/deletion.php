<?php
file_put_contents(__DIR__ . '/expire-cleanup-log.txt', "[" . date('Y-m-d H:i:s') . "] Cron started\n", FILE_APPEND);

require_once(dirname(__FILE__, 5) . '/wp-load.php');

function mark_all_expired_reports_deleted() {
    global $wpdb;

    $table = $wpdb->prefix . 'user_reports';
    $now = current_time('mysql');

    $updated = $wpdb->query($wpdb->prepare("
        UPDATE $table
        SET is_deleted = 1, updated_at = %s
        WHERE expires_at IS NOT NULL AND expires_at < %s AND is_deleted = 0
    ", $now, $now));

    file_put_contents(
        __DIR__ . '/expire-cleanup-log.txt',
        "[" . date('Y-m-d H:i:s') . "] Marked $updated expired report(s) as deleted\n",
        FILE_APPEND
    );
}

try {
    mark_all_expired_reports_deleted();
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/expire-cleanup-log.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
}
