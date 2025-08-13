<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

enable_cors();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$request_id = $input['request_id'] ?? '';
$code = $input['code'] ?? '';

if (!$request_id || !$code) bad_request('Missing fields');
$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM otps WHERE request_id=? LIMIT 1");
$stmt->execute([$request_id]);
$otp = $stmt->fetch();
if (!$otp) bad_request('Invalid request');
if ($otp['consumed']) bad_request('OTP already used');
if (strtotime($otp['expires_at']) < time()) bad_request('OTP expired');

if ($otp['attempts'] >= 5) bad_request('Too many attempts');

$valid = password_verify($code, $otp['code_hash']);
$pdo->prepare("UPDATE otps SET attempts=attempts+1 WHERE id=?")->execute([$otp['id']]);
if (!$valid) bad_request('Incorrect OTP');

$pdo->prepare("UPDATE otps SET consumed=1 WHERE id=?")->execute([$otp['id']]);

// Mark user as verified if exists
if ($otp['channel'] === 'phone') {
  $pdo->prepare("UPDATE users SET is_verified=1 WHERE phone=?")->execute([$otp['target']]);
} else {
  $pdo->prepare("UPDATE users SET is_verified=1 WHERE email=?")->execute([$otp['target']]);
}

json_response(['ok'=>true]);