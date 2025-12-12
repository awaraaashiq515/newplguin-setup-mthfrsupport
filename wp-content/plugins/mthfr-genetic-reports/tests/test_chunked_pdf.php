<?php
/**
 * Test script for chunked streaming PDF generation
 * Measures memory usage and performance improvements
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-load.php';

// Test data - simulate a large genetic report
function generate_test_data($variant_count = 1000) {
    $categories = [
        'Methylation & Methionine/Homocysteine Pathways',
        'Liver Detox - Phase I',
        'COMT Activity',
        'Neurotransmitter Pathway: Serotonin & Dopamine',
        'Eye Health',
        'Thyroid'
    ];

    $variants = [];
    $category_data = [];

    for ($i = 0; $i < $variant_count; $i++) {
        $category = $categories[array_rand($categories)];
        $snp_id = 'rs' . rand(100000, 999999);
        $result = ['+/-', '+/+', '-/-'][array_rand(['+/-', '+/+', '-/-'])];

        $variant = [
            'SNP ID' => $snp_id,
            'SNP Name' => 'Test Gene ' . rand(1, 100),
            'Risk Allele' => 'A',
            'Your Allele' => ($result === '+/+') ? 'AA' : (($result === '+/-') ? 'AG' : 'GG'),
            'Result' => $result,
            'rs10306114' => $category
        ];

        $variants[] = $variant;

        if (!isset($category_data[$category])) {
            $category_data[$category] = [];
        }
        $category_data[$category][] = $variant;
    }

    return [
        'variants' => $variants,
        'categories' => $category_data
    ];
}

function test_memory_usage($callback, $label) {
    $start_memory = memory_get_usage(true);
    $start_peak = memory_get_peak_usage(true);
    $start_time = microtime(true);

    $result = $callback();

    $end_memory = memory_get_usage(true);
    $end_peak = memory_get_peak_usage(true);
    $end_time = microtime(true);

    $memory_used = $end_memory - $start_memory;
    $peak_memory = $end_peak - $start_peak;
    $time_taken = $end_time - $start_time;

    echo "\n=== {$label} ===\n";
    echo "Time taken: " . round($time_taken, 3) . " seconds\n";
    echo "Memory used: " . round($memory_used / 1024 / 1024, 2) . " MB\n";
    echo "Peak memory increase: " . round($peak_memory / 1024 / 1024, 2) . " MB\n";
    echo "Final memory: " . round($end_memory / 1024 / 1024, 2) . " MB\n";
    echo "Peak memory: " . round($end_peak / 1024 / 1024, 2) . " MB\n";

    return [
        'time' => $time_taken,
        'memory_used' => $memory_used,
        'peak_increase' => $peak_memory,
        'result' => $result
    ];
}

function run_chunked_pdf_test() {
    echo "Testing Chunked Streaming PDF Generation\n";
    echo "========================================\n";

    // Generate test data
    $test_data = generate_test_data(500); // Test with 500 variants
    echo "Generated test data with " . count($test_data['variants']) . " variants\n";

    // Test streaming chunked generation
    $streaming_result = test_memory_usage(function() use ($test_data) {
        if (!class_exists('MTHFR\\PDF\\PdfGenerator')) {
            return 'ERROR: PdfGenerator class not found';
        }

        try {
            $pdf_content = MTHFR\PDF\PdfGenerator::generate_pdf(
                $test_data,
                'Test Product',
                'variant',
                false,
                'test-data.zip'
            );

            if ($pdf_content && is_string($pdf_content) && strpos($pdf_content, '%PDF') === 0) {
                return 'SUCCESS: Generated PDF of ' . strlen($pdf_content) . ' bytes';
            } else {
                return 'ERROR: Invalid PDF generated';
            }
        } catch (Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }, 'Streaming Chunked PDF Generation');

    echo "\nResult: " . $streaming_result['result'] . "\n";

    // Test memory monitoring
    echo "\nMemory Monitoring Test:\n";
    echo "----------------------\n";

    $memory_stats = MTHFR\PDF\PdfGenerator::format_memory_stats();
    echo "Memory stats: " . json_encode($memory_stats, JSON_PRETTY_PRINT) . "\n";

    return $streaming_result;
}

// Run the test
if (isset($_GET['test']) && $_GET['test'] === 'chunked_pdf') {
    run_chunked_pdf_test();
    exit;
}

echo "<h1>Chunked PDF Generation Test</h1>";
echo "<p><a href='?test=chunked_pdf'>Run Chunked PDF Test</a></p>";
echo "<p>This will test the streaming chunked PDF generation with memory monitoring.</p>";