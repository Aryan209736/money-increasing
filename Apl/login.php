<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['username']) || empty($input['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
    exit;
}

$username = trim($input['username']);
$password = trim($input['password']);

# Check if username is email or phone
if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
}

$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

if (!password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
    exit;
}

# Create simple session token (JWT is better for production)
$token = bin2hex(random_bytes(32));
$_SESSION['user_id'] = $user['id'];
$_SESSION['token'] = $token;

echo json_encode([
    'status' => 'success',
    'message' => 'Login successful',
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'phone' => $user['phone'],
        'email' => $user['email'],
        'referral_code' => $user['referral_code']
    ]
]);
