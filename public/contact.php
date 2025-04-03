<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Function to send email
function sendEmail($name, $email, $message) {
    $to = $_ENV['CONTACT_EMAIL'] ?? 'your-email@example.com';
    $subject = "New Contact Form Submission from Friend of a Global Economy";
    
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $emailContent = "
    <html>
    <body>
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br(htmlspecialchars($message)) . "</p>
    </body>
    </html>
    ";
    
    return mail($to, $subject, $emailContent, $headers);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Send email
        if (sendEmail($name, $email, $message)) {
            $success = "Thank you for your message! We'll get back to you soon.";
        } else {
            $error = "Sorry, there was an error sending your message. Please try again later.";
        }
    }
}

// Redirect back to the form with status
$status = isset($error) ? "error=" . urlencode($error) : "success=" . urlencode($success ?? "");
header("Location: /#contact?$status");
exit; 