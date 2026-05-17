<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$name       = trim($_POST['name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$company    = trim($_POST['company'] ?? '');
$package    = trim($_POST['package'] ?? '');
$num_users  = trim($_POST['num_users'] ?? '');
$locations  = trim($_POST['locations'] ?? '');
$challenges = trim($_POST['challenges'] ?? '');
$notes      = trim($_POST['notes'] ?? '');
$ip         = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$package_labels = [
    'pay-per-use' => 'Pay-Per-Use (RM200/hr)',
    'basic'    => 'Basic Plan (RM900/month)',
    'advanced' => 'Advanced Plan (RM1,500/month)',
    'silver'   => 'Silver Plan (RM2,499/month)',
    'gold'     => 'Gold Plan (RM3,999/month)',
    'platinum' => 'Platinum Plan (RM6,499+/month)',
];
$package_label = $package_labels[$package] ?? $package;

$errors = [];
if (empty($name))  $errors[] = 'Name is required';
if (empty($email)) $errors[] = 'Email is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
if (empty($phone)) $errors[] = 'Phone is required';
if (empty($package)) $errors[] = 'Package selection is required';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

$log_dir = dirname(__DIR__) . '/submissions';
if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);

$entry = [
    'id'         => uniqid('its_'),
    'timestamp'  => date('Y-m-d H:i:s'),
    'type'       => 'IT Support Inquiry',
    'name'       => $name,
    'email'      => $email,
    'phone'      => $phone,
    'company'    => $company,
    'package'    => $package_label,
    'num_users'  => $num_users,
    'locations'  => $locations,
    'challenges' => $challenges,
    'notes'      => $notes,
    'ip'         => $ip,
    'user_agent' => $user_agent,
];

$today = date('Y-m-d');
$log_file = "$log_dir/$today.json";
$submissions = [];
if (file_exists($log_file)) {
    $submissions = json_decode(file_get_contents($log_file), true) ?? [];
}
$submissions[] = $entry;
file_put_contents($log_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

$to   = 'info@marztechnology.com.my';
$subj = "New IT Support Inquiry: $package_label - $name";
$body = "New IT Support inquiry from website.\n\n";
$body .= "Name:       $name\n";
$body .= "Email:      $email\n";
$body .= "Phone:      $phone\n";
$body .= "Company:    $company\n";
$body .= "Package:    $package_label\n";
$body .= "Users/PCs:  $num_users\n";
$body .= "Locations:  $locations\n";
$body .= "Challenges: $challenges\n";
$body .= "\n--- Notes ---\n$notes\n";

$headers  = "From: IT Support Inquiry <noreply@marztechnology.com.my>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Cc: marzcomputer@gmail.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$mail_sent = @mail($to, $subj, $body, $headers);

echo json_encode([
    'success' => true,
    'message' => 'Thank you! Our IT support team will get back to you within 24 hours.',
    'mail_sent' => $mail_sent
]);

