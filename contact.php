<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for CORS and JSON response
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize and validate input data
    $name = isset($_POST['name']) ? htmlspecialchars(strip_tags(trim($_POST['name']))) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $message = isset($_POST['message']) ? htmlspecialchars(strip_tags(trim($_POST['message']))) : '';
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If there are validation errors
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors
        ]);
        exit;
    }
    
    // OPTION 1: Save to database (RECOMMENDED FOR LOCALHOST)
    try {
        // Database configuration
        $db_host = 'localhost';
        $db_name = 'slgadgetman_db';
        $db_user = 'root';
        $db_pass = '';
        
        // Create connection
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message, created_at) VALUES (:name, :email, :message, NOW())");
        
        // Bind parameters
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':message', $message);
        
        // Execute
        $stmt->execute();
        
        $db_saved = true;
        
    } catch(PDOException $e) {
        $db_saved = false;
        $db_error = $e->getMessage();
    }
    
    // OPTION 2: Save to text file (BACKUP METHOD)
    $file = 'contact_messages.txt';
    $current_time = date('Y-m-d H:i:s');
    $file_content = "\n\n=== New Message ===\n";
    $file_content .= "Date: $current_time\n";
    $file_content .= "Name: $name\n";
    $file_content .= "Email: $email\n";
    $file_content .= "Message: $message\n";
    $file_content .= "==================\n";
    
    file_put_contents($file, $file_content, FILE_APPEND);
    
    // OPTION 3: Send email notification to admin
    // This works on most live servers, may not work on localhost
    $to = "vimansacdissanayaka@gmail.com"; // Your email
    $subject = "New Contact Form Message - SL Gadget Man";
    $email_body = "You have received a new message from your website contact form.\n\n";
    $email_body .= "Name: $name\n";
    $email_body .= "Email: $email\n";
    $email_body .= "Date: $current_time\n\n";
    $email_body .= "Message:\n$message\n\n";
    $email_body .= "---\n";
    $email_body .= "Reply to this person at: $email\n";
    
    $headers = "From: noreply@slgadgetman.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Try to send email (will work on live server)
    @mail($to, $subject, $email_body, $headers);
    
    // Send success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.',
        'saved_to_database' => isset($db_saved) ? $db_saved : false
    ]);
    
} else {
    // If not POST request
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>