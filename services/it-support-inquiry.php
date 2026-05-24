<?php
header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Load secure DB config (outside web root)
require_once '/etc/marztech-config/db.php';
require_once '/etc/marztech-config/security.php';

// ----- SANITIZE INPUT -----
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

// ----- VALIDATE -----
$allowed_packages = [
    'pay-per-use' => 'Pay-Per-Use (RM200/hr)',
    'basic'       => 'Basic Plan (RM900/month)',
    'advanced'    => 'Advanced Plan (RM1,500/month)',
    'silver'      => 'Silver Plan (RM2,499/month)',
    'gold'        => 'Gold Plan (RM3,999/month)',
    'platinum'    => 'Platinum Plan (RM6,499+/month)',
];

$errors = [];
if (empty($name))                         $errors[] = 'Name is required';
if (empty($email))                        $errors[] = 'Email is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address';
if (empty($phone))                        $errors[] = 'Phone is required';
if (empty($package) || !array_key_exists($package, $allowed_packages)) $errors[] = 'Please select a valid package';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// ----- SECURITY CHECKS -----
$security_errors = security_validate();
if (!empty($security_errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh the page and try again.']);
    exit;
}

$package_label = $allowed_packages[$package];

// ----- CONNECT TO DATABASE -----
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
    ]);
} catch (PDOException $e) {
    // Log error, don't expose details to user
    error_log("IT Support DB connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System error. Please try again later.']);
    exit;
}

// ----- INSERT USING PREPARED STATEMENTS (SQL injection safe) -----
$inquiry_id = 'its_' . bin2hex(random_bytes(12));

try {
    $stmt = $pdo->prepare("
        INSERT INTO it_support_inquiries 
            (inquiry_id, name, email, phone, company, package, num_users, 
             locations, challenges, notes, ip_address, user_agent)
        VALUES 
            (:inquiry_id, :name, :email, :phone, :company, :package, :num_users,
             :locations, :challenges, :notes, :ip_address, :user_agent)
    ");

    $stmt->execute([
        ':inquiry_id' => $inquiry_id,
        ':name'       => $name,
        ':email'      => $email,
        ':phone'      => $phone,
        ':company'    => $company ?: null,
        ':package'    => $package_label,
        ':num_users'  => $num_users ?: null,
        ':locations'  => $locations ?: null,
        ':challenges' => $challenges ?: null,
        ':notes'      => $notes ?: null,
        ':ip_address' => $ip,
        ':user_agent' => $user_agent,
    ]);
    
    $db_id = $pdo->lastInsertId();

} catch (PDOException $e) {
    error_log("IT Support DB insert failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System error. Please try again later.']);
    exit;
}

// ----- SEND EMAIL -----
$to   = INQUIRY_TO;
$subj = "New IT Support Inquiry: $package_label - $name";
$body = "New IT Support inquiry from website.

";
$body .= "Inquiry ID: $inquiry_id
";
$body .= "Name:       $name
";
$body .= "Email:      $email
";
$body .= "Phone:      $phone
";
$body .= "Company:    $company
";
$body .= "Package:    $package_label
";
$body .= "Users/PCs:  $num_users
";
$body .= "Locations:  $locations
";
$body .= "Challenges: $challenges
";
$body .= "
--- Notes ---
$notes
";
$body .= "
--- Submitted ---
" . date('Y-m-d H:i:s') . "
";
$body .= "IP: $ip
";

$headers  = "From: " . INQUIRY_FROM . "
";
$headers .= "Reply-To: $email
";
$headers .= "Cc: " . INQUIRY_CC . ", " . SALES_CC . "
";
$headers .= "MIME-Version: 1.0
";
$headers .= "Content-Type: text/plain; charset=UTF-8
";

$mail_sent = @mail($to, $subj, $body, $headers);

// Update email_sent flag in DB
try {
    $stmt = $pdo->prepare("UPDATE it_support_inquiries SET email_sent = :sent WHERE id = :id");
    $stmt->execute([':sent' => $mail_sent ? 1 : 0, ':id' => $db_id]);
} catch (PDOException $e) {
    error_log("IT Support email flag update failed: " . $e->getMessage());
}

// ----- RESPONSE -----
echo json_encode([
    'success'  => true,
    'message'  => 'Thank you! Our IT support team will get back to you within 24 hours.',
    'inquiry_id' => $inquiry_id,
]);
