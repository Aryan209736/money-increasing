<?php
function send_email_otp($email, $code){
  // For shared hosting, simple mail() may work, but SMTP is recommended.
  $subject = 'Your OTP Code';
  $message = "Your Money Increasing verification code is: {$code}\nThis code expires in " . OTP_EXP_MINUTES . " minutes.";
  $headers = 'From: no-reply@your-domain.com' . "\r\n" .
             'Reply-To: support@your-domain.com' . "\r\n" .
             'X-Mailer: PHP/' . phpversion();
  // NOTE: Replace with PHPMailer + SMTP for reliability in production.
  return mail($email, $subject, $message, $headers);
}