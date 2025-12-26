<?php
// Database Configuration
$db_host = 'localhost';
$db_name = 'slgadgetman_db';
$db_user = 'root';
$db_pass = '';

echo "<h1>Setting up SL Gadget Man Database...</h1>";
echo "<style>body { font-family: Arial; padding: 2rem; } .success { color: green; } .error { color: red; } .warning { color: orange; }</style>";

try {
    // Connect without database first
    $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $conn->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    echo "<p class='success'>✓ Database '$db_name' created successfully!</p>";
    
    // Connect to the new database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create videos table with UNIQUE constraint
    $sql = "CREATE TABLE IF NOT EXISTS videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        youtube_url VARCHAR(500) NOT NULL,
        thumbnail VARCHAR(500) NOT NULL,
        views INT DEFAULT 0,
        duration VARCHAR(20) DEFAULT '0:00',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_url (youtube_url)
    )";
    
    $conn->exec($sql);
    echo "<p class='success'>✓ Table 'videos' created successfully!</p>";
    
    // Create contact_messages table
    $sql = "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "<p class='success'>✓ Table 'contact_messages' created successfully!</p>";
    
    // YouTube ID extraction function
    function getYouTubeId($url) {
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
        return false;
    }
    
    // Sample videos array (FIXED - added missing comma)
    $sample_videos = [
        [
            'title' => 'Starlink එක්ක නැජීෂෙන්න ආවර ලංකාවම හොයන 5G යමක් මෙන්න 🥵 AW1000 5G Wifi Router Review ❤️🔥',
            'url' => 'https://www.youtube.com/watch?v=example1',
            'views' => 23000,
            'duration' => '32:57'
        ],
        [
            'title' => 'ලිමිට් නැතිව අසීමිතව ඉන්ටර්නෙට් යන්න Starlink එක ඇවුවා!! 🔥 | Starlink Unboxing and Full Review LK',
            'url' => 'https://www.youtube.com/watch?v=example2',
            'views' => 48000,
            'duration' => '38:48'
        ],
        [
            'title' => 'Dialog TV Signal Setup Using an App',
            'url' => 'https://www.youtube.com/watch?v=example3',
            'views' => 482000,
            'duration' => '9:35'
        ]
    ];
    
    $added_count = 0;
    $skipped_count = 0;
    
    foreach ($sample_videos as $video) {
        $video_id = getYouTubeId($video['url']);
        
        if ($video_id) {
            // Check if video already exists
            $check = $conn->prepare("SELECT id FROM videos WHERE youtube_url = ?");
            $check->execute([$video['url']]);
            
            if ($check->rowCount() == 0) {
                $thumbnail = "https://img.youtube.com/vi/$video_id/maxresdefault.jpg";
                
                try {
                    $stmt = $conn->prepare("INSERT INTO videos (title, youtube_url, thumbnail, views, duration) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$video['title'], $video['url'], $thumbnail, $video['views'], $video['duration']]);
                    $added_count++;
                } catch(PDOException $e) {
                    echo "<p class='warning'>⚠ Skipped duplicate: " . htmlspecialchars($video['title']) . "</p>";
                    $skipped_count++;
                }
            } else {
                $skipped_count++;
            }
        }
    }
    
    if ($added_count > 0) {
        echo "<p class='success'>✓ Added $added_count sample video(s)!</p>";
    }
    if ($skipped_count > 0) {
        echo "<p class='warning'>⚠ Skipped $skipped_count duplicate video(s)</p>";
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Setup Complete!</h2>";
    echo "<p><strong>Admin Login Details:</strong></p>";
    echo "<ul>";
    echo "<li>Username: <code>admin</code></li>";
    echo "<li>Password: <code>admin123</code></li>";
    echo "</ul>";
    echo "<p><strong>⚠️ IMPORTANT: Change these credentials in admin.php line 5-6!</strong></p>";
    echo "<p><a href='admin.php' style='background: #FF0000; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 1rem;'>Go to Admin Panel</a></p>";
    echo "<p><a href='index.php' style='background: #333; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 0.5rem;'>View Website</a></p>";
    
} catch(PDOException $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>