<?php
// Database configuration code eka
$db_host = 'localhost';
$db_name = 'slgadgetman_db';
$db_user = 'root';
$db_pass = '';

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

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all videos and filter duplicates
    $stmt = $conn->query("SELECT * FROM videos ORDER BY created_at DESC");
    $all_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // video id eka anuwa duplicates check krl remove krnw
    $videos = [];
    $seen_ids = [];
    
    foreach ($all_videos as $video) {
        $vid_id = getYouTubeId($video['youtube_url']);
        if ($vid_id && !in_array($vid_id, $seen_ids)) {
            // Format views
            if ($video['views'] >= 1000000) {
                $video['views_formatted'] = round($video['views'] / 1000000, 1) . 'M views';
            } elseif ($video['views'] >= 1000) {
                $video['views_formatted'] = round($video['views'] / 1000, 1) . 'K views';
            } else {
                $video['views_formatted'] = $video['views'] . ' views';
            }
            
            $videos[] = $video;
            $seen_ids[] = $vid_id;
            
            // home page eke pennanna puluwn videos 6 also can change
            if (count($videos) >= 8) {
                break;
            }
        }
    }
    
    // Get total unique video count
    $total_videos = count($videos);
    $total_views = array_sum(array_column($videos, 'views'));
    
} catch(PDOException $e) {
    $videos = [];
    $total_videos = 0;
    $total_views = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SL Gadget Man - Tech Reviews</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* body eke font */
            line-height: 1.6;
            color: #333;
        }

        .navbar { /* vavigations wla bg shadows & etc */
            background: black;
            box-shadow: 0 2px 10px rgba(201, 199, 199, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 2px solid #ffffffff;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }

        .logo { /* logo & font eka athara dura*/
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

    
        .logo-icon img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
        }

        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 900;
            color: #cfcfcfff;
        }

        .logo-text p {
            font-size: 0.7rem;
            color: #cfcfcfff;
            font-weight: bold;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-menu a {
            text-decoration: none;
            color: #cfcfcfff;
            font-weight: 660;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: #FF0000;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #FF0000;
            cursor: pointer;
        }

        .hero {
            margin-top: 80px;
            position: relative;
            width: 100%;
            height: 600px;
            overflow: hidden;
        }

        .hero-banner {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-content {
            max-width: 1200px;
            margin: -200px auto 0;
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            padding: 2rem 0;
        }

        .btn {
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-primary {
            background: #FF0000;
            color: white;
            box-shadow: 0 4px 15px rgba(255, 0, 0, 0.3);
        }

        .btn-primary:hover {
            background: #CC0000;
            transform: scale(1.05);
        }

        .btn-secondary {
            background: white;
            color: #FF0000;
            border: 2px solid #ffffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background: #000000ff;
            transform: scale(1.05);
        }

        .about {
            padding: 5rem 2rem;
            background: black;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            color: #ddddddff;
        }

        .section-divider {
            width: 100px;
            height: 4px;
            background: #FF0000;
            margin: 0 auto 3rem;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .about-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 3px solid #535353ff;
        }

        .about-text p {
            font-size: 1.1rem;
            color: #d8d4d4ff;
            margin-bottom: 1.0rem;
            line-height: 1.8;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: #b1aeaeff;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            border: 2px solid #ffe0e0;
            transition: all 0.3s;
        }

        .stat-card:hover {
            border-color: #f78c8cff;
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            color: #be2626ff;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            color: #FF0000;
        }

        .stat-label {
            color: #666;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .videos {
            padding: 5rem 2rem;
            background: #000000ff;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .video-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 2px solid #f0f0f0;
        }

        .video-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(255, 0, 0, 0.2);
            border-color: #FF0000;
        }

        .video-thumbnail {
            position: relative;
            overflow: hidden;
            height: 200px;
        }

        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .video-card:hover .video-thumbnail img {
            transform: scale(1.1);
        }

        .video-duration {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #FF0000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .video-card:hover .play-overlay {
            opacity: 1;
        }

        .play-overlay i {
            color: white;
            font-size: 1.5rem;
            margin-left: 3px;
        }

        .video-info {
            padding: 1.5rem;
        }

        .video-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .video-views {
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .contact {
            padding: 5rem 2rem;
            background: black;
        }

        .contact-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 3rem 0;
        }

        .social-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .social-icon:hover {
            transform: scale(1.1) translateY(-5px);
        }

        .youtube { background: #FF0000; }
        .facebook { background: #1877F2; }
        .twitter { background: #1DA1F2; }
        .instagram { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }

        .contact-form {
            background: #000000ff;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #706d6dff;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF0000;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: #FF0000;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background: #CC0000;
            transform: scale(1.02);
        }

        .footer {
            background: #000000ff;
            color: #999;
            padding: 3rem 2rem;
            text-align: center;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .footer-logo i {
            font-size: 2rem;
            color: #FF0000;
        }

        .footer-logo h3 {
            font-size: 1.5rem;
            font-weight: 900;
            color: white;
        }

        .footer p {
            color: #8a8787ff;
            margin-top: 0.5rem;
        }

        .success-message {
            display: none;
            background: #4CAF50;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success-message.show {
            display: block;
        }

        @media (max-width: 768px) {
            .logo-icon img {
                width: 45px;
                height: 45px;
            }

            .logo-text h1 {
                font-size: 1.2rem;
            }

            .logo-text p {
                font-size: 0.6rem;
            }

            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            }

            .nav-menu.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .about-grid {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .hero {
                height: 400px;
            }

            .hero-content {
                margin-top: -100px;
            }

            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-icon">
                    <img src="logo.png" alt="SL Gadget Man Logo">
                </div>
                <div class="logo-text">
                    <h1>SL GADGET MAN</h1>
                    <p>TECH REVIEWS</p>
                </div>
            </div>
            <ul class="nav-menu" id="navMenu">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#videos">Videos</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <section class="hero" id="home">
        <img src="banner.jpg" alt="SL Gadget Man Banner" class="hero-banner">
    </section>

    <div class="hero-content">
        <div class="cta-buttons">
            <a href="https://www.youtube.com/@slgadgetman" target="_blank" class="btn btn-primary">
                <i class="fab fa-youtube"></i>
                Subscribe Now
            </a>
            <a href="#videos" class="btn btn-secondary">
                Watch Videos
            </a>
        </div>
    </div>

    <section class="about" id="about">
        <div class="container">
            <h2 class="section-title">About the Channel</h2>
            <div class="section-divider"></div>
            
            <div class="about-grid">
                <div class="about-image">
                    <img src="about.jpeg" alt="me">
                </div>
                <div class="about-text">
                    <p>Hi, I'm Lasitha Dissanayake, also known as SL Gadget Man. I create content about the latest smartphones, gadgets, apps, and smart tech tips to make everyday life easier.</p>
                    <p>
                    <b>What I cover:</b><br>
                        Smartphone reviews & unboxings 📱<br>
                        Cool gadgets & accessories 🛠️<br>
                        Useful apps & tech hacks 💡<br>
                        Honest opinions & buying guides 🎥<br>
                    </p><p>
                   <b>Collaborations:</b><br>
                        Brands and creators can collaborate with me to reach a growing community of tech enthusiasts.<br>
                        <b>📩 Email:</b> slgadgetman1@gmail.com</p><br>
                    
                    <div class="stats">
                        <div class="stat-card">
                            <i class="fas fa-star"></i>
                            <div class="stat-number">137K+</div>
                            <div class="stat-label">Subscribers</div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-play"></i>
                            <div class="stat-number"><?php echo $total_videos > 0 ? $total_videos . '+' : '500+'; ?></div>
                            <div class="stat-label">Videos</div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-bolt"></i>
                            <div class="stat-number"><?php echo $total_views > 0 ? number_format($total_views/1000000, 1) . 'M+' : '5M+'; ?></div>
                            <div class="stat-label">Views</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="videos" id="videos">
        <div class="container">
            <h2 class="section-title">Latest Videos</h2>
            <div class="section-divider"></div>
            
            <div class="video-grid">
                <?php if (!empty($videos)): ?>
                    <?php foreach ($videos as $video): ?>
                        <a href="<?php echo htmlspecialchars($video['youtube_url']); ?>" target="_blank" style="text-decoration: none;">
                            <div class="video-card">
                                <div class="video-thumbnail">
                                    <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
                                    <div class="video-duration"><?php echo htmlspecialchars($video['duration']); ?></div>
                                    <div class="play-overlay">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                                <div class="video-info">
                                    <h3 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                                    <p class="video-views"><?php echo $video['views_formatted']; ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #999;">No videos uploaded yet. Check the admin panel to add videos!</p>
                <?php endif; ?>
            </div>

            <div style="text-align: center;">
                <a href="https://www.youtube.com/@slgadgetman" target="_blank" class="btn btn-primary">
                    View All Videos on YouTube
                </a>
            </div>
        </div>
    </section>

    <section class="contact" id="contact">
        <div class="contact-content">
            <h2 class="section-title">Get in Touch</h2>
            <div class="section-divider"></div>
            <p style="text-align: center; font-size: 1.1rem; color: #d8d4d4ff; margin-bottom: 2rem;">
                Connect with us on social media or send a message for collaborations and business inquiries.
            </p>

            <div class="social-links">
                <a href="https://www.youtube.com/@slgadgetman" target="_blank" class="social-icon youtube">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="https://facebook.com/sl_gadget_man" target="_blank" class="social-icon facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/sl_gadget_man" target="_blank" class="social-icon twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.instagram.com/slgadgetman1?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" class="social-icon instagram">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>

            <div class="contact-form">
                <div class="success-message" id="successMessage">
                    Thank you! Your message has been sent successfully.
                </div>
                <form id="contactForm" action="contact.php" method="POST">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Your Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Your Email" required>
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="Your Message" required></textarea>
                    </div>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-logo">
            <i class="fab fa-youtube"></i>
            <h3>SL GADGET MAN</h3>
        </div>
        <p>&copy; 2024 SL GADGET MAN. All rights reserved.</p>
        <p>Technology Reviews • Gadget News • Unboxing Videos • Comparisons</p>
    </footer>

    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navMenu = document.getElementById('navMenu');

        mobileMenuBtn.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    navMenu.classList.remove('active');
                }
            });
        });

        const contactForm = document.getElementById('contactForm');
        const successMessage = document.getElementById('successMessage');

        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(contactForm);
            
            try {
                const response = await fetch('contact.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    successMessage.classList.add('show');
                    contactForm.reset();
                    
                    setTimeout(() => {
                        successMessage.classList.remove('show');
                    }, 5000);
                }
            } catch (error) {
                alert('There was an error sending your message. Please try again.');
            }
        });
    </script>
</body>
</html>