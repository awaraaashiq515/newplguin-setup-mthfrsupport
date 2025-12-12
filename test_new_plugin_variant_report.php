<?php
/**
 * Test script for new plugin's variant report generation
 * Simulates loading genetic variant data for 'Variant' report type
 */

// Include WordPress core
require_once('wp-load.php');

// Include the new plugin's autoloader or main file
require_once('wp-content/plugins/mthfr-genetic-reports/mthfr-genetic-reports.php');

// Use the new plugin's namespace
use MTHFR\Core\Database\Database;
use MTHFR\Core\Report\ReportGenerator;

echo "=== Testing New Plugin Variant Report Generation ===\n\n";

// Sample RSIDs for testing
$sample_rsids = [
    'rs1801133', // MTHFR
    'rs1801131', // MTHFR
    'rs4680',    // COMT
    'rs267598',  // CBS
    'rs1695',    // GSTP1
    'rs4244285', // CYP2C19
    'rs1065852', // CYP2D6
    'rs776746',  // CYP3A5
    'rs1800497', // ANKK1
    'rs25531'    // SLC6A4
];

echo "Sample RSIDs to test: " . implode(', ', $sample_rsids) . "\n\n";

// Step 1: Query database using new plugin's get_variants_by_rsids function
echo "Step 1: Querying database with get_variants_by_rsids()...\n";
$database_variants = Database::get_variants_by_rsids($sample_rsids);

echo "Database returned " . count($database_variants) . " RSID entries\n";

if (empty($database_variants)) {
    echo "ERROR: No variants returned from database!\n";
    exit(1);
}

// Step 2: Get category filters for 'Variant' report type
echo "\nStep 2: Getting category filters for 'Variant' report type...\n";

// Replicate the category filter logic from ReportGenerator
$report_type_lower = strtolower('Variant');
$category_filters = null;

switch ($report_type_lower) {
    case 'variant':
        $category_filters = array(
            'Alzheimers/Cardio/Lipid',
            'COMT Activity',
            'Cannabinoid Pathway',
            'Celiac Disease/Gluten Intolerance',
            'Clotting Factors',
            'Eye Health',
            'Glyoxylate Metabolic Process',
            'HLA',
            'IgA',
            'IgE',
            'IgG',
            'Iron Uptake & Transport',
            'Liver Detox - Phase I',
            'Liver Detox - Phase II',
            'Methylation & Methionine/Homocysteine Pathways',
            'Mitochondrial Function',
            'Molybdenum',
            'Neurotransmitter Pathway: Glutamate & GABA',
            'Neurotransmitter Pathway: Serotonin & Dopamine',
            'Other Immune Factors',
            'Pentose Phosphate Pathway',
            'Thiamin/Thiamine Degradation',
            'Thyroid',
            'Trans-Sulfuration Pathway',
            'Yeast/Alcohol Metabolism'
        );
        break;
    default:
        $category_filters = null; // No filtering
}

echo "Category filters for Variant report: " . (empty($category_filters) ? 'None (all categories)' : implode(', ', $category_filters)) . "\n";

// Step 3: Simulate genetic data input (user's uploaded data)
echo "\nStep 3: Simulating user genetic data...\n";
$genetic_data = [];
foreach ($sample_rsids as $rsid) {
    // Simulate user genotypes (some risk, some not)
    $genotypes = ['AA', 'AT', 'TT', 'CC', 'CT', 'GG', 'AG', 'AC', 'CG'];
    $random_genotype = $genotypes[array_rand($genotypes)];

    $genetic_data[] = [
        'rsid' => $rsid,
        'allele1' => $random_genotype[0],
        'allele2' => $random_genotype[1],
        'genotype' => $random_genotype
    ];
}

echo "Simulated " . count($genetic_data) . " genetic variants\n";

// Step 4: Generate JSON report structure (similar to old plugin format)
echo "\nStep 4: Generating JSON report structure...\n";

$json_report = [];
$total_processed = 0;
$matched_count = 0;
$filtered_count = 0;

