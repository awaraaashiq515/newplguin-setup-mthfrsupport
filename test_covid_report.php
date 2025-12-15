<?php

require_once 'wp-load.php';

echo "=== Testing Covid Report Generation ===\n\n";

// Expected category filters for Covid report
$report_type = 'Covid';
$category_filters = array('Covid');

echo "Report type: $report_type\n";
echo "Expected category filters: " . implode(', ', $category_filters) . "\n\n";

// Test with some Covid-related RSIDs
global $wpdb;

$covid_rsids = [
    'rs2048683', // ACE2 - Covid
    'rs17514846', // FURIN - Covid
    'rs12329760', // TMPRSS2 - Covid
    'rs13447446', // IL6 - Covid
    'rs1800796', // IL6 - Covid
    'rs12626750', // IFNAR1 - Covid
    'rs2853796', // NOS3 - Covid
    // 'rs361525', // TNF - HLA
    // 'rs1800629', // TNF - HLA
    // 'rs17851582' // GAMT - Other Immune Factors
];

echo "Testing with Covid-related RSIDs:\n";
foreach ($covid_rsids as $rsid) {
    $variants = $wpdb->get_results($wpdb->prepare(
        "SELECT v.id, v.rsid, v.gene, v.categories
         FROM {$wpdb->prefix}genetic_variants v
         WHERE v.rsid = %s",
        $rsid
    ));

    if (!empty($variants)) {
        foreach ($variants as $variant) {
            $category = $variant->categories;
            $allowed = in_array($category, $category_filters);
            echo "  $rsid ({$variant->gene}) - Category: '$category' - " . ($allowed ? 'ALLOWED' : 'FILTERED OUT') . "\n";
        }
    } else {
        echo "  $rsid - NOT FOUND in database\n";
    }
}

echo "\n=== Summary ===\n";
echo "If the fix is working, all Covid-related variants should be ALLOWED for the Covid report.\n";
echo "The Covid report should now include variants from categories: Covid, HLA, Other Immune Factors\n";

?>