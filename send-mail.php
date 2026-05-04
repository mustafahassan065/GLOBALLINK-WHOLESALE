<?php
// ============================================================
//  GLOBALLINK WHOLESALE - Contact Form Mailer
//  Pure PHP SMTP — no library needed, works on any PHP host
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ── ⚙️  CONFIG — Edit these ──────────────────────────────────
$smtp_host     = 'smtp.gmail.com';
$smtp_port     = 587;
$smtp_user     = 'mustafaprogrammer786@gmail.com';  // Your Gmail
$smtp_pass     = 'jfcuemlfkdqtcyqx';           // 16-char Gmail App Password (no spaces)
$mail_from     = 'mustafaprogrammer786@gmail.com';
$mail_from_name= 'GLOBALLINK WHOLESALE';
$mail_to       = 'Info.glinkltd@on.co.uk';           // Client's inbox
$mail_to_name  = 'GLOBALLINK WHOLESALE LIMITED';
$mail_subject  = 'New Contact Form Message – GLOBALLINK WHOLESALE';
// ─────────────────────────────────────────────────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function xss($v){ return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'); }

$name    = xss($_POST['name']    ?? '');
$phone   = xss($_POST['phone']   ?? '');
$email   = xss($_POST['email']   ?? '');
$message = xss($_POST['message'] ?? '');

if (!$name || !$email || !$message) {
    echo json_encode(['success'=>false,'message'=>'Please fill in all required fields.']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>'Please enter a valid email address.']); exit;
}

// ── HTML email body ───────────────────────────────────────────
$html_body = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#f5f7fa;padding:20px;'>
<div style='max-width:560px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;'>
  <div style='background:#0a7c8c;padding:22px 28px;'>
    <h2 style='color:#fff;margin:0;font-size:18px;letter-spacing:0.3px;'>New Contact Form Message</h2>
    <p style='color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:13px;'>GLOBALLINK WHOLESALE LIMITED</p>
  </div>
  <div style='padding:28px;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='font-size:14px;color:#2d3748;'>
      <tr>
        <td style='padding:8px 0;width:90px;font-weight:600;color:#718096;vertical-align:top;'>Name</td>
        <td style='padding:8px 0;font-weight:500;'>".htmlspecialchars($name)."</td>
      </tr>
      <tr style='border-top:1px solid #f0f4f8;'>
        <td style='padding:8px 0;font-weight:600;color:#718096;vertical-align:top;'>Phone</td>
        <td style='padding:8px 0;'>".htmlspecialchars($phone ?: '—')."</td>
      </tr>
      <tr style='border-top:1px solid #f0f4f8;'>
        <td style='padding:8px 0;font-weight:600;color:#718096;vertical-align:top;'>Email</td>
        <td style='padding:8px 0;'><a href='mailto:".htmlspecialchars($email)."' style='color:#0a7c8c;'>".htmlspecialchars($email)."</a></td>
      </tr>
      <tr style='border-top:1px solid #f0f4f8;'>
        <td style='padding:10px 0;font-weight:600;color:#718096;vertical-align:top;'>Message</td>
        <td style='padding:10px 0;line-height:1.7;'>".nl2br(htmlspecialchars($message))."</td>
      </tr>
    </table>
  </div>
  <div style='background:#f7f8fa;padding:14px 28px;font-size:12px;color:#a0aec0;border-top:1px solid #e2e8f0;'>
    Sent via GLOBALLINK WHOLESALE contact form · Company No. 17104734 · Bedford, UK
  </div>
</div></body></html>";

$plain_body = "Name: $name\nPhone: $phone\nEmail: $email\n\nMessage:\n$message";

// ── Pure-PHP SMTP sender ──────────────────────────────────────
function smtp_send($host, $port, $user, $pass, $from, $from_name, $to, $to_name, $subject, $html, $plain, $reply_to='') {
    $boundary = md5(uniqid(time()));

    // Build MIME message
    $headers  = "From: =?UTF-8?B?".base64_encode($from_name)."?= <$from>\r\n";
    $headers .= "To: =?UTF-8?B?".base64_encode($to_name)."?= <$to>\r\n";
    if ($reply_to) $headers .= "Reply-To: $reply_to\r\n";
    $headers .= "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "X-Mailer: PHP\r\n";
    $headers .= "Date: ".date('r')."\r\n";

    $body  = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($plain))."\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($html))."\r\n";
    $body .= "--$boundary--\r\n";

    $full_message = $headers . "\r\n" . $body;

    // Open SMTP socket (STARTTLS on port 587)
    $errno = 0; $errstr = '';
    $sock = fsockopen($host, $port, $errno, $errstr, 15);
    if (!$sock) throw new Exception("Socket error: $errstr ($errno)");

    function smtp_cmd($sock, $cmd=null, $expect=null) {
        if ($cmd !== null) fwrite($sock, $cmd."\r\n");
        $resp = '';
        while (!feof($sock)) {
            $line = fgets($sock, 512);
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') break; // last line
        }
        if ($expect && substr($resp,0,3) != $expect)
            throw new Exception("SMTP error (expected $expect): ".trim($resp));
        return trim($resp);
    }

    smtp_cmd($sock, null, '220');                              // greeting
    smtp_cmd($sock, "EHLO ".gethostname(), '250');             // EHLO
    smtp_cmd($sock, "STARTTLS", '220');                        // start TLS

    // Upgrade to TLS
    stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    smtp_cmd($sock, "EHLO ".gethostname(), '250');             // re-EHLO after TLS
    smtp_cmd($sock, "AUTH LOGIN", '334');                      // auth
    smtp_cmd($sock, base64_encode($user), '334');              // username
    smtp_cmd($sock, base64_encode($pass), '235');              // password
    smtp_cmd($sock, "MAIL FROM:<$from>", '250');
    smtp_cmd($sock, "RCPT TO:<$to>", '250');
    smtp_cmd($sock, "DATA", '354');

    fwrite($sock, $full_message."\r\n.\r\n");
    smtp_cmd($sock, null, '250');                              // message accepted
    smtp_cmd($sock, "QUIT");
    fclose($sock);
    return true;
}

// ── Send ─────────────────────────────────────────────────────
try {
    smtp_send(
        $smtp_host, $smtp_port,
        $smtp_user, $smtp_pass,
        $mail_from, $mail_from_name,
        $mail_to,   $mail_to_name,
        $mail_subject,
        $html_body, $plain_body,
        $email  // reply-to = visitor's email
    );
    echo json_encode([
        'success' => true,
        'message' => 'Your message has been sent successfully! We will get back to you soon.'
    ]);
} catch (Exception $e) {
    error_log('GLOBALLINK Mailer Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, something went wrong. Please email us directly at Info.glinkltd@on.co.uk'
    ]);
}
