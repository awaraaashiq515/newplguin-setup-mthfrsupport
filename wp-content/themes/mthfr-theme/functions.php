<?php

function child_theme_enqueue_styles() {
    
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));


    wp_enqueue_style('report_styles', get_stylesheet_directory_uri() . '/report_styles.css');
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_styles');

function add_custom_font_awesome() {
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' );
}

add_action( 'wp_enqueue_scripts', 'add_custom_font_awesome' );

function add_sweet_dialog() {
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
}
add_action( 'wp_enqueue_scripts', 'add_sweet_dialog' );




function enqueue_custom_upload_script() {

    // Uploaded files page script
    if (is_page('order-report')) { // Replace 'uploaded-files' with your page slug
        wp_enqueue_script(
            'uploaded-files-script',
            get_stylesheet_directory_uri() . '/js/uploaded-files.js',
            ['jquery', 'datatables-js'], // Dependencies
            '1.0.0',
            true
        );

        // Pass data to the script (if needed)
        wp_localize_script('uploaded-files-script', 'uploadData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce( 'file_upload' ),
            'delete_file_nonce' => wp_create_nonce('delete_file_nonce'),
            'get_user_uploads_nonce' => wp_create_nonce('get_user_uploads_nonce')
        ));
    }

    // view-report
    if(is_page('view-report') || is_page('sterlings-app')){ 
        
        wp_enqueue_script(
            'result-files-script',
            get_stylesheet_directory_uri() . '/js/result-files.js',
            ['jquery', 'datatables-js'], // Dependencies
            '1.0.0',
            true
        );

        wp_enqueue_script(
            'download-report-script',
            get_stylesheet_directory_uri() . '/js/download-report-button.js',
            ['jquery'], // Dependencies
            '1.0.0',
            true
        );

        // Pass data to the script (if needed)
        wp_localize_script('result-files-script', 'resultData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'delete_file_nonce' => wp_create_nonce('delete_file_nonce'),
            'get_user_result_nonce' => wp_create_nonce('get_user_result_nonce'),
            'load_report_visualization' => wp_create_nonce('load_report_visualization'),
            'reload_results_table' => wp_create_nonce('reload_results_table'),
        ));

        wp_localize_script('download-report-script', 'downloadResult', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'download_pdf' => wp_create_nonce('download_pdf')
        ));

    }

    if(is_page('sterlings-app')){

        wp_enqueue_script(
            'view-files-script',
            get_stylesheet_directory_uri() . '/js/view-files.js',
            ['jquery','datatables-js'], // Dependencies
            '1.0.0',
            true
        );

        // Pass data to the script (if needed)
        wp_localize_script('view-files-script', 'viewData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'delete_file_nonce' => wp_create_nonce('delete_file_nonce'),
            'get_user_uploads_nonce' => wp_create_nonce('get_user_uploads_nonce')
        ));

    }


    // wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
    wp_enqueue_style('datatables-css','https://cdn.datatables.net/2.2.1/css/dataTables.dataTables.css');
    // DataTables JS
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/2.2.1/js/dataTables.js', ['jquery'], null, true);
    wp_enqueue_script('datatables-rowgroup-js', 'https://cdn.datatables.net/rowgroup/1.5.1/js/dataTables.rowGroup.min.js', ['datatables-js'], null, true);

    wp_enqueue_style('datatables-responsive-css', 'https://cdn.datatables.net/responsive/3.0.3/css/responsive.dataTables.min.css');
    wp_enqueue_script('datatables-responsive-js', 'https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js', ['datatables-js'], null, true);
    wp_enqueue_style('datatables-scroller-css','https://cdn.datatables.net/scroller/2.4.3/css/scroller.dataTables.min.css');
    wp_enqueue_script('datatables-scroller-js', 'https://cdn.datatables.net/scroller/2.4.3/js/dataTables.scroller.min.js', ['datatables-js'], null, true);
    wp_enqueue_script('datatables-scroll-resize-js','https://cdn.datatables.net/plug-ins/2.2.1/features/scrollResize/dataTables.scrollResize.min.js', ['datatables-js'], null, true);

    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'));

    wp_enqueue_script('simplebar-js','https://cdn.jsdelivr.net/npm/simplebar@6.3.0/dist/simplebar.min.js');
    wp_enqueue_style('simplebar-css','https://cdn.jsdelivr.net/npm/simplebar@6.3.0/dist/simplebar.min.css');


}
add_action('wp_enqueue_scripts', 'enqueue_custom_upload_script');


add_filter('wpsl_templates', 'custom_templates');

function custom_templates($templates) {
    $templates[] = array(
        'id' => 'custom',
        'name' => 'Custom Table Template',
        'path' => get_stylesheet_directory() . '/wpsl-templates/custom.php',
    );
    return $templates;
}

add_filter( 'wpsl_listing_template', 'custom_listing_template' );

function custom_listing_template() {
    global $wpsl, $wpsl_settings;
    
    $listing_template = '<li data-store-id="<%= id %>" class="wpsl-table-row">' . "\r\n";
    $listing_template .= "\t" . '<div class="wpsl-table-cell store-info">' . "\r\n";
    $listing_template .= "\t\t" . '<%= thumb %>' . "\r\n";
    $listing_template .= "\t\t" . '<%= store %>' . "\r\n";
    $listing_template .= "\t\t\t" . wpsl_more_info_template() . "\r\n"; // Check if we need to show the 'More Info' link and info
    // $listing_template .= "\t\t" . wpsl_store_header_template('listing') . "\r\n";

    $listing_template .= "\t" . '</div>' . "\r\n";
      
    $listing_template .= "\t" . '<div class="wpsl-table-cell address-info">' . "\r\n";
    $listing_template .= "\t\t" . '<span class="wpsl-street"><%= address %></span>' . "\r\n";
    $listing_template .= "\t\t" . '<% if ( address2 ) { %>' . "\r\n";
    $listing_template .= "\t\t" . '<span class="wpsl-street"><%= address2 %></span>' . "\r\n";
    $listing_template .= "\t\t" . '<% } %>' . "\r\n";
    $listing_template .= "\t\t" . '<span>' . wpsl_address_format_placeholders() . '</span>' . "\r\n";
    
    if (!$wpsl_settings['hide_country']) {
        $listing_template .= "\t\t" . '<span class="wpsl-country"><%= country %></span>' . "\r\n";
    }
    $listing_template .= "\t" . '</div>' . "\r\n";
    
    if ($wpsl_settings['show_contact_details']) {
        $listing_template .= "\t" . '<div class="wpsl-table-cell contact-info">' . "\r\n";
        $listing_template .= "\t\t" . '<% if ( phone ) { %>' . "\r\n";
        $listing_template .= "\t\t" . '<span>Phone: <%= formatPhoneNumber( phone ) %></span><br>' . "\r\n";
        
        $listing_template .= "\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t" . '<% if ( fax ) { %>' . "\r\n";
        $listing_template .= "\t\t" . '<span>Fax: <%= fax %></span><br>' . "\r\n";
        $listing_template .= "\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t" . '<% if ( email ) { %>' . "\r\n";
        $listing_template .= "\t\t" . '<span>Email: <%= formatEmail( email ) %></span>' . "\r\n";
        $listing_template .= "\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t" . '</div>' . "\r\n";
    }
    
    $listing_template .= "\t" . '<div class="wpsl-table-cell direction-info" >' . "\r\n";
    if (!$wpsl_settings['hide_distance']) {
        $listing_template .= "\t\t" . '<span><%= distance %> ' . esc_html(wpsl_get_distance_unit()) . '</span>' . "\r\n";
    }
    $listing_template .= "\t\t" . '<%= createDirectionUrl() %>' . "\r\n";
    $listing_template .= "\t" . '</div>' . "\r\n";
    
    $listing_template .= '</li>' . "\r\n";

    return $listing_template;
}





function custom_api_endpoint() {
    // Register the tags search API
    register_rest_route('custom/v1', '/tags/', [
        'methods' => 'GET',
        'callback' => 'custom_tags_search_api',
        'args' => [
            'q' => [
                'validate_callback' => function ($param, $request, $key) {
                    return is_string($param); // Validate if 'q' is a string (search term)
                }
            ]
        ]
    ]);

    // Register the filter table API
    register_rest_route('custom/v1', '/filter-table', [
        'methods' => 'GET',
        'callback' => 'custom_filter_table_api',
        'args' => [
            'tags' => [
                'validate_callback' => function ($param, $request, $key) {
                    return is_array($param) || empty($param); // Allow 'tags' to be an empty array
                }
            ],
            'snps' => [
                'validate_callback' => function ($param, $request, $key) {
                    return is_string($param) || empty($param); // Allow 'snps' to be an empty string
                }
            ]
        ]
    ]);
}
add_action('rest_api_init', 'custom_api_endpoint');
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/tags/', [
        'methods'  => 'GET',
        'callback' => 'custom_tags_search_api',
        'permission_callback' => '__return_true',
    ]);
});

