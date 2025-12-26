<?php
// Database config from your files
$db_host = 'localhost'; $db_name = 'slgadgetman_db'; $db_user = 'root'; $db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $videos = $conn->query("SELECT id, youtube_url FROM videos")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($videos as $v) {
        // Logic to extract ID correctly from any URL
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $v['youtube_url'], $match);
        if (isset($match[1])) {
            $new_thumb = "https://img.youtube.com/" . $match[1] . "/maxresdefault.jpg";
            $update = $conn->prepare("UPDATE videos SET thumbnail = ? WHERE id = ?");
            $update->execute([$new_thumb, $v['id']]);
        }
    }
    echo "All thumbnails fixed!";
} catch(PDOException $e) { echo $e->getMessage(); }
?>