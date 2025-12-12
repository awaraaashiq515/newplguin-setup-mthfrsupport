<?php
require_once('wp-load.php');

global $wpdb;

$result = $wpdb->get_row("SHOW CREATE TABLE wpub_genetic_variants", ARRAY_A);

if ($result) {
    echo "SHOW CREATE TABLE wpub_genetic_variants;\n";
    echo $result['Create Table'];
} else {
    echo "Table not found or error.";
}
?>