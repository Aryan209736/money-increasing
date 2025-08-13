<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validate request fields
if (empty($input['phone']) || empty($input['password']) || empty($input['otp']) || empty($input['recaptcha_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$phone = trim($input['phone']);
$password = password_hash($input['password'], PASSWORD_BCRYPT);
$otp = trim($input['otp']);
$referral_code = !empty($input['referral']) ? trim($input['referral']) : null;
$recaptcha_token = $input['recaptcha_token'];

// 1. Verify reCAPTCHA
if (!verifyRecaptcha($recaptcha_token)) {
    echo json_encode(['status' => 'error', 'message' => 'reCAPTCHA verification failed']);
    exit;
}

// 2. Verify OTP
$stmt = $pdo->prepare("SELECT * FROM phone_otps WHERE phone = ? AND otp = ? AND expires_at > NOW()");
$stmt->execute([$phone, $otp]);
if ($stmt->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP']);
    exit;
}

// 3. Check if phone already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Phone number already registered']);
    exit;
}

// 4. Handle referral
$referrer_id = null;
if ($referral_code) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
    $stmt->execute([$referral_code]);
    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($referrer) {
        $referrer_id = $referrer['id'];
    }
}

// 5. Insert new user
$new_referral_code = generateReferralCode();
$stmt = $pdo->prepare("INSERT INTO users (phone, password, referral_code, referred_by, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$phone, $password, $new_referral_code, $referrer_id]);

// 6. Delete used OTP
$stmt = $pdo->prepare("DELETE FROM phone_otps WHERE phone = ?");
$stmt->execute([$phone]);

echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
