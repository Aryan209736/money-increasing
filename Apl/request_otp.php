<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/sms.php';
require_once __DIR__ . '/../lib/mailer.php';

enable_cors();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$channel = $input['channel'] ?? null; // 'phone' | 'email'
$target  = trim($input['target'] ?? '');

if (!in_array($channel, ['phone','email'], true)) bad_request('Invalid channel');
if ($target === '') bad_request('Target required');

$pdo = db();

// Rate limit: ensure last OTP to same target is older than cooldown
$stmt = $pdo->prepare("SELECT created_at FROM otps WHERE channel=? AND target=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$channel, $target]);
$last = $stmt->fetch();
if ($last) {
  $lastTs = strtotime($last['created_at']);
  if (time() - $lastTs < OTP_RESEND_COOLDOWN_SECONDS) {
    bad_request('Please wait before requesting another OTP');
  }
}

$code = random_code(6);
$code_hash = password_hash($code, PASSWORD_BCRYPT);
$request_id = uuidv4();
$expires_at = now_plus_minutes(OTP_EXP_MINUTES);

$stmt = $pdo->prepare("INSERT INTO otps(channel,target,code_hash,request_id,expires_at) VALUES (?,?,?,?,?)");
$stmt->execute([$channel, $target, $code_hash, $request_id, $expires_at]);

$sent = $channel === 'phone' ? send_sms_otp($target, $code) : send_email_otp($target, $code);
if (!$sent) json_response(['ok'=>false,'error'=>'Failed to send OTP'], 500);

json_response(['ok'=>true,'request_id'=>$request_id,'expires_at'=>$expires_at]);