function custom_tags_search_api(WP_REST_Request $request) {
    global $wpdb;

    $search_term = $request->get_param('q');
    $search_term = $search_term ? strtolower($search_term) : '';
    $result_id   = intval($request->get_param('result_id'));  // Get upload_id

    if (!$result_id) {
        return rest_ensure_response([]);
    }

    // Get report_path from DB
    $table_name  = $wpdb->prefix . 'user_reports';
    $report_path = $wpdb->get_var(
        $wpdb->prepare("SELECT report_path FROM $table_name WHERE id = %d", $result_id)
    );

    if (empty($report_path) || !file_exists($report_path)) {
        return new WP_Error('file_not_found', 'Report file not found.', ['status' => 404]);
    }

    $json_content = file_get_contents($report_path);
    if (empty($json_content)) {
        return new WP_Error('file_read_error', 'Unable to read report file.', ['status' => 500]);
    }

    $json_data = json_decode($json_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_parse_error', 'Invalid JSON format.', ['status' => 500]);
    }

    // Handle variants array
    $data = $json_data['variants'] ?? $json_data;

    $all_tags = [];
    foreach ($data as $row) {
        if (!is_array($row)) continue;

        $tags = [];

        // Tags field
        if (!empty($row['Tags'])) {
            $tags = array_filter(array_map('trim', explode(',', $row['Tags'])));
        }

        // Include Group if Tags is empty or just to add more tags
        if (!empty($row['Group'])) {
            $tags[] = trim($row['Group']);
        }

        $all_tags = array_merge($all_tags, $tags);
    }

      $all_tags = array_unique($all_tags);
    sort($all_tags);

    // If search term is empty → return all tags (max 20)
    if (empty($search_term)) {
        return rest_ensure_response(array_slice($all_tags, 0, 20));
    }

    // Filter tags based on search term
    $filtered_tags = array_filter($all_tags, function($tag) use ($search_term) {
        return stripos($tag, $search_term) !== false;
    });

    $filtered_tags = array_slice(array_values($filtered_tags), 0, 20);
    return rest_ensure_response($filtered_tags);
}






function custom_filter_table_api(WP_REST_Request $request) {
    global $wpdb;

    // ========== INPUT PARAMETERS ==========
    $tags    = $request->get_param('tags') ? $request->get_param('tags') : [];
    $snps    = $request->get_param('snps') ? sanitize_text_field($request->get_param('snps')) : '';
    $pathway = $request->get_param('pathway') ? sanitize_text_field($request->get_param('pathway')) : '';
    $pathway = str_replace(' ', '_', $pathway);
    $result_id = intval($request->get_param('result_id'));

    error_log('========== CUSTOM FILTER TABLE API START ==========');
    error_log('INPUT PARAMS:');
    error_log('  - result_id: ' . $result_id);
    error_log('  - tags: ' . json_encode($tags));
    error_log('  - snps: ' . $snps);
    error_log('  - pathway: ' . $pathway);

    try {
        // ========== STEP 1: FETCH REPORT INFO FROM DATABASE ==========
        if (!$result_id) {
            error_log('ERROR: Invalid result_id');
            return new WP_Error('invalid_upload_id', 'Invalid upload ID.', ['status' => 400]);
        }

        $table_name = $wpdb->prefix . 'user_reports';
        error_log('STEP 1: Querying database table: ' . $table_name);
        
        // Fetch report_path from database
        $report_info = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, upload_id, report_type, report_name, report_path, report_data 
                FROM $table_name 
                WHERE id = %d",
                $result_id
            )
        );

        if (!$report_info) {
            error_log('ERROR: No report found with ID: ' . $result_id);
            return new WP_Error('report_not_found', 'Report not found.', ['status' => 404]);
        }

        error_log('STEP 1 RESULT:');
        error_log('  - id: ' . $report_info->id);
        error_log('  - upload_id: ' . $report_info->upload_id);
        error_log('  - report_type: ' . $report_info->report_type);
        error_log('  - report_name: ' . $report_info->report_name);
        error_log('  - report_path: ' . $report_info->report_path);

        // Check if JSON file exists at the path
        if (empty($report_info->report_path) || !file_exists($report_info->report_path)) {
            error_log('ERROR: JSON file not found at path: ' . ($report_info->report_path ?? 'NULL'));
            error_log('  - file_exists: ' . (file_exists($report_info->report_path ?? '') ? 'YES' : 'NO'));
            return new WP_Error('file_not_found', 'JSON report file not found.', ['status' => 404]);
        }

        error_log('  - JSON file exists: YES');
        error_log('  - File size: ' . filesize($report_info->report_path) . ' bytes');

        // ========== STEP 2: LOAD REPORT CONFIG ==========
        $report_configs = [
            'Excipient' => [
                'enable_tags' => false,
                'enable_pathway' => false,
                'default_pathway' => null
            ],
            'Covid' => [
                'enable_tags' => false,
                'enable_pathway' => false,
                'default_pathway' => null
            ],
            'Variant' => [
                'enable_tags' => true,
                'enable_pathway' => true,
                'default_pathway' => 'Liver_detox'
            ],
            'Methylation' => [
                'enable_tags' => true,
                'enable_pathway' => false,
                'default_pathway' => 'Methylation'
            ],
            'Bundled' => [                   
                'enable_tags' => true,        
                'enable_pathway' => true,      
                'default_pathway' => 'Liver_detox'
            ]     
        ];

        $config = $report_configs[$report_info->report_type] ?? $report_configs['Excipient'];
        error_log('STEP 2: Report config loaded');
        error_log('  - report_type: ' . $report_info->report_type);
        error_log('  - enable_tags: ' . ($config['enable_tags'] ? 'true' : 'false'));
        error_log('  - enable_pathway: ' . ($config['enable_pathway'] ? 'true' : 'false'));

        // ========== STEP 3: READ JSON FILE ==========
        error_log('STEP 3: Reading JSON file from path');
        $json_content = file_get_contents($report_info->report_path);

        if (empty($json_content)) {
            error_log('ERROR: Empty JSON content');
            return new WP_Error('file_read_error', 'Unable to read JSON file.', ['status' => 500]);
        }

        error_log('  - JSON content length: ' . strlen($json_content) . ' bytes');
        error_log('  - First 200 chars: ' . substr($json_content, 0, 200));

        // ========== STEP 4: DECODE JSON ==========
        error_log('STEP 4: Decoding JSON data');
        $json_data = json_decode($json_content, true);
             
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('ERROR: JSON decode failed - ' . json_last_error_msg());
            return new WP_Error('json_parse_error', 'Invalid JSON data: ' . json_last_error_msg(), ['status' => 500]);
        }

        error_log('  - JSON decoded successfully');
        error_log('  - Top level keys: ' . implode(', ', array_keys($json_data)));

        // Extract the variants array
        if (isset($json_data['variants'])) {
            $data = $json_data['variants'];
            error_log('  - Found "variants" key in JSON');
        } elseif (is_array($json_data)) {
            $data = $json_data;
            error_log('  - Using entire JSON array as data');
        } else {
            $data = [];
            error_log('  - No valid data structure found');
        }

        error_log('  - Total variants found: ' . count($data));

        if (empty($data)) {
            error_log('WARNING: No variants found in JSON');
            return new WP_REST_Response([
                'body_html' => '',
                'isSearchResult' => false,
                'data' => ['data' => []],
                'message' => 'No variants found in report'
            ], 200);
        }

        // Log sample variant structure
        if (!empty($data)) {
            $first_variant = reset($data);
            error_log('  - First variant keys: ' . implode(', ', array_keys($first_variant)));
            error_log('  - Sample variant: ' . json_encode($first_variant));
        }

        // ========== STEP 5: FILTER DATA ==========
        error_log('STEP 5: Filtering data');
        $filtered_data = $data;
        $original_count = count($filtered_data);

        // Filter by pathway (only if pathway filtering is enabled)
        if ($config['enable_pathway'] && !empty($pathway) && $report_info->report_type !== 'Covid') {
            error_log('  - Filtering by pathway: ' . $pathway);
            $before = count($filtered_data);
            
            $filtered_data = array_filter($filtered_data, function($row) use ($pathway) {
                if (!is_array($row)) return false;
                $group = $row['Group'] ?? '';
                $group_normalized = str_replace(' ', '_', strtolower($group));
                $pathway_normalized = strtolower($pathway);
                return stripos($group_normalized, $pathway_normalized) !== false;
            });
            
            error_log('  - After pathway filter: ' . count($filtered_data) . ' variants (was ' . $before . ')');
        } else {
            if (!$config['enable_pathway']) {
                error_log('  - Pathway filtering disabled for report type: ' . $report_info->report_type);
            } else if (empty($pathway)) {
                error_log('  - No pathway specified, skipping pathway filter');
            }
        }

        // Filter by tags
        if (!empty($tags)) {
            error_log('  - Filtering by tags: ' . json_encode($tags));
            $before = count($filtered_data);
            
            $filtered_data = array_filter($filtered_data, function($row) use ($tags) {
                if (!is_array($row)) return false;
                $row_tags_str = $row['Tags'] ?? '';
                $row_tags = array_filter(array_map('trim', explode(',', $row_tags_str)));
                
                foreach ($tags as $tag) {
                    if (in_array(trim($tag), $row_tags)) {
                        return true;
                    }
                }
                return false;
            });
            
            error_log('  - After tags filter: ' . count($filtered_data) . ' variants (was ' . $before . ')');
        }

        // Filter by SNP search
        if (!empty($snps)) {
            error_log('  - Filtering by SNP search: ' . $snps);
            $before = count($filtered_data);
            
            $filtered_data = array_filter($filtered_data, function($row) use ($snps) {
                if (!is_array($row)) return false;
                $snp_name = $row['SNP Name'] ?? '';
                $snp_id = $row['SNP ID'] ?? '';
                return stripos($snp_name, $snps) !== false || stripos($snp_id, $snps) !== false;
            });
            
            error_log('  - After SNP filter: ' . count($filtered_data) . ' variants (was ' . $before . ')');
        }

        // ========== STEP 6: MAP FILTERED DATA TO OUTPUT FORMAT ==========
        error_log('STEP 6: Mapping filtered data to output format');
        $output_data = [];

        foreach ($filtered_data as $entry) {
            if (!is_array($entry)) {
                error_log('  - WARNING: Skipping non-array entry');
                continue;
            }
            
            $output_data[] = [
                "SNP Name" => $entry["SNP Name"] ?? "",
                "Risk Allele" => $entry["Risk Allele"] ?? "",
                "Your Allele" => $entry["Your Allele"] ?? "",
                "Result" => $entry["Result"] ?? "",
                "SNP ID" => $entry["SNP ID"] ?? "",
                "Info" => $entry["Info"] ?? "",
                "Video" => $entry["Video"] ?? "",
                "Group" => $entry["Group"] ?? "",
                "Tags" => $entry["Tags"] ?? ""
            ];
        }

        error_log('  - Final mapped data count: ' . count($output_data));

        // Special handling for Covid reports (deduplication by SNP ID)
        if ($report_info->report_type === 'Covid') {
            error_log('  - Applying Covid-specific deduplication');
            $before_dedup = count($output_data);
            $output_data = array_values(array_column($output_data, null, "SNP ID"));
            error_log('  - After deduplication: ' . count($output_data) . ' variants (was ' . $before_dedup . ')');
        }

        // ========== STEP 7: PREPARE RESPONSE ==========
        error_log('STEP 7: Preparing API response');
        $response_data = [
            'body_html'      => '',
            'isSearchResult' => (!empty($tags) || !empty($snps)),
            'data' => [
                'data' => array_values($output_data)  // Re-index array
            ],
            'meta' => [
                'report_id' => $report_info->id,
                'upload_id' => $report_info->upload_id,
                'report_type' => $report_info->report_type,
                'report_name' => $report_info->report_name,
                'total_variants_in_file' => count($data),
                'filtered_variants' => count($output_data)
            ]
        ];

        error_log('RESPONSE SUMMARY:');
        error_log('  - Report Type: ' . $report_info->report_type);
        error_log('  - Total variants in JSON: ' . count($data));
        error_log('  - Filtered variants returned: ' . count($output_data));
        error_log('  - isSearchResult: ' . ($response_data['isSearchResult'] ? 'true' : 'false'));
        
        if (!empty($output_data)) {
            error_log('  - Sample output (first item): ' . json_encode($output_data[0]));
        }

        error_log('========== CUSTOM FILTER TABLE API END (SUCCESS) ==========');
        return new WP_REST_Response($response_data, 200);

    } catch (Exception $e) {
        error_log('========== FATAL ERROR ==========');
        error_log('Exception: ' . $e->getMessage());
        error_log('File: ' . $e->getFile());
        error_log('Line: ' . $e->getLine());
        error_log('Trace: ' . $e->getTraceAsString());
        error_log('========== CUSTOM FILTER TABLE API END (ERROR) ==========');
        return new WP_Error('processing_error', $e->getMessage(), ['status' => 500]);
    }
}

