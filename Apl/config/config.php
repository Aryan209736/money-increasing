<?php
// Load .env (very small loader; no composer needed)
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
  $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#')) continue;
    [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
    $_ENV[trim($k)] = trim($v);
  }
}

function env($key, $default = null){ return $_ENV[$key] ?? $default; }

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'money_increasing'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

define('APP_ENV', env('APP_ENV', 'production'));

define('APP_ORIGIN', env('APP_ORIGIN', '*'));

define('RECAPTCHA_SECRET', env('RECAPTCHA_SECRET', ''));

define('OTP_EXP_MINUTES', (int)env('OTP_EXP_MINUTES', 10));

define('OTP_RESEND_COOLDOWN_SECONDS', (int)env('OTP_RESEND_COOLDOWN_SECONDS', 60));