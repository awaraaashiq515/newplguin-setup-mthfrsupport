<?php
/**
 * MTHFR PDF Utilities Class
 * Utility functions for PDF generation
 */

namespace MTHFR\PDF;

if (!defined('ABSPATH')) {
    exit;
}

class PdfUtils {

    /**
     * Extract file name from database
     */
    public static function extract_file_name_from_database($enhanced_data) {
        // Default filename
        $filename = 'genetic_report';

        // Try to extract from enhanced_data
        if (isset($enhanced_data['file_info']['original_filename']) && !empty($enhanced_data['file_info']['original_filename'])) {
            $filename = $enhanced_data['file_info']['original_filename'];
        }

        // Clean filename
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);

        return $filename;
    }

    /**
     * Optimize memory settings
     */
    public static function optimize_memory_settings() {
        // Increase memory limit if possible
        $current_limit = ini_get('memory_limit');
        if ($current_limit && $current_limit !== '-1') {
            $current_mb = self::parse_memory_limit($current_limit);
            if ($current_mb < 512) {
                @ini_set('memory_limit', '512M');
                error_log('MTHFR Fixed: Increased memory limit to 512M');
            }
        }

        // Set execution time
        @ini_set('max_execution_time', 300);

        // Enable garbage collection
        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }

    /**
     * Cleanup memory
     */
    public static function cleanup_memory() {
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
            if ($collected > 0) {
                error_log("MTHFR Fixed: Garbage collected {$collected} cycles");
            }
        }

        // Clear WordPress object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Parse memory limit
     */
    private static function parse_memory_limit($limit) {
        $limit = trim($limit);
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        switch ($unit) {
            case 'g': return $value * 1024;
            case 'm': return $value;
            case 'k': return $value / 1024;
            default: return $value / (1024 * 1024);
        }
    }

    /**
     * Format memory usage
     */
    public static function format_memory($bytes) {
        $mb = $bytes / 1024 / 1024;
        return round($mb, 2) . 'MB';
    }

    /**
     * Format memory stats
     */
    public static function format_memory_stats() {
        $memory_stats = PdfGenerator::$memory_stats;
        $processing_time = PdfGenerator::$processing_time;

        return array(
            'start' => self::format_memory($memory_stats['start'] ?? 0),
            'end' => self::format_memory($memory_stats['end'] ?? 0),
            'peak' => self::format_memory($memory_stats['peak'] ?? 0),
            'used' => self::format_memory(($memory_stats['end'] ?? 0) - ($memory_stats['start'] ?? 0)),
            'processing_time' => round($processing_time, 2) . 's'
        );
    }

    /**
     * Create temporary directory for mPDF
     */
    public static function create_temp_directory() {
        $upload_dir = wp_upload_dir();
        $temp_base = $upload_dir['basedir'] . '/mthfr_temp';

        if (!file_exists($temp_base)) {
            if (!wp_mkdir_p($temp_base)) {
                error_log('MTHFR Fixed: Could not create temp base directory');
                return false;
            }
        }

        $temp_dir = $temp_base . '/mpdf_' . uniqid();

        if (!wp_mkdir_p($temp_dir)) {
            error_log('MTHFR Fixed: Could not create temp directory: ' . $temp_dir);
            return false;
        }

        return $temp_dir;
    }

    /**
     * Cleanup temporary directory
     */
    public static function cleanup_temp_directory($temp_dir) {
        if (!$temp_dir || !file_exists($temp_dir)) {
            return;
        }

        try {
            // Recursively delete all files and subdirectories
            $items = scandir($temp_dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $item_path = $temp_dir . DIRECTORY_SEPARATOR . $item;

                if (is_dir($item_path)) {
                    self::cleanup_temp_directory($item_path); // Recursive call
                } else {
                    unlink($item_path);
                }
            }

            // Now remove the empty directory
            rmdir($temp_dir);

        } catch (Exception $e) {
            error_log('MTHFR Fixed: Error cleaning temp directory "' . $temp_dir . '": ' . $e->getMessage());
        }
    }

    /**
     * Validate PDF content
     */
    public static function is_valid_pdf($content) {
        if (!$content || !is_string($content)) {
            return false;
        }

        if (strlen($content) < 100) {
            return false;
        }

        // Check PDF header
        if (strpos($content, '%PDF') !== 0) {
            error_log('MTHFR Fixed: Content does not start with PDF header');
            return false;
        }

        // Check for common PDF structure
        if (strpos($content, '%%EOF') === false) {
            error_log('MTHFR Fixed: Content does not contain PDF EOF marker');
            return false;
        }

        return true;
    }

    /**
     * Calculate result based on genotype and risk allele
     */
    public static function calculate_result($genotype, $risk_allele) {
        if (!$genotype || !$risk_allele) {
            return 'Unknown';
        }

        $genotype = strtoupper(trim($genotype));
        $risk_allele = strtoupper(trim($risk_allele));

        if (strlen($genotype) < 2) {
            return 'Unknown';
        }

        $alleles = str_split($genotype);
        $risk_count = 0;

        foreach ($alleles as $allele) {
            if ($allele === $risk_allele) {
                $risk_count++;
            }
        }

        switch ($risk_count) {
            case 0: return '-/-';
            case 1: return '+/-';
            case 2: return '+/+';
            default: return 'Unknown';
        }
    }

    /**
     * Get result CSS class
     */
    public static function get_result_css_class($result) {
        switch ($result) {
            case '+/+': return 'result-high';
            case '+/-': return 'result-moderate';
            case '-/-': return 'result-low';
            default: return 'result-unknown';
        }
    }

    /**
     * Get figure number for a pathway category
     */
    public static function get_figure_number($category_name) {
        return PdfGenerator::FIGURE_NUMBERS[$category_name] ?? null;
    }

    /**
     * Count available figures for categories
     */
    public static function count_available_figures($categories) {
        $figure_count = 0;
        foreach (array_keys($categories) as $category_name) {
            if (isset(PdfGenerator::FIGURE_NUMBERS[$category_name])) {
                $figure_count++;
            }
        }
        return $figure_count;
    }

    /**
     * Generate dynamic bookmark data
     */
    public static function generate_dynamic_bookmark_data($processed_data, $report_type) {
        $stats = array(
            'total_variants' => count($processed_data['variants'] ?? []),
            'categories_with_data' => count($processed_data['categories'] ?? []),
            'high_risk_variants' => 0,
            'moderate_risk_variants' => 0,
            'low_risk_variants' => 0,
            'available_figures' => self::count_available_figures($processed_data['categories'] ?? [])
        );

        // Count risk levels
        if (!empty($processed_data['variants'])) {
            foreach ($processed_data['variants'] as $variant) {
                $result = $variant['Result'] ?? '';
                if ($result === '+/+') $stats['high_risk_variants']++;
                elseif ($result === '+/-') $stats['moderate_risk_variants']++;
                elseif ($result === '-/-') $stats['low_risk_variants']++;
            }
        }

        $bookmarks = array();

        // Add overview bookmark
        $bookmarks[] = array(
            'title' => 'Report Overview',
            'level' => 0,
            'anchor' => 'overview'
        );

        // Add category bookmarks
        if (!empty($processed_data['categories'])) {
            foreach ($processed_data['categories'] as $category_name => $variants) {
                $variant_count = count($variants);
                $risk_summary = self::get_category_risk_summary($variants);

                $bookmark_title = $category_name . " ({$variant_count})";
                if ($risk_summary['high'] > 0) {
                    $bookmark_title .= " ⚠️";
                }

                $bookmarks[] = array(
                    'title' => $bookmark_title,
                    'level' => 1,
                    'anchor' => 'cat_' . sanitize_title($category_name)
                );
            }
        }

        // Add figures bookmark if applicable
        if ($stats['available_figures'] > 0 && in_array(strtolower($report_type), ['variant','methylation'], true)) {
            $bookmarks[] = array(
                'title' => 'Figures',
                'level' => 0,
                'anchor' => 'figures_section'
            );
        }

        return array(
            'bookmarks' => $bookmarks,
            'stats' => $stats
        );
    }

    /**
     * Get category risk summary
     */
    private static function get_category_risk_summary($variants) {
        $summary = array('high' => 0, 'moderate' => 0, 'low' => 0);

        foreach ($variants as $variant) {
            $result = $variant['Result'] ?? '';
            if ($result === '+/+') $summary['high']++;
            elseif ($result === '+/-') $summary['moderate']++;
            elseif ($result === '-/-') $summary['low']++;
        }

        return $summary;
    }

    /**
     * Generate dynamic table of contents
     */
    public static function generate_dynamic_table_of_contents($bookmark_data) {
        $stats = $bookmark_data['stats'];

        $toc_html = '<div class="table-of-contents">
            <h3 style="margin-top: 0; color: #1B80B6;">📋 Table of Contents</h3>
            <div class="toc-item">
                <span>Report Overview</span>
                <span>' . $stats['total_variants'] . ' variants</span>
            </div>';

        if (!empty($bookmark_data['bookmarks'])) {
            foreach ($bookmark_data['bookmarks'] as $bookmark) {
                if ($bookmark['level'] === 1) {
                    $toc_html .= '<div class="toc-item toc-category">
                        <a href="#' . esc_attr($bookmark['anchor']) . '" style="color: #1B80B6; text-decoration: none;">' . esc_html($bookmark['title']) . '</a>
                    </div>';
                }
            }
        }

        if ($stats['available_figures'] > 0) {
            $toc_html .= '<div class="toc-item toc-figure">
                <a href="#figures_section" style="color: #1B80B6; text-decoration: none;">Figures (' . $stats['available_figures'] . ' pathway diagrams)</a>
            </div>';
        }

        $toc_html .= '</div>';

        return $toc_html;
    }

    /**
     * Generate navigation bar
     */
    public static function generate_navigation_bar($bookmark_data, $current_category = null) {
        $nav_html = '<div class="navigation-bar">
            <strong>Quick Navigation:</strong> ';

        $nav_links = array();

        if (!empty($bookmark_data['bookmarks'])) {
            foreach ($bookmark_data['bookmarks'] as $bookmark) {
                if ($bookmark['level'] === 1) {
                    $category_name = preg_replace('/\s*\(\d+\)\s*⚠️?\s*$/', '', $bookmark['title']);
                    $is_current = ($current_category && $category_name === $current_category);

                    if ($is_current) {
                        $nav_links[] = '<span style="color: #666;">' . esc_html($category_name) . '</span>';
                    } else {
                        $nav_links[] = '<a href="#' . esc_attr($bookmark['anchor']) . '" class="nav-link">' . esc_html($category_name) . '</a>';
                    }
                }
            }
        }

        $nav_html .= implode(' | ', $nav_links) . '</div>';

        return $nav_html;
    }

    /**
     * Generate fallback report when PDF generation fails
     */
    public static function generate_fallback_report($product_name, $report_type, $error_message, $folder_name) {
        $html_content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($product_name) . ' - ' . esc_html($report_type) . ' Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f0f0f0; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #ffebee; border: 1px solid #f44336; color: #c62828; padding: 15px; border-radius: 5px; }
        .info { background: #e3f2fd; border: 1px solid #2196f3; color: #0d47a1; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . esc_html($product_name) . '</h1>
        <h2>' . esc_html($report_type) . ' Genetic Report</h2>
        <p><strong>Report ID:</strong> ' . esc_html($folder_name) . '</p>
        <p><strong>Generated:</strong> ' . date('F j, Y \a\t g:i A') . '</p>
    </div>

    <div class="error">
        <h3>Report Generation Error</h3>
        <p>The PDF report could not be generated due to the following error:</p>
        <p><strong>' . esc_html($error_message) . '</strong></p>
    </div>

    <div class="info">
        <h3>What This Means</h3>
        <p>This report contains genetic analysis data that could not be formatted into a PDF document. The genetic data has been processed and saved to the database, but the PDF generation failed.</p>

        <h4>Possible Causes:</h4>
        <ul>
            <li>Memory limit exceeded during PDF generation</li>
            <li>Invalid or corrupted genetic data</li>
            <li>Server configuration issues</li>
            <li>Large dataset causing processing timeouts</li>
        </ul>

        <h4>Recommendations:</h4>
        <ul>
            <li>Contact your system administrator to check server resources</li>
            <li>Try generating the report again with a smaller dataset</li>
            <li>Check the genetic data file for any formatting issues</li>
            <li>Ensure PHP memory limit is set to at least 512MB</li>
        </ul>

        <p><strong>Technical Details:</strong></p>
        <ul>
            <li>Report Type: ' . esc_html($report_type) . '</li>
            <li>Product: ' . esc_html($product_name) . '</li>
            <li>Timestamp: ' . current_time('c') . '</li>
            <li>PHP Memory Limit: ' . ini_get('memory_limit') . '</li>
            <li>Max Execution Time: ' . ini_get('max_execution_time') . ' seconds</li>
        </ul>
    </div>
</body>
</html>';

        return $html_content;
    }
}