function report_visualization_shortcode($atts) {
    global $wpdb;
    ob_start();
    
    $atts = shortcode_atts([
        'result_id' => 0,
        'file_name' => '',
        'folder_name' => ''
    ], $atts);

    // Extract the values
    $result_id = intval($atts['result_id']);
    $file_name = sanitize_text_field($atts['file_name']);
    $folder_name = sanitize_text_field($atts['folder_name']);

    $table_name = $wpdb->prefix . 'user_reports';
    $report_info = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT report_type FROM $table_name WHERE id = %d",
            $result_id
        )
    );

    $report_configs = [
        'Excipient' => [
            'enable_tags' => false,
            'enable_pathway' => false,
            'default_pathway' => null
        ],
        'Covid' => [
            'enable_tags' => false,
            'enable_pathway' => false,
            'default_pathway' => null
        ],
        'Variant' => [
            'enable_tags' => true,
            'enable_pathway' => true,
            'default_pathway' => 'Liver_detox'
            ],
        'Methylation' => [
            'enable_tags' => true,
            'enable_pathway' => false,
            'default_pathway' => 'Methylation'
        ],
         'Bundled' => [                   
                 'enable_tags' => true,        
                 'enable_pathway' => true,      
                 'default_pathway' => 'Liver_detox'
            ]  
        ];
    $config = $report_configs[$report_info->report_type] ?? $report_configs['Excipient'];


    ?>
        <style>
        * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    

    .view-report-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .search-section {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }

    .search-input {
        flex: 1;
        min-width: 250px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    input[type="text"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .main-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        display: flex;
        gap: 20px;
    }

    .left-section {
        flex: 1;
        min-width: 0; /* Prevents flex item from overflowing */
    }

    .right-section {
        width: 350px; /* Fixed width for results table */
        min-width: 350px;
    }


    .pathway-links {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
        min-height: 120px;
    }

    .column {
        width: 50%;
        padding: 0 15px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .left-column {
        align-self: center;
        text-align: right;
    }

    .right-column {
    
        align-self: center;
        text-align: left;
    }

    .divider {
        width: 1px;
        background-color: #000;
        margin: 0 10px;
    }

    .pathway-link {
        color: #4F81BD;
        font-size: 13px;
        font-family: Arial, sans-serif;
        text-decoration: none;
        cursor: pointer;
        line-height: 1.2;
    }

    .diagram-section {
        margin-bottom: 20px;
    }

    .diagram-image {
        width: 100%;
        height: auto;
        max-width: 100%;
    }

    .divider {
        width: 1px;
        background-color: #000;
        margin: 0 10px;
    }

    .pathway-link {
        color: #4F81BD;
        font-size: 13px;
        font-family: Arial, sans-serif;
        text-decoration: none;
        cursor: pointer;
        line-height: 1.2;
    }

    .diagram-section {
        margin-bottom: 20px;
    }

    .diagram-image {
        width: 100%;
        height: auto;
        max-width: 100%;
    }

    .results-section {
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
        width: 100%;
        height: 100%;
    }

    .results-table {
        width: 100%;
        border-collapse: collapse;
        
    }

    .results-table th {
        text-align: center;
        font-size: 13px;  /* Allow wrapping of text */
    }
    
    .results-table td {
        text-align: center;
        font-size: 13px;  /* Allow wrapping of text */
    }
    

    .plus-circle {
        cursor: pointer;
        display: inline-block;
        width: 20px;
        height: 20px;
        text-align: center;
        line-height: 18px;
        border: 1px solid #ccc;
        border-radius: 50%;
        background-color: #f8f8f8;
    }

    .info-row {
        background-color: #f9f9f9;
    }

    .additional-info {
        padding: 15px;
    }

    .info-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .info-content p {
        margin: 8px 0;
    }

    .info-content a {
        color: #4F81BD;
        text-decoration: none;
    }

    .info-content a:hover {
        text-decoration: underline;
    }

    .dt-search {
        display: none;
    }

    div.dt-container div.dt-layout-row {
        display: initial;
    }

    
    
    .select2-dropdown {
        z-index: 100001;
        margin-top: 30px;
    }

    .custom-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }

    .dt-scroll-body {
        overflow: visible !important;
    }


    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 1024px) {
        .main-content {
            flex-direction: column;
        }

        .right-section {
            width: 100%;
            min-width: 0;
        }
    }

    @media (max-width: 768px) {
        .view-report-container {
            max-width: 1200px;
            margin: 0;
            padding: 20px;
        }
        .pathway-links {
            flex-direction: column;
            align-items: center;
            min-height: auto;
        }

        .column {
            width: 100%;
            text-align: center;
            padding: 10px 0;
        }

        .left-column {
            text-align: center;
        }

        .right-column {
            text-align: center;
        }

        .divider {
            width: 80%;
            height: 1px;
            margin: 10px 0;
        }

        .results-table {
            font-size: 14px;
        }

        .results-table td,
        .results-table th {
            padding: 8px;
            text-align: left;
        }
    }



    @media (max-width: 760px) {
        .main-content {
            flex-direction: column;
        }

        .diagram {
            flex-direction: column;
        }

        .arrow {
            transform: rotate(90deg);
        }


        .results-section {
            overflow-x: auto;
        }
        .results-table tr.hidden {
            display: none;
        }
        .link-text {
            margin: 2px 1px;
        }

        
    }
        </style>
        <div class="view-report-container">
                <div class="details">
                    <?php echo do_shortcode('[user_result_download_button result_id="' . esc_attr($result_id) . '" 
                             file_name="' . esc_attr($file_name) . '" 
                             folder_name="' . esc_attr($folder_name) . '"]');
                     ?>
                </div>
                <div class="search-section">
                    
                    <div class='search-input'>
                        <label for="tag-search">Tag Search:</label>
                            
                        <?php if ($config['enable_tags']): ?>
                            <!-- Actual select box if tags are enabled -->
                            <select class="select2-search" id="tag-search" placeholder="Tags.." style="width: 100%;" multiple>
                                <!-- Options go here -->
                            </select>
                        <?php else: ?>
                            <div class="fake-input" style="width: 100%; padding: 6px; background-color: #f0f0f0; color: #999; border-radius:4px;">
                                Tags...
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="search-input">
                        <label for="snp-search">SNP Search:</label>
                        <input type="text" id="snp-search" placeholder="Snps..">
                    </div>
                </div>

                <div class="main-content">
                    <?php if ($config['enable_pathway']): ?>
                    <div class="left-section">
                        <div class="pathway-links">
                            <div class="column left-column">
                                <a href="#" class="pathway-link" data-image='Liver_detox.png'>Liver Detox - Phase I & II</a>
                                <a href="#" class="pathway-link" data-image='Methylation.png'>Methylation & Methionine/Homocysteine Pathways</a>
                                <a href="#" class="pathway-link" data-image='Neuro_transmitter.png'>Neurotransmitter Pathway: Serotonin & Dopamine</a>
                                <a href="#" class="pathway-link" data-image='COMT.png'>COMT Activity</a>
                                <a href="#" class="pathway-link" data-image='Glyphosate_degradation.png'>Glyoxylate Metabolic Process</a>
                                <a href="#" class="pathway-link" data-image='biotoxin_pathway.png'>Biotoxin pathway</a>
                                
                            </div>
                            <div class="divider"></div>
                            <div class="column right-column">
                                <a href="#" class="pathway-link" data-image='yeast.png'>Yeast/Alcohol Metabolism</a>
                                <a href="#" class="pathway-link" data-image='Trans_sulfuration.png'>Trans-sulferation Pathway</a>
                                <a href="#" class="pathway-link" data-image='neuro_transmitter_glutamate.png'>Neurotransmitter Pathway: Glutamate & GABA</a>
                                <a href="#" class="pathway-link" data-image='Pentose_phosphate_pathway.png'>Pentose Phosphate Pathwaytest1</a>
                                <a href="#" class="pathway-link" data-image='Glycolysis.png'>Thiamin/Thiamine Degradation</a>
                                <a href="#" class="pathway-link" data-image='electron_transport.png'>Mitochondrial Function</a>
                            </div>
                        </div>




                        <div class="diagram-section">
                            <img src="http://mthfrsupport.org/wp-content/uploads/2025/01/Liver_detox.png" alt="Pathway Diagram" class="diagram-image">
                        </div>
                    </div>
                    <?php elseif($config['default_pathway'] == 'Methylation'): ?>
                    <div class="left-section">
                        <div class="diagram-section">
                            <img src="http://mthfrsupport.org/wp-content/uploads/2025/01/Methylation.png" alt="Pathway Diagram" class="diagram-image">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="left-section" style="filter:blur(5px);">
                        <div class="pathway-links">
                            <div class="column left-column">
                                <a href="#" class="pathway-link" data-image='#'>Liver Detox - Phase I & II</a>
                                <a href="#" class="pathway-link" data-image='#'>Methylation & Methionine/Homocysteine Pathways</a>
                                <a href="#" class="pathway-link" data-image='#'>Neurotransmitter Pathway: Serotonin & Dopamine</a>
                                <a href="#" class="pathway-link" data-image='#'>COMT Activity</a>
                            </div>
                            <div class="divider"></div>
                            <div class="column right-column">
                                <a href="#" class="pathway-link" data-image='#'>Yeast/Alcohol Metabolism</a>
                                <a href="#" class="pathway-link" data-image='#'>Trans-sulferation Pathway</a>
                                <a href="#" class="pathway-link" data-image='#'>Neurotransmitter Pathway: Glutamate & GABA</a>
                                <a href="#" class="pathway-link" data-image='#'>Mitochondrial Function</a>
                            </div>
                        </div>
                        <div class="diagram-section">
                            <img src="http://mthfrsupport.org/wp-content/uploads/2025/01/Liver_detox.png" alt="Pathway Diagram" class="diagram-image">
                        </div>
                    </div>
                    <?php endif; ?>
                <div class="right-section">
                    <div class="results-section" >
                        <table class="results-table">
                        </table>
                    </div>
                </div>
                </div>
            </div>
    <script>

    
