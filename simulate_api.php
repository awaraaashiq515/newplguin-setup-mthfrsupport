<?php
// Simulate the custom_filter_table_api call for multiple result_ids

// Include WordPress
require_once('wp-load.php');

// Test different report_ids
$test_cases = [
    ['result_id' => 1302, 'pathway' => 'Covid'],
    ['result_id' => 1306, 'pathway' => 'Covid'],
];

foreach ($test_cases as $test) {
    echo "\n=== Testing result_id: {$test['result_id']} ===\n";

    // Create a proper WP_REST_Request object
    $request = new WP_REST_Request('GET', '/custom/v1/filter-table');
    $request->set_param('result_id', $test['result_id']);
    $request->set_param('tags', []);
    $request->set_param('snps', '');
    $request->set_param('pathway', $test['pathway']);

    // Call the API function
    try {
        $response = custom_filter_table_api($request);

        if (is_wp_error($response)) {
            echo "Error: " . $response->get_error_message() . "\n";
        } elseif ($response instanceof WP_REST_Response) {
            $response_data = $response->get_data();
            $data = $response_data['data'] ?? [];
            echo "Total variants returned: " . count($data) . "\n";
            echo "Response data keys: " . implode(', ', array_keys($response_data)) . "\n";
            echo "isSearchResult: " . ($response_data['isSearchResult'] ? 'true' : 'false') . "\n";
            echo "Full data: " . json_encode($data) . "\n";
        } else {
            echo "Unexpected response type: " . gettype($response) . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->get_message() . "\n";
    }
}
?>
