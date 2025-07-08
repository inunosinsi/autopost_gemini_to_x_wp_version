<?php
// 管理画面メニューにプラグインの設定ページを追加する関数
function autopost_gemini_to_x_menu() {
    add_options_page(
        '記事紹介プラグイン By Gemini',           // ページのタイトル
        '記事紹介プラグイン',                    // メニューに表示されるテキスト
        'manage_options',               // このページにアクセスするために必要な権限
        'autopost_gemini_to_x-settings',           // ページのユニークなスラッグ
        'autopost_gemini_to_x_page_html'  // ページの内容を表示する関数
    );
}
add_action( 'admin_menu', 'autopost_gemini_to_x_menu' );

// 設定ページの内容を表示する関数
function autopost_gemini_to_x_page_html() {
    // ユーザーがページにアクセスできる権限があるか確認
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // エラーメッセージがあれば表示
    //settings_errors();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // Settings APIの隠しフィールドを挿入
            settings_fields( 'autopost_gemini_to_x_settings_group' ); // register_settingで登録したグループ名
            // 登録された設定セクションとフィールドを表示
            do_settings_sections( 'autopost_gemini_to_x-settings' ); // add_options_pageで登録したページスラッグ
            // 保存ボタンを表示
            submit_button( '設定を保存' );
            ?>
        </form>
    </div>
    <?php
}

// プラグインの設定を登録する関数
function autopost_gemini_to_x_settings_init() {
    // -------------------------------------------------------------------
    // 1. 設定グループを登録
    // このグループに属する設定は一括で保存・検証される
    // -------------------------------------------------------------------
    register_setting(
        'autopost_gemini_to_x_settings_group',     // 設定グループ名
        'autopost_gemini_to_x_api_key',         // データベースに保存する設定キー (テキストフィールド用)
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field', // 入力値をサニタイズするコールバック関数
            'default' => '',
        )
    );

    register_setting(
        'autopost_gemini_to_x_settings_group',     // 設定グループ名
        'autopost_gemini_to_x_prompt',         // データベースに保存する設定キー (テキストフィールド用)
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field', // 入力値をサニタイズするコールバック関数
            'default' => '',
        )
    );

    // -------------------------------------------------------------------
    // 2. 設定セクションを登録
    // 設定ページ内で設定フィールドをグループ化する
    // -------------------------------------------------------------------
    add_settings_section(
        'autopost_gemini_to_x_main_section',        // セクションのID
        'プラグインの設定',                      // セクションのタイトル
        'autopost_gemini_to_x_main_section_callback', // セクションの簡単な説明を表示する関数
        'autopost_gemini_to_x-settings'             // 設定ページのユニークなスラッグ
    );

    // -------------------------------------------------------------------
    // 3. 設定フィールドを登録
    // 各入力フィールドを特定のセクションに関連付ける
    // -------------------------------------------------------------------
    add_settings_field(
        'autopost_gemini_to_x_api_key',          // フィールドのID (HTMLのID属性にもなる)
        'Gemini API Key',                  // フィールドのラベル
        'autopost_gemini_to_x_api_key_callback', // フィールドのHTMLを表示する関数
        'autopost_gemini_to_x-settings',            // 設定ページのユニークなスラッグ
        'autopost_gemini_to_x_main_section',        // このフィールドが属するセクションのID
        array(
            'label_for' => 'autopost_gemini_to_x_api_key', // ラベルとinputの関連付け用
            'class' => 'autopost_gemini_to_x-row',
        )
    );

    add_settings_field(
        'autopost_gemini_to_x_prompt',          // フィールドのID (HTMLのID属性にもなる)
        'プロンプト',                  // フィールドのラベル
        'autopost_gemini_to_x_prompt_callback', // フィールドのHTMLを表示する関数
        'autopost_gemini_to_x-settings',            // 設定ページのユニークなスラッグ
        'autopost_gemini_to_x_main_section',        // このフィールドが属するセクションのID
        array(
            'label_for' => 'autopost_gemini_to_x_prompt', // ラベルとinputの関連付け用
            'class' => 'autopost_gemini_to_x-row',
        )
    );
}
add_action( 'admin_init', 'autopost_gemini_to_x_settings_init' );

// 設定セクションの説明を表示するコールバック関数
function autopost_gemini_to_x_main_section_callback() {
    echo '<p>記事紹介プラグイン By Geminiの設定を行います。</p>';
}

// テキスト入力フィールドのHTMLを表示するコールバック関数
function autopost_gemini_to_x_api_key_callback() {
    // データベースから現在の値を取得
    $value = get_option( 'autopost_gemini_to_x_api_key', '' ); // デフォルト値は空文字列
    ?>
    <input type="text" id="autopost_gemini_to_x_api_key" name="autopost_gemini_to_x_api_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
    <?php
}

function autopost_gemini_to_x_prompt_callback() {
    // データベースから現在の値を取得
    $value = get_option( 'autopost_gemini_to_x_prompt', '' ); // デフォルト値は空文字列
    $value = trim($value);
    if(!strlen($value)){
    	$value = file_get_contents(__DIR__."/template/prompt_sample.txt");
    }
    ?>
    <textarea id="autopost_gemini_to_x_prompt" name="autopost_gemini_to_x_prompt" style="width:80%;height:400px;"><?php echo esc_attr( $value ); ?></textarea>
    <?php
}