jQuery(document).ready(function($) {
        const resultId = <?php echo intval($result_id); ?>;
        const enableTags = <?php echo json_encode($config['enable_tags']); ?>;
        const defaultPathway = <?php echo json_encode($config['default_pathway']); ?>;

        let jsonLookup = {};
        let geneLookup = {};
        
        try {
            jsonLookup = <?php
                try {
                    $json_file = get_stylesheet_directory() . '/lookup/old_videos_lookup.json';
                    
                    // File name print karen
                    error_log('Loading JSON file: ' . $json_file);
                    
                    if (!file_exists($json_file)) {
                        throw new Exception('JSON file not found.');
                    }
                    $json_data = file_get_contents($json_file);
                    
                    $json_array = json_decode($json_data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('JSON decoding error: ' . json_last_error_msg());
                    }
                    
                    // Data ki details print karen
                    error_log('old_videos_lookup.json loaded successfully. Count: ' . count($json_array));
                    error_log('Data preview: ' . print_r(array_slice($json_array, 0, 3), true));
                    
                    echo json_encode($json_array);
                } catch (Exception $e) {
                    error_log('Error loading JSON: ' . $e->getMessage());
                    echo '{}';
                }
            ?>;
        } catch (error) {
            console.error('Failed to load JSON:', error);
        }
        
        try {
            geneLookup = <?php 
                try {
                    $gene_json_file = get_stylesheet_directory() . '/lookup/new_urls.json';
                    
                    // File name print karen
                    error_log('Loading JSON file: ' . $gene_json_file);
                    
                    if (!file_exists($gene_json_file)) {
                        throw new Exception('JSON file not found.');
                    }
                    $gene_json_data = file_get_contents($gene_json_file);
                    $gene_json_array = json_decode($gene_json_data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('JSON decoding error: ' . json_last_error_msg());
                    }
                    
                    // Data ki details print karen
                    error_log('new_urls.json loaded successfully. Count: ' . count($gene_json_array));
                    error_log('Data preview: ' . print_r(array_slice($gene_json_array, 0, 3), true));
                    
                    echo json_encode($gene_json_array);
                } catch (Exception $e) {
                    error_log('Error loading JSON: ' . $e->getMessage());
                    echo '{}';
                }
            ?>;
        } catch (error) {
            console.error('Failed to load JSON:', error);
        }

        // JavaScript mein data check karen
        console.log('=== JSON Data Debug Info ===');
        console.log('jsonLookup keys count:', Object.keys(jsonLookup).length);
        console.log('jsonLookup data:', jsonLookup);
        console.log('geneLookup keys count:', Object.keys(geneLookup).length);
        console.log('geneLookup data:', geneLookup);
        console.log('===========================');

        let currentPathway = '';

        const pathway_mapping = {
            "Liver Detox - Phase I" : 'Liver_detox.png',
            "Liver Detox - Phase II": 'Liver_detox.png',
            'Yeast/Alcohol Metabolism' : 'yeast.png',
            'Methylation & Methionine/Homocysteine Pathways' : 'Methylation.png',
            'Trans-Sulfuration Pathway' : 'Trans_sulfuration.png',
            'Serotonin & Dopamine' : 'Neuro_transmitter.png',
            'Glutamate & GABA' : 'neuro_transmitter_glutamate.png',
            'COMT Activity' : 'COMT.png',
            'Mitochondrial Function' : 'electron_transport.png',
            'Pentose Phosphate Pathway' : 'Pentose_phosphate_pathway.png',
            'Glyoxylate Metabolic Process' : 'Glyphosate_degradation.png',
            'Thiamin/Thiamine Degradation' : 'Glycolysis.png',
            'HLA': 'biotoxin_pathway.png'
        };
        const pathway_alias = {
                'COMT Activity': /(COMT|Catechol[\w\s/-]*methyltransferase|Catecholamine)/i,
                'Serotonin & Dopamine': /(Serotonin.*Dopamine|Neurotransmitter.*Serotonin.*Dopamine)/i,
                'Glutamate & GABA': /(Glutamate|GABA)/i,
                'Trans-Sulfuration Pathway': /(Trans[-\s]?sulfur|Trans[-\s]?sulfuration)/i,
                'Yeast/Alcohol Metabolism': /(Yeast|Alcohol)/i,
                'Pentose Phosphate Pathway': /(Pentose)/i,
                'Thiamin/Thiamine Degradation': /(Thiamin|Thiamine|Glycolysis)/i,
                'Mitochondrial Function': /(Mitochondrial|Electron Transport|Oxidative)/i,
                'Liver Detox - Phase I': /(Liver\s*Detox).*I(\b|$)/i,
                'Liver Detox - Phase II': /(Liver\s*Detox).*II(\b|$)/i,
                'Glyoxylate Metabolic Process': /(Glyoxylate|Glyoxalate)/i,
                'Biotoxin pathway': /(Biotoxin|HLA)/i
                };
        
        let searchTimeout = null;
        let isLoading = false;

        // Create loading overlay
        const $loadingOverlay = $('<div class="loading-overlay" style="display: none;">' +
            '<div class="loading-spinner"></div>' +
            '</div>');
        $('.results-section').append($loadingOverlay);

        function showLoading() {
            isLoading = true;
            $loadingOverlay.fadeIn(200);
        }

        function hideLoading() {
            isLoading = false;
            $loadingOverlay.fadeOut(200);
        }

        // Initialize plus/minus toggle functionality
        

        // Image changing functionality
        function changeImage(imageUrl) {
            $('.diagram-image').attr('src', 'http://mthfrsupport.local/wp-content/uploads/pathway-images/' + imageUrl);
        }

        // Initialize Select2 with original configuration
        $('.select2-search').select2({
            placeholder: 'Search Tags...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 2,
            cache: false,
            ajax: {
                url: 'http://mthfrsupport.local/wp-json/custom/v1/tags/',
                dataType: 'json',
                delay: 500,
                data: function (params) {
                    return {
                        q: params.term,
                        result_id: resultId  // Send upload_id to the API
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(function (tag) {
                            return { id: tag, text: tag };
                        })
                    };
                }
            }
        });

        // Debounced search function
        function debouncedSearch(callback, delay = 500) {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            searchTimeout = setTimeout(callback, delay);
        }

        $('#snp-search').on('input', function() {
            const $input = $(this);
            if ($input.val()) {
                $('#tag-search').val(null).trigger('change');
                $('#tag-search').prop('disabled', true);
            } else {
                if (enableTags) {
                    $('#tag-search').prop('disabled', false);
                }
            }
        })

        $('#tag-search').on('change', function() {
            const $select = $(this);
            if ($select.val() && $select.val().length > 0) {
                $('#snp-search').val('');
                $('#snp-search').prop('disabled', true);
            } else {
                $('#snp-search').prop('disabled', false);
            }
        });



        function format(d) {

            // Define the content based on the columns (d[5] for rsID, d[6] for Info, d[7] for Video)
            let additionalInfo = '';

            // Check if rsID exists (d[5])
            if (d['SNP ID'] && d['SNP ID'] !== "") {
                additionalInfo += `<p style="margin: 2px; text-align: left;"><strong>rsID:</strong> ${d['SNP ID']}</p>`;
            }

            // Check if Info exists (d[6])
            if (d['Info'] && d['Info'] !== "") {
                additionalInfo += `<p style="margin: 2px; text-align: left;"><strong>Info:</strong> <a href="${d['Info']}" target="_blank" class="text-blue-600 hover:underline">View Info</a></p>`;
            }

            // Check if Video exists (d[7])
            if (d['Video'] && d['Video'] !== "") {
                
                if (d['Video'] && d['Video'].trim() !== "") {
                    if (jsonLookup && geneLookup) {
                        let videos = d['Video'].split(',').map(video => video.trim());
                        let videoLinks = videos.map(videoId => {
                            let mappedId = jsonLookup[videoId] || videoId; // Use lookup or fallback to original
                            let geneName = geneLookup[mappedId] || '';
                            let linkText = geneName ? `Watch Video(${geneName})` : 'Watch Video';
                            return `<a href="https://www.youtube.com/watch?v=${mappedId}" target="_blank" class="text-blue-600 hover:underline">
                                        ${linkText}
                                    </a>`;
                        }).join(', '); // Join links with commas

                        additionalInfo += `<p style="margin: 2px; text-align: left;"><strong>Video:</strong> ${videoLinks}</p>`;
                    }
                }

            }

            // Return formatted HTML with the additional information
            return `
                <div class="additional-info" style="padding: 5px; text-align: left;">
                
                <h5 style="margin: 2px;">Additional Information</h5>
                ${additionalInfo || '<p style="margin: 2px;">No additional information available.</p>'}
            
                </div>
            `;
        }


        // Enhanced filterData with error handling
        async function filterData() {
            if (isLoading) return;
            
            showLoading();
            
            try {

                // Show loading state in tbody before request
                $('.results-table').html(`
                    <tr class="loading-row">
                        <td colspan="8" class="text-center py-4">
                            
                            <div class="custom-spinner"></div>
                            <p style="margin-top: 10px;">Loading...</p>
                        </td>
                    </tr>
                `);

                const ajaxData = {
                    snps: '',
                    tags: '',
                    result_id: resultId  // Send upload_id
                };

                // Only add pathway if it's not null (for Covid reports, don't filter by pathway)
                if (currentPathway !== null) {
                    ajaxData.pathway = currentPathway;
                }

                const response = await $.ajax({
                    url: 'http://mthfrsupport.local/wp-json/custom/v1/filter-table',
                    method: 'GET',
                    data: ajaxData
                });



                // if ($.fn.DataTable.isDataTable('.results-table')) {
                //     $('.results-table').DataTable().destroy();
                // }

                
                // Update table content


                $('.results-table').DataTable({
                    data: response.data.data,
                    rowGroup: {
                        dataSrc: 'Group',
                        startRender: function(row, group) {
                            // Custom rendering for the group header
                            return $('<tr/>')
                                .append('<td colspan="5" style="background-color: #187cbc; color: white; text-align: center;">' + group + '</td>');
                        }
                    },
                    initComplete: function() {
                        // Ensure table layout is set to fixed
                        $('.loading-row').remove();

                        $('.results-table').css('table-layout', 'fixed');
                    },
                    processing: true,
                    paging: false,
                    searching: true,
                    ordering: true,
                    bAutoWidth: false,
                    autoWidth: false,
                    scrollResize: true,
                    pageLength: 10,
                    info: false,
                    scrollY: 100,  // Set scroll height to 100% of the parent container
                    scrollCollapse: true, 
                    order: [[8, 'asc']],
                    columns: [
                        {   
                            title: "More",
                            className: 'dt-control',
                            orderable: false,
                            data: null,
                            defaultContent: '',
                            searchable: false
                        },
                        { data: "SNP Name", title: "SNP Name", width: "40%" },
                        { data: "Risk Allele", title: "Risk Allele", searchable: false },
                        { data: "Your Allele", title: "Your Allele", searchable: false},
                        { data: "Result", title: "Your Results", searchable: false },
                        { data: "SNP ID", title: "SNP ID", searchable: false },
                        { data: "Info" , title: 'Info', searchable: false},
                        {data: 'Video', title: "Video", searchable: false},
                        {data: 'Group', title: "Group"},
                        {data: 'Tags'}
                    ],
                    columnDefs: [
                        { targets: 0, width: '15%' },  // Set column 1 width to 20%
                        { targets: 1, width: '30%' },  // Set column 2 width to 30%
                        { targets: 2, width: '25%' },  // Set column 3 width to 25%
                        { targets: 3, width: '20%' }, 
                        {targets: 4, width:  '20%'},  // Set column 4 width to 25%
                        {
                            targets: 4, // Assuming the Result column is in the 4th position (index 3)
                            createdCell: function(td, cellData, rowData, row, col) {
                                // Color coding for results in the Result column
                                if (cellData === '+/+') {
                                    $(td).css('background-color', '#f47c80').css('color', 'black');
                                } else if (cellData === '-/-') {
                                    $(td).css('background-color', '#78ac74').css('color', 'black');
                                } else if (cellData === '+/-') {
                                    $(td).css('background-color', '#fffcbc').css('color', 'black');
                                }
                            }
                        },
                        {
                            targets: [5, 6, 7, 8, 9],  // Indices of columns you want to hide 
                            visible: false       // Hide these columns
                        },
                        { orderable: false, targets: 0 }  // Disable sorting on the 'More' column (column 1)
                    ],
                });

                $('.dt-scroll-body').each(function(){
                    new SimpleBar($(this)[0], { autoHide: false });
                });

                $('.results-table').DataTable().columns.adjust();

                $('.results-table').DataTable().on('click', 'td.dt-control', function (e) {
                    let tr = $(e.target).closest('tr');
                    
                    let row = $('.results-table').DataTable().row(tr);
                    
                            // Check if the row is already expanded
                    if (row.child.isShown()) {
                        // If it's open, close it
                        row.child.hide();
                        tr.removeClass('shown');
                    } else {
                        // If it's closed, expand it
                        row.child(format(row.data())).show();
                        const pathwayGroup = row.data()['Group']; // Assuming 'Group' contains the pathway name
                        const currentImageSrc = $('.diagram-image').attr('src'); // Assuming #pathway-image is the image element

                        // Determine the new image source
                        const newImageSrc = pathway_mapping[pathwayGroup] || '';
                        if(newImageSrc){
                            changeImage(newImageSrc)
                        }

                        tr.addClass('shown');
                    }
                    var table = $('.results-table').DataTable();


                    table.on('search.dt draw.dt', function() {
                            table.rows().every(function() {
                               if (this.child.isShown()) {
                                      this.child.hide();           // child row close
                                $(this.node()).removeClass('shown');  // class remove
                     }
              });
});

                });

//                 $('#snp-search').keyup(function(){
//                  const table = $('.results-table').DataTable();
//     const searchValue = $(this).val();
    
//     table.search(searchValue).draw();
// });

$('#snp-search').keyup(function () {
    const searchValue = $(this).val().toLowerCase();

    $('.results-table').each(function () {
        const $tbody = $(this).find('tbody');

        if (searchValue) {
            $tbody.find('tr').hide(); 

            $tbody.find('tr').each(function () {
                const $row = $(this);

                if ($row.find('td').attr('colspan')) {
                    let matchFound = false;

                    let $nextRows = $row.nextUntil('tr:has(td[colspan])');

                    $nextRows.each(function () {
                        const text = $(this).find('td:eq(1)').text().toLowerCase();
                        if (text.includes(searchValue)) {
                            $(this).show();   
                            matchFound = true;
                        }
                    });

                    if (matchFound) {
                        $row.show(); 
                    }
                }
            });
        } else {
            $tbody.find('tr').show();
        }
    });
});


                $('.select2-search').on('change', function () {
                    const selectedTags = $(this).val(); // Get selected tags as an array
                    const table = $('.results-table').DataTable();
                    table.columns().search(''); // Clear previous searches
                    
                    $.fn.dataTable.ext.search.length = 0; // Clear all custom search functions

                    if (selectedTags && selectedTags.length > 0) {
                        // Custom search function to check if all selected tags are present in the specified column
                        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                            const columnData = data[9]; // Get data from column 9 (adjust index as needed)
                            
                            // Check if all selected tags are present in the column data
                            return selectedTags.every(tag => columnData.includes(tag));
                        });

                        table.draw(); // Redraw the table to apply the custom search
                    } else {
                        table.columns(9).search('').draw(); // Clear filter if no tags are selected
                    }

                        // Change the image based on the first row's group
                    setTimeout(() => {
                        const firstRowData = table.row(':visible').data(); // Get first visible row
                        if (firstRowData) {

                            const groupValue = firstRowData['Group']; // Adjust index based on the group column
                            pathway_image = pathway_mapping[groupValue] ?? pathway_mapping['Liver Detox - Phase I'];
                            console.log(pathway_image, 'pathway');
                            changeImage(pathway_image);
                        }
                    }, 100);
                });

               $('.pathway-link').on('click', function(e) {
    e.preventDefault();

    console.log('Pathway link clicked.');

    const table = $('.results-table').DataTable();
    const img = $(this).data('image'); // e.g., "COMT.png"
    console.log('Image data:', img);

    const pathwayName = Object.keys(pathway_mapping).find(k => pathway_mapping[k] === img);
    console.log('Resolved pathway name:', pathwayName);

    if (pathwayName) {
        const alias = pathway_alias[pathwayName];
        console.log('Alias for pathway:', alias);

        if (alias) {
            console.log('Searching table column 8 for alias source:', alias.source);
            table.columns(8).search(alias.source, true, false).draw(); // regex search
        } else {
            const rx = '^' + $.fn.dataTable.util.escapeRegex(pathwayName) + '$';
            table.columns(8).search(rx, true, false).draw();
        }
    } else {
        console.warn('No matching pathway name found for image:', img);
    }

    console.log('Calling changeImage with:', img);
    changeImage(img);
});


            } catch (error) {
                console.error('Error fetching data:', error);
                $('.results-table tbody').html(
                    '<tr><td colspan="8" class="text-center py-4 text-red-600">' +
                    'Error loading data. Please try again.</td></tr>'
                );
            } finally {
                hideLoading();
            }
        }


        // Initial load
        filterData();
    });

    </script>
    <?php
    
    return ob_get_clean();
}


