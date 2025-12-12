<?php
// Report functions for Neve theme

function custom_tags_search_api(WP_REST_Request $request) {
    global $wpdb;

    $search_term = strtolower($request->get_param('q'));
    $result_id   = intval($request->get_param('result_id'));

    if (!$result_id) {
        return rest_ensure_response([]);
    }

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

    $data = $json_data['variants'] ?? $json_data;

    $all_tags = [];
    foreach ($data as $row) {
        if (!is_array($row)) continue;

        $tags = [];
        if (!empty($row['Tags'])) {
            $tags = array_filter(array_map('trim', explode(',', $row['Tags'])));
        }
        if (!empty($row['Group'])) {
            $tags[] = trim($row['Group']);
        }
        $all_tags = array_merge($all_tags, $tags);
    }

    $all_tags = array_unique($all_tags);
    sort($all_tags);

    if (empty($search_term)) {
        return rest_ensure_response(array_slice($all_tags, 0, 20));
    }

    $filtered_tags = array_filter($all_tags, function($tag) use ($search_term) {
        return stripos($tag, $search_term) !== false;
    });

    $filtered_tags = array_slice(array_values($filtered_tags), 0, 20);
    return rest_ensure_response($filtered_tags);
}

function custom_filter_table_api(WP_REST_Request $request) {
    global $wpdb;

    $tags    = $request->get_param('tags') ? $request->get_param('tags') : [];
    $snps    = $request->get_param('snps') ? sanitize_text_field($request->get_param('snps')) : '';
    $pathway = $request->get_param('pathway') ? sanitize_text_field($request->get_param('pathway')) : 'Liver_detox';
    $result_id = intval($request->get_param('result_id'));

    try {
        if (!$result_id) {
            return new WP_Error('invalid_upload_id', 'Invalid upload ID.', ['status' => 400]);
        }

        $table_name  = $wpdb->prefix . 'user_reports';
        $report_info = $wpdb->get_row(
            $wpdb->prepare("SELECT report_path, report_type FROM $table_name WHERE id = %d", $result_id)
        );

        if (!$report_info || empty($report_info->report_path) || !file_exists($report_info->report_path)) {
            return new WP_Error('file_not_found', 'Report file not found.', ['status' => 404]);
        }

        $report_configs = [
            'Excipient' => ['enable_tags' => false, 'enable_pathway' => false, 'default_pathway' => null],
            'Covid' => ['enable_tags' => false, 'enable_pathway' => false, 'default_pathway' => null],
            'Variant' => ['enable_tags' => true, 'enable_pathway' => true, 'default_pathway' => 'Liver_detox'],
            'Methylation' => ['enable_tags' => true, 'enable_pathway' => false, 'default_pathway' => 'Methylation'],
            'Bundled' => ['enable_tags' => true, 'enable_pathway' => true, 'default_pathway' => 'Liver_detox']
        ];

        $config = $report_configs[$report_info->report_type] ?? $report_configs['Excipient'];

        $json_content = file_get_contents($report_info->report_path);
        if (empty($json_content)) {
            return new WP_Error('file_read_error', 'Unable to read report file.', ['status' => 500]);
        }

        $json_data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_parse_error', 'Invalid JSON data.', ['status' => 500]);
        }

        $data = $json_data['variants'] ?? [];

        if (empty($data)) {
            return new WP_Error('no_variants', 'No variants found in report.', ['status' => 404]);
        }

        $grouped_data = [];
        foreach ($data as $row) {
            if (!is_array($row)) continue;
            $group = $row['Group'] ?? ($row['rs10306114'] ?? 'Other');
            if (!isset($grouped_data[$group])) {
                $grouped_data[$group] = [];
            }
            $grouped_data[$group][] = $row;
        }

        $filteredData = [];
        foreach ($data as $entry) {
            if (!is_array($entry)) continue;
            $filteredData[] = [
                "SNP Name" => $entry["SNP Name"] ?? "",
                "Risk Allele" => $entry["Risk Allele"] ?? "",
                "Your Allele" => $entry["Your Allele"] ?? "",
                "Result" => $entry["Result"] ?? "",
                "SNP ID" => $entry["SNP ID"] ?? "",
                "Info" => $entry["Info"] ?? "",
                'Video' => $entry['Video'] ?? "",
                'Group' => $entry['Group'] ?? "",
                'Tags' => $entry['Tags'] ?? ""
            ];
        }

        if($report_info->report_type === 'Covid'){
            $filteredData = array_values(array_column($filteredData, null, "SNP ID"));
        }

        $response_data = [
            'body_html' => '',
            'isSearchResult' => (!empty($tags) || !empty($snps)),
            'data' => $filteredData
        ];

        return new WP_REST_Response($response_data, 200);

    } catch (Exception $e) {
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
        'Excipient' => ['enable_tags' => false, 'enable_pathway' => false, 'default_pathway' => null],
        'Covid' => ['enable_tags' => false, 'enable_pathway' => false, 'default_pathway' => null],
        'Variant' => ['enable_tags' => true, 'enable_pathway' => true, 'default_pathway' => 'Liver_detox'],
        'Methylation' => ['enable_tags' => true, 'enable_pathway' => false, 'default_pathway' => 'Methylation'],
        'Bundled' => ['enable_tags' => true, 'enable_pathway' => true, 'default_pathway' => 'Liver_detox']
    ];
    $config = $report_configs[$report_info->report_type] ?? $report_configs['Excipient'];

    ?>
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    .view-report-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .search-section { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
    .search-input { flex: 1; min-width: 250px; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .main-content { max-width: 1200px; margin: 0 auto; padding: 20px; display: flex; gap: 20px; }
    .left-section { flex: 1; min-width: 0; }
    .right-section { width: 350px; min-width: 350px; }
    .pathway-links { display: flex; justify-content: center; margin-bottom: 20px; min-height: 120px; }
    .column { width: 50%; padding: 0 15px; display: flex; flex-direction: column; gap: 8px; }
    .left-column { align-self: center; text-align: right; }
    .right-column { align-self: center; text-align: left; }
    .divider { width: 1px; background-color: #000; margin: 0 10px; }
    .pathway-link { color: #4F81BD; font-size: 13px; font-family: Arial, sans-serif; text-decoration: none; cursor: pointer; line-height: 1.2; }
    .diagram-section { margin-bottom: 20px; }
    .diagram-image { width: 100%; height: auto; max-width: 100%; }
    .results-section { border: 1px solid #ddd; border-radius: 4px; overflow: hidden; width: 100%; height: 100%; }
    .results-table { width: 100%; border-collapse: collapse; }
    .results-table th { text-align: center; font-size: 13px; }
    .results-table td { text-align: center; font-size: 13px; }
    .plus-circle { cursor: pointer; display: inline-block; width: 20px; height: 20px; text-align: center; line-height: 18px; border: 1px solid #ccc; border-radius: 50%; background-color: #f8f8f8; }
    .info-row { background-color: #f9f9f9; }
    .additional-info { padding: 15px; }
    .info-content { max-width: 800px; margin: 0 auto; }
    .info-content p { margin: 8px 0; }
    .info-content a { color: #4F81BD; text-decoration: none; }
    .info-content a:hover { text-decoration: underline; }
    .dt-search { display: none; }
    div.dt-container div.dt-layout-row { display: initial; }
    .select2-dropdown { z-index: 100001; margin-top: 30px; }
    .custom-spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto; }
    .dt-scroll-body { overflow: visible !important; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @media (max-width: 1024px) { .main-content { flex-direction: column; } .right-section { width: 100%; min-width: 0; } }
    @media (max-width: 768px) { .view-report-container { max-width: 1200px; margin: 0; padding: 20px; } .pathway-links { flex-direction: column; align-items: center; min-height: auto; } .column { width: 100%; text-align: center; padding: 10px 0; } .left-column { text-align: center; } .right-column { text-align: center; } .divider { width: 80%; height: 1px; margin: 10px 0; } .results-table { font-size: 14px; } .results-table td, .results-table th { padding: 8px; text-align: left; } }
    @media (max-width: 760px) { .main-content { flex-direction: column; } .diagram { flex-direction: column; } .arrow { transform: rotate(90deg); } .results-section { overflow-x: auto; } .results-table tr.hidden { display: none; } .link-text { margin: 2px 1px; } }
    </style>
    <div class="view-report-container">
        <div class="details">
            <?php echo do_shortcode('[user_result_download_button result_id="' . esc_attr($result_id) . '" file_name="' . esc_attr($file_name) . '" folder_name="' . esc_attr($folder_name) . '"]'); ?>
        </div>
        <div class="search-section">
            <div class='search-input'>
                <label for="tag-search">Tag Search:</label>
                <?php if ($config['enable_tags']): ?>
                    <select class="select2-search" id="tag-search" placeholder="Tags.." style="width: 100%;" multiple></select>
                <?php else: ?>
                    <div class="fake-input" style="width: 100%; padding: 6px; background-color: #f0f0f0; color: #999; border-radius:4px;">Tags...</div>
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
                    <img src="https://mthfrsupport.org/wp-content/uploads/2025/01/Liver_detox.png" alt="Pathway Diagram" class="diagram-image">
                </div>
            </div>
            <?php elseif($config['default_pathway'] == 'Methylation'): ?>
            <div class="left-section">
                <div class="diagram-section">
                    <img src="https://mthfrsupport.org/wp-content/uploads/2025/01/Methylation.png" alt="Pathway Diagram" class="diagram-image">
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
                    <img src="https://mthfrsupport.org/wp-content/uploads/2025/01/Liver_detox.png" alt="Pathway Diagram" class="diagram-image">
                </div>
            </div>
            <?php endif; ?>
            <div class="right-section">
                <div class="results-section">
                    <table class="results-table"></table>
                </div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        const resultId = <?php echo intval($result_id); ?>;
        const enableTags = <?php echo json_encode($config['enable_tags']); ?>;

        let jsonLookup = {};
        let geneLookup = {};
        try {
            jsonLookup = <?php
                $json_file = get_template_directory() . '/../mthfr-theme/lookup/old_videos_lookup.json';
                if (file_exists($json_file)) {
                    $json_data = file_get_contents($json_file);
                    $json_array = json_decode($json_data, true);
                    echo json_encode($json_array);
                } else {
                    echo '{}';
                }
            ?>;
        } catch (error) {
            console.error('Failed to load JSON:', error);
        }
        try {
            geneLookup = <?php
                $gene_json_file = get_template_directory() . '/../mthfr-theme/lookup/new_urls.json';
                if (file_exists($gene_json_file)) {
                    $gene_json_data = file_get_contents($gene_json_file);
                    $gene_json_array = json_decode($gene_json_data, true);
                    echo json_encode($gene_json_array);
                } else {
                    echo '{}';
                }
            ?>;
        } catch (error) {
            console.error('Failed to load JSON:', error);
        }

        let currentPathway = 'Liver detox';

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

        const $loadingOverlay = $('<div class="loading-overlay" style="display: none;"><div class="loading-spinner"></div></div>');
        $('.results-section').append($loadingOverlay);

        function showLoading() {
            isLoading = true;
            $loadingOverlay.fadeIn(200);
        }

        function hideLoading() {
            isLoading = false;
            $loadingOverlay.fadeOut(200);
        }

        $('.select2-search').select2({
            placeholder: 'Search Tags...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 2,
            cache: false,
            ajax: {
                url: 'https://mthfrsupport.org/wp-json/custom/v1/tags/',
                dataType: 'json',
                delay: 500,
                data: function (params) {
                    return {
                        q: params.term,
                        result_id: resultId
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
        });

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
            let additionalInfo = '';
            if (d['SNP ID'] && d['SNP ID'] !== "") {
                additionalInfo += `<p style="margin: 2px; text-align: left;"><strong>rsID:</strong> ${d['SNP ID']}</p>`;
            }
            if (d['Info'] && d['Info'] !== "") {
                additionalInfo += `<p style="margin: 2px; text-align: left;"><strong>Info:</strong> <a href="${d['Info']}" target="_blank" class="text-blue-600 hover:underline">View Info</a></p>`;
            }
            if (d['Video'] && d['Video'] !== "") {
                if (d['Video'] && d['Video'].trim() !== "") {
                    if (jsonLookup && geneLookup) {
                        let videos = d['Video'].split(',').map(video => video.trim());
                        let videoLinks = videos.map(videoId => {
                            let mappedId = jsonLookup[videoId] || videoId;
                            let geneName = geneLookup[mappedId] || '';
                            let linkText = geneName ? `Watch Video(${geneName})` : 'Watch Video';
                            return `<a href="https://www.youtube.com/watch?v=${mappedId}" target="_blank" class="text-blue-600 hover:underline">${linkText}</a>`;
                        }).join(', ');
                        additionalInfo += `<p style="margin: 2px; text-align: left;"><strong>Video:</strong> ${videoLinks}</p>`;
                    }
                }
            }
            return `<div class="additional-info" style="padding: 5px; text-align: left;"><h5 style="margin: 2px;">Additional Information</h5>${additionalInfo || '<p style="margin: 2px;">No additional information available.</p>'}</div>`;
        }

        async function filterData() {
            if (isLoading) return;
            showLoading();
            try {
                $('.results-table').html(`<tr class="loading-row"><td colspan="8" class="text-center py-4"><div class="custom-spinner"></div><p style="margin-top: 10px;">Loading...</p></td></tr>`);
                const response = await $.ajax({
                    url: 'https://mthfrsupport.org/wp-json/custom/v1/filter-table',
                    method: 'GET',
                    data: {
                        snps: '',
                        tags: '',
                        pathway: currentPathway,
                        result_id: resultId
                    }
                });

                $('.results-table').DataTable({
                    data: response.data,
                    rowGroup: {
                        dataSrc: 'Group',
                        startRender: function(row, group) {
                            return $('<tr/>').append('<td colspan="5" style="background-color: #187cbc; color: white; text-align: center;">' + group + '</td>');
                        }
                    },
                    initComplete: function() {
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
                    scrollY: 100,
                    scrollCollapse: true,
                    order: [[8, 'asc']],
                    columns: [
                        { title: "More", className: 'dt-control', orderable: false, data: null, defaultContent: '', searchable: false },
                        { data: "SNP Name", title: "SNP Name", width: "40%" },
                        { data: "Risk Allele", title: "Risk Allele", searchable: false },
                        { data: "Your Allele", title: "Your Allele", searchable: false },
                        { data: "Result", title: "Your Results", searchable: false },
                        { data: "SNP ID", title: "SNP ID", searchable: false },
                        { data: "Info" , title: 'Info', searchable: false },
                        { data: 'Video', title: "Video", searchable: false },
                        { data: 'Group', title: "Group" },
                        { data: 'Tags' }
                    ],
                    columnDefs: [
                        { targets: 0, width: '15%' },
                        { targets: 1, width: '30%' },
                        { targets: 2, width: '25%' },
                        { targets: 3, width: '20%' },
                        { targets: 4, width: '20%' },
                        { targets: 4, createdCell: function(td, cellData, rowData, row, col) {
                            if (cellData === '+/+') {
                                $(td).css('background-color', '#f47c80').css('color', 'black');
                            } else if (cellData === '-/-') {
                                $(td).css('background-color', '#78ac74').css('color', 'black');
                            } else if (cellData === '+/-') {
                                $(td).css('background-color', '#fffcbc').css('color', 'black');
                            }
                        }},
                        { targets: [5, 6, 7, 8, 9], visible: false },
                        { orderable: false, targets: 0 }
                    ],
                });

                $('.dt-scroll-body').each(function(){
                    new SimpleBar($(this)[0], { autoHide: false });
                });

                $('.results-table').DataTable().columns.adjust();

                $('.results-table').DataTable().on('click', 'td.dt-control', function (e) {
                    let tr = $(e.target).closest('tr');
                    let row = $('.results-table').DataTable().row(tr);
                    if (row.child.isShown()) {
                        row.child.hide();
                        tr.removeClass('shown');
                    } else {
                        row.child(format(row.data())).show();
                        const pathwayGroup = row.data()['Group'];
                        const newImageSrc = pathway_mapping[pathwayGroup] || '';
                        if(newImageSrc){
                            changeImage(newImageSrc);
                        }
                        tr.addClass('shown');
                    }
                    var table = $('.results-table').DataTable();
                    table.on('search.dt draw.dt', function() {
                        table.rows().every(function() {
                            if (this.child.isShown()) {
                                this.child.hide();
                                $(this.node()).removeClass('shown');
                            }
                        });
                    });
                });

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
                    const selectedTags = $(this).val();
                    const table = $('.results-table').DataTable();
                    table.columns().search('');
                    $.fn.dataTable.ext.search.length = 0;
                    if (selectedTags && selectedTags.length > 0) {
                        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                            const columnData = data[9];
                            return selectedTags.every(tag => columnData.includes(tag));
                        });
                        table.draw();
                    } else {
                        table.columns(9).search('').draw();
                    }
                    setTimeout(() => {
                        const firstRowData = table.row(':visible').data();
                        if (firstRowData) {
                            const groupValue = firstRowData['Group'];
                            pathway_image = pathway_mapping[groupValue] ?? pathway_mapping['Liver Detox - Phase I'];
                            changeImage(pathway_image);
                        }
                    }, 100);
                });

                $('.pathway-link').on('click', function(e) {
                    e.preventDefault();
                    const table = $('.results-table').DataTable();
                    const img = $(this).data('image');
                    const pathwayName = Object.keys(pathway_mapping).find(k => pathway_mapping[k] === img);
                    if (pathwayName) {
                        const alias = pathway_alias[pathwayName];
                        if (alias) {
                            table.columns(8).search(alias.source, true, false).draw();
                        } else {
                            const rx = '^' + $.fn.dataTable.util.escapeRegex(pathwayName) + '$';
                            table.columns(8).search(rx, true, false).draw();
                        }
                    }
                    changeImage(img);
                });

            } catch (error) {
                console.error('Error fetching data:', error);
                $('.results-table tbody').html('<tr><td colspan="8" class="text-center py-4 text-red-600">Error loading data. Please try again.</td></tr>');
            } finally {
                hideLoading();
            }
        }

        filterData();
    });
    </script>
    <?php
    return ob_get_clean();
}

function render_user_results_table() {
    ob_start(); 
    ?>
    <table id="result-files" class="display">
        <thead>
            <tr>
                <th>Report Name</th>
                <th>Based on</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <?php 
    return ob_get_clean();
}

function render_user_results_download_button($atts) {
    $atts = shortcode_atts(['result_id' => '', 'file_name' => '', 'folder_name' => ''], $atts);
    $result_id = esc_html($atts['result_id']);
    ob_start(); 
    ?>
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h3><?php echo esc_html($atts['file_name']); ?></h3>
            <p><?php echo esc_html($atts['folder_name']); ?></p>
        </div>
        <div>
            <button id="download-btn" data-result-id="<?php echo $result_id; ?>" style="color: white; background-color: var(--e-global-color-primary); padding: 10px 20px; cursor: pointer;">Download Report</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>