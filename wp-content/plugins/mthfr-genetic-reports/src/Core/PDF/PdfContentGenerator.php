<?php
/**
 * MTHFR PDF Content Generator
 * Generates PDF content sections with streaming chunk-based processing
 */

namespace MTHFR\PDF;

if (!defined('ABSPATH')) {
    exit;
}

class PdfContentGenerator {

    /**
     * Generate enhanced PDF content with dynamic bookmarks using streaming
     */
    public static function generate_enhanced_pdf_content_with_dynamic_bookmarks($processed_data, $product_name, $folder_name, $report_type, $bookmark_data = null) {
        // Use streaming generation for better memory efficiency
        return PdfGenerator::generate_streaming_pdf_content($processed_data, $product_name, $folder_name, $report_type, $bookmark_data);
    }

    /**
     * Generate streaming CSS header
     */
    public static function generate_css_header($stats) {
        return PdfGenerator::generate_streaming_css_header($stats);
    }

    /**
     * Generate streaming header section
     */
    public static function generate_header($product_name, $folder_name) {
        return PdfGenerator::generate_streaming_header($product_name, $folder_name);
    }

    /**
     * Generate streaming overview section
     */
    public static function generate_overview($stats, $bookmark_data) {
        return PdfGenerator::generate_streaming_overview($stats, $bookmark_data);
    }

    /**
     * Generate streaming category content
     */
    public static function generate_category($processed_data, $category_name, $category_index, $total_categories, $report_type, $bookmark_data) {
        return PdfGenerator::generate_streaming_category($processed_data, $category_name, $category_index, $total_categories, $report_type, $bookmark_data);
    }

    /**
     * Generate streaming figures content
     */
    public static function generate_figures($processed_data, $figures, $report_type) {
        return PdfGenerator::generate_streaming_figures($processed_data, $figures, $report_type);
    }

    /**
     * Generate streaming disclaimer
     */
    public static function generate_disclaimer() {
        return PdfGenerator::generate_streaming_disclaimer();
    }
}