<?php
require_once('wp-load.php');

global $wpdb;

$table_name = $wpdb->prefix . 'genetic_variants';

echo "Table: $table_name\n\n";

// Get table structure
$result = $wpdb->get_results("DESCRIBE $table_name");
echo "Columns:\n";
foreach ($result as $row) {
    echo "- {$row->Field}: {$row->Type} ";
    if ($row->Null == 'NO') echo "NOT NULL ";
    if ($row->Key == 'PRI') echo "PRIMARY KEY ";
    if ($row->Key == 'UNI') echo "UNIQUE ";
    if ($row->Default !== null) echo "DEFAULT '{$row->Default}' ";
    if ($row->Extra) echo "{$row->Extra} ";
    echo "\n";
}

echo "\nIndexes:\n";
$indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
foreach ($indexes as $index) {
    echo "- {$index->Key_name}: {$index->Column_name} ";
    if ($index->Non_unique == 0) echo "(UNIQUE) ";
    echo "\n";
}

echo "\nCreate Table Statement:\n";
$create = $wpdb->get_row("SHOW CREATE TABLE $table_name", ARRAY_N);
echo $create[1] . "\n";
?>