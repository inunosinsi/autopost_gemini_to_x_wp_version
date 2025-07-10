<?php
function autopost_gemini_to_x_add_meta_box() {
    add_meta_box(
        'autopost_gemini_to_x',         // メタボックスのHTML ID
        '記事紹介プラグイン By Gemini',                           // メタボックスのタイトル
        'autopost_gemini_to_x_meta_box_callback', // メタボックスの内容を表示するコールバック関数
        'post',                               // メタボックスを表示する投稿タイプ (post, page, custom_post_type など)
        'normal',                             // メタボックスを表示するコンテキスト (normal, advanced, side)
        'high'                                // メタボックスの優先度 (high, core, default, low)
    );
}
add_action( 'add_meta_boxes', 'autopost_gemini_to_x_add_meta_box' );

/**
 * メタボックスの内容を表示
 * @param WP_Post $post 現在の投稿オブジェクト
 */
function autopost_gemini_to_x_meta_box_callback( $post ) {
	// セキュリティ対策: Nonceフィールドを追加
	//wp_nonce_field( basename( __FILE__ ), 'my_post_enhancer_nonce' );

	$gen = get_post_meta( $post->ID, '_autopost_gemini_to_x_key', true );
	echo $gen;
}

/**
 * 投稿保存時にカスタムフィールドのデータを保存
 * @param int $post_id 現在の投稿ID
 */
function autopost_gemini_to_x_save_postdata( $post_id, $post, $update ) {
    // Nonceが設定されていない、または不正な場合は処理を中止
    /**if ( ! isset( $_POST['my_post_enhancer_nonce'] ) || ! wp_verify_nonce( $_POST['my_post_enhancer_nonce'], basename( __FILE__ ) ) ) {
        // return $post_id;
    }**/

    // 自動保存またはリビジョンの場合は処理を中止
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }

    // ユーザー権限の確認
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    // 入力値の取得とサニタイズ
    include_once(__DIR__."/generate.php");
	$gen = generate($post);

    // 古い値を取得
    $old = get_post_meta( $post_id, '_autopost_gemini_to_x_key', true );

    // 値が変更された場合のみ更新
	if ( $gen && $gen !== $old ) {
		update_post_meta( $post_id, '_autopost_gemini_to_x_key', $gen );
	} elseif ( empty( $gen ) && $old ) {
		// 値が空になった場合は削除
		delete_post_meta( $post_id, '_autopost_gemini_to_x_key' );
	}
}
add_action( 'save_post', 'autopost_gemini_to_x_save_postdata', 10, 3 );
