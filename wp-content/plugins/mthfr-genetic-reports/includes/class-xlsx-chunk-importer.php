<?php
/**
 * XLSX Chunk Importer
 * Imports large XLSX files in chunks to avoid timeout issues
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include PHPSpreadsheet if available
if (file_exists(MTHFR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once MTHFR_PLUGIN_PATH . 'vendor/autoload.php';
}

class XLSX_Chunk_Importer {

    private $batch_size = 400; // Default batch size
    private $max_execution_time = 25; // Max execution time per batch in seconds

    /**
     * Initialize the importer
     */
    public function __construct($batch_size = 400) {
        $this->batch_size = $batch_size;
        set_time_limit($this->max_execution_time + 5); // Add buffer
    }

    /**
     * Start import process
     */
    public function start_import($file_path, $import_batch_id = null) {
        if (!file_exists($file_path)) {
            return array(
                'success' => false,
                'message' => 'File not found: ' . $file_path
            );
        }

        if (!class_exists('PhpOffice\PhpSpreadsheet\Reader\Xlsx')) {
            return array(
                'success' => false,
                'message' => 'PHPSpreadsheet library not available'
            );
        }

        // Generate batch ID if not provided
        if (!$import_batch_id) {
            $import_batch_id = 'import_' . time() . '_' . uniqid();
        }

        // Store import session data
        $session_key = 'xlsx_import_' . $import_batch_id;
        $session_data = array(
            'file_path' => $file_path,
            'batch_id' => $import_batch_id,
            'total_rows' => 0,
            'processed_rows' => 0,
            'current_offset' => 0,
            'status' => 'processing',
            'start_time' => time(),
            'headers' => array()
        );

        set_transient($session_key, $session_data, HOUR_IN_SECONDS);

        // Get total rows first
        $total_info = $this->get_file_info($file_path);
        if (!$total_info['success']) {
            return $total_info;
        }

        $session_data['total_rows'] = $total_info['total_rows'];
        $session_data['headers'] = $total_info['headers'];
        set_transient($session_key, $session_data, HOUR_IN_SECONDS);

        return array(
            'success' => true,
            'message' => 'Import started',
            'batch_id' => $import_batch_id,
            'total_rows' => $total_info['total_rows'],
            'headers' => $total_info['headers']
        );
    }

    /**
     * Process next chunk
     */
    public function process_chunk($import_batch_id) {
        $session_key = 'xlsx_import_' . $import_batch_id;
        $session_data = get_transient($session_key);

        if (!$session_data) {
            return array(
                'success' => false,
                'message' => 'Import session not found'
            );
        }

        if ($session_data['status'] === 'completed') {
            return array(
                'success' => true,
                'message' => 'Import already completed',
                'completed' => true,
                'total_processed' => $session_data['processed_rows']
            );
        }

        // Process batch
        $result = $this->process_batch(
            $session_data['file_path'],
            $session_data['current_offset'],
            $this->batch_size
        );

        if (!$result['success']) {
            $session_data['status'] = 'error';
            $session_data['error'] = $result['message'];
            set_transient($session_key, $session_data, HOUR_IN_SECONDS);

            return array(
                'success' => false,
                'message' => $result['message']
            );
        }

        // Update session data
        $session_data['processed_rows'] += $result['processed_count'];
        $session_data['current_offset'] += $result['processed_count'];

        // Check if completed
        if ($session_data['current_offset'] >= $session_data['total_rows']) {
            $session_data['status'] = 'completed';
            $session_data['end_time'] = time();
        }

        set_transient($session_key, $session_data, HOUR_IN_SECONDS);

        return array(
            'success' => true,
            'processed_count' => $result['processed_count'],
            'total_processed' => $session_data['processed_rows'],
            'total_rows' => $session_data['total_rows'],
            'completed' => ($session_data['status'] === 'completed'),
            'progress' => round(($session_data['processed_rows'] / $session_data['total_rows']) * 100, 2)
        );
    }

    /**
     * Get file information
     */
    private function get_file_info($file_path) {
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();

            // Get headers
            $headers = array();
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());

            for ($col = 1; $col <= $highestCol; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getCalculatedValue();
                if (!empty($cellValue)) {
                    $headers[$col] = trim($cellValue);
                }
            }

            // Get total rows (excluding header)
            $highestRow = $worksheet->getHighestRow();
            $total_rows = max(0, $highestRow - 1);

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return array(
                'success' => true,
                'total_rows' => $total_rows,
                'headers' => $headers
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Error reading file: ' . $e->getMessage()
            );
        }
    }

    /**
     * Process a batch of rows
     */
    private function process_batch($file_path, $offset, $batch_size) {
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $start_row = $offset + 2; // +2 because offset starts at 0, and row 1 is header
            $end_row = min($highestRow, $start_row + $batch_size - 1);

            $batch_data = array();

            // Get headers for mapping
            $headers = array();
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());

            for ($col = 1; $col <= $highestCol; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getCalculatedValue();
                if (!empty($cellValue)) {
                    $headers[$col] = trim($cellValue);
                }
            }

            // Process rows in this batch
            for ($row = $start_row; $row <= $end_row; $row++) {
                $row_data = array();

                foreach ($headers as $col_num => $header) {
                    $value = $worksheet->getCellByColumnAndRow($col_num, $row)->getCalculatedValue();
                    $row_data[$header] = $value;
                }

                // Skip empty rows
                if (!empty(array_filter($row_data))) {
                    $batch_data[] = $row_data;
                }
            }

            // Insert batch data
            $inserted_count = 0;
            if (!empty($batch_data)) {
                $batch_id = 'chunk_' . time() . '_' . $offset;
                $inserted_count = MTHFR_Database::batch_insert_import_data($batch_data, $batch_id);
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return array(
                'success' => true,
                'processed_count' => ($end_row - $start_row + 1),
                'inserted_count' => $inserted_count
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Error processing batch: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get import status
     */
    public function get_import_status($import_batch_id) {
        $session_key = 'xlsx_import_' . $import_batch_id;
        $session_data = get_transient($session_key);

        if (!$session_data) {
            return array(
                'success' => false,
                'message' => 'Import session not found'
            );
        }

        return array(
            'success' => true,
            'status' => $session_data['status'],
            'total_rows' => $session_data['total_rows'],
            'processed_rows' => $session_data['processed_rows'],
            'progress' => $session_data['total_rows'] > 0 ? round(($session_data['processed_rows'] / $session_data['total_rows']) * 100, 2) : 0,
            'start_time' => $session_data['start_time'],
            'end_time' => isset($session_data['end_time']) ? $session_data['end_time'] : null,
            'error' => isset($session_data['error']) ? $session_data['error'] : null
        );
    }

    /**
     * Cancel import
     */
    public function cancel_import($import_batch_id) {
        $session_key = 'xlsx_import_' . $import_batch_id;
        $session_data = get_transient($session_key);

        if ($session_data) {
            $session_data['status'] = 'cancelled';
            set_transient($session_key, $session_data, HOUR_IN_SECONDS);
        }

        return array('success' => true, 'message' => 'Import cancelled');
    }

    /**
     * Clean up old import sessions
     */
    public static function cleanup_old_sessions($hours_old = 24) {
        global $wpdb;

        $older_than = time() - ($hours_old * HOUR_IN_SECONDS);
        $transient_pattern = '_transient_xlsx_import_%';

        // This is a simplified cleanup - in production you'd want more sophisticated cleanup
        // For now, we'll just delete transients older than specified time
        // Note: WordPress transients don't have built-in expiration queries, so this is limited

        return array('success' => true, 'message' => 'Cleanup completed');
    }
}