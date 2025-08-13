<?php
function json_response($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function enable_cors() {
  header('Access-Control-Allow-Origin: ' . APP_ORIGIN);
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, Authorization');
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

function strong_hash_password($password) {
  if (defined('PASSWORD_ARGON2ID')) {
    return password_hash($password, PASSWORD_ARGON2ID);
  }
  return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verify_recaptcha($token) {
  if (!RECAPTCHA_SECRET) return true; // if not configured, skip (dev mode)
  $data = http_build_query([
    'secret' => RECAPTCHA_SECRET,
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
  ]);
  $context = stream_context_create([
    'http' => [
      'method' => 'POST',
      'header' => "Content-type: application/x-www-form-urlencoded\r\n",
      'content' => $data,
      'timeout' => 5,
    ]
  ]);
  $res = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
  if ($res === false) return false;
  $json = json_decode($res, true);
  // For v3, you may also check $json['score'] >= 0.5
  return !empty($json['success']);
}

function random_code($length = 6) {
  $digits = '';
  for ($i=0; $i<$length; $i++) { $digits .= random_int(0,9); }
  return $digits;
}

function uuidv4() {
  $data = random_bytes(16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function now_plus_minutes($m) {
  return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify("+{$m} minutes")->format('Y-m-d H:i:s');
}

function bad_request($msg){ json_response(['ok'=>false,'error'=>$msg], 400); }