<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Collect & sanitize
$name           = trim($_POST['name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$company        = trim($_POST['company'] ?? '');
$package        = trim($_POST['package'] ?? '');
$quantity       = trim($_POST['quantity'] ?? '');
$duration       = trim($_POST['duration'] ?? '');
$start_date     = trim($_POST['start_date'] ?? '');
$purpose        = trim($_POST['purpose'] ?? '');
$delivery_addr  = trim($_POST['delivery_addr'] ?? '');
$notes          = trim($_POST['notes'] ?? '');

// Package labels
$package_labels = [
    'basic'   => 'Basic Package',
    'standard'=> 'Standard Package',
    'premium' => 'Premium Package',
    'custom'  => 'Custom Package',
];
$package_label = $package_labels[$package] ?? $package;

$duration_labels = [
    'daily'   => 'Daily',
    'weekly'  => 'Weekly',
    'monthly' => 'Monthly',
    'custom'  => 'Custom',
];
$duration_label = $duration_labels[$duration] ?? $duration;

// Validate
$errors = [];
if (empty($name))       $errors[] = 'Name is required';
if (empty($email))      $errors[] = 'Email is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
if (empty($phone))      $errors[] = 'Phone is required';
if (empty($package))    $errors[] = 'Package selection is required';
if (empty($quantity))   $errors[] = 'Quantity is required';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Save to JSON
$log_dir = dirname(__DIR__) . '/submissions';
if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$entry = [
    'id'        => uniqid('rent_'),
    'timestamp' => date('Y-m-d H:i:s'),
    'type'      => 'Rental Inquiry',
    'name'      => $name,
    'email'     => $email,
    'phone'     => $phone,
    'company'   => $company,
    'package'   => $package_label,
    'quantity'  => $quantity,
    'duration'  => $duration_label,
    'start_date'=> $start_date,
    'purpose'   => $purpose,
    'delivery_addr' => $delivery_addr,
    'notes'     => $notes,
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

// Send email
$to    = 'info@marztechnology.com.my';
$subj  = "New Rental Inquiry: $package_label x$quantity - $name";
$body  = "You have received a new rental inquiry from the website.\n\n";
$body .= "Name:        $name\n";
$body .= "Email:       $email\n";
$body .= "Phone:       $phone\n";
$body .= "Company:     $company\n";
$body .= "Package:     $package_label\n";
$body .= "Quantity:    $quantity\n";
$body .= "Duration:    $duration_label\n";
$body .= "Start Date:  $start_date\n";
$body .= "Purpose:     $purpose\n";
$body .= "Delivery:    $delivery_addr\n";
$body .= "\n--- Notes ---\n$notes\n";

$headers  = "From: Rental Inquiry <noreply@marztechnology.com.my>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Cc: marzcomputer@gmail.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$mail_sent = @mail($to, $subj, $body, $headers);
$entry['email_sent'] = $mail_sent;

if (!$mail_sent) {
    $err_log = "$log_dir/email_errors.log";
    file_put_contents($err_log, date('Y-m-d H:i:s') . " | Rental email failed for {$entry['id']}\n", FILE_APPEND | LOCK_EX);
}

echo json_encode(['success' => true, 'message' => 'Thank you! Your rental inquiry has been received. We will contact you within 24 hours.']);



