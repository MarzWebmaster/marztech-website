<?php
/**
 * Marz Stay Secure - Cybersecurity Inquiry Handler
 * Saves submissions and sends email notifications
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once '/etc/marztech-config/db.php';
require_once '/etc/marztech-config/security.php';
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Helper function to sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Collect and sanitize POST data
$name     = isset($_POST['name'])     ? sanitize($_POST['name'])     : '';
$email    = isset($_POST['email'])    ? sanitize($_POST['email'])    : '';
$phone    = isset($_POST['phone'])    ? sanitize($_POST['phone'])    : '';
$company  = isset($_POST['company'])  ? sanitize($_POST['company'])  : '';
$package  = isset($_POST['package'])  ? sanitize($_POST['package'])  : '';
$industry = isset($_POST['industry']) ? sanitize($_POST['industry']) : '';
$concerns = isset($_POST['concerns']) ? sanitize($_POST['concerns']) : '';
$notes    = isset($_POST['notes'])    ? sanitize($_POST['notes'])    : '';

// Validate required fields
$errors = [];
if (empty($name))    $errors[] = 'Full name is required';
if (empty($email))   $errors[] = 'Email address is required';
if (empty($phone))   $errors[] = 'Phone number is required';
if (empty($package)) $errors[] = 'Package interest is required';

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode('. ', $errors) . '.']);
    exit;
}

// ----- SECURITY CHECKS -----
$security_errors = security_validate();
if (!empty($security_errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh the page and try again.']);
    exit;
}

// Build submission data
$submission = [
    'type'       => 'Cybersecurity Inquiry',
    'name'       => $name,
    'email'      => $email,
    'phone'      => $phone,
    'company'    => $company,
    'package'    => $package,
    'industry'   => $industry,
    'concerns'   => $concerns,
    'notes'      => $notes,
    'submitted_at' => date('Y-m-d H:i:s'),
    'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

// Build filename based on date
$dateStr   = date('Y-m-d');
$timestamp = time();
$submissionsDir = __DIR__ . '/../submissions';
$filename  = $dateStr . '.json';
$filepath  = $submissionsDir . '/' . $filename;

// Ensure submissions directory exists
if (!is_dir($submissionsDir)) {
    mkdir($submissionsDir, 0755, true);
}

// Load existing submissions for today or create new array
$todaySubmissions = [];
if (file_exists($filepath)) {
    $existing = file_get_contents($filepath);
    if (!empty($existing)) {
        $data = json_decode($existing, true);
        if (is_array($data)) {
            $todaySubmissions = $data;
        }
    }
}

// If it's a flat array (first entry), convert to indexed
if (isset($todaySubmissions['type'])) {
    $todaySubmissions = [$todaySubmissions];
}

// Add new submission
$todaySubmissions[] = $submission;

// Save back to file
$saved = file_put_contents(
    $filepath,
    json_encode($todaySubmissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

if ($saved === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save submission. Please try again.']);
    exit;
}

// --- SEND EMAIL ---
$to      = 'info@marztechnology.com.my';
$cc      = 'marzcomputer@gmail.com';
$subject = 'New Cybersecurity Inquiry - Marz Stay Secure';

// Build email body
$emailBody = "
<html>
<head><meta charset=\"UTF-8\">
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
  .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .header { background: #0f172a; padding: 30px; text-align: center; }
  .header h1 { color: #DC2626; margin: 0; font-size: 24px; }
  .header p { color: #ffffff; margin: 5px 0 0; font-size: 14px; }
  .body { padding: 30px; }
  .field { margin-bottom: 15px; }
  .field-label { font-weight: bold; color: #0f172a; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
  .field-value { color: #333; font-size: 15px; padding: 8px 12px; background: #f8f9fa; border-radius: 4px; }
  .footer { background: #f8f9fa; padding: 20px 30px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; }
  .tag { display: inline-block; background: #DC2626; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
</style>
</head>
<body>
<div class=\"container\">
  <div class=\"header\">
    <h1>🔒 Marz Stay Secure</h1>
    <p>New Cybersecurity Inquiry</p>
  </div>
  <div class=\"body\">
    <div style=\"text-align: center; margin-bottom: 20px;\">
      <span class=\"tag\">" . htmlspecialchars($package, ENT_QUOTES, 'UTF-8') . "</span>
    </div>
    <div class=\"field\">
      <div class=\"field-label\">Full Name</div>
      <div class=\"field-value\">" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</div>
    </div>
    <div class=\"field\">
      <div class=\"field-label\">Email Address</div>
      <div class=\"field-value\">" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</div>
    </div>
    <div class=\"field\">
      <div class=\"field-label\">Phone Number</div>
      <div class=\"field-value\">" . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</div>
    </div>
    <div class=\"field\">
      <div class=\"field-label\">Company</div>
      <div class=\"field-value\">" . (!empty($company) ? htmlspecialchars($company, ENT_QUOTES, 'UTF-8') : '<em>Not provided</em>') . "</div>
    </div>
    <div class=\"field\">
      <div class=\"field-label\">Package Interest</div>
      <div class=\"field-value\">" . htmlspecialchars($package, ENT_QUOTES, 'UTF-8') . "</div>
    </div>
    <div class=\"field\">
      <div class=\"field-label\">Industry</div>
      <div class=\"field-value\">" . (!empty($industry) ? htmlspecialchars($industry, ENT_QUOTES, 'UTF-8') : '<em>Not provided</em>') . "</div>
    </div>
    <div class=\"field\">
      <div class=\"field-label\">Security Concerns</div>
      <div class=\"field-value\">" . (!empty($concerns) ? nl2br(htmlspecialchars($concerns, ENT_QUOTES, 'UTF-8')) : '<em>Not provided</em>') . "</div>
    </div>
    <div class=\"field\">
      <div class=\"field-label\">Additional Notes</div>
      <div class=\"field-value\">" . (!empty($notes) ? nl2br(htmlspecialchars($notes, ENT_QUOTES, 'UTF-8')) : '<em>Not provided</em>') . "</div>
    </div>
    <div class=\"field\">
      <div class=\"field-label\">Submitted At</div>
      <div class=\"field-value\">" . $submission['submitted_at'] . "</div>
    </div>
  </div>
  <div class=\"footer\">
    Marz Technology &amp; Trading &bull; SSM: 001884868v<br>
    This inquiry was submitted via the Marz Stay Secure page.
  </div>
</div>
</body>
</html>
";

// Headers
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Marz Stay Secure <noreply@marztechnology.com.my>\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Cc: marzcomputer@gmail.com, sales@marz.my\r\n";
$headers .= "CC: " . $cc . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$mailSent = mail($to, $subject, $emailBody, $headers);

// If mail fails, try sending without CC
if (!$mailSent) {
    $headers = str_replace("CC: " . $cc . "\r\n", "", $headers);
    $mailSent = mail($to, $subject, $emailBody, $headers);

    // Try CC separately
    if ($mailSent) {
        $ccHeaders = "MIME-Version: 1.0\r\n";
        $ccHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
        $ccHeaders .= "From: Marz Stay Secure <noreply@marztechnology.com.my>\r\n";
$ccHeaders .= "Cc: marzcomputer@gmail.com, sales@marz.my\r\n";
        mail($cc, "[CC] $subject", $emailBody, $ccHeaders);
    }
}

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Thank you for your inquiry! Our cybersecurity team will get back to you within 24 hours.',
    'mail_sent' => $mailSent
]);
