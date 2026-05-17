<?php
// Marz Technology & Trading — Contact Form Handler
// Saves submission to JSON + sends email notification

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Collect and sanitize input
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate required fields
$errors = [];
if (empty($name))    $errors[] = 'Name is required';
if (empty($email))   $errors[] = 'Email is required';
if (empty($subject)) $errors[] = 'Subject is required';
if (empty($message)) $errors[] = 'Message is required';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Subject labels
$subject_labels = [
    'general'     => 'General Inquiry',
    'it-support'  => 'IT Support',
    'ai-solutions'=> 'AI Solutions',
    'products'    => 'Products',
    'partnership' => 'Partnership',
];

$subject_label = $subject_labels[$subject] ?? $subject;

// --- 1. Save to JSON log ---
$log_dir = __DIR__ . '/submissions';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$entry = [
    'id'        => uniqid('msg_'),
    'timestamp' => date('Y-m-d H:i:s'),
    'type'      => 'Contact',
    'name'      => $name,
    'email'     => $email,
    'phone'     => $phone,
    'subject'   => $subject_label,
    'message'   => $message,
    'ip'        => $ip,
    'user_agent'=> $user_agent,
];

$today = date('Y-m-d');
$log_file = "$log_dir/$today.json";

$submissions = [];
if (file_exists($log_file)) {
    $submissions = json_decode(file_get_contents($log_file), true) ?? [];
}
$submissions[] = $entry;
file_put_contents($log_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

// --- 2. Send email notification ---
$to      = 'info@marztechnology.com.my';
$subject_email = "New Contact Form: $subject_label from $name";

$email_body = "You have received a new message from your website contact form.\n\n";
$email_body .= "Name:    $name\n";
$email_body .= "Email:   $email\n";
$email_body .= "Phone:   $phone\n";
$email_body .= "Subject: $subject_label\n";
$email_body .= "\n--- Message ---\n";
$email_body .= "$message\n";

$headers  = "From: Contact Form <noreply@marztechnology.com.my>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Cc: marzcomputer@gmail.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$mail_sent = @mail($to, $subject_email, $email_body, $headers);

// Log email status
$entry['email_sent'] = $mail_sent;

// --- Response ---
if ($mail_sent) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent successfully.']);
} else {
    // Still saved, but email failed
    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been received.']);
    // Log email failure
    $err_log = "$log_dir/email_errors.log";
    file_put_contents($err_log, date('Y-m-d H:i:s') . " | Failed to send email for {$entry['id']} | From: $email\n", FILE_APPEND | LOCK_EX);
}


