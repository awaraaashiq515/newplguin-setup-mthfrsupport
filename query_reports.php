<?php
// Database credentials from wp-config.php
$dsn = 'mysql:unix_socket=/Users/bot/Library/Application Support/Local/run/X7kPppFz5/mysql/mysqld.sock;dbname=local';
$user = 'root';
$password = 'root';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to get all report_path values
    $stmt = $pdo->query("SELECT id, report_path, report_type FROM wpub_user_reports ORDER BY id DESC");

    $reports = [];
    echo "Reports:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];
        $path = $row['report_path'];
        $type = $row['report_type'];
        echo "ID: $id, Type: $type, Path: $path\n";
        $reports[] = $row;
    }

    echo "\nChecking file existence:\n";
    $missing = [];
    foreach ($reports as $report) {
        $path = $report['report_path'];
        if (!file_exists($path)) {
            $missing[] = $path;
            echo "MISSING: $path\n";
        }
    }
    if (empty($missing)) {
        echo "All files exist.\n";
    } else {
        echo "Total missing: " . count($missing) . "\n";
    }

    // Examine a few JSON files
    echo "\nExamining first 3 JSON files:\n";
    $count = 0;
    foreach ($reports as $report) {
        if ($count >= 3) break;
        if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'json') {
            echo "\nFile: $path\n";
            $content = file_get_contents($path);
            $data = json_decode($content, true);
            if ($data !== null) {
                echo "Structure: " . json_encode(array_keys($data), JSON_PRETTY_PRINT) . "\n";
                echo "Sample data: " . substr(json_encode($data, JSON_PRETTY_PRINT), 0, 500) . "...\n";
            } else {
                echo "Invalid JSON\n";
            }
            $count++;
        }
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>