<?php
declare(strict_types=1);

/* --- 1) Çıktıyı en baştan tamponla --- */
if (ob_get_level() === 0) { ob_start(); }

/* --- 2) Genel ini ayarları (güvenli + UTF-8) --- */
ini_set('default_charset', 'UTF-8');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

/* --- 3) Session'ı tek merkezden başlat --- */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* --- 4) Yardımcılar: redirect & json --- */
function redirect(string $url, int $code = 302): never {
    if (!headers_sent()) {
        header('Location: '.$url, true, $code);
    } else {
        // Header çok geç kaldıysa bile fallback
        echo '<script>location.href='.json_encode($url).'</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url='.htmlspecialchars($url, ENT_QUOTES).'"></noscript>';
    }
    if (ob_get_level() > 0) { @ob_end_flush(); }
    exit;
}

function json_response($data, int $code = 200): never {
    if (!headers_sent()) header('Content-Type: application/json; charset=UTF-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    if (ob_get_level() > 0) { @ob_end_flush(); }
    exit;
}

/* --- 5) Sık kullanılan: güvenli cookie set --- */
function set_cookie(string $name, string $value, int $ttl = 31536000): void {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    @setcookie($name, $value, [
        'expires'  => time() + $ttl,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
