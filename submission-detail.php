<?php
session_start();
if (!($_SESSION['logged_in'] ?? false)) {
    header('Location: admin.php');
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) { echo 'No ID specified'; exit; }

// Search for submission in all JSON files
$submissions_dir = __DIR__ . '/submissions';
$entry = null;
$file_date = '';

if (is_dir($submissions_dir)) {
    $files = glob($submissions_dir . '/*.json');
    rsort($files);
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true) ?? [];
        foreach ($data as $e) {
            if (($e['id'] ?? '') === $id) {
                $entry = $e;
                $file_date = basename($file, '.json');
                break 2;
            }
        }
    }
}

if (!$entry) { echo 'Submission not found'; exit; }

// Geolocation lookup
$geo = null;
$ip = $entry['ip'] ?? '';
if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
    $geo_json = @file_get_contents("http://ip-api.com/json/" . $ip . "?fields=status,country,regionName,city,isp,org,as,lat,lon,timezone");
    if ($geo_json) {
        $geo = json_decode($geo_json, true);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submission Detail - Marz Technology</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
.header-bar { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: #1e293b; border-bottom: 1px solid #334155; }
.header-bar h2 { font-size: 18px; color: #fff; }
.header-bar a { color: #60a5fa; text-decoration: none; font-size: 13px; }
.header-bar a:hover { text-decoration: underline; }
.container { max-width: 800px; margin: 0 auto; padding: 24px; }
.card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 24px; margin-bottom: 16px; }
.card h3 { font-size: 14px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #334155; }
.detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #0f172a; }
.detail-row:last-child { border-bottom: none; }
.detail-label { width: 140px; font-size: 13px; color: #94a3b8; flex-shrink: 0; }
.detail-value { font-size: 14px; color: #fff; word-break: break-word; }
.detail-value.ip { color: #60a5fa; font-family: monospace; }
.detail-value.msg { color: #cbd5e1; line-height: 1.6; white-space: pre-wrap; }
.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-contact { background: #1e3a5f; color: #93c5fd; }
.badge-rental { background: #1f3b2f; color: #86efac; }
.geo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.geo-item { background: #0f172a; border-radius: 8px; padding: 12px; }
.geo-item .label { font-size: 11px; color: #64748b; margin-bottom: 4px; }
.geo-item .value { font-size: 14px; color: #fff; font-weight: 600; }
.map-wrap { margin-top: 12px; border-radius: 8px; overflow: hidden; }
.back-link { display: inline-flex; align-items: center; gap: 6px; color: #60a5fa; font-size: 13px; text-decoration: none; margin-bottom: 16px; }
.back-link:hover { text-decoration: underline; }
@media (max-width: 600px) {
    .detail-row { flex-direction: column; gap: 4px; }
    .detail-label { width: auto; }
    .geo-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="header-bar">
<div>
<h2>📄 Submission Detail</h2>
<span><?=htmlspecialchars($entry['id'] ?? '')?></span>
</div>
<div>
<a href="admin.php">← Back to List</a>
</div>
</div>

<div class="container">
<a href="admin.php" class="back-link">← Back to All Submissions</a>

<div class="card">
<h3>Submission Info</h3>
<div class="detail-row">
<span class="detail-label">ID</span>
<span class="detail-value" style="font-family:monospace;color:#94a3b8;"><?=htmlspecialchars($entry['id'] ?? '')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Type</span>
<span class="detail-value"><span class="badge <?=($entry['type']??'Contact')==='Rental Inquiry'?'badge-rental':'badge-contact'?>"><?=htmlspecialchars($entry['type'] ?? 'Contact')?></span></span>
</div>
<div class="detail-row">
<span class="detail-label">Date / Time</span>
<span class="detail-value"><?=htmlspecialchars($entry['timestamp'] ?? '')?></span>
</div>
<?php if (($entry['type'] ?? 'Contact') === 'Contact'): ?>
<div class="detail-row">
<span class="detail-label">Subject</span>
<span class="detail-value"><?=htmlspecialchars($entry['subject'] ?? '')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Message</span>
<span class="detail-value msg"><?=htmlspecialchars($entry['message'] ?? '')?></span>
</div>
<?php else: ?>
<div class="detail-row">
<span class="detail-label">Package</span>
<span class="detail-value"><?=htmlspecialchars($entry['package'] ?? '')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Quantity</span>
<span class="detail-value"><?=htmlspecialchars($entry['quantity'] ?? '')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Duration</span>
<span class="detail-value"><?=htmlspecialchars($entry['duration'] ?? '')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Start Date</span>
<span class="detail-value"><?=htmlspecialchars($entry['start_date'] ?? '-')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Purpose</span>
<span class="detail-value"><?=htmlspecialchars($entry['purpose'] ?? '-')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Company</span>
<span class="detail-value"><?=htmlspecialchars($entry['company'] ?? '-')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Delivery Address</span>
<span class="detail-value"><?=htmlspecialchars($entry['delivery_addr'] ?? '-')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Notes</span>
<span class="detail-value msg"><?=htmlspecialchars($entry['notes'] ?? '-')?></span>
</div>
<?php endif; ?>
</div>

<div class="card">
<h3>Contact Info</h3>
<div class="detail-row">
<span class="detail-label">Name</span>
<span class="detail-value" style="font-weight:600;"><?=htmlspecialchars($entry['name'] ?? '')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Email</span>
<span class="detail-value" style="color:#60a5fa;"><?=htmlspecialchars($entry['email'] ?? '')?></span>
</div>
<div class="detail-row">
<span class="detail-label">Phone</span>
<span class="detail-value"><?=htmlspecialchars($entry['phone'] ?? '-')?></span>
</div>
</div>

<div class="card">
<h3>IP &amp; Location</h3>
<div class="detail-row">
<span class="detail-label">IP Address</span>
<span class="detail-value ip"><?=htmlspecialchars($ip ?: 'Not recorded')?></span>
</div>
<?php if ($geo && ($geo['status'] ?? '') === 'success'): ?>
<div class="geo-grid" style="margin-top:12px;">
<div class="geo-item"><div class="label">Country</div><div class="value"><?=htmlspecialchars($geo['country'] ?? '-')?></div></div>
<div class="geo-item"><div class="label">Region</div><div class="value"><?=htmlspecialchars($geo['regionName'] ?? '-')?></div></div>
<div class="geo-item"><div class="label">City</div><div class="value"><?=htmlspecialchars($geo['city'] ?? '-')?></div></div>
<div class="geo-item"><div class="label">ISP</div><div class="value"><?=htmlspecialchars($geo['isp'] ?? '-')?></div></div>
<div class="geo-item"><div class="label">Organization</div><div class="value"><?=htmlspecialchars($geo['org'] ?? '-')?></div></div>
<div class="geo-item"><div class="label">Timezone</div><div class="value"><?=htmlspecialchars($geo['timezone'] ?? '-')?></div></div>
</div>
<?php if (!empty($geo['lat']) && !empty($geo['lon'])): ?>
<div class="map-wrap">
<iframe
src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1000!2d<?=$geo['lon']?>!3d<?=$geo['lat']?>!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e1"
width="100%" height="300" style="border:0;border-radius:8px;" allowfullscreen loading="lazy" title="IP Location Map">
</iframe>
</div>
<?php endif; ?>
<?php else: ?>
<div class="detail-row">
<span class="detail-label">Location</span>
<span class="detail-value" style="color:#94a3b8;"><?=$ip ? 'Unable to resolve location' : 'IP not available'?></span>
</div>
<?php endif; ?>
<div class="detail-row">
<span class="detail-label">User Agent</span>
<span class="detail-value" style="font-size:12px;color:#94a3b8;word-break:break-all;"><?=htmlspecialchars($entry['user_agent'] ?? '-')?></span>
</div>
</div>
</div>
</body>
</html>



