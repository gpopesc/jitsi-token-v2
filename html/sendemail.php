<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require("./PHPMailer/src/Exception.php");
require("./PHPMailer/src/PHPMailer.php");
require("./PHPMailer/src/SMTP.php");

$first_name = $_POST['first_name']; // required
$room_name = $_POST['room_name']; // required
$email = $_POST['email']; // required
$gravatar = $_POST['gravatar']; //optional
$meetingtime = $_POST['meetingtime']; //optional
$duration = $_POST['duration']; //required

$room_name = str_replace(" ","", $room_name);
$t = strtotime ($meetingtime);


if(strlen($meetingtime) < 10) {
	$t = strtotime("now");
  }

//Get variables from docker
$jitsi_server=getenv('JITSI_SERVER');
#$jwt_alg=getenv('JWT_ALG');
$jwt_secret=getenv('JWT_SECRET');
$jwt_iss=getenv('JWT_ISS');
$jwt_aud=getenv('JWT_AUD');
$TZ=getenv('TZ');
$jwt_sub=getenv('JWT_SUB');
$email_server=getenv('EMAIL_SERVER');
$email_port= (int) getenv('EMAIL_PORT');
$smtpauth=getenv('SMTPAUTH');
$smtpsecure=getenv('SMTPSECURE');
$email_username=getenv('EMAIL_USERNAME');
$email_pass=getenv('EMAIL_PASS');
$sender_email=getenv('SENDER_EMAIL');
$sender_name=getenv('SENDER_NAME');
$email_signature=getenv('EMAIL_SIGNATURE');
$yrl_username=getenv('YRL_USERNAME');
$yrl_password=getenv('YRL_PASSWORD');
$yrl_api_url=getenv('YRL_API_URL');
$jwt_server_name=getenv('JWT_SERVER_NAME');

// Get jwt
$exp = $t + ($duration * 60);

$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
function generate_jwt($headers, $payload ) {
	global $jwt_secret;
	$headers_encoded = base64url_encode(json_encode($headers));
	
	$payload_encoded = base64url_encode(json_encode($payload));
	
	$signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $jwt_secret, true);
	$signature_encoded = base64url_encode($signature);
	
	$jwt = "$headers_encoded.$payload_encoded.$signature_encoded";
	
	return $jwt;
}
function base64url_encode($str) {
    return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
}
$headers = array('alg'=>'HS256','typ'=>'JWT');
$payload = array(
	'aud'=> $jwt_aud,
	'iss'=> $jwt_iss,
	'sub'=> $jwt_sub,
	'room'=> $room_name,
	'iat' => strtotime("now"),
	'exp'=> $exp,
	'context' => array(
/*		'features'=> array(
			'recording'=> true,
			'livestreaming'=> true,
			'screen-sharing'=> true,
		), */
		'user' => array(
			'name' => $first_name,
			'email' => $email,
			'avatar' => $gravatar,
     )));

$jwt = generate_jwt($headers, $payload);


// Full meeting link
$link = $jitsi_server.$room_name."?jwt=".$jwt;

// YOURLS : API querry
$username = $yrl_username;
$password = $yrl_password;
$url     = $link;                     // URL to shrink
$keyword = '';                        // optional keyword
$title   = '';                       // optional, if omitted YOURLS will lookup title with an HTTP request
$format  = 'simple';                   // output format: 'json', 'xml' or 'simple'
$api_url = $yrl_api_url;

$ch = curl_init();
curl_setopt( $ch, CURLOPT_URL, $api_url );
curl_setopt( $ch, CURLOPT_HEADER, 0 );            // No header in the result
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // Return, do not echo result
curl_setopt( $ch, CURLOPT_POST, 1 );              // This is a POST request
curl_setopt( $ch, CURLOPT_POSTFIELDS, array(      // Data to POST
    'url'      => $url,
    'keyword'  => $keyword,
    'title'    => $title,
    'format'   => $format,
    'action'   => 'shorturl',
    'username' => $username,
    'password' => $password
) );
$yrl = curl_exec($ch);
curl_close($ch);


// Build the ics file

function dateToCal($time) {
	return date('Ymd\THis', $time);
}
$UTC = (date('Ymd\THis', strtotime("now")).'Z');
 

$ical = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN
X-WR-TIMEZONE:'.$TZ.'
BEGIN:VEVENT
CREATED:'.$UTC.'
DTSTAMP:'.$UTC.'
LAST-MODIFIED:'.$UTC.'
DTEND;TZID='.$TZ.':' . dateToCal($exp) . '
UID:' . md5('ID '.$UTC) . '
LOCATION:' . addslashes('online Jitsi meeting') . '
DESCRIPTION:' . $jitsi_server.$room_name . '
X-ALT-DESC;FMTTYPE=text/html:'. 'Jitsi meeting'.$jitsi_server.$room_name. '
URL;VALUE=URI: '.$jitsi_server.$room_name . '
SUMMARY:' . addslashes('meeting with '.$email_signature ). '
DTSTART;TZID='.$TZ.':' . dateToCal($t) .'
STATUS:CONFIRMED
END:VEVENT
END:VCALENDAR';

