<?php
// Test the custom_filter_table_api endpoint

// Include WordPress
require_once('wp-load.php');

// Create a proper REST request
$request = new WP_REST_Request('GET', '/custom/v1/filter-table');
$request->set_param('result_id', '1303');
// No pathway for Covid reports

echo "Testing custom_filter_table_api with result_id=1303\n\n";

// Call the function directly
$result = custom_filter_table_api($request);

echo "API Response:\n";
if (is_wp_error($result)) {
    echo "WP_Error: " . $result->get_error_message() . "\n";
} else {
    $response_data = $result->get_data();
    echo "Status: " . $result->get_status() . "\n";
    echo "Data count: " . count($response_data['data']) . "\n";
    if (count($response_data['data']) > 0) {
        echo "First item: " . json_encode($response_data['data'][0], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "No data returned\n";
    }
}
?>