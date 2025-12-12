<?php
require_once('wp-load.php');

global $wpdb;

$table = $wpdb->prefix . 'user_reports';

$total_reports = $wpdb->get_var("SELECT COUNT(*) FROM $table");

echo "Total reports: $total_reports\n";

$reports_with_paths = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE report_path IS NOT NULL OR pdf_report IS NOT NULL");

echo "Reports with paths: $reports_with_paths\n";

$sample = $wpdb->get_row("SELECT id, report_path, pdf_report, json_url, pdf_url FROM $table LIMIT 1");

echo "Sample report:\n";
echo "ID: {$sample->id}\n";
echo "report_path: {$sample->report_path}\n";
echo "pdf_report: {$sample->pdf_report}\n";
echo "json_url: {$sample->json_url}\n";
echo "pdf_url: {$sample->pdf_url}\n";

$results = $wpdb->get_results("SELECT id, json_url, pdf_url FROM $table WHERE json_url IS NOT NULL OR pdf_url IS NOT NULL LIMIT 5");

echo "Sample reports with URLs:\n";

foreach ($results as $row) {
    echo "ID: {$row->id}, JSON: {$row->json_url}, PDF: {$row->pdf_url}\n";
}

$total_with_urls = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE json_url IS NOT NULL OR pdf_url IS NOT NULL");

echo "\nTotal reports with URLs: $total_with_urls\n";