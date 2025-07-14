<?php
// WordPressのルートディレクトリへのパスを適切に設定してください
// 通常はwp-load.phpがあるディレクトリのパスです
define('WP_USE_THEMES', false); // テーマの読み込みをしない
require_once(dirname(dirname(dirname(__DIR__))).'/wp-load.php');

if(!function_exists("generate")){
	include_once(__DIR__."/generate.php");
}
// 任意のpost_id
$post_id = 1; // 取得したい投稿のIDを設定してください
$post = get_post($post_id);

$gen = generate($post);
$old = get_post_meta( $post_id, '_autopost_gemini_to_x_key', true );

// 値が変更された場合のみ更新
if ( $gen && $gen !== $old ) {
	update_post_meta( $post_id, '_autopost_gemini_to_x_key', $gen );
} elseif ( empty( $gen ) && $old ) {
	// 値が空になった場合は削除
	delete_post_meta( $post_id, '_autopost_gemini_to_x_key' );
}

if(!function_exists("post")){
	include_once(__DIR__."/post.php");
}

$res = post($gen);
var_dump($res["http_code"]);
var_dump($res["error_message"]);
