<?php
function get_gemini_api_key(){
	return get_option( 'autopost_gemini_to_x_api_key', '' ); // デフォルト値は空文字列	
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
