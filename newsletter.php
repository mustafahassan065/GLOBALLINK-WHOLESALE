<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// ── CONFIG ───────────────────────────────────────────────
$smtp_host  = 'smtp.gmail.com';
$smtp_port  = 587;
$smtp_user  = 'mustafaprogrammer786@gmail.com';
$smtp_pass  = 'jfcuemlfkdqtcyqx';   // same App Password
$mail_to    = 'Info@glinkltd.co.uk';
$mail_subj  = 'New Newsletter Subscriber – GLOBALLINK WHOLESALE';
// ─────────────────────────────────────────────────────────

header('Content-Type: application/json');

$sub_email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$sub_email) {
    echo json_encode(['success'=>false,'message'=>'Please enter a valid email address.']); exit;
}

$html = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#f5f7fa;padding:20px;'>
<div style='max-width:480px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;'>
  <div style='background:#0a7c8c;padding:20px 28px;'>
    <h2 style='color:#fff;margin:0;font-size:17px;'>New Newsletter Subscriber</h2>
    <p style='color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:12px;'>GLOBALLINK WHOLESALE LIMITED</p>
  </div>
  <div style='padding:24px 28px;font-size:14px;color:#2d3748;'>
    <p>A new user has subscribed to your newsletter:</p>
    <p style='background:#f0fff4;border:1px solid #9ae6b4;border-radius:4px;padding:12px 16px;font-weight:600;color:#276749;'>".htmlspecialchars($sub_email)."</p>
  </div>
  <div style='background:#f7f8fa;padding:12px 28px;font-size:12px;color:#a0aec0;border-top:1px solid #e2e8f0;'>
    GLOBALLINK WHOLESALE LIMITED · Company No. 17104734 · Bedford, UK
  </div>
</div></body></html>";

$plain = "New Newsletter Subscriber: $sub_email";

function smtp_send_nl($host,$port,$user,$pass,$from,$to,$subj,$html,$plain){
    $b = md5(uniqid());
    $hdrs  = "From: GLOBALLINK WHOLESALE <$from>\r\nTo: $to\r\n";
    $hdrs .= "Subject: =?UTF-8?B?".base64_encode($subj)."?=\r\n";
    $hdrs .= "MIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$b\"\r\nDate: ".date('r')."\r\n";
    $body  = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($plain))."\r\n";
    $body .= "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($html))."\r\n--$b--\r\n";
    $sock = fsockopen($host,$port,$e,$s,15);
    if(!$sock) throw new Exception("Socket: $s");
    function r($sock,$cmd=null,$ex=null){
        if($cmd!==null) fwrite($sock,"$cmd\r\n");
        $resp='';
        while(!feof($sock)){$l=fgets($sock,512);$resp.=$l;if(isset($l[3])&&$l[3]===' ')break;}
        if($ex&&substr($resp,0,3)!=$ex) throw new Exception("Expected $ex got: ".trim($resp));
        return trim($resp);
    }
    r($sock,null,'220'); r($sock,"EHLO ".gethostname(),'250');
    r($sock,"STARTTLS",'220');
    stream_socket_enable_crypto($sock,true,STREAM_CRYPTO_METHOD_TLS_CLIENT);
    r($sock,"EHLO ".gethostname(),'250');
    r($sock,"AUTH LOGIN",'334'); r($sock,base64_encode($user),'334');
    r($sock,base64_encode($pass),'235');
    r($sock,"MAIL FROM:<$from>",'250'); r($sock,"RCPT TO:<$to>",'250');
    r($sock,"DATA",'354');
    fwrite($sock,$hdrs."\r\n".$body."\r\n.\r\n");
    r($sock,null,'250'); r($sock,"QUIT"); fclose($sock);
}

try {
    smtp_send_nl($smtp_host,$smtp_port,$smtp_user,$smtp_pass,$smtp_user,$mail_to,$mail_subj,$html,$plain);
    echo json_encode(['success'=>true,'message'=>'Thank you for subscribing!']);
} catch(Exception $e){
    error_log('Newsletter error: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Something went wrong. Please try again.']);
}
