<?php
// Check database for result_id 3260

// Include WordPress
require_once('wp-load.php');

// Database credentials from wp-config.php
$dsn = 'mysql:unix_socket=/Users/bot/Library/Application Support/Local/run/X7kPppFz5/mysql/mysqld.sock;dbname=local';
$user = 'root';
$password = 'root';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query for the specific file path
    $file_path = '/Users/bot/Local Sites/mthfrsupport/app/public/wp-content/uploads/user_reports/upload_847/report_json_dna-data-2025-06-21_3261_1765433008.json';
    $stmt = $pdo->prepare("SELECT * FROM wpub_user_reports WHERE report_path = ?");
    $stmt->execute([$file_path]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "Found record for the file:\n";
        print_r($result);
        echo "Correct result_id: " . $result['id'] . "\n";
    } else {
        echo "No record found for the file path\n";

        // Get all reports
        $stmt2 = $pdo->prepare("SELECT * FROM wpub_user_reports");
        $stmt2->execute();
        $results = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if ($results) {
            echo "Found " . count($results) . " total records:\n";
            foreach ($results as $row) {
                echo "ID: {$row['id']}, Path: {$row['report_path']}, Type: {$row['report_type']}\n";
            }
        } else {
            echo "No records found\n";
        }
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>