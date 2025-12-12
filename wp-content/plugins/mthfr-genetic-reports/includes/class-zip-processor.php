<?php
/**
 * MTHFR ZIP Processor Class - COMPLETE VERSION
 * Handles extraction and processing of genetic data from ZIP files
 */

if (!defined('ABSPATH')) {
    exit;
}

class MTHFR_ZIP_Processor {
    
    /**
     * Process ZIP file to extract genetic data
     */
    public static function process_zip($zip_file_path, $upload_id) {
       
        error_log('MTHFR: === STARTING ZIP PROCESSING ===');
        error_log('MTHFR: Processing ZIP file: ' . $zip_file_path);
        
        if (!file_exists($zip_file_path)) {
            error_log('MTHFR: ERROR - ZIP file does not exist: ' . $zip_file_path);
            throw new Exception('ZIP file not found');
        }
        
        if (!class_exists('ZipArchive')) {
            error_log('MTHFR: ERROR - ZipArchive class not available');
            throw new Exception('ZIP processing not supported on this server');
        }
        
        $genetic_data = array();
        
        try {
            $zip = new ZipArchive();
            $result = $zip->open($zip_file_path);
            
            if ($result !== TRUE) {
                error_log('MTHFR: ERROR - Could not open ZIP file. Error code: ' . $result);
                throw new Exception('Could not open ZIP file: ' . self::get_zip_error_message($result));
            }
            
            error_log('MTHFR: ZIP opened successfully. Contains ' . $zip->numFiles . ' files');
            
            // List all files in ZIP for debugging
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $stat = $zip->statIndex($i);
                $filesize = $stat['size'];
                error_log("MTHFR: ZIP file {$i}: {$filename} ({$filesize} bytes)");
            }
            
            // Look for genetic data files
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                
                if (self::is_genetic_data_file($filename)) {
                    error_log("MTHFR: Processing genetic data file: {$filename}");
                    
                    $file_content = $zip->getFromIndex($i);
                    
                    if ($file_content === false) {
                        error_log("MTHFR: ERROR - Could not read file content from {$filename}");
                        continue;
                    }
                    
                    if (empty($file_content)) {
                        error_log("MTHFR: WARNING - File {$filename} is empty");
                        continue;
                    }
                    
                    // CRITICAL FIX: Normalize line endings before splitting
                    $file_content = str_replace("\r\n", "\n", $file_content);
                    $file_content = str_replace("\r", "\n", $file_content);
                    
                    // Debug: Show file content preview
                    $lines = explode("\n", $file_content);
                    error_log("MTHFR: File {$filename} has " . count($lines) . " lines");
                    
                    // Show first few lines (safely)
                    for ($j = 0; $j < min(5, count($lines)); $j++) {
                        $line_preview = substr(trim($lines[$j]), 0, 100);
                        error_log("MTHFR: Line {$j}: {$line_preview}");
                    }
                    
                    $extracted_data = self::parse_genetic_file($file_content, $filename);
                    error_log("MTHFR: Extracted " . count($extracted_data) . " variants from {$filename}");
                    
                    if (!empty($extracted_data)) {
                        // Debug: Show first extracted variant
                        error_log("MTHFR: First extracted variant: " . json_encode($extracted_data[0]));
                        $genetic_data = array_merge($genetic_data, $extracted_data);
                    }
                } else {
                    error_log("MTHFR: Skipping non-genetic file: {$filename}");
                }
            }
            
