<?php
/**
 * Debug script to check Covid-related variant categories
 */

require_once('wp-load.php');

global $wpdb;

// Query 1: Get all categories for variants with Covid-related genes
$covid_genes = array('ACE2', 'FURIN', 'TMPRSS2', 'IL6', 'NLRP3', 'IFNAR1', 'NOS3', 'TNF', 'IL10');

echo "=== COVID-RELATED GENES AND THEIR CATEGORIES ===\n\n";

foreach ($covid_genes as $gene) {
    echo "Gene: $gene\n";

    // Get variants for this gene
    $variants = $wpdb->get_results($wpdb->prepare(
        "SELECT v.id, v.rsid, v.gene, GROUP_CONCAT(DISTINCT c.category_name) as categories
         FROM {$wpdb->prefix}genetic_variants v
         LEFT JOIN {$wpdb->prefix}variant_categories c ON v.id = c.variant_id
         WHERE v.gene = %s
         GROUP BY v.id
         ORDER BY v.rsid",
        $gene
    ));

    if (empty($variants)) {
        echo "  No variants found for this gene\n";
    } else {
        foreach ($variants as $variant) {
            $categories = $variant->categories ? explode(',', $variant->categories) : array('No categories');
            echo "  RSID: {$variant->rsid} - Categories: " . implode(', ', $categories) . "\n";
        }
    }
    echo "\n";
}

// Query 2: Check for specific Covid-related categories
$covid_categories = array('Covid', 'Immune Response', 'Inflammatory Response', 'HLA', 'Other Immune Factors');

echo "=== CHECKING FOR SPECIFIC COVID CATEGORIES ===\n\n";

foreach ($covid_categories as $category) {
    echo "Category: '$category'\n";

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}variant_categories WHERE category_name = %s",
        $category
    ));

    if ($count > 0) {
        echo "  Found $count variants with this category\n";

        // Show some examples
        $examples = $wpdb->get_results($wpdb->prepare(
            "SELECT v.rsid, v.gene, c.category_name
             FROM {$wpdb->prefix}genetic_variants v
             JOIN {$wpdb->prefix}variant_categories c ON v.id = c.variant_id
             WHERE c.category_name = %s
             LIMIT 5",
            $category
        ));

        foreach ($examples as $example) {
            echo "    {$example->rsid} ({$example->gene})\n";
        }
    } else {
        echo "  No variants found with this category\n";
    }
    echo "\n";
}

// Query 3: Get all distinct categories
echo "=== ALL DISTINCT CATEGORIES IN DATABASE ===\n\n";

$all_categories = $wpdb->get_col("SELECT DISTINCT category_name FROM {$wpdb->prefix}variant_categories ORDER BY category_name");

echo "Total distinct categories: " . count($all_categories) . "\n\n";
foreach ($all_categories as $category) {
    echo "- $category\n";
}

// Query 4: Check how category filtering works in reports
echo "\n=== CATEGORY FILTERING ANALYSIS ===\n\n";

// Check if there are any report generation issues
echo "Checking report generation logic...\n";

// Look at ReportGenerator to see how categories are filtered
$report_generator_file = 'wp-content/plugins/mthfr-genetic-reports/src/Core/Report/ReportGenerator.php';
if (file_exists($report_generator_file)) {
    echo "ReportGenerator file exists, checking for category filtering logic...\n";

    // Search for category filtering in the file
    $content = file_get_contents($report_generator_file);
    if (strpos($content, 'category') !== false || strpos($content, 'Category') !== false) {
        echo "Found category-related code in ReportGenerator\n";
    } else {
        echo "No category filtering found in ReportGenerator\n";
    }
} else {
    echo "ReportGenerator file not found\n";
}

echo "\n=== SUMMARY ===\n";
echo "This debug script has checked:\n";
echo "1. Categories for Covid-related genes (ACE2, FURIN, TMPRSS2, IL6, NLRP3, IFNAR1, NOS3, TNF, IL10)\n";
echo "2. Existence of specific Covid categories ('Covid', 'Immune Response', 'Inflammatory Response', 'HLA', 'Other Immune Factors')\n";
echo "3. All distinct categories in the database\n";
echo "4. Basic report generation category filtering analysis\n";