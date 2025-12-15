<?php

require_once 'wp-load.php';

global $wpdb;

echo "=== Removing Duplicate RSIDs from Database ===\n\n";

// First, let's see what duplicates exist
$duplicates = $wpdb->get_results("
    SELECT rsid, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
    FROM {$wpdb->prefix}genetic_variants
    WHERE rsid IS NOT NULL AND rsid != ''
    GROUP BY rsid
    HAVING COUNT(*) > 1
    ORDER BY count DESC
");

echo "Found " . count($duplicates) . " RSIDs with duplicates\n\n";

$total_to_delete = 0;
foreach ($duplicates as $dup) {
    $ids = explode(',', $dup->ids);
    $keep_id = $ids[0]; // Keep the first ID
    $delete_ids = array_slice($ids, 1); // Delete the rest

    echo "RSID {$dup->rsid}: keeping ID $keep_id, deleting " . implode(', ', $delete_ids) . "\n";

    if (!empty($delete_ids)) {
        // For safety, let's just count what would be deleted
        $total_to_delete += count($delete_ids);
        echo "  Would delete " . count($delete_ids) . " rows\n";

        // Actually delete the duplicates
        $placeholders = str_repeat('%d,', count($delete_ids) - 1) . '%d';
        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}genetic_variants WHERE id IN ($placeholders)",
            $delete_ids
        );

        $deleted = $wpdb->query($sql);
        if ($deleted !== false) {
            $total_to_delete += $deleted;
            echo "  Deleted $deleted rows\n";
        } else {
            echo "  Error deleting rows\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Total duplicate rows deleted: $total_to_delete\n";

// Verify the cleanup
$remaining_duplicates = $wpdb->get_var("
    SELECT COUNT(*) FROM (
        SELECT rsid, COUNT(*) as count
        FROM {$wpdb->prefix}genetic_variants
        WHERE rsid IS NOT NULL AND rsid != ''
        GROUP BY rsid
        HAVING COUNT(*) > 1
    ) as dup_check
");

echo "Remaining RSIDs with duplicates: $remaining_duplicates\n";

$total_rsids = $wpdb->get_var("SELECT COUNT(DISTINCT rsid) FROM {$wpdb->prefix}genetic_variants WHERE rsid IS NOT NULL AND rsid != ''");
$total_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}genetic_variants");

echo "Total unique RSIDs: $total_rsids\n";
echo "Total database entries: $total_entries\n";

?>