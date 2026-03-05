<?php
/**
 * RookieVerse i18n Helper
 * Loads translations from JSON files based on user's language preference (cookie).
 * Usage: <?= __('nav.courses') ?>
 */

// Detect language from cookie, default to 'tr'
function rv_get_lang(): string {
    $allowed = ['tr', 'en'];
    $lang = $_COOKIE['rv_lang'] ?? 'tr';
    return in_array($lang, $allowed, true) ? $lang : 'tr';
}

// Load translations (cached per request)
function rv_get_translations(): array {
    static $translations = null;
    if ($translations !== null) return $translations;

    $lang = rv_get_lang();
    $file = __DIR__ . '/lang/' . $lang . '.json';

    if (!file_exists($file)) {
        $file = __DIR__ . '/lang/tr.json'; // fallback
    }

    $json = file_get_contents($file);
    $translations = json_decode($json, true) ?? [];
    return $translations;
}

/**
 * Translate a key. Returns the key itself if not found.
 * Supports sprintf-style replacements: __('key', $arg1, $arg2)
 */
function __($key, ...$args) {
    $translations = rv_get_translations();
    $text = $translations[$key] ?? $key;
    if (!empty($args)) {
        $text = vsprintf($text, $args);
    }
    return $text;
}

// Current language code
define('CURRENT_LANG', rv_get_lang());
