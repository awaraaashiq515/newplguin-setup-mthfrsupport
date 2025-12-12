<?php
require_once('wp-load.php');

global $wpdb;

$reports_table = $wpdb->prefix . 'user_reports';

// Check if json_url column exists, add if not
$json_url_exists = $wpdb->get_results(
    "SHOW COLUMNS FROM {$reports_table} LIKE 'json_url'"
);

if (empty($json_url_exists)) {
    $result = $wpdb->query(
        "ALTER TABLE {$reports_table} ADD COLUMN json_url varchar(500) DEFAULT NULL AFTER pdf_report"
    );
    if ($result !== false) {
        echo "Successfully added json_url column\n";
    } else {
        echo "Failed to add json_url column: " . $wpdb->last_error . "\n";
    }
} else {
    echo "json_url column already exists\n";
}

// Check if pdf_url column exists, add if not
$pdf_url_exists = $wpdb->get_results(
    "SHOW COLUMNS FROM {$reports_table} LIKE 'pdf_url'"
);

if (empty($pdf_url_exists)) {
    $result = $wpdb->query(
        "ALTER TABLE {$reports_table} ADD COLUMN pdf_url varchar(500) DEFAULT NULL AFTER json_url"
    );
    if ($result !== false) {
        echo "Successfully added pdf_url column\n";
    } else {
        echo "Failed to add pdf_url column: " . $wpdb->last_error . "\n";
    }
} else {
    echo "pdf_url column already exists\n";
}