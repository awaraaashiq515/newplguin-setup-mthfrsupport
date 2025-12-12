<?php
/**
 * Script to populate URL columns in wp_user_reports table
 * Updates json_url and pdf_url from report_path and pdf_report paths
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress
    $wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die("Could not find wp-load.php. Please check the path.\n");
    }
}

echo "Starting URL population script...\n";
echo "Timestamp: " . current_time('mysql') . "\n\n";

global $wpdb;

$upload_dir = wp_upload_dir();
$reports_table = $wpdb->prefix . 'user_reports';

// Check if columns exist
$json_url_exists = $wpdb->get_results("SHOW COLUMNS FROM {$reports_table} LIKE 'json_url'");
$pdf_url_exists = $wpdb->get_results("SHOW COLUMNS FROM {$reports_table} LIKE 'pdf_url'");

$json_url_column = !empty($json_url_exists);
$pdf_url_column = !empty($pdf_url_exists);

echo "Column status:\n";
echo "json_url column exists: " . ($json_url_column ? 'Yes' : 'No') . "\n";
echo "pdf_url column exists: " . ($pdf_url_column ? 'Yes' : 'No') . "\n\n";

// Build query conditions
$where_conditions = array();
$where_conditions[] = "(report_path IS NOT NULL OR pdf_report IS NOT NULL)";

if ($json_url_column) {
    $where_conditions[] = "(json_url IS NULL OR json_url = '')";
}
if ($pdf_url_column) {
    $where_conditions[] = "(pdf_url IS NULL OR pdf_url = '')";
}

$where_clause = implode(' AND ', $where_conditions);

// Get all reports that have file paths but no URLs
$query = "SELECT id, report_path, pdf_report FROM {$reports_table} WHERE {$where_clause}";
$reports = $wpdb->get_results($query);

echo "Found " . count($reports) . " reports to process.\n\n";

$updated_count = 0;

foreach ($reports as $report) {
    $json_url = null;
    $pdf_url = null;

    if (!empty($report->report_path) && file_exists($report->report_path)) {
        $json_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $report->report_path);
        echo "Setting JSON URL for report ID {$report->id}: {$json_url}\n";
    }

    if (!empty($report->pdf_report) && file_exists($report->pdf_report)) {
        $pdf_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $report->pdf_report);
        echo "Setting PDF URL for report ID {$report->id}: {$pdf_url}\n";
    }

    $update_data = array('updated_at' => current_time('mysql'));
    $update_formats = array('%s');
    $has_updates = false;

    if ($json_url && $json_url_column) {
        $update_data['json_url'] = $json_url;
        $update_formats[] = '%s';
        $has_updates = true;
    }
    if ($pdf_url && $pdf_url_column) {
        $update_data['pdf_url'] = $pdf_url;
        $update_formats[] = '%s';
        $has_updates = true;
    }

    if ($has_updates) {
        $result = $wpdb->update(
            $reports_table,
            $update_data,
            array('id' => $report->id),
            $update_formats,
            array('%d')
        );

        if ($result !== false) {
            $updated_count++;
            echo "✅ Updated report ID {$report->id}\n";
        } else {
            echo "❌ Failed to update report ID {$report->id}: " . $wpdb->last_error . "\n";
        }
    } else {
        echo "⚠️  No valid files or columns to update for report ID {$report->id}\n";
    }
}

echo "\n✅ URL population completed! Updated {$updated_count} reports.\n";

// Show statistics
$total_reports = $wpdb->get_var("SELECT COUNT(*) FROM {$reports_table}");

$reports_with_json_url = 0;
if ($json_url_column) {
    $reports_with_json_url = $wpdb->get_var("SELECT COUNT(*) FROM {$reports_table} WHERE json_url IS NOT NULL AND json_url != ''");
}

$reports_with_pdf_url = 0;
if ($pdf_url_column) {
    $reports_with_pdf_url = $wpdb->get_var("SELECT COUNT(*) FROM {$reports_table} WHERE pdf_url IS NOT NULL AND pdf_url != ''");
}

echo "\nFinal Statistics:\n";
echo "Total reports: {$total_reports}\n";
if ($json_url_column) {
    echo "Reports with JSON URLs: {$reports_with_json_url}\n";
} else {
    echo "JSON URL column not yet added to database\n";
}
if ($pdf_url_column) {
    echo "Reports with PDF URLs: {$reports_with_pdf_url}\n";
} else {
    echo "PDF URL column not yet added to database\n";
}

echo "\nScript execution completed.\n";