//$ical = (string)$ical;

// print it out email 
$alt_email_body = "Hello $first_name,\n\nHere it is your jitsi meeting requested link:\n ".$yrl." \n\n Participant link: ". $jitsi_server.$room_name ." \n\nHave a nice day!\n $email_signature";
$email_body_1 = "<h4>Hello $first_name,</h4><br>".PHP_EOL;
$email_body_2 = "<div>Here it is your jitsi meeting requested link:</div><br> <div><a href=".$link."> Meeting link</a></div><div><a href=".$yrl.">".$yrl."<a></div> <br>".PHP_EOL;
$email_body_3 = "<div>Participant link: </div><div><a href=".$jitsi_server.$room_name.">".$jitsi_server.$room_name."</a></div><br>".PHP_EOL;
$email_body_4 = "<div>Have a nice day! </div> <div><strong>$email_signature</strong></div>".PHP_EOL;
$email_body = $email_body_1.$email_body_2.$email_body_3.$email_body_4;

//send email
$mail = new PHPMailer();
$mail->IsSMTP();
//$mail->SMTPDebug = SMTP::DEBUG_SERVER;
$mail->Mailer = "smtp";
$mail->Host = $email_server;
$mail->Port = $email_port; // 8025, 587 and 25 can also be used. Use Port 465 for SSL.
$mail->SMTPAuth = $smtpauth;
$mail->SMTPSecure = $smtpsecure;
$mail->Username = $email_username;
$mail->Password = $email_pass;

$mail->From = "$sender_email";
$mail->FromName = "$sender_name";
$mail->AddAddress("$email", "$first_name");
$mail->AddReplyTo("$sender_email", "$sender_name");

$mail->Subject = "jitsi meeting link for room: $room_name ";
$mail->IsHTML(true);
$mail->Body = "$email_body";
$mail->AltBody = "$alt_email_body";
$mail->WordWrap = 50;
$mail->AddStringAttachment("$ical", "filename.ics", "base64", "text/calendar; charset=utf-8; method=REQUEST");

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Jitsi token generator</title>
	<!-- CSS FOR STYLING THE PAGE -->
	<style>
	h4 {
	    margin: auto;
		color: white;
	    border:1px solid #000;
	    background: black;
	    padding: 10px;
	    max-width: 850px;
	    border-radius: 10px;
	    text-align: center;}

	p{
	    word-break: break-all;

		margin: 0 auto 0 auto;
	    padding: 20px;
	    max-width: 850px;
	    border-radius: 10px;
	    text-align: center;
	}
	.area {
	    margin-top: 20px;
	    margin-left: auto;
	    margin-right:auto;
		border:1px solid #000;
	    padding: 20px;
	    max-width: 850px;
	    border-radius: 10px;
	}
	.text {
	    font-size: 1.1rem;
	    font-weight: bold;
		padding:5px;
		margin-top: 5px;
		margin-bottom: 0;
	}    
    .valid {
        
		padding: 20px;
	    max-width: 850px;
	    border-radius: 10px;
	    text-align: left;
	}
	</style>
</head>
<body>
     <?php
     echo "<h4>Hello ".$first_name."</br></h4>";

    // print it out screen
    echo "<div class=area><strong>Your generated Jitsi meeting link is:</strong><br>";
    echo "<p><a href=$link>$link</a></p>";
	echo "<strong>Access short link: </strong><br>";
    echo "<p><a href=$yrl>$yrl</a></p></div>";

    echo "<div class=area><strong>Jitsi meeting link for participants is:</strong><br>";
    echo "<p><a href= $jitsi_server$room_name>$jitsi_server$room_name</a><br></p></div>";

	echo "<div class=area><strong>Token is valid until: </strong> <br>";
	echo "<div class=valid>Meeting end date: ".(date('Y.m.d \a\t H:i:s',$exp)). "<br></div></div>";
    
    if(!$mail->Send()) {
    echo "<div class=area>Message was not sent.";
    echo 'Mailer error: ' . $mail->ErrorInfo."</br></div>";
    exit;
    } else {
    echo "<div><p class=text>Links have been sent to your email<br></p>";
    echo "<p class=text><a href=$jwt_server_name> Generate new JITSI meeting access link</a></p></div>";}
     ?>


</body>
</html>
