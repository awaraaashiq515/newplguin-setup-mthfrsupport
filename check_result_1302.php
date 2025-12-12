<?php
// Check database for result_id 1302

// Include WordPress
require_once('wp-load.php');

global $wpdb;

$result_id = 1302;
$table_name = $wpdb->prefix . 'user_reports';

$report_info = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $result_id)
);

if ($report_info) {
    echo "Report info for result_id $result_id:\n";
    echo "ID: " . $report_info->id . "\n";
    echo "Report Path: " . $report_info->report_path . "\n";
    echo "Report Type: " . $report_info->report_type . "\n";
    echo "Status: " . $report_info->status . "\n";
    echo "File exists: " . (file_exists($report_info->report_path) ? 'YES' : 'NO') . "\n";
    if (file_exists($report_info->report_path)) {
        echo "File size: " . filesize($report_info->report_path) . " bytes\n";
        $content = file_get_contents($report_info->report_path);
        echo "First 200 chars: " . substr($content, 0, 200) . "\n";
    }
} else {
    echo "No record found for result_id $result_id\n";

    // List all records
    $all_reports = $wpdb->get_results("SELECT id, report_path, report_type, status FROM $table_name ORDER BY id DESC LIMIT 10");
    echo "\nLast 10 records in user_reports:\n";
    foreach ($all_reports as $report) {
        echo "ID: {$report->id}, Type: {$report->report_type}, Status: {$report->status}, Path: {$report->report_path}\n";
    }
}
?>