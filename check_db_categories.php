<?php
require_once('wp-load.php');

global $wpdb;

// Query 1: Get all categories for variants with Covid-related genes
$covid_genes = array('ACE2', 'FURIN', 'TMPRSS2', 'IL6', 'NLRP3', 'IFNAR1', 'NOS3', 'TNF', 'IL10');

echo "=== COVID-RELATED GENES AND THEIR CATEGORIES ===\n\n";

foreach ($covid_genes as $gene) {
    echo "Gene: $gene\n";

    // Get variants for this gene
    $variants = $wpdb->get_results($wpdb->prepare(
        "SELECT v.id, v.rsid, v.gene, v.categories
         FROM {$wpdb->prefix}genetic_variants v
         WHERE v.gene = %s
         ORDER BY v.rsid",
        $gene
    ));

    if (empty($variants)) {
        echo "  No variants found for this gene\n";
    } else {
        foreach ($variants as $variant) {
            $categories = $variant->categories ? array($variant->categories) : array('No categories');
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
        "SELECT COUNT(*) FROM {$wpdb->prefix}genetic_variants WHERE categories = %s",
        $category
    ));

    if ($count > 0) {
        echo "  Found $count variants with this category\n";

        // Show some examples
        $examples = $wpdb->get_results($wpdb->prepare(
            "SELECT v.rsid, v.gene, v.categories
             FROM {$wpdb->prefix}genetic_variants v
             WHERE v.categories = %s
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

$all_categories = $wpdb->get_col("SELECT DISTINCT categories FROM {$wpdb->prefix}genetic_variants WHERE categories IS NOT NULL AND categories != '' ORDER BY categories");

echo "Total distinct categories: " . count($all_categories) . "\n\n";
foreach ($all_categories as $category) {
    echo "- $category\n";
}

echo "\n=== SUMMARY ===\n";
echo "Checked Covid-related genes and categories in database\n";