add_shortcode('report_visualization', 'report_visualization_shortcode');

function render_user_uploads_table() {
    ob_start(); 
    ?>
        <table id="view-files" class="display">
            <thead>
                <tr>
                    <th data-sort="file_name">File Name <span class="sort-icon"></span></th>
                    <th data-sort="file_type">Format<span class="sort-icon"></span></th>
                    <th data-sort="created_at">Upload Date<span class="sort-icon"></span></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- AJAX content will be loaded here -->
            </tbody>
        </table>

    <?php 
    return ob_get_clean();
}

add_shortcode('user_uploads_table', 'render_user_uploads_table');


function render_user_orders_table() {
    ob_start(); 
    ?>
        <table id="uploaded-files" class="display">
            <thead>
                <tr>
                    <th data-sort="file_name">File Name <span class="sort-icon"></span></th>
                    <th data-sort="file_type">Format<span class="sort-icon"></span></th>
                    <th data-sort="created_at">Upload Date<span class="sort-icon"></span></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- AJAX content will be loaded here -->
            </tbody>
        </table>

    <?php 
    return ob_get_clean();
}

add_shortcode('user_orders_table', 'render_user_orders_table');


function render_user_results_table() {

    ob_start(); 
    ?>
        <table id="result-files" class="display">
            <thead>
                <tr>
                    <th data-sort="file_name">Report Name <span class="sort-icon"></span></th>
                    <th data-sort="file_type">Based on <span class="sort-icon"></span></th>
                    <th data-sort="created_at">Date <span class="sort-icon"></span></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- AJAX content will be loaded here -->
            </tbody>
        </table>

    <?php 
    return ob_get_clean();
}

