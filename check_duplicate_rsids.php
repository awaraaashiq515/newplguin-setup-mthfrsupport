<?php

require_once 'wp-load.php';

global $wpdb;

echo "=== Checking for Duplicate RSIDs in Database ===\n\n";

// Check for duplicate RSIDs
$duplicates = $wpdb->get_results("
    SELECT rsid, COUNT(*) as count
    FROM {$wpdb->prefix}genetic_variants
    WHERE rsid IS NOT NULL AND rsid != ''
    GROUP BY rsid
    HAVING COUNT(*) > 1
    ORDER BY count DESC
    LIMIT 20
");

if (!empty($duplicates)) {
    echo "Found duplicate RSIDs:\n";
    foreach ($duplicates as $dup) {
        echo "  {$dup->rsid}: {$dup->count} entries\n";

        // Show details of duplicates
        $details = $wpdb->get_results($wpdb->prepare("
            SELECT id, gene, categories, risk_allele
            FROM {$wpdb->prefix}genetic_variants
            WHERE rsid = %s
            ORDER BY id
        ", $dup->rsid));

        foreach ($details as $detail) {
            echo "    ID: {$detail->id}, Gene: {$detail->gene}, Category: {$detail->categories}, Risk: {$detail->risk_allele}\n";
        }
        echo "\n";
    }
} else {
    echo "No duplicate RSIDs found in database.\n";
}

// Check total counts
$total_rsids = $wpdb->get_var("SELECT COUNT(DISTINCT rsid) FROM {$wpdb->prefix}genetic_variants WHERE rsid IS NOT NULL AND rsid != ''");
$total_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}genetic_variants");

echo "\nTotal unique RSIDs: $total_rsids\n";
echo "Total database entries: $total_entries\n";

if ($total_rsids < $total_entries) {
    echo "WARNING: There are " . ($total_entries - $total_rsids) . " duplicate entries!\n";
}

?>