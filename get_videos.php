<?php
header('Content-Type: application/json');

// Database configuration
$db_host = 'localhost';
$db_name = 'slgadgetman_db';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all videos
    $stmt = $conn->query("SELECT * FROM videos ORDER BY created_at DESC");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format views
    foreach ($videos as &$video) {
        if ($video['views'] >= 1000000) {
            $video['views_formatted'] = round($video['views'] / 1000000, 1) . 'M views';
        } elseif ($video['views'] >= 1000) {
            $video['views_formatted'] = round($video['views'] / 1000, 1) . 'K views';
        } else {
            $video['views_formatted'] = $video['views'] . ' views';
        }
    }
    
    echo json_encode([
        'success' => true,
        'videos' => $videos
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>