add_shortcode('user_results_table', 'render_user_results_table');

function render_user_results_download_button($atts) {

    // Extract attributes and set default values
    $atts = shortcode_atts([
        'result_id' => '', // Default empty upload_id
        'file_name' => '',  // Default empty file_name
        'folder_name' => '', // Default empty folder_name
    ], $atts);

    $result_id = esc_html($atts['result_id']);
   
    ob_start(); 
    // HTML structure with two divs
    ?>
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <!-- Left div with file_name and folder_name -->
                <h3 style="margin-bottom: 5px;"><?php echo esc_html($atts['file_name']); ?></h3>
                <p style="margin-top: 0;"><?php echo esc_html($atts['folder_name']); ?></p>
            </div>

            <div>
                <!-- Right div with the Download button -->
                
                <!-- Right div with the Download button -->
                <button id="download-btn" data-result-id="<?php echo $result_id; ?>" style="color: white; background-color: var(--e-global-color-primary); padding: 10px 20px; cursor: pointer;">
                    Download Report
                </button>
            
            </div>
        </div>

    <?php
    return ob_get_clean();
}

add_shortcode('user_result_download_button', 'render_user_results_download_button');




// file uploading script
function file_upload_callback() {
    check_ajax_referer('file_upload', 'security');
    global $wpdb;

    $arr_file_ext = array('application/zip');

    
    if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
        // Get the current user ID
        $user_id = get_current_user_id();
        if ($user_id == 0) {
            $user_id = 'guest'; // If user is not logged in, treat as 'guest'
        }

        // Get the uploaded file details
        $uploaded_file = $_FILES['file'];

        // Check if the file is a valid ZIP file
        $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
        if (strtolower($file_extension) !== 'zip') {
            wp_send_json_error(array('message' => 'Uploaded file is not a valid ZIP file. Please upload a valid ZIP file.'));
            return;
        }

        // Set the upload directory (uploads/user_uploads/{user_id})
        $upload_dir = wp_upload_dir();
        $user_uploads_dir = $upload_dir['basedir'] . '/user_uploads/' . $user_id;

        // Create the directory if it doesn't exist
        if (!file_exists($user_uploads_dir)) {
            mkdir($user_uploads_dir, 0777, true);
        }


        // Generate a unique filename for the uploaded file
        $unique_filename = basename($uploaded_file['name']);
        $target_file = $user_uploads_dir . '/' . $unique_filename;

        if (file_exists($target_file)) {
            wp_send_json_error(array('message' => 'File already exists.'));
            return;
            
        } 

        // Open the ZIP file and check for a .txt file inside
        $zip = new ZipArchive;
        $source_type = null;
        if ($zip->open($uploaded_file['tmp_name']) === TRUE) {
            // Find the .txt file inside the ZIP
            $txt_file = '';  // Initialize variable to hold the .txt file name
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $file_name = $zip->getNameIndex($i);
                
                // Check if the file is a .txt file
                if (pathinfo($file_name, PATHINFO_EXTENSION) === 'txt') {
                    $txt_file = $file_name; // Get the .txt file name
                    break;  // Stop after finding the first .txt file
                }
            }

            if ($txt_file) {
                // Read the contents of the .txt file inside the ZIP
                $file_contents = $zip->getFromName($txt_file);

                $lines = preg_split('/\r\n|\r|\n/', $file_contents);


                $cleaned_lines = [];

                foreach ($lines as $line) {
                    // Replace commas with tabs
                    $new_line = str_replace(',', "\t", $line);

                    // Remove all double quotes
                    $new_line = str_replace('"', '', $new_line);

                    $cleaned_lines[] = $new_line;
                }

                $source_type = null;
                $header_found = false;
                $header_line_index = null;
                $valid_snp_count = 0;

                foreach ($cleaned_lines as $i => $line) {
                    $line = trim($line);
                    if ($line === '') continue;

                    // MyHeritage: RSID	CHROMOSOME	POSITION RESULT
                    if (preg_match('/^RSID\s+CHROMOSOME\s+POSITION\s+RESULT$/i', $line)) {
                        $source_type = 'myheritage';
                        $header_found = true;
                        $header_line_index = $i;
                        continue;
                    }

                    // Ancestry: rsid	chromosome	position allele1	allele2
                    if (preg_match('/^rsid\s+chromosome\s+position\s+allele1\s+allele2$/i', $line)) {
                        $source_type = 'ancestry';
                        $header_found = true;
                        $header_line_index = $i;
                        continue;
                    }

                    // 23andMe: # rsid	chromosome	position genotype
                    if (preg_match('/^#\s*rsid\s+chromosome\s+position\s+genotype$/i', $line)) {
                        $source_type = '23andme';
                        $header_found = true;
                        $header_line_index = $i;
                        continue;
                    }

                    // Skip before header
                    if (!$header_found) continue;

                    // Check for valid SNP data lines
                    $fields = preg_split('/\t|,/', $line);

                    if ($source_type === 'myheritage' && count($fields) === 4 && preg_match('/^rs\d+$/i', $fields[0])) {
                        $valid_snp_count++;
                    }

                    if ($source_type === 'ancestry' && count($fields) === 5 && preg_match('/^rs\d+$/i', $fields[0])) {
                        $valid_snp_count++;
                    }

                    if ($source_type === '23andme' && count($fields) === 4 && preg_match('/^rs\d+$/i', $fields[0])) {
                        $valid_snp_count++;
                    }

                    if ($valid_snp_count >= 3) break;
                }

                if (!$header_found || !$source_type || $valid_snp_count < 3) {
                    wp_send_json_error(['message' => 'Invalid or unsupported raw DNA file.']);
                    return;
                }

            } else {
                wp_send_json_error(array('message' => 'No .txt file found inside the ZIP.'));
                return;
            }
        } else {
            wp_send_json_error(array('message' => 'Failed to open ZIP file.'));
            return;
        }

        if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
            // Insert the data into the database
            $table_name = $wpdb->prefix . 'user_uploads';
            $folder_name = basename($target_file);  // Get the folder name where the file is uploaded

            $insert_data = array(
                'user_id' => $user_id,
                'file_name' => $folder_name,
                'file_path' => $target_file,
                'source_type' => $source_type
            );

            $insert_format = array('%d', '%s', '%s', '%s');
            $wpdb->insert($table_name, $insert_data, $insert_format);
            $upload_id = $wpdb->insert_id;

            if (!$upload_id) {
                wp_send_json_error(array('message' => 'Failed to save upload details to the database.'));
                return;
            }

            $created_at = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT created_at FROM $table_name WHERE id = %d",
                    $upload_id
                )
            );

            wp_send_json_success(array(
                'folder_name' => $folder_name,
                'source_type' => $source_type,
                'upload_id' => $upload_id,
                'created_at' => date('Y-m-d', strtotime($created_at)),
            ));

        } else {
            wp_send_json_error(array('message' => 'Failed to move the uploaded file.'));
        }
    }
    wp_die();
}


