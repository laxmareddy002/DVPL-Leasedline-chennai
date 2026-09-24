<?php
// DVPL Leased Line lead processor
// Sends website enquiries to marketing@dvpl.org using the server's mail() function.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$isChennai = isset($_POST['lead_page']) && $_POST['lead_page'] === 'chennai';
$returnPath = $isChennai ? '/leased-line-chennai/' : '/leased-line/';

function clean_text($value, $max = 200) {
    $value = is_string($value) ? trim($value) : '';
    $value = preg_replace('/[\r\n]+/', ' ', $value);
    return substr($value, 0, $max);
}

$name     = clean_text($_POST['full_name'] ?? '', 120);
$email    = clean_text($_POST['email'] ?? '', 254);
$phone    = clean_text($_POST['phone'] ?? '', 40);
$company  = clean_text($_POST['company_name'] ?? '', 160);
$location = clean_text($_POST['location'] ?? '', 200);
$bandwidth = clean_text($_POST['bandwidth'] ?? '', 80);
$solution = clean_text($_POST['solution'] ?? '', 80);
$honeypot = clean_text($_POST['website'] ?? '');

// Silently reject simple bot submissions.
if ($honeypot !== '') {
    header('Location: ' . $returnPath . '?lead=success#quote', true, 303);
    exit;
}

if ($name === '' || $phone === '' || $company === '' || $location === '' || $bandwidth === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $returnPath . '?lead=error#quote', true, 303);
    exit;
}

// Keep the sender on your own domain; use the visitor's email only as Reply-To.
$to = 'marketing@dvpl.org';
$subject = $isChennai ? 'New Chennai Leased Line Enquiry - DVPL' : 'New Leased Line Enquiry - DVPL';

$body = "New Leased Line website enquiry\n\n"
      . "Page: " . ($isChennai ? "Chennai leased line" : "Leased line") . "\n"
      . "Full Name: {$name}\n"
      . "Email: {$email}\n"
      . "Phone: {$phone}\n"
      . "Company: " . ($company !== '' ? $company : 'Not provided') . "\n"
      . "Installation Location / Pincode: {$location}\n"
      . "Expected Bandwidth: {$bandwidth}\n"
      . "Solution of Interest: {$solution}\n"
      . "Submitted: " . date('Y-m-d H:i:s') . "\n"
      . "IP: " . clean_text($_SERVER['REMOTE_ADDR'] ?? 'Unknown', 64) . "\n";

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: DVPL Website <website@dvpl.in>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . PHP_VERSION
];

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if ($sent) {
    header('Location: ' . $returnPath . '?lead=success#quote', true, 303);
    exit;
}

error_log('DVPL lead mail failed for: ' . $email);
header('Location: ' . $returnPath . '?lead=error#quote', true, 303);
exit;
