<?php
// Test script for verifying optimizations in report generation

// Load WordPress
require_once 'wp-load.php';

// Include the classes
require_once 'wp-content/plugins/mthfr-genetic-reports/src/Core/Database/Database.php';
require_once 'wp-content/plugins/mthfr-genetic-reports/src/Core/Report/ReportGenerator.php';

echo "Testing optimizations...\n";

// Test 1: Check database stats
echo "Test 1: Checking database stats\n";
$stats = \MTHFR\Core\Database\Database::get_genetic_database_stats();
echo "Database stats: " . json_encode($stats) . "\n";

// Test 2: Database chunking with existing RSIDs
echo "Test 2: Testing database chunking\n";
if ($stats['variants'] > 0) {
    // Get some existing RSIDs
    $variants = \MTHFR\Core\Database\Database::get_variants_paginated(5, 0);
    $test_rsids = array_keys($variants);
    echo "Testing with " . count($test_rsids) . " existing RSIDs\n";
    $result = \MTHFR\Core\Database\Database::get_variants_by_rsids($test_rsids);
    echo "Retrieved variants for " . count($result) . " RSIDs\n";
} else {
    echo "No variants in database to test chunking\n";
}

// Test 3: Test connection
echo "Test 3: Testing database connection\n";
$conn = \MTHFR\Core\Database\Database::test_connection();
echo "Connection test: " . ($conn['status'] == 'success' ? 'OK' : 'FAILED') . "\n";

echo "Tests completed.\n";
?>