add_action('wp_ajax_file_upload', 'file_upload_callback');
add_action('wp_ajax_nopriv_file_upload', 'file_upload_callback');

function handle_delete_file() {
    // Verify nonce
    check_ajax_referer('delete_file_nonce', 'security');

    if (!isset($_POST['upload_id'])) {
        wp_send_json_error(array('message' => 'No upload ID provided.'));
        return;
    }

    $upload_id = intval($_POST['upload_id']);
    global $wpdb;
    
    // Table names
    $upload_table = $wpdb->prefix . 'user_uploads';
    $report_table = $wpdb->prefix . 'user_reports';

    // Start transaction
    $wpdb->query('START TRANSACTION');

    try {
        // Get upload data
        $upload_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$upload_table} WHERE id = %d",
            $upload_id
        ));

        if (!$upload_data) {
            throw new Exception('File not found in the database.');
        }

        // Check if file exists on server
        if (!file_exists($upload_data->file_path)) {
            throw new Exception('File not found on the server.');
        }

        // Get all associated reports
        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$report_table} WHERE upload_id = %d",
            $upload_id
        ));

        $deleted_reports_count = 0;

        // Delete all associated reports
        if ($reports) {
            foreach ($reports as $report) {
                if (!empty($report->report_path) && file_exists($report->report_path)) {
                    if (!unlink($report->report_path)) {
                        throw new Exception('Failed to delete report file: ' . $report->report_path);
                    }
                    $deleted_reports_count++;
                }
            }
            
            // Delete all report records for this upload
            $reports_deleted = $wpdb->delete(
                $report_table, 
                array('upload_id' => $upload_id),
                array('%d')
            );
            
            if ($reports_deleted === false) {
                throw new Exception('Failed to delete report records from database.');
            }
        }

        // Delete original upload file
        if (!unlink($upload_data->file_path)) {
            throw new Exception('Failed to delete uploaded file.');
        }

        // Delete upload record
        $upload_deleted = $wpdb->delete($upload_table, array('id' => $upload_id));
        if ($upload_deleted === false) {
            throw new Exception('Failed to delete upload record from database.');
        }

        // If we got here, everything worked
        $wpdb->query('COMMIT');
        wp_send_json_success(array(
            'message' => sprintf(
                'File and %d associated report(s) deleted successfully.',
                $deleted_reports_count
            ),
            'deleted_reports_count' => $deleted_reports_count
        ));

    } catch (Exception $e) {
        // Something went wrong, rollback changes
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}
add_action('wp_ajax_delete_file', 'handle_delete_file');
add_action('wp_ajax_nopriv_delete_file', 'handle_delete_file');


add_action('wp_ajax_get_user_uploads', 'get_user_uploads');
add_action('wp_ajax_nopriv_get_user_uploads', 'get_user_uploads');

// getting data from database for user uploads
function get_user_uploads() {
    // Verify nonce for security
    check_ajax_referer('get_user_uploads_nonce', 'security');

    // Get the current user's ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'User not logged in.']);
        return;
    }

    // Fetch user uploads from the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_uploads'; // Replace with your table name
    $uploads = $wpdb->get_results(
        $wpdb->prepare("SELECT id, file_name, source_type, created_at FROM $table_name WHERE user_id = %d", $user_id),
        ARRAY_A
    );

    if ($uploads) {
        // Construct file paths and folder names
        $upload_dir = wp_upload_dir();
        $user_folder = "user_uploads/{$user_id}/";
        $uploads_data = array_map(function ($upload) use ($upload_dir, $user_folder) {
            $file_name = $upload['file_name'];
            $source_type = $upload['source_type'];
            $created_at = $upload['created_at']; // Get created_at timestamp

            return [
                'upload_id' => $upload['id'],
                'file_name' => $file_name,
                'format' => $source_type,
                'created_at' => date('Y-m-d', strtotime($created_at)),
                // 'file_url' => $upload_dir['baseurl'] . '/' . $file_location,
            ];
        }, $uploads);

        wp_send_json_success(['uploads' => $uploads_data]);
    } else {
        wp_send_json_error(['message' => 'No uploads found.']);
    }
}

add_action('wp_ajax_get_user_result', 'get_user_results');
add_action('wp_ajax_nopriv_get_user_result', 'get_user_results');

// getting data from database for user uploads
function get_user_results() {
    check_ajax_referer('get_user_result_nonce', 'security');

    global $wpdb;



    // FIX: Hardcoded table names
    $table_reports = "wpub_user_reports";
    $table_uploads = "wpub_user_uploads";

    // USER ID REMOVE — so all reports come
    $results = $wpdb->get_results(
        "SELECT r.*, u.file_name, u.user_id
         FROM $table_reports r
         INNER JOIN $table_uploads u ON r.upload_id = u.id
         WHERE r.status = 'completed'
         AND r.is_deleted = 0
         ORDER BY r.updated_at DESC",
        ARRAY_A
    );

    if (!$results) {
        wp_send_json_error(['message' => 'No reports found']);
        return;
    }

    $final = array_map(function ($row) {
        return [
            'result_id'   => $row['id'],
            'upload_id'   => $row['upload_id'],
            'user_id'     => $row['user_id'], // just for reference
            'report_name' => 'MTHFRSupport ' . $row['report_name'],
            'file_name'   => basename($row['file_name']),
            'json_url'    => $row['json_url'],
            'pdf_url'     => $row['pdf_url'],
            'created_at'  => $row['updated_at'],
        ];
    }, $results);

    wp_send_json_success(['results' => $final]);
}






// Create a shortcode to display the file name based on the upload_id stored in session
function file_name_shortcode() {
    // Start session if not already started
    if (!session_id()) {
        session_start();
    }
    $user_id = get_current_user_id();
    if ($user_id == 0) {
        wp_redirect(home_url(), 301 );
        exit();
    }

    // Get the upload_id from session storage (if it exists)
    if (isset($_GET['upload_id'])) {
        $upload_id = intval($_GET['upload_id']); // Ensure it's an integer

        // Query the database to get the file_name (adjust table name if necessary)
        global $wpdb;
        $file_name = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT file_path FROM {$wpdb->prefix}user_uploads WHERE id = %d AND user_id = %d",
                $upload_id,
                $user_id
            )
        );



        // Return the file name or a default message if not found
        if ($file_name) {
            $file_name = basename($file_name);
            return esc_html($file_name);
        } else {            
            $report_url = site_url('/order-report');
            wp_redirect($report_url, 301 );
            exit();
        }
    } 
    else {
        $report_url = site_url('/order-report');
        wp_redirect($report_url, 301 );
        exit();
    }
}
add_shortcode('file_name_display', 'file_name_shortcode');


// Modified add to cart button function
function custom_add_to_cart_button($atts) {
    // die("here");
    $atts = shortcode_atts(array(
        'id' => '',
    ), $atts);

    if (!empty($atts['id'])) {
        // Store upload_id in session before adding to cart
        if (isset($_GET['upload_id'])) {
            WC()->session->set('temp_upload_id', intval($_GET['upload_id']));
        }

        $url = wc_get_cart_url() . '?add-to-cart=' . $atts['id'];
        
        // return sprintf(
        //     '<a href="%s" class="add_to_cart_button ajax_add_to_cart" data-product_id="%s">Select</a>',
        //     esc_url($url),
        //     esc_attr($atts['id'])
        // );
        return sprintf(
            '<a href="%s" class="add_to_cart_button ajax_add_to_cart" data-product_id="%s" style="display:block; text-align:center; background-color: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; transition: background-color 0.3s ease;">
                Select
            </a>',
            esc_url($url),
            esc_attr($atts['id'])
        );

    }
    return '';
}
add_shortcode('add_to_cart_button', 'custom_add_to_cart_button');

// add_action('woocommerce_before_cart', function() {
//     echo '<p style="color:red;">This is before cart hook!</p>';
// });

// Store upload_id with cart item when product is added
// add_filter('woocommerce_add_cart_item_data', 'store_upload_id_with_cart_item', 10, 3);
// function store_upload_id_with_cart_item($cart_item_data, $product_id, $variation_id) {
//     error_log('store_upload_id_with_cart_item function called');
    
//     // Get upload_id from session
//     $upload_id = WC()->session->get('temp_upload_id');
    
//     if ($upload_id) {
//         error_log("Upload ID found in session: $upload_id");
//         $cart_item_data['upload_id'] = $upload_id;
//         // Clear the temporary session data
//         // WC()->session->__unset('temp_upload_id');
//         error_log('Upload ID stored in cart item data');
//     } else {
//         error_log('No upload ID found in session');
//     }
    
//     return $cart_item_data;
// }

// Save upload_id to order item meta during checkout
// add_action('woocommerce_checkout_create_order_line_item', 'save_upload_id_to_order_item', 10, 4);
// function save_upload_id_to_order_item($item, $cart_item_key, $values, $order) {
//     error_log('save_upload_id_to_order_item function called');
    
//     if (isset($values['upload_id'])) {
//         $upload_id = $values['upload_id'];
//         error_log("Saving upload ID to order item: $upload_id");
//         $item->add_meta_data('_upload_id', $upload_id);
//     } else {
//         error_log('No upload ID found in cart item values');
//     }
// }

// check if the product exists
// function order_has_product( $order, $product_id ) {
//     foreach ( $order->get_items() as $item ) {
//         if ( $item->get_product_id() == $product_id ) {
//             return true;
//         }
//     }
//     return false;
// }


