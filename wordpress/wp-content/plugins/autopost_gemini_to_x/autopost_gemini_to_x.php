<?php
/*
Plugin Name: 記事紹介自動投稿プラグインz
Description: Gemini
Version: 1.0
Author: saito
*/

function autopost_gemini_to_x_add_meta_box() {
    add_meta_box(
        'autopost_gemini_to_x',         // メタボックスのHTML ID
        'Gemini',                           // メタボックスのタイトル
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

    // 保存されているカスタムフィールドの値を取得
    //$my_custom_field = get_post_meta( $post->ID, '_my_custom_field_key', true );
    ?>
    <p>
        <label for="autopost_gemini_to_x_id">Geminiによる記事の紹介文を出力したい:</label><br>
    </p>
    <?php
    /**<input type="text" id="my_custom_field_id" name="my_custom_field_name" value="<?php echo esc_attr( $my_custom_field ); ?>" style="width:100%;" />**/
}
