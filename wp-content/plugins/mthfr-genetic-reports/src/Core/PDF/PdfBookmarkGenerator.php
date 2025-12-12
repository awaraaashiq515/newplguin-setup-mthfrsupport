<?php
/**
 * MTHFR PDF Bookmark Generator
 * Generates PDF bookmarks
 */

namespace MTHFR\PDF;

if (!defined('ABSPATH')) {
    exit;
}

class PdfBookmarkGenerator {

    /**
     * Generate dynamic bookmark data
     */
    public static function generate_dynamic_bookmark_data($processed_data, $report_type) {
        return PdfUtils::generate_dynamic_bookmark_data($processed_data, $report_type);
    }
}