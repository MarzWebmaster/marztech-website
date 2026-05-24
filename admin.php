<?php
session_start();

$admin_user = 'webmaster@marzcomputer.com.my';
$admin_pass = 'Marzcomputer1!';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
        $_SESSION['logged_in'] = true;
        header('Location: admin.php');
        exit;
    }
    $login_error = 'Invalid credentials';
}

$is_logged_in = $_SESSION['logged_in'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Marz Technology</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
.container { max-width: 1200px; margin: 0 auto; padding: 20px; }

/* Login */
.login-wrap { display: flex; justify-content: center; align-items: center; min-height: 100vh; }
.login-card { background: #1e293b; padding: 40px; border-radius: 16px; width: 100%; max-width: 400px; border: 1px solid #334155; }
.login-card h1 { color: #fff; font-size: 22px; margin-bottom: 8px; text-align: center; }
.login-card p { color: #94a3b8; font-size: 14px; margin-bottom: 24px; text-align: center; }
.login-card .logo { width: 48px; height: 48px; background: linear-gradient(135deg, #DC2626, #2563EB); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 22px; font-weight: 800; margin: 0 auto 16px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; }
.form-group input { width: 100%; padding: 12px 16px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #fff; font-size: 14px; outline: none; transition: border .2s; }
.form-group input:focus { border-color: #2563EB; }
.btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #DC2626, #2563EB); border: none; border-radius: 10px; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; transition: opacity .2s; }
.btn:hover { opacity: .9; }
.error { background: #7f1d1d; color: #fca5a5; padding: 10px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; text-align: center; }

/* Dashboard */
.header-bar { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: #1e293b; border-bottom: 1px solid #334155; }
.header-bar h2 { font-size: 18px; color: #fff; }
.header-bar span { color: #94a3b8; font-size: 13px; }
.header-bar a { color: #f87171; text-decoration: none; font-size: 13px; font-weight: 600; }
.header-bar a:hover { text-decoration: underline; }

.stats { display: flex; gap: 16px; margin: 24px 0; flex-wrap: wrap; }
.stat-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px 24px; flex: 1; min-width: 150px; }
.stat-card h3 { font-size: 28px; font-weight: 700; color: #fff; }
.stat-card p { font-size: 13px; color: #94a3b8; margin-top: 4px; }

.filters { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.filters input, .filters select { padding: 10px 14px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #fff; font-size: 13px; outline: none; }
.filters input:focus, .filters select:focus { border-color: #2563EB; }
.filters input[type="date"] { color-scheme: dark; }

table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
th { text-align: left; padding: 12px 16px; font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #334155; background: #0f172a; }
td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #1e293b; vertical-align: top; }
tr:hover td { background: #0f172a; }
td.name { color: #fff; font-weight: 600; }
td.email { color: #60a5fa; }
td.msg { max-width: 300px; white-space: pre-wrap; color: #cbd5e1; line-height: 1.5; }
td.time { color: #94a3b8; white-space: nowrap; }
.subject-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.subject-general { background: #1e3a5f; color: #93c5fd; }
.subject-it-support { background: #3b1f1f; color: #fca5a5; }
.subject-ai-solutions { background: #1f3b2f; color: #86efac; }
.subject-products { background: #3b2f1f; color: #fcd34d; }
.subject-partnership { background: #2f1f3b; color: #d8b4fe; }
.empty { text-align: center; padding: 48px; color: #64748b; font-size: 14px; }
tr[style*="cursor:pointer"]:hover td { background: #1e293b !important; }

@media (max-width: 768px) {
    .container { padding: 12px; }
    .header-bar { flex-wrap: wrap; gap: 8px; }
    table { font-size: 12px; }
    th, td { padding: 8px 10px; }
    td.msg { max-width: 150px; }
    .stat-card { min-width: 100%; }
}
</style>
</head>
<body>

<?php if (!$is_logged_in): ?>
<div class="login-wrap">
<div class="login-card">
<div class="logo">M</div>
<h1>Admin Login</h1>
<p>Marz Technology & Trading — Contact Submissions</p>
<?php if (isset($login_error)): ?>
<div class="error"><?=htmlspecialchars($login_error)?></div>
<?php endif; ?>
<form method="POST">
<div class="form-group">
<label>Username</label>
<input type="text" name="username" placeholder="Enter username" required>
</div>
<div class="form-group">
<label>Password</label>
<input type="password" name="password" placeholder="Enter password" required>
</div>
<button type="submit" name="login" class="btn">Sign In</button>
</form>
</div>
</div>

<?php else:
require_once '/etc/marztech-config/db.php';

// Load submissions from MySQL (all 4 tables)
$submissions = [];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Contact inquiries
    $rows = $pdo->query("SELECT id, inquiry_id, name, email, phone, subject AS package, '' AS num_users, '' AS locations, message AS challenges, '' AS company, '' AS quantity, '' AS duration, '' AS start_date, '' AS purpose, '' AS delivery_addr, '' AS concerns, '' AS industry, '' AS description, ip_address, user_agent, email_sent, created_at FROM contact_inquiries")->fetchAll();
    foreach ($rows as $r) {
        $r['type'] = 'Contact';
        $r['subject_or_pkg'] = $r['package'] ?: 'General';
        $submissions[] = $r;
    }

    // IT Support inquiries
    $rows = $pdo->query("SELECT id, inquiry_id, name, email, phone, package, num_users, locations, challenges, company, '' AS quantity, '' AS duration, '' AS start_date, '' AS purpose, '' AS delivery_addr, '' AS concerns, '' AS industry, '' AS description, ip_address, user_agent, email_sent, created_at FROM it_support_inquiries")->fetchAll();
    foreach ($rows as $r) {
        $r['type'] = 'IT Support Inquiry';
        $r['subject_or_pkg'] = $r['package'] ?: '-';
        $submissions[] = $r;
    }

    // Rental inquiries
    $rows = $pdo->query("SELECT id, inquiry_id, name, email, phone, package, '' AS num_users, '' AS locations, notes AS challenges, company, quantity, duration, start_date, purpose, delivery_addr, '' AS concerns, '' AS industry, '' AS description, ip_address, user_agent, email_sent, created_at FROM rental_inquiries")->fetchAll();
    foreach ($rows as $r) {
        $r['type'] = 'Rental Inquiry';
        $r['subject_or_pkg'] = $r['package'] ?: '-';
        $submissions[] = $r;
    }

    // Cybersecurity inquiries
    $rows = $pdo->query("SELECT id, inquiry_id, name, email, phone, package, '' AS num_users, '' AS locations, notes AS challenges, company, '' AS quantity, '' AS duration, '' AS start_date, '' AS purpose, '' AS delivery_addr, concerns, industry, description, ip_address, user_agent, email_sent, created_at FROM cybersecurity_inquiries")->fetchAll();
    foreach ($rows as $r) {
        $r['type'] = 'Cybersecurity Inquiry';
        $r['subject_or_pkg'] = $r['package'] ?: '-';
        $submissions[] = $r;
    }

} catch (PDOException $e) {
    error_log("Admin DB load failed: " . $e->getMessage());
    $submissions = [];
}

// Sort by created_at DESC (newest first)
usort($submissions, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

// Filters
$filter_date = $_GET['date'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_subject = $_GET['subject'] ?? '';

if ($filter_date) {
    $submissions = array_filter($submissions, fn($s) => str_starts_with($s['created_at'] ?? '', $filter_date));
}
if ($filter_type) {
    $submissions = array_filter($submissions, fn($s) => $s['type'] === $filter_type);
}
if ($filter_subject) {
    $submissions = array_filter($submissions, fn($s) => ($s['subject_or_pkg'] ?? '') === $filter_subject);
}

// Stats
$total = count($submissions);
$type_count = [];
foreach ($submissions as $s) {
    $t = $s['type'] ?? 'Unknown';
    $type_count[$t] = ($type_count[$t] ?? 0) + 1;
}
$package_count = [];
foreach ($submissions as $s) {
    $pkg = $s['subject_or_pkg'] ?? 'Unknown';
    $package_count[$pkg] = ($package_count[$pkg] ?? 0) + 1;
}
?>
<div class="header-bar">
<div>
<h2>📋 All Submissions</h2>
<span>marztechnology.com.my</span>
</div>
<div>
<a href="?logout=1">Logout</a>
</div>
</div>

<div class="container">
<div class="stats">
<div class="stat-card"><h3><?=$total?></h3><p>Total Submissions</p></div>
<?php foreach ($type_count as $type => $count): ?>
<div class="stat-card"><h3><?=$count?></h3><p><?=htmlspecialchars($type)?></p></div>
<?php endforeach; ?>
</div>

<form method="GET" class="filters">
<input type="date" name="date" value="<?=htmlspecialchars($filter_date)?>">
<select name="type">
<option value="">All Types</option>
<option value="Contact" <?=$filter_type==='Contact'?'selected':''?>>Contact Form</option>
<option value="Rental Inquiry" <?=$filter_type==='Rental Inquiry'?'selected':''?>>Rental Inquiry</option>
<option value="Cybersecurity Inquiry" <?=$filter_type==='Cybersecurity Inquiry'?'selected':''?>>Cybersecurity Inquiry</option>
<option value="IT Support Inquiry" <?=$filter_type==='IT Support Inquiry'?'selected':''?>>IT Support Inquiry</option>
</select>
<select name="subject">
<option value="">All Subjects / Packages</option>
<option value="General Inquiry" <?=$filter_subject==='General Inquiry'?'selected':''?>>General Inquiry</option>
<option value="IT Support" <?=$filter_subject==='IT Support'?'selected':''?>>IT Support</option>
<option value="AI Solutions" <?=$filter_subject==='AI Solutions'?'selected':''?>>AI Solutions</option>
<option value="Products" <?=$filter_subject==='Products'?'selected':''?>>Products</option>
<option value="Partnership" <?=$filter_subject==='Partnership'?'selected':''?>>Partnership</option>
<option value="Basic Package" <?=$filter_subject==='Basic Package'?'selected':''?>>Basic Package</option>
<option value="Standard Package" <?=$filter_subject==='Standard Package'?'selected':''?>>Standard Package</option>
<option value="Premium Package" <?=$filter_subject==='Premium Package'?'selected':''?>>Premium Package</option>
<option value="Custom Package" <?=$filter_subject==='Custom Package'?'selected':''?>>Custom Package</option>
<option value="Essential Security" <?=$filter_subject==='Essential Security'?'selected':''?>>Essential Security</option>
<option value="E-Commerce Protect" <?=$filter_subject==='E-Commerce Protect'?'selected':''?>>E-Commerce Protect</option>
<option value="Health Secure" <?=$filter_subject==='Health Secure'?'selected':''?>>Health Secure</option>
<option value="Financial Shield" <?=$filter_subject==='Financial Shield'?'selected':''?>>Financial Shield</option>
<option value="Gov Safe" <?=$filter_subject==='Gov Safe'?'selected':''?>>Gov Safe</option>
<option value="Enterprise Security Suite" <?=$filter_subject==='Enterprise Security Suite'?'selected':''?>>Enterprise Security Suite</option>
<option value="Pay-Per-Use (RM200/hr)" <?=$filter_subject==='Pay-Per-Use (RM200/hr)'?'selected':''?>>Pay-Per-Use</option>
<option value="Basic Plan (RM900/month)" <?=$filter_subject==='Basic Plan (RM900/month)'?'selected':''?>>Basic Plan</option>
<option value="Advanced Plan (RM1,500/month)" <?=$filter_subject==='Advanced Plan (RM1,500/month)'?'selected':''?>>Advanced Plan</option>
<option value="Silver Plan (RM2,499/month)" <?=$filter_subject==='Silver Plan (RM2,499/month)'?'selected':''?>>Silver Plan</option>
<option value="Gold Plan (RM3,999/month)" <?=$filter_subject==='Gold Plan (RM3,999/month)'?'selected':''?>>Gold Plan</option>
<option value="Platinum Plan (RM6,499+/month)" <?=$filter_subject==='Platinum Plan (RM6,499+/month)'?'selected':''?>>Platinum Plan</option>
</select>
<button type="submit" class="btn" style="width:auto;padding:10px 20px;">Filter</button>
<?php if ($filter_date || $filter_type || $filter_subject): ?>
<a href="admin.php" style="color:#94a3b8;font-size:13px;align-self:center;">Clear</a>
<?php endif; ?>
</form>

<?php if (empty($submissions)): ?>
<div class="empty">No submissions found.</div>
<?php else: ?>
<table>
<thead>
<tr>
<th>Type</th>
<th>Name</th>
<th>Email</th>
<th>Details</th>
<th>Phone</th>
<th>Date</th>
</tr>
</thead>
<tbody>
<?php foreach ($submissions as $s): 
$type = $s['type'] ?? 'Contact';
$subj_or_pkg = $s['subject_or_pkg'] ?? '-';
$subj_class = match($subj_or_pkg) {
    'General Inquiry', 'IT Support', 'AI Solutions', 'Products', 'Partnership' => 'subject-general',
    'Basic Package' => 'subject-general',
    'Standard Package' => 'subject-ai-solutions',
    'Premium Package' => 'subject-products',
    'Custom Package' => 'subject-it-support',
    'Essential Security', 'E-Commerce Protect', 'Health Secure', 'Financial Shield', 'Gov Safe', 'Enterprise Security Suite' => 'subject-ai-solutions',
    'Pay-Per-Use (RM200/hr)', 'Basic Plan (RM900/month)', 'Advanced Plan (RM1,500/month)', 'Silver Plan (RM2,499/month)', 'Gold Plan (RM3,999/month)', 'Platinum Plan (RM6,499+/month)' => 'subject-products',
    default => 'subject-general',
};
$type_class = match($type) {
    'Rental Inquiry' => 'subject-ai-solutions',
    'Cybersecurity Inquiry' => 'subject-ai-solutions',
    'IT Support Inquiry' => 'subject-products',
    default => 'subject-general',
};
$details = '';
if ($type === 'Rental Inquiry') {
    $details = 'Pkg: ' . htmlspecialchars($subj_or_pkg) . ' | Qty: ' . htmlspecialchars($s['quantity'] ?? '-') . ' | Dur: ' . htmlspecialchars($s['duration'] ?? '-');
    if (!empty($s['company'])) $details = htmlspecialchars($s['company']) . ' — ' . $details;
    if (!empty($s['challenges'])) $details .= ' | Notes: ' . htmlspecialchars(substr($s['challenges'], 0, 60));
} elseif ($type === 'Cybersecurity Inquiry') {
    $pkg = htmlspecialchars($subj_or_pkg);
    $industry = htmlspecialchars($s['industry'] ?? '-');
    $details = "Pkg: $pkg | Industry: $industry";
    if (!empty($s['concerns'])) $details .= ' | Concerns: ' . htmlspecialchars(substr($s['concerns'], 0, 60));
} elseif ($type === 'IT Support Inquiry') {
    $pkg = htmlspecialchars($subj_or_pkg);
    $users = htmlspecialchars($s['num_users'] ?? '-');
    $loc = htmlspecialchars($s['locations'] ?? '-');
    $details = "Pkg: $pkg | Users: $users | Loc: $loc";
    if (!empty($s['challenges'])) $details .= ' | Issues: ' . htmlspecialchars(substr($s['challenges'], 0, 60));
} else {
    $details = htmlspecialchars($s['challenges'] ?? '');
}
?>
<tr onclick="window.location='submission-detail.php?id=<?=htmlspecialchars($s['inquiry_id'] ?? '')?>'" style="cursor:pointer;">
<td><span class="subject-badge <?=$type_class?>"><?=htmlspecialchars($type)?></span></td>
<td class="name"><?=htmlspecialchars($s['name'] ?? '')?></td>
<td class="email"><?=htmlspecialchars($s['email'] ?? '')?></td>
<td class="msg"><?=$details?></td>
<td style="color:#94a3b8;"><?=htmlspecialchars($s['phone'] ?? '-')?></td>
<td class="time"><?=htmlspecialchars($s['created_at'] ?? '')?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<?php endif; ?>
</body>
</html>