            $zip->close();
            
        } catch (Exception $e) {
            error_log('MTHFR: Exception during ZIP processing: ' . $e->getMessage());
            throw $e;
        }
        
        error_log('MTHFR: === ZIP PROCESSING COMPLETE ===');
        error_log('MTHFR: Total extracted variants: ' . count($genetic_data));
        
        if (empty($genetic_data)) {
            throw new Exception('No genetic data found in ZIP file');
        }
        
        return $genetic_data;
    }
    
    /**
     * Check if filename indicates genetic data
     */
    private static function is_genetic_data_file($filename) {
        $filename_lower = strtolower($filename);
        
        // Skip directories
        if (substr($filename, -1) === '/') {
            return false;
        }
        
        // Skip hidden files and system files
        $basename = basename($filename_lower);
        if (strpos($basename, '.') === 0 || strpos($basename, '__macosx') !== false) {
            return false;
        }
        
        // Check for genetic data file patterns
        $genetic_patterns = array(
            '.txt',      // Generic text files
            '.csv',      // CSV files  
            '.tsv',      // Tab-separated files
            '_raw',      // Raw data files
            'ancestry',  // AncestryDNA files
            '23andme',   // 23andMe files
            'ftdna',     // FamilyTreeDNA files
            'myheritage', // MyHeritage files
            'genome',    // Genome files
            'genetic',   // Genetic files
            'genotype',  // Genotype files
            'snp',       // SNP files
            'dna'        // DNA files
        );
        
        foreach ($genetic_patterns as $pattern) {
            if (strpos($filename_lower, $pattern) !== false) {
                error_log("MTHFR: File {$filename} matches genetic pattern: {$pattern}");
                return true;
            }
        }
        
        // Check file extensions
        $genetic_extensions = array('.txt', '.csv', '.tsv', '.data', '.genome');
        $file_extension = '.' . pathinfo($filename_lower, PATHINFO_EXTENSION);
        
        if (in_array($file_extension, $genetic_extensions)) {
            error_log("MTHFR: File {$filename} has genetic extension: {$file_extension}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Parse genetic data file content
     */
    private static function parse_genetic_file($content, $filename) {
        error_log("MTHFR: === PARSING GENETIC FILE: {$filename} ===");
        
        $genetic_data = array();
        
        // CRITICAL FIX: Normalize line endings
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        
        $lines = explode("\n", $content);
        $total_lines = count($lines);
        
        error_log("MTHFR: File has {$total_lines} lines");
        
        $valid_variants = 0;
        $skipped_lines = 0;
        $header_found = false;
        
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Skip comment lines (start with #)
            if ($line[0] === '#') {
                continue;
            }
            
            // Detect and skip header line
            if (!$header_found && (strpos(strtolower($line), 'rsid') !== false || strpos(strtolower($line), 'snp') !== false)) {
                error_log("MTHFR: Found header line {$line_num}: {$line}");
                $header_found = true;
                continue;
            }
            
            // Try to parse the line
            $variant = self::parse_genetic_line($line, $line_num);
            
            if ($variant) {
                $genetic_data[] = $variant;
                $valid_variants++;
                
                // Debug: Show first few parsed variants
                if ($valid_variants <= 3) {
                    error_log("MTHFR: Parsed variant {$valid_variants}: " . json_encode($variant));
                }
            } else {
                $skipped_lines++;
                
                // Debug: Show first few skipped lines
                if ($skipped_lines <= 5) {
                    error_log("MTHFR: Skipped line {$line_num}: " . substr($line, 0, 100));
                }
            }
            
            // Prevent memory issues with very large files
            if ($valid_variants >= 1000000) {
                error_log("MTHFR: Reached maximum variant limit (1M), stopping parse");
                break;
            }
        }
        
        error_log("MTHFR: Parsing complete - Valid variants: {$valid_variants}, Skipped lines: {$skipped_lines}");
        
        return $genetic_data;
    }
    
    /**
     * Parse a single line of genetic data
     */
    private static function parse_genetic_line($line, $line_num) {
        // Handle MyHeritage specific format: "rsid,""chr"",""pos"",""genotype"""
        if (preg_match('/^"([^,]+),""([^"]+)"",""([^"]+)"",""([^"]+)"""$/', $line, $matches)) {
            $rsid = $matches[1];
            $chromosome = $matches[2];
            $position = $matches[3];
            $genotype = strtoupper($matches[4]);
            
            $parts = array($rsid, $chromosome, $position, $genotype);
        } else {
            // Fallback for other formats
            if (strpos($line, '"') !== false) {
                $line = trim($line, '"');
                $line = str_replace('""', '"', $line);
                $parts = str_getcsv($line, ',');
            } else {
                $parts = preg_split('/[\t,]+/', $line);
            }
            
            $parts = array_map(function($part) {
                return trim($part, " \t\n\r\0\x0B\"");
            }, $parts);
        }
        
        if (count($parts) < 4) {
            return null;
        }

        $rsid       = trim($parts[0]);
        $chromosome = trim($parts[1]);
        $position   = trim($parts[2]);

        // Validate RSID format - IMPROVED
        if (empty($rsid) || strpos(strtolower($rsid), 'rs') !== 0 || strlen($rsid) < 3) {
            return null;
        }
        
        // Skip header-like values
        if (strtolower($rsid) === 'rsid' || strtolower($rsid) === 'snp') {
            return null;
        }

        $allele1 = null;
        $allele2 = null;
        $genotype = null;

        // Case 1: AncestryDNA (5 columns: rsid, chr, pos, allele1, allele2)
        if (count($parts) >= 5) {
            $allele1  = strtoupper($parts[3]);
            $allele2  = strtoupper($parts[4]);
            $genotype = $allele1 . $allele2;
        } 
        // Case 2: 23andMe / MyHeritage (4 columns: rsid, chr, pos, genotype)
        else {
            $genotype = strtoupper(trim($parts[3]));
            if (strlen($genotype) == 2) {
                $allele1 = substr($genotype, 0, 1);
                $allele2 = substr($genotype, 1, 1);
            }
        }

        // Validate alleles
        if (empty($allele1) || empty($allele2)) {
            return null;
        }
        if (!preg_match('/^[ATCG]$/', $allele1) || !preg_match('/^[ATCG]$/', $allele2)) {
            return null;
        }

        return array(
            'rsid'       => $rsid,
            'chromosome' => $chromosome,
            'position'   => $position,
            'allele1'    => $allele1,
            'allele2'    => $allele2,
            'genotype'   => $genotype
        );
    }

    
    /**
     * Get human-readable ZIP error message
     */
    private static function get_zip_error_message($error_code) {
        switch ($error_code) {
            case ZipArchive::ER_OK: return 'No error';
            case ZipArchive::ER_MULTIDISK: return 'Multi-disk zip archives not supported';
            case ZipArchive::ER_RENAME: return 'Renaming temporary file failed';
            case ZipArchive::ER_CLOSE: return 'Closing zip archive failed';
            case ZipArchive::ER_SEEK: return 'Seek error';
            case ZipArchive::ER_READ: return 'Read error';
            case ZipArchive::ER_WRITE: return 'Write error';
            case ZipArchive::ER_CRC: return 'CRC error';
            case ZipArchive::ER_ZIPCLOSED: return 'Containing zip archive was closed';
            case ZipArchive::ER_NOENT: return 'No such file';
            case ZipArchive::ER_EXISTS: return 'File already exists';
            case ZipArchive::ER_OPEN: return 'Can not open file';
            case ZipArchive::ER_TMPOPEN: return 'Failure to create temporary file';
            case ZipArchive::ER_ZLIB: return 'Zlib error';
            case ZipArchive::ER_MEMORY: return 'Memory allocation failure';
            case ZipArchive::ER_CHANGED: return 'Entry has been changed';
            case ZipArchive::ER_COMPNOTSUPP: return 'Compression method not supported';
            case ZipArchive::ER_EOF: return 'Premature EOF';
            case ZipArchive::ER_INVAL: return 'Invalid argument';
            case ZipArchive::ER_NOZIP: return 'Not a zip archive';
            case ZipArchive::ER_INTERNAL: return 'Internal error';
            case ZipArchive::ER_INCONS: return 'Zip archive inconsistent';
            case ZipArchive::ER_REMOVE: return 'Can not remove file';
            case ZipArchive::ER_DELETED: return 'Entry has been deleted';
            default: return "Unknown error code: {$error_code}";
        }
    }
    
    /**
     * Test method to verify ZIP processing works
     */
    public static function test_zip_processing() {
        try {
            // Check if ZipArchive is available
            if (!class_exists('ZipArchive')) {
                return array(
                    'status' => 'error',
                    'message' => 'ZipArchive class not available'
                );
            }
            
            // Test creating a ZIP
            $test_zip = new ZipArchive();
            
            return array(
                'status' => 'success',
                'message' => 'ZIP processing is available',
                'php_version' => PHP_VERSION,
                'ziparchive_available' => true
            );
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'ZIP processing test failed: ' . $e->getMessage()
            );
        }
    }

}