// Process PDF creation after order is completed - REMOVED to avoid conflict with plugin
// add_action('woocommerce_order_status_completed', 'process_pdf_creation', 10, 1);
// function process_pdf_creation($order_id) {
//     error_log("process_pdf_creation function called for order: $order_id");

//     $order = wc_get_order($order_id);
//     try {
//         $has_subscription = order_has_product($order, 2152);
//         error_log('has subscriptions: ' . ($has_subscription ? 'true' : 'false'));
//     } catch (Throwable $e) {
//         error_log('Error checking for subscription product: ' . $e->getMessage());
//         $has_subscription = false; // Set a default fallback
//     }

//     $product_ids_to_check = [120, 938, 971, 977, 1698]; // Replace with your actual product IDs


//     foreach ($order->get_items() as $item_id => $item) {
//         // Get upload_id from order item meta
//         $upload_id = $item->get_meta('_upload_id');
//         $product = $item->get_product();
//         $product_id = $product->get_id();
//         $product_name = $product->get_name();

//         // Log product details for debugging
//         error_log("Product ID: $product_id, Product Name: $product_name");

//         if ($upload_id && in_array($product_id, $product_ids_to_check)) {
//             error_log("Found upload ID in order item: $upload_id");
//             // Your PDF creation logic here
//             $result = create_pdf_from_upload($upload_id,$order_id, $product_id, $product_name, $has_subscription);  // Pass 'variant' as the variant value

//             error_log("PDF creation successful for upload ID: $result");
//             if ($result) {
//                 error_log("PDF creation successful for upload ID: $upload_id");
//                 $item->add_meta_data('_pdf_result', $result);
//                 $item->save();
//             } else {
//                 error_log("PDF creation failed for upload ID: $upload_id");
//             }
//         } else {
//             error_log("No upload ID found for order item: $item_id");
//         }
//     }
// }

// Helper function for PDF creation (implement your specific logic)
function create_pdf_from_upload($upload_id,$order_id, $product_id, $product_name, $has_subscription) {
    // Construct the API URL with query parameters
    $api_url = 'http://api.mthfrsupport.org/backend/api/creation';
    $url = add_query_arg(
        array(
            'upload_id' => $upload_id,
            'order_id' => $order_id,
            'product_name' => $product_name,
            'has_subscription' => $has_subscription
        ),
        $api_url
    );

    // Make the API request using the constructed URL
    $api_response = wp_remote_get($url);  // Use wp_remote_get to make a GET request

    if (!is_wp_error($api_response)) {
        return wp_remote_retrieve_body($api_response);
    }

    return false;
}



// // add_action('wp_ajax_download_pdf', 'handle_download_pdf');
// // add_action('wp_ajax_nopriv_download_pdf', 'handle_download_pdf');

// function handle_download_pdf() {
//     die("here");
//     // Security check
//     check_ajax_referer('download_pdf', 'security');
    
//     if (!isset($_POST['result_id']) || empty($_POST['result_id'])) {
//         error_log('Download PDF: No result_id provided');
//         wp_die('No result ID provided');
//     }
    
//     $result_id = intval($_POST['result_id']);
//     $user_id = get_current_user_id();
    
//     if (!$user_id) {
//         error_log('Download PDF: User not logged in');
//         wp_die('User not logged in');
//     }
    
//     // Debug log
//     error_log('Download PDF Request - Result ID: ' . $result_id . ', User ID: ' . $user_id);
    
//     global $wpdb;
    
//     // IMPORTANT: Get the EXACT report based on result_id
//     $report = $wpdb->get_row($wpdb->prepare(
//         "SELECT ur.id, ur.report_path, ur.report_name, ur.report_type, uu.file_name
//          FROM {$wpdb->prefix}user_reports ur
//          INNER JOIN {$wpdb->prefix}user_uploads uu ON ur.upload_id = uu.id
//          WHERE ur.id = %d AND uu.user_id = %d AND ur.status = 'completed'",
//         $result_id,
//         $user_id
//     ));
    
//     print_r($report);
//     die;
//     // Debug what we found
//     if ($report) {
//         error_log('Found report: ID=' . $report->id . ', Path=' . $report->report_path . ', Name=' . $report->report_name);
//     } else {
//         error_log('No report found for result_id=' . $result_id . ' and user_id=' . $user_id);
//     }
    
//     if (!$report) {
//         error_log('Download PDF: Report not found or access denied for result_id: ' . $result_id);
//         wp_die('Report not found or access denied');
//     }
    
//     $file_path = $report->report_path;
    
//     // Check if file actually exists
//     if (!file_exists($file_path)) {
//         error_log('Download PDF: Report file does not exist at path: ' . $file_path);
//         wp_die('Report file not found on server');
//     }
    
//     // Debug log - confirm we're serving the right file
//     error_log('Serving PDF file: ' . $file_path . ' for result_id: ' . $result_id);
    
//     // Create proper filename
//     $safe_filename = sanitize_file_name($report->report_name) . '.pdf';
    
//     // Clear any output buffers
//     if (ob_get_level()) {
//         ob_end_clean();
//     }
    
//     // Set proper headers
//     header('Content-Type: application/pdf');
//     header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
//     header('Content-Length: ' . filesize($file_path));
//     header('Cache-Control: private, max-age=0, must-revalidate');
//     header('Pragma: public');
    
//     // Output the file
//     readfile($file_path);
//     exit;
// }

// View report redirection
function load_report_visualization() {
     global $wpdb;
    check_ajax_referer('load_report_visualization', 'security');


    $result_id = isset($_POST['result_id']) ? intval($_POST['result_id']) : 0;
    if (!$result_id) {
        wp_send_json_error(['message' => 'Invalid result ID']);
    }

    $table = $wpdb->prefix . 'user_reports';
    $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $result_id));

    if (!$report) {
        wp_send_json_error(['message' => 'Report not found']);
    }

    $order_id    = intval($report->order_id);

    //  $generator_info = null;
    // if (!$report->json_url || !$report->pdf_url) {
    //     $_POST['order_id'] = $order_id;

    //     // Capture output from generator but discard it
    //     ob_start();
    //     $generator_result = mthfr_ajax_test_report(); // returns array of report info
    //     ob_end_clean();

    //     $generator_info = $generator_result; // optional: include generator info in final response
    // }


    if (isset($_POST['result_id'])) {
        $result_id = intval($_POST['result_id']);
        $file_name = ($_POST['file_name']);
        $folder_name = ($_POST['folder_name']);
        
        echo do_shortcode('[report_visualization result_id="' . esc_attr($result_id) . '" 
                                    file_name="' . esc_attr($file_name) . '" 
                                    folder_name="' . esc_attr($folder_name) . '"]');

    
        
    }
    wp_die();  // Always terminate AJAX with wp_die()
}

add_action('wp_ajax_load_report_visualization', 'load_report_visualization');
add_action('wp_ajax_nopriv_load_report_visualization', 'load_report_visualization');

function reload_results_table() {
    check_ajax_referer('reload_results_table', 'security');

    // Render the table again when "Back" button is clicked
    echo do_shortcode('[user_results_table]');
    wp_die();  // Always terminate AJAX with wp_die()
}

add_action('wp_ajax_reload_results_table', 'reload_results_table');
add_action('wp_ajax_nopriv_reload_results_table', 'reload_results_table');


// Shortcode to display product price
function custom_product_price_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts);
    return wc_price(get_post_meta($atts['id'], '_price', true));
}
add_shortcode('product_price', 'custom_product_price_shortcode');

// Shortcode to display product name
function custom_product_name_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts);
    $product = wc_get_product($atts['id']);
    return $product ? $product->get_name() : 'Product not found';
}
add_shortcode('product_name', 'custom_product_name_shortcode');


// Step 1: Detect when a report is already in the cart
add_filter( 'woocommerce_add_to_cart_validation', 'check_existing_report_before_add', 10, 3 );
function check_existing_report_before_add( $passed, $product_id, $quantity ) {

    // Check if cart already has an item
    if ( ! WC()->cart->is_empty() ) {

        // Prevent auto-adding
        $passed = false;

        // Create replace URL
        $replace_url = add_query_arg( array(
            'replace_report' => 'yes',
            'new_report_id'  => $product_id
        ), wc_get_cart_url() );

        // Show notice with replace option
        wc_add_notice(
            sprintf(
                'You already have a report in your cart. <a href="%s" class="button">Replace with this new report</a>',
                esc_url( $replace_url )
            ),
            'notice'
        );
    }

    return $passed;
}

// Step 2: Handle replace action
add_action( 'template_redirect', 'handle_replace_report_action' );
function handle_replace_report_action() {
    if ( isset( $_GET['replace_report'] ) && $_GET['replace_report'] === 'yes' && ! empty( $_GET['new_report_id'] ) ) {

        // Remove all existing items (reports)
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            WC()->cart->remove_cart_item( $cart_item_key );
        }

        // Add new report
        $new_report_id = intval( $_GET['new_report_id'] );
        WC()->cart->add_to_cart( $new_report_id );

        // Success notice
        wc_add_notice( 'Your cart has been updated with the new report.', 'success' );

        // Redirect back to cart
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }
}

// Force allow public access to specific pages for both logged-in and logged-out users
add_action('template_redirect', function() {
    // Slugs of pages that should be public
    $public_pages = [
        'about-us',
        'snp-learning-series',
        'mthfr-support-faqs',
        'covid-19',
        'what-happened-to-my-reports'
    ];
    
    if (is_page($public_pages)) {
        // Remove any redirect applied by membership plugins or theme
        remove_all_actions('template_redirect');
    }
});
add_filter('wp_kses_no_null', function($content, $options) {
    if (!is_array($options)) {
        $options = array('slash_zero' => 'remove');
    }
    return $content;
}, 10, 2);

