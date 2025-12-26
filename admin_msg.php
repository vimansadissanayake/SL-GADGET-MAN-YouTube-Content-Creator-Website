<?php
session_start();

// Simple authentication (CHANGE THESE!)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // CHANGE THIS!

// Database configuration
$db_host = 'localhost';
$db_name = 'slgadgetman_db';
$db_user = 'root';
$db_pass = '';

// Login check
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        if ($_POST['username'] == ADMIN_USERNAME && $_POST['password'] == ADMIN_PASSWORD) {
            $_SESSION['logged_in'] = true;
            header('Location: admin_msg.php');
            exit;
        } else {
            $login_error = "Invalid username or password!";
        }
    }
    
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - SL GADGET MAN</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-box {
                background: white;
                padding: 3rem;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                width: 400px;
            }
            h2 { color: #333; margin-bottom: 2rem; text-align: center; }
            .form-group { margin-bottom: 1.5rem; }
            label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: bold; }
            input {
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #ddd;
                border-radius: 8px;
                font-size: 1rem;
            }
            input:focus { outline: none; border-color: #667eea; }
            button {
                width: 100%;
                padding: 1rem;
                background: #FF0000;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: bold;
                cursor: pointer;
            }
            button:hover { background: #CC0000; }
            .error {
                background: #ffebee;
                color: #c62828;
                padding: 0.75rem;
                border-radius: 8px;
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔒 Admin Login</h2>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_msg.php');
    exit;
}

// Connect to database
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// YouTube ID extraction
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

// Handle video upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_video'])) {
    $title = trim($_POST['title']);
    $youtube_url = trim($_POST['youtube_url']);
    $views = intval($_POST['views']);
    $duration = trim($_POST['duration']);
    
    $video_id = getYouTubeId($youtube_url);

    if ($video_id) {
        $check_stmt = $conn->prepare("SELECT id, youtube_url FROM videos");
        $check_stmt->execute();
        $existing_videos = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $duplicate_found = false;
        foreach ($existing_videos as $existing) {
            $existing_id = getYouTubeId($existing['youtube_url']);
            if ($existing_id === $video_id) {
                $duplicate_found = true;
                break;
            }
        }
        
        if ($duplicate_found) {
            $error = "This video has already been added!";
        } else {
            $thumbnail = "https://img.youtube.com/vi/$video_id/maxresdefault.jpg";
            $normalized_url = "https://www.youtube.com/watch?v=" . $video_id;
            
            try {
                $stmt = $conn->prepare("INSERT INTO videos (title, youtube_url, thumbnail, views, duration) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $normalized_url, $thumbnail, $views, $duration]);
                header('Location: admin_msg.php?success=video_added&tab=videos');
                exit;
            } catch(PDOException $e) {
                $error = "Error adding video: " . $e->getMessage();
            }
        }
    } else {
        $error = "Invalid YouTube URL! Please use a valid YouTube video link.";
    }
}

// Handle video delete - FIXED REDIRECT
if (isset($_GET['delete_video'])) {
    $id = intval($_GET['delete_video']);
    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin_msg.php?success=video_deleted&tab=videos');
    exit;
}

// Handle message delete - FIXED REDIRECT
if (isset($_GET['delete_message'])) {
    $id = intval($_GET['delete_message']);
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin_msg.php?success=message_deleted&tab=messages');
    exit;
}

// Get all unique videos
$videos_raw = $conn->query("SELECT * FROM videos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$videos = [];
$seen_ids = [];

foreach ($videos_raw as $video) {
    $vid_id = getYouTubeId($video['youtube_url']);
    if ($vid_id && !in_array($vid_id, $seen_ids)) {
        $videos[] = $video;
        $seen_ids[] = $vid_id;
    }
}

// Get all messages
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Determine active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'videos';

// Success messages
if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case 'video_added':
            $success = "Video added successfully!";
            break;
        case 'video_deleted':
            $success = "Video deleted successfully!";
            break;
        case 'message_deleted':
            $success = "Message deleted successfully!";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - SL GADGET MAN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #222;
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 1.5rem; }
        .header-buttons { display: flex; gap: 1rem; }
        .btn-header {
            background: #FF0000;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-home {
            background: #2196F3;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        /* Tabs */
        .tabs {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            gap: 1rem;
        }
        .tab-btn {
            flex: 1;
            padding: 1rem 2rem;
            background: #f5f5f5;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #666;
        }
        .tab-btn.active {
            background: #FF0000;
            color: white;
        }
        .tab-btn:hover:not(.active) {
            background: #e0e0e0;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .card h2 {
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 3px solid #FF0000;
            padding-bottom: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #FF0000;
        }
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #999;
        }
        .btn {
            background: #FF0000;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
        }
        .btn:hover { background: #CC0000; }
        .success {
            background: #4CAF50;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .error {
            background: #f44336;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        /* Videos */
        .video-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        .video-item {
            border: 2px solid #ddd;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .video-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .video-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .video-item-info {
            padding: 1rem;
        }
        .video-item h3 {
            margin-bottom: 0.5rem;
            color: #333;
            font-size: 1rem;
        }
        .video-item p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .video-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        /* Messages */
        .message-list {
            display: grid;
            gap: 1.5rem;
        }
        .message-item {
            border: 2px solid #ddd;
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        .message-item:hover {
            border-color: #FF0000;
            box-shadow: 0 5px 15px rgba(255, 0, 0, 0.1);
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        .message-info h3 {
            color: #333;
            margin-bottom: 0.25rem;
        }
        .message-info p {
            color: #666;
            font-size: 0.9rem;
        }
        .message-date {
            background: #f5f5f5;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #666;
        }
        .message-body {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        .message-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Buttons */
        .btn-delete {
            background: #f44336;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view {
            background: #2196F3;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-reply {
            background: #4CAF50;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-reply:hover {
            background: #45a049;
        }
        
        /* Reply Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
            overflow-y: auto;
            padding: 2rem 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            background-color: white;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        .modal-header h3 {
            color: #333;
            font-size: 1.5rem;
        }
        .close-btn {
            background: #f44336;
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .close-btn:hover {
            background: #d32f2f;
        }
        .reply-form-group {
            margin-bottom: 1.5rem;
        }
        .reply-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }
        .reply-form-group input,
        .reply-form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
        }
        .reply-form-group input:focus,
        .reply-form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .reply-form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .reply-form-group input[readonly] {
            background: #f5f5f5;
            color: #666;
        }
        .btn-send {
            background: #4CAF50;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }
        .btn-send:hover {
            background: #45a049;
        }
        
        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
        }
        .stat-card h3 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .stat-card p { font-size: 1rem; opacity: 0.9; }
        
        .empty {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚙️ Admin Panel - SL GADGET MAN</h1>
        <div class="header-buttons">
            <a href="index.php" class="btn-header btn-home"><i class="fas fa-home"></i> View Website</a>
            <a href="?logout=1" class="btn-header"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Tabs -->
        <div class="tabs">
            <a href="?tab=videos" class="tab-btn <?php echo $active_tab == 'videos' ? 'active' : ''; ?>">
                <i class="fas fa-video"></i> Manage Videos (<?php echo count($videos); ?>)
            </a>
            <a href="?tab=messages" class="tab-btn <?php echo $active_tab == 'messages' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Messages (<?php echo count($messages); ?>)
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- VIDEOS TAB -->
        <div class="tab-content <?php echo $active_tab == 'videos' ? 'active' : ''; ?>">
            <!-- Video Statistics -->
            <div class="stats">
                <div class="stat-card">
                    <h3><?php echo count($videos); ?></h3>
                    <p>Total Videos</p>
                </div>
                <div class="stat-card">
                    <h3>
                        <?php 
                        $total_views = array_sum(array_column($videos, 'views'));
                        echo number_format($total_views);
                        ?>
                    </h3>
                    <p>Total Views</p>
                </div>
                <div class="stat-card">
                    <h3>Active</h3>
                    <p>Status</p>
                </div>
            </div>

            <!-- Add Video Form -->
            <div class="card">
                <h2>➕ Add New Video</h2>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Video Title</label>
                        <input type="text" name="title" placeholder="Enter video title" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fab fa-youtube"></i> YouTube URL</label>
                        <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required>
                        <small>Paste the full YouTube video URL</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-eye"></i> Views</label>
                        <input type="number" name="views" placeholder="15000" required>
                        <small>Enter the number of views</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Duration</label>
                        <input type="text" name="duration" placeholder="12:45" pattern="[0-9]{1,2}:[0-9]{2}" required>
                        <small>Format: MM:SS (e.g., 12:45)</small>
                    </div>

                    <button type="submit" name="add_video" class="btn">
                        <i class="fas fa-plus"></i> Add Video
                    </button>
                </form>
            </div>

            <!-- Video List -->
            <div class="card">
                <h2>📺 All Videos (<?php echo count($videos); ?>)</h2>
                
                <?php if (empty($videos)): ?>
                    <p class="empty">No videos uploaded yet. Add your first video above!</p>
                <?php else: ?>
                    <div class="video-list">
                        <?php foreach ($videos as $video): ?>
                            <div class="video-item">
                                <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
                                <div class="video-item-info">
                                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                                    <p><i class="fas fa-eye"></i> <?php echo number_format($video['views']); ?> views</p>
                                    <p><i class="fas fa-clock"></i> <?php echo htmlspecialchars($video['duration']); ?></p>
                                    <div class="video-actions">
                                        <a href="<?php echo htmlspecialchars($video['youtube_url']); ?>" target="_blank" class="btn-view">
                                            <i class="fas fa-play"></i> Watch
                                        </a>
                                        <a href="?delete_video=<?php echo $video['id']; ?>&tab=videos" class="btn-delete" onclick="return confirm('Delete this video?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- MESSAGES TAB -->
        <div class="tab-content <?php echo $active_tab == 'messages' ? 'active' : ''; ?>">
            <!-- Message Statistics -->
            <div class="stats">
                <div class="stat-card">
                    <h3><?php echo count($messages); ?></h3>
                    <p>Total Messages</p>
                </div>
                <div class="stat-card">
                    <h3>
                        <?php 
                        $today = date('Y-m-d');
                        $today_count = 0;
                        foreach ($messages as $msg) {
                            if (date('Y-m-d', strtotime($msg['created_at'])) == $today) {
                                $today_count++;
                            }
                        }
                        echo $today_count;
                        ?>
                    </h3>
                    <p>Today</p>
                </div>
                <div class="stat-card">
                    <h3>Active</h3>
                    <p>Status</p>
                </div>
            </div>

            <!-- Messages List -->
            <div class="card">
                <h2>📬 All Messages (<?php echo count($messages); ?>)</h2>
                
                <?php if (empty($messages)): ?>
                    <div class="empty">
                        <i class="fas fa-inbox" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <p>No messages yet. Messages from your contact form will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="message-list">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-item">
                                <div class="message-header">
                                    <div class="message-info">
                                        <h3><i class="fas fa-user"></i> <?php echo htmlspecialchars($msg['name']); ?></h3>
                                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($msg['email']); ?></p>
                                    </div>
                                    <div class="message-date">
                                        <i class="fas fa-clock"></i> 
                                        <?php echo date('M d, Y - g:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="message-body">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                
                                <div class="message-actions">
                                    <button class="btn-reply" onclick="openReplyModal('<?php echo htmlspecialchars($msg['email']); ?>', '<?php echo htmlspecialchars($msg['name']); ?>')">
                                        <i class="fas fa-reply"></i> Reply via Email
                                    </button>
                                    <a href="?delete_message=<?php echo $msg['id']; ?>&tab=messages" class="btn-delete" onclick="return confirm('Delete this message?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-reply"></i> Reply to Message</h3>
                <button class="close-btn" onclick="closeReplyModal()">×</button>
            </div>
            <form id="replyForm">
                <div class="reply-form-group">
                    <label><i class="fas fa-user"></i> To:</label>
                    <input type="text" id="replyName" readonly>
                </div>
                <div class="reply-form-group">
                    <label><i class="fas fa-envelope"></i> Email:</label>
                    <input type="email" id="replyEmail" readonly>
                </div>
                <div class="reply-form-group">
                    <label><i class="fas fa-heading"></i> Subject:</label>
                    <input type="text" id="replySubject" value="Re: Your message to SL Gadget Man" required>
                </div>
                <div class="reply-form-group">
                    <label><i class="fas fa-comment"></i> Your Reply:</label>
                    <textarea id="replyMessage" placeholder="Type your reply here..." required></textarea>
                </div>
                <button type="button" class="btn-send" onclick="openGmail()" style="background: #EA4335;">
                    <i class="fab fa-google"></i> Open in Gmail
                </button>
                <button type="button" class="btn-send" onclick="copyEmailDetails()" style="background: #2196F3; margin-top: 0.5rem;">
                    <i class="fas fa-copy"></i> Copy to Clipboard
                </button>
            </form>
        </div>
    </div>

    <script>
        // Reply Modal Functions
        function openReplyModal(email, name) {
            document.getElementById('replyModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.getElementById('replyName').value = name;
            document.getElementById('replyEmail').value = email;
            document.getElementById('replyMessage').value = 
                `Hi ${name},\n\nThank you for contacting SL Gadget Man!\n\n\n\nBest regards,\nLasitha Dissanayake\nSL Gadget Man\nslgadgetman1@gmail.com`;
        }

        function closeReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('replyForm').reset();
        }

        document.getElementById('replyModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeReplyModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('replyModal');
                if (modal.style.display === 'block') {
                    closeReplyModal();
                }
            }
        });

        function openGmail() {
            const email = document.getElementById('replyEmail').value;
            const subject = document.getElementById('replySubject').value;
            const message = document.getElementById('replyMessage').value;
            
            const gmailUrl = `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(email)}&su=${encodeURIComponent(subject)}&body=${encodeURIComponent(message)}`;
            
            window.open(gmailUrl, '_blank');
            
            setTimeout(() => {
                closeReplyModal();
            }, 500);
        }

        function copyEmailDetails() {
            const email = document.getElementById('replyEmail').value;
            const subject = document.getElementById('replySubject').value;
            const message = document.getElementById('replyMessage').value;
            
            const emailDetails = `To: ${email}\nSubject: ${subject}\n\nMessage:\n${message}`;
            
            navigator.clipboard.writeText(emailDetails).then(function() {
                alert('✅ Email details copied to clipboard!\n\nNow:\n1. Open your email (Gmail/Outlook)\n2. Paste (Ctrl+V or Cmd+V)\n3. Send your reply');
                closeReplyModal();
            }, function(err) {
                const textArea = document.createElement('textarea');
                textArea.value = emailDetails;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('✅ Copied to clipboard!');
                    closeReplyModal();
                } catch (err) {
                    prompt('Copy this text manually:', emailDetails);
                }
                document.body.removeChild(textArea);
            });
        }
    </script>
</body>
</html>