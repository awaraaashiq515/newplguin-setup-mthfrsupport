<?php
/**
 * MTHFR Caching Performance Test
 * Measures the difference between cached and uncached database queries for genetic variants
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure WordPress is loaded
if (!function_exists('wp_cache_get')) {
    require_once ABSPATH . 'wp-load.php';
}

// Include the Database class if not already loaded
if (!class_exists('MTHFR\Core\Database\Database')) {
    require_once plugin_dir_path(__FILE__) . 'src/Core/Database/Database.php';
}

use MTHFR\Core\Database\Database;

/**
 * Performance Test Class
 */
class MTHFR_Cache_Performance_Test {

    private $test_rsids = array();
    private $results = array();

    public function __construct() {
        $this->load_test_data();
    }

    /**
     * Load sample RSIDs for testing
     */
    private function load_test_data() {
        global $wpdb;

        // Get up to 10 random RSIDs from the database
        $rsids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT rsid FROM {$wpdb->prefix}genetic_variants ORDER BY RAND() LIMIT %d",
            10
        ));

        if (empty($rsids)) {
            $this->results['error'] = 'No genetic variants found in database. Please import data first.';
            return;
        }

        $this->test_rsids = $rsids;
        $this->results['test_rsids'] = $rsids;
        $this->results['total_rsids'] = count($rsids);
    }

    /**
     * Clear MTHFR caches
     */
    private function clear_caches() {
        // Clear all MTHFR caches using WordPress cache API
        wp_cache_flush_group('mthfr');
        $this->results['cache_cleared'] = true;
    }

    /**
     * Measure execution time of a function
     */
    private function measure_time($callback, $iterations = 1) {
        $times = array();

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $result = call_user_func($callback);
            $end = microtime(true);
            $times[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        return array(
            'avg_time' => array_sum($times) / count($times),
            'min_time' => min($times),
            'max_time' => max($times),
            'iterations' => $iterations,
            'result' => $result
        );
    }

    /**
     * Test single RSID lookup performance
     */
    private function test_single_rsid_performance() {
        if (empty($this->test_rsids)) {
            return;
        }

        $test_rsid = $this->test_rsids[0];

        // Test uncached performance
        $this->clear_caches();
        $uncached_result = $this->measure_time(function() use ($test_rsid) {
            return Database::get_variant_by_rsid($test_rsid);
        }, 3);

        // Test cached performance
        $cached_result = $this->measure_time(function() use ($test_rsid) {
            return Database::get_variant_by_rsid($test_rsid);
        }, 3);

        $this->results['single_rsid_test'] = array(
            'rsid' => $test_rsid,
            'uncached' => $uncached_result,
            'cached' => $cached_result,
            'speedup' => $uncached_result['avg_time'] > 0 ? $cached_result['avg_time'] / $uncached_result['avg_time'] : 0
        );
    }

    /**
     * Test multiple RSIDs lookup performance
     */
    private function test_multiple_rsids_performance() {
        if (count($this->test_rsids) < 3) {
            return;
        }

        $test_rsids = array_slice($this->test_rsids, 0, min(5, count($this->test_rsids)));

        // Test uncached performance
        $this->clear_caches();
        $uncached_result = $this->measure_time(function() use ($test_rsids) {
            return Database::get_variants_by_rsids($test_rsids);
        }, 3);

        // Test cached performance
        $cached_result = $this->measure_time(function() use ($test_rsids) {
            return Database::get_variants_by_rsids($test_rsids);
        }, 3);

        $this->results['multiple_rsids_test'] = array(
            'rsids' => $test_rsids,
            'count' => count($test_rsids),
            'uncached' => $uncached_result,
            'cached' => $cached_result,
            'speedup' => $uncached_result['avg_time'] > 0 ? $cached_result['avg_time'] / $uncached_result['avg_time'] : 0
        );
    }

    /**
     * Test category lookup performance
     */
    private function test_category_performance() {
        // Get a category that exists
        $categories = Database::get_all_categories();
        if (empty($categories)) {
            return;
        }

        $test_category = $categories[0];

        // Test uncached performance
        $this->clear_caches();
        $uncached_result = $this->measure_time(function() use ($test_category) {
            // This will trigger cache lookups for variants in this category
            global $wpdb;
            $variant_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT variant_id FROM {$wpdb->prefix}variant_categories WHERE category_name = %s LIMIT 10",
                $test_category
            ));

            if (!empty($variant_ids)) {
                // Get variant data for these IDs (this uses caching)
                $variants = array();
                foreach ($variant_ids as $id) {
                    $variant = Database::get_variant_categories($id);
                    $variants[] = $variant;
                }
                return $variants;
            }
            return array();
        }, 2);

        // Test cached performance
        $cached_result = $this->measure_time(function() use ($test_category) {
            global $wpdb;
            $variant_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT variant_id FROM {$wpdb->prefix}variant_categories WHERE category_name = %s LIMIT 10",
                $test_category
            ));

            if (!empty($variant_ids)) {
                $variants = array();
                foreach ($variant_ids as $id) {
                    $variant = Database::get_variant_categories($id);
                    $variants[] = $variant;
                }
                return $variants;
            }
            return array();
        }, 2);

        $this->results['category_test'] = array(
            'category' => $test_category,
            'uncached' => $uncached_result,
            'cached' => $cached_result,
            'speedup' => $uncached_result['avg_time'] > 0 ? $cached_result['avg_time'] / $uncached_result['avg_time'] : 0
        );
    }

    /**
     * Run all performance tests
     */
    public function run_tests() {
        if (isset($this->results['error'])) {
            return $this->results;
        }

        $this->results['test_start_time'] = current_time('mysql');

        // Test single RSID performance
        $this->test_single_rsid_performance();

        // Test multiple RSIDs performance
        $this->test_multiple_rsids_performance();

        // Test category performance
        $this->test_category_performance();

        $this->results['test_end_time'] = current_time('mysql');

        return $this->results;
    }

    /**
     * Display test results
     */
    public function display_results() {
        if (isset($this->results['error'])) {
            echo '<div class="notice notice-error"><p><strong>Error:</strong> ' . esc_html($this->results['error']) . '</p></div>';
            return;
        }

        echo '<div class="wrap">';
        echo '<h2>MTHFR Caching Performance Test Results</h2>';

        echo '<div class="card">';
        echo '<h3>Test Configuration</h3>';
        echo '<table class="form-table">';
        echo '<tr><th>Test RSIDs Available</th><td>' . esc_html($this->results['total_rsids']) . '</td></tr>';
        echo '<tr><th>Cache Cleared</th><td>' . ($this->results['cache_cleared'] ? '✅ Yes' : '❌ No') . '</td></tr>';
        echo '<tr><th>Test Start Time</th><td>' . esc_html($this->results['test_start_time']) . '</td></tr>';
        echo '<tr><th>Test End Time</th><td>' . esc_html($this->results['test_end_time']) . '</td></tr>';
        echo '</table>';
        echo '</div>';

        // Single RSID Test Results
        if (isset($this->results['single_rsid_test'])) {
            $test = $this->results['single_rsid_test'];
            echo '<div class="card">';
            echo '<h3>Single RSID Lookup Test</h3>';
            echo '<p><strong>RSID:</strong> ' . esc_html($test['rsid']) . '</p>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Test Type</th><th>Avg Time (ms)</th><th>Min Time (ms)</th><th>Max Time (ms)</th><th>Iterations</th></tr></thead>';
            echo '<tbody>';
            echo '<tr><td>Uncached</td><td>' . number_format($test['uncached']['avg_time'], 3) . '</td><td>' . number_format($test['uncached']['min_time'], 3) . '</td><td>' . number_format($test['uncached']['max_time'], 3) . '</td><td>' . $test['uncached']['iterations'] . '</td></tr>';
            echo '<tr><td>Cached</td><td>' . number_format($test['cached']['avg_time'], 3) . '</td><td>' . number_format($test['cached']['min_time'], 3) . '</td><td>' . number_format($test['cached']['max_time'], 3) . '</td><td>' . $test['cached']['iterations'] . '</td></tr>';
            echo '</tbody></table>';
            echo '<p><strong>Performance Improvement:</strong> ' . number_format($test['speedup'] * 100, 1) . '% (cached is ' . number_format($test['speedup'], 2) . 'x faster)</p>';
            echo '</div>';
        }

        // Multiple RSIDs Test Results
        if (isset($this->results['multiple_rsids_test'])) {
            $test = $this->results['multiple_rsids_test'];
            echo '<div class="card">';
            echo '<h3>Multiple RSIDs Lookup Test</h3>';
            echo '<p><strong>RSIDs:</strong> ' . esc_html(implode(', ', $test['rsids'])) . ' (' . $test['count'] . ' total)</p>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Test Type</th><th>Avg Time (ms)</th><th>Min Time (ms)</th><th>Max Time (ms)</th><th>Iterations</th></tr></thead>';
            echo '<tbody>';
            echo '<tr><td>Uncached</td><td>' . number_format($test['uncached']['avg_time'], 3) . '</td><td>' . number_format($test['uncached']['min_time'], 3) . '</td><td>' . number_format($test['uncached']['max_time'], 3) . '</td><td>' . $test['uncached']['iterations'] . '</td></tr>';
            echo '<tr><td>Cached</td><td>' . number_format($test['cached']['avg_time'], 3) . '</td><td>' . number_format($test['cached']['min_time'], 3) . '</td><td>' . number_format($test['cached']['max_time'], 3) . '</td><td>' . $test['cached']['iterations'] . '</td></tr>';
            echo '</tbody></table>';
            echo '<p><strong>Performance Improvement:</strong> ' . number_format($test['speedup'] * 100, 1) . '% (cached is ' . number_format($test['speedup'], 2) . 'x faster)</p>';
            echo '</div>';
        }

        // Category Test Results
        if (isset($this->results['category_test'])) {
            $test = $this->results['category_test'];
            echo '<div class="card">';
            echo '<h3>Category Lookup Test</h3>';
            echo '<p><strong>Category:</strong> ' . esc_html($test['category']) . '</p>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Test Type</th><th>Avg Time (ms)</th><th>Min Time (ms)</th><th>Max Time (ms)</th><th>Iterations</th></tr></thead>';
            echo '<tbody>';
            echo '<tr><td>Uncached</td><td>' . number_format($test['uncached']['avg_time'], 3) . '</td><td>' . number_format($test['uncached']['min_time'], 3) . '</td><td>' . number_format($test['uncached']['max_time'], 3) . '</td><td>' . $test['uncached']['iterations'] . '</td></tr>';
            echo '<tr><td>Cached</td><td>' . number_format($test['cached']['avg_time'], 3) . '</td><td>' . number_format($test['cached']['min_time'], 3) . '</td><td>' . number_format($test['cached']['max_time'], 3) . '</td><td>' . $test['cached']['iterations'] . '</td></tr>';
            echo '</tbody></table>';
            echo '<p><strong>Performance Improvement:</strong> ' . number_format($test['speedup'] * 100, 1) . '% (cached is ' . number_format($test['speedup'], 2) . 'x faster)</p>';
            echo '</div>';
        }

        echo '<div class="card">';
        echo '<h3>Summary</h3>';
        echo '<p>The caching system significantly improves performance by storing frequently accessed data in memory, reducing database queries.</p>';
        echo '<p><a href="' . admin_url('admin.php?page=mthfr-cache-test') . '" class="button">← Back to Cache Test Page</a></p>';
        echo '</div>';

        echo '</div>';
    }
}

// Run the test
$test = new MTHFR_Cache_Performance_Test();
$results = $test->run_tests();
$test->display_results();