foreach ($genetic_data as $variant) {
    $total_processed++;
    $rsid = $variant['rsid'];

    if (!isset($database_variants[$rsid])) {
        continue;
    }

    $matched_count++;
    $db_entries = $database_variants[$rsid];

    // Handle both single and multi-entry formats
    if (!isset($db_entries[0]) || !is_array($db_entries[0])) {
        $db_entries = [$db_entries];
    }

    foreach ($db_entries as $db_entry) {
        // Determine category
        $variant_category = $db_entry['Category'] ?? $db_entry['Group'] ?? '';

        if (empty($variant_category)) {
            // Use gene-based pathway determination
            $gene = $db_entry['Gene'] ?? '';
            $gene_pathway_map = [
                'MTHFR' => 'Methylation & Methionine/Homocysteine Pathways',
                'MTR' => 'Methylation & Methionine/Homocysteine Pathways',
                'MTRR' => 'Methylation & Methionine/Homocysteine Pathways',
                'COMT' => 'COMT Activity',
                'CBS' => 'Trans-Sulfuration Pathway',
                'GSTP1' => 'Liver Detox',
                'CYP2D6' => 'Liver Detox',
                'CYP2C19' => 'Liver Detox',
                'CYP3A5' => 'Liver Detox',
                'ANKK1' => 'COMT Activity',
                'SLC6A4' => 'Neurotransmitter Pathway: Serotonin & Dopamine'
            ];
            $variant_category = $gene_pathway_map[$gene] ?? 'Primary SNPs';
        }

        // Apply category filtering for Variant report
        if ($category_filters && !in_array($variant_category, $category_filters)) {
            $filtered_count++;
            continue;
        }

        // Format user genotype
        $user_genotype = strtoupper(trim($variant['genotype'] ?? ''));
        if (empty($user_genotype) || $user_genotype === 'UNKNOWN') {
            $user_genotype = 'Unknown';
        }

        // Calculate result
        $risk_allele = $db_entry['Risk'] ?? 'Unknown';
        $result = 'Unknown';
        if ($user_genotype !== 'Unknown' && $risk_allele !== 'Unknown') {
            $genotype_upper = strtoupper($user_genotype);
            $risk_upper = strtoupper($risk_allele);
            $alleles = str_split($genotype_upper);
            $risk_count = 0;
            foreach ($alleles as $allele) {
                if ($allele === $risk_upper) {
                    $risk_count++;
                }
            }
            switch ($risk_count) {
                case 0: $result = '-/-'; break;
                case 1: $result = '+/-'; break;
                case 2: $result = '+/+'; break;
            }
        }

        // Create SNP name
        $gene = $db_entry['Gene'] ?? 'Unknown Gene';
        $snp = $db_entry['SNP'] ?? '';
        $snp_name = $gene;
        if (!empty($snp)) {
            $snp_name .= ' ' . $snp;
        } else {
            $snp_name .= ' variant';
        }

        $json_variant = [
            "SNP ID" => $rsid,
            "SNP Name" => $snp_name,
            "Risk Allele" => $risk_allele,
            "Your Allele" => $user_genotype,
            "Result" => $result,
            "Report Name" => $db_entry['Report Name'] ?? 'MTHFRSupport Variant Report v2.5',
            "Info" => $db_entry['Info'] ?? null,
            "Video" => $db_entry['Video'] ?? null,
            "Tags" => $db_entry['Tags'] ?? null,
            "Group" => $variant_category
        ];

        $json_report[] = $json_variant;
    }
}

// Step 5: Output results
echo "\n=== RESULTS ===\n";
echo "Total variants processed: $total_processed\n";
echo "Database matches found: $matched_count\n";
echo "Variants after filtering: " . count($json_report) . "\n";
echo "Variants filtered out: $filtered_count\n\n";

// Step 6: Output JSON
echo "Generated JSON Report:\n";
echo json_encode($json_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// Step 7: Verification
echo "=== VERIFICATION ===\n";

$verification_passed = true;
$errors = [];

if (empty($json_report)) {
    $errors[] = "No variants in final report!";
    $verification_passed = false;
}

foreach ($json_report as $index => $variant) {
    $required_fields = ["SNP ID", "SNP Name", "Risk Allele", "Your Allele", "Result", "Group"];

    foreach ($required_fields as $field) {
        if (!isset($variant[$field])) {
            $errors[] = "Variant $index missing required field: $field";
            $verification_passed = false;
        }
    }

    // Check SNP ID format
    if (isset($variant["SNP ID"]) && !preg_match('/^rs\d+$/', $variant["SNP ID"])) {
        $errors[] = "Invalid SNP ID format: " . $variant["SNP ID"];
        $verification_passed = false;
    }

    // Check Result format
    if (isset($variant["Result"]) && !in_array($variant["Result"], ['-/-', '+/-', '+/+', 'Unknown'])) {
        $errors[] = "Invalid Result format: " . $variant["Result"];
        $verification_passed = false;
    }
}

// Check structure matches old plugin format
$expected_structure = [
    "SNP ID", "SNP Name", "Risk Allele", "Your Allele", "Result",
    "Report Name", "Info", "Video", "Tags", "Group"
];

foreach ($json_report as $variant) {
    $variant_keys = array_keys($variant);
    $missing_keys = array_diff($expected_structure, $variant_keys);
    if (!empty($missing_keys)) {
        $errors[] = "Missing expected fields: " . implode(', ', $missing_keys);
        $verification_passed = false;
    }
}

if ($verification_passed) {
    echo "✅ VERIFICATION PASSED: All expected fields present, data correctly read from DB, structure matches old plugin format\n";
} else {
    echo "❌ VERIFICATION FAILED:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n=== TEST COMPLETED ===\n";