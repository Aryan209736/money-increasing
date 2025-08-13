<?php
function send_sms_otp($phone, $code){
  // TODO: integrate your SMS API here (e.g., Twilio, MSG91, Textlocal)
  // return true on success, false on failure
  error_log("[SMS DEBUG] Sending OTP {$code} to {$phone}");
  return true;
}