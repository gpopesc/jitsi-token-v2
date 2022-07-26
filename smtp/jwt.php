<?php 
$t = strtotime("10.09.2022 10:22:00");
echo($t . "<br>");
echo(date("Y-m-d h-m-s",$t)). "<br>";
// Create token header as a JSON string
$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

function generate_jwt($headers, $payload, $secret = 'samsungs8') {
	$headers_encoded = base64url_encode(json_encode($headers));
	
	$payload_encoded = base64url_encode(json_encode($payload));
	
	$signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
	$signature_encoded = base64url_encode($signature);
	
	$jwt = "$headers_encoded.$payload_encoded.$signature_encoded";
	
	return $jwt;
}
function base64url_encode($str) {
    return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
}
$headers = array('alg'=>'HS256','typ'=>'JWT');
$payload = array(
	'sub'=>'*',
	'name'=>'Gabi',
	'iat' => time(),
	'iss'=>viorelp,
	'aud'=>myapp,
	'room'=>test,
	'exp'=>(time() + 600)
	);

$jwt = generate_jwt($headers, $payload);

echo $jwt;
?>
