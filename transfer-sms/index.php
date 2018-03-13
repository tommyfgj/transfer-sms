<?php
include "vendor/autoload.php";
use Tx\Mailer;

function errorRet($msg) {
	$ret['errno'] = -1;
	$ret['msg'] = $msg;
	return json_encode($ret);
}

function decodePdu($pdu) {
	//初始化
    $ch = curl_init();
    //设置抓取的url
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:9000/decode');
    curl_setopt($ch, CURLOPT_POSTFIELDS, "pdu=$pdu");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $data = curl_exec($ch); 
    curl_close($ch);
    return $data;	
}

function sendMail($obj, $count = 0 ) {
	if ($count > 3) {
		return false;
	}
	$ret = (new Mailer())
    ->setServer('smtp.189.cn', 465, 'ssl')
    ->setAuth('', '')
    ->setFrom('', '')
    ->setFakeFrom($obj->sender, $obj->sender."@189.cn") // if u want, a fake name, a fake email
    ->addTo('', '')
    ->setSubject($obj->body)
    ->setBody($obj->body)
	->send();
	if (!$ret) {
		sendMail($obj, ++$count);
	}
	return true;
}

function sc_send($text, $desp = '', $key = '') {
	$postdata = http_build_query(
    array(
        'text' => $text,
        'desp' => $desp
    )
	);

	$opts = array('http' =>
    array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
    	)
	);
	$context  = stream_context_create($opts);
	return $result = file_get_contents('https://sc.ftqq.com/'.$key.'.send', false, $context);
}

$serverKey = '';
$pdu = $_POST['pdu'];
if ($pdu == null) {
	die(errorRet("pdu is null"));
}
$ret = decodePdu($pdu);
$obj = json_decode($ret);

if ($obj->sender == null) {
	die(errorRet("decode pdu fail..."));
}

try {
	$send = sendMail($obj);
} catch (Exception $e) {
	sc_send($obj->sender, $obj->body, $serverKey);
}
