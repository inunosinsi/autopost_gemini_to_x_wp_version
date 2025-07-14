<?php
require_once __DIR__.'/vendor/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

function post(string $text){
	// 文字数制限　@ToDo いずれは有償版対応を追加したい
	if(mb_strlen($text) >= 280){
		return array(
			"http_code" => 400,
			"error_message" => "Exceeded character limit for X"
		);
	}
		
	// X APIに接続
	$conn = new TwitterOAuth(
		get_x_consumer_key(),
		get_x_consumer_secret(),
		get_x_access_token(),
		get_x_access_token_secret()
	);
	$conn->setApiVersion('2');
	
	$res = $conn->post(
	    'tweets', 
	    ['text' => $text],
	    ['jsonPayload'=>true]
	);

	$httpCode = $conn->getLastHttpCode();
	if($httpCode == 201) {
		return array(
			"http_code" => $httpCode,
			"error_message" => ""
		);
	} else {
		if(isset($res->errors)){
			$errorMessage = json_encode($res->errors, JSON_UNESCAPED_UNICODE);
		}else if(isset($res->title)){
			$errorMessage = $res->title;
		}else{
			$errorMessage = "unknown error";
		}
		
		return array(
			"http_code" => $httpCode,
			"error_message" => $errorMessage
		);			
	}
}
