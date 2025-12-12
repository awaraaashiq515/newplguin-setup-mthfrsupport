<?php
require_once('wp-load.php');

global $wpdb;

$result = $wpdb->get_results("SHOW INDEXES FROM wpub_genetic_variants");

echo "SHOW INDEXES FROM wpub_genetic_variants;\n";
foreach ($result as $row) {
    echo "Table: {$row->Table}, Key_name: {$row->Key_name}, Column_name: {$row->Column_name}, Non_unique: {$row->Non_unique}\n";
}
?>