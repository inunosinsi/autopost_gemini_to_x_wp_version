<?php
function get_option_common(string $key){
	return get_option( 'autopost_gemini_to_x_'.$key, '' );
}

function get_gemini_api_key(){
	return get_option_common("api_key");
}

function get_x_consumer_key(){
	return get_option_common("consumer_key");
}

function get_x_consumer_secret(){
	return get_option_common("consumer_secret");
}

function get_x_access_token(){
	return get_option_common("access_token");
}

function get_x_access_token_secret(){
	return get_option_common("access_token_secret");
}

/**
 * @return string
 */
function get_prompt_config(){
	$prompt = get_option( 'autopost_gemini_to_x_prompt', '' ); // デフォルト値は空文字列
    $prompt = trim($prompt);
    if(!strlen($prompt)){
    	$prompt = file_get_contents(__DIR__."/template/prompt_sample.txt");
    }
    return $prompt;
}
