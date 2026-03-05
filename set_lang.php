<?php
/**
 * Language switcher endpoint.
 * Usage: set_lang.php?lang=en&redirect=/courses.php
 */
$allowed = ['tr', 'en'];
$lang = $_GET['lang'] ?? 'tr';

if (!in_array($lang, $allowed, true)) {
    $lang = 'tr';
}

// Set cookie for 1 year
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
setcookie('rv_lang', $lang, [
    'expires'  => time() + 365 * 24 * 3600,
    'path'     => '/',
    'secure'   => $secure,
    'httponly'  => false,
    'samesite'  => 'Lax'
]);

// Redirect back
$redirect = $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'index.php';

// Basic sanitization - only allow relative URLs or same-host
if (strpos($redirect, '//') !== false) {
    $redirect = 'index.php';
}

header('Location: ' . $redirect, true, 302);
exit;
