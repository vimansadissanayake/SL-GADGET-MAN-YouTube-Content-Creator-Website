<?php
// Database Configuration
$db_host = 'localhost';
$db_name = 'slgadgetman_db';
$db_user = 'root';
$db_pass = '';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Cleanup Duplicates - SL GADGET MAN</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; max-width: 800px; margin: 0 auto; }
        h1 { color: #FF0000; }
        .success { color: green; background: #e8f5e9; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .error { color: red; background: #ffebee; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .info { color: #1976d2; background: #e3f2fd; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .btn { 
            display: inline-block;
            background: #FF0000; 
            color: white; 
            padding: 1rem 2rem; 
            text-decoration: none; 
            border-radius: 8px; 
            margin-top: 1rem;
        }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { border: 1px solid #ddd; padding: 0.5rem; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>🔧 Cleanup Duplicate Videos</h1>";

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='info'>✓ Connected to database successfully!</div>";
    
    // Extract YouTube ID from URL
    function extractVideoId($url) {
        $url = trim($url);
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    // Get all videos
    $videos = $conn->query("SELECT * FROM videos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $total = count($videos);
    
    echo "<div class='info'>Found $total total videos in database</div>";
    
    // Track video IDs and duplicates
    $seen_ids = [];
    $duplicates = [];
    
    foreach ($videos as $video) {
        $video_id = extractVideoId($video['youtube_url']);
        
        if ($video_id) {
            if (isset($seen_ids[$video_id])) {
                // This is a duplicate
                $duplicates[] = $video;
            } else {
                // First occurrence, keep track
                $seen_ids[$video_id] = $video['id'];
            }
        }
    }
    
    $duplicate_count = count($duplicates);
    
    if ($duplicate_count > 0) {
        echo "<div class='error'>⚠️ Found $duplicate_count duplicate video(s)</div>";
        
        echo "<h2>Duplicates to be Removed:</h2>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>URL</th></tr>";
        
        foreach ($duplicates as $dup) {
            echo "<tr>";
            echo "<td>" . $dup['id'] . "</td>";
            echo "<td>" . htmlspecialchars($dup['title']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($dup['youtube_url'], 0, 50)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Delete duplicates
        $deleted = 0;
        foreach ($duplicates as $dup) {
            $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
            if ($stmt->execute([$dup['id']])) {
                $deleted++;
            }
        }
        
        echo "<div class='success'>✓ Successfully deleted $deleted duplicate video(s)!</div>";
        
    } else {
        echo "<div class='success'>✓ No duplicates found! Your database is clean.</div>";
    }
    
    // Show remaining videos
    $remaining = $conn->query("SELECT * FROM videos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $remaining_count = count($remaining);
    
    echo "<h2>Remaining Videos: $remaining_count</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Views</th><th>Duration</th></tr>";
    
    foreach ($remaining as $video) {
        echo "<tr>";
        echo "<td>" . $video['id'] . "</td>";
        echo "<td>" . htmlspecialchars($video['title']) . "</td>";
        echo "<td>" . number_format($video['views']) . "</td>";
        echo "<td>" . htmlspecialchars($video['duration']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='success'><strong>✅ Cleanup Complete!</strong></div>";
    echo "<a href='admin.php' class='btn'>Go to Admin Panel</a> ";
    echo "<a href='index.php' class='btn' style='background: #333;'>View Website</a>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>