<?php
require_once __DIR__.'/vendor/autoload.php';

use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

/**
 * 記事タイトルと記事URLからXのポスト用の紹介文を生成する
 * @param post
 * @return string
 */
function generate($post){
	if(!function_exists("get_gemini_api_key")){
		include_once(__DIR__."/util.php");
	}

	$prompt = get_prompt_config();
	if(is_numeric(strpos($prompt, "##CONTENT##"))){
		$prompt = str_replace("##CONTENT##", str_replace("\n", "", $post->post_content), $prompt);
	}
 
	if(is_numeric(strpos($prompt, "##URL##"))){
		$prompt = str_replace("##URL##", get_permalink( $post->ID ), $prompt);
	}
 
	$client = new Client(get_gemini_api_key());
	try{
		$resp = $client->generativeModel("gemini-2.5-flash")->generateContent(
		    new TextPart($prompt),
		);
		return $resp->text();
	}catch(Exception $e){
		//
	}
}
