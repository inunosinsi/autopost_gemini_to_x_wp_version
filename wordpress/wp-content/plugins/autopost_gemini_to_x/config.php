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
	$types = array(
		"api_key" => "Gemini API Key",
		"consumer_key" => "X consumer key",
		"consumer_secret" => "X consumer secret",
		"access_token" => "X access token",
		"access_token_secret" => "X access token secret"
	);
	foreach($types as $idx => $label){
		register_setting(
			'autopost_gemini_to_x_settings_group',
			'autopost_gemini_to_x_'.$idx,
			array(
			'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default' => '',
			)
		);
	}

	register_setting(
		'autopost_gemini_to_x_settings_group',     // 設定グループ名
		'autopost_gemini_to_x_prompt',         // データベースに保存する設定キー (テキストフィールド用)
		array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_textarea_field', // 入力値をサニタイズするコールバック関数
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
    foreach($types as $idx => $label){
		add_settings_field(
			'autopost_gemini_to_x_'.$idx,
			$label,
			'autopost_gemini_to_x_'.$idx.'_callback',
			'autopost_gemini_to_x-settings',
			'autopost_gemini_to_x_main_section',
			array(
	 			'label_for' => 'autopost_gemini_to_x_'.$idx,
				'class' => 'autopost_gemini_to_x-row',
			)
		);
	}

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
    echo '<p>Gemini APIキーは<a href="https://ai.google.dev/gemini-api/docs" target="_blank" rel="noopener">Google AI Gemini API |Google AI Studio |Google for Developers  |  Google AI for Developers</a>で生成してください。</p>';
    echo '<p>X APIのキーは<a href="https://developer.x.com/" target="_blank" rel="noopener">X Developers</a>で生成してください。</p>';
}

function autopost_gemini_to_x_api_key_callback() {
	if(!function_exists("get_gemini_api_key")) include_once(__DIR__."/util.php");
    ?>
    <input type="text" id="autopost_gemini_to_x_api_key" name="autopost_gemini_to_x_api_key" value="<?php echo esc_attr( get_gemini_api_key() ); ?>" class="regular-text">
    <?php
}

function autopost_gemini_to_x_consumer_key_callback() {
	if(!function_exists("get_x_consumer_key")) include_once(__DIR__."/util.php");	
    ?>
    <input type="text" id="autopost_gemini_to_x_consumer_key" name="autopost_gemini_to_x_consumer_key" value="<?php echo esc_attr( get_x_consumer_key() ); ?>" class="regular-text">
    <?php
}

function autopost_gemini_to_x_consumer_secret_callback() {
	if(!function_exists("get_x_consumer_secret")) include_once(__DIR__."/util.php");	
    ?>
    <input type="text" id="autopost_gemini_to_x_consumer_secret" name="autopost_gemini_to_x_consumer_secret" value="<?php echo esc_attr( get_x_consumer_secret() ); ?>" class="regular-text">
    <?php
}

function autopost_gemini_to_x_access_token_callback() {
	if(!function_exists("get_x_access_token")) include_once(__DIR__."/util.php");	
    ?>
    <input type="text" id="autopost_gemini_to_x_access_token" name="autopost_gemini_to_x_access_token" value="<?php echo esc_attr( get_x_access_token() ); ?>" class="regular-text">
    <?php
}

function autopost_gemini_to_x_access_token_secret_callback() {
	if(!function_exists("get_x_access_token_secret")) include_once(__DIR__."/util.php");	
    ?>
    <input type="text" id="autopost_gemini_to_x_access_token_secret" name="autopost_gemini_to_x_access_token_secret" value="<?php echo esc_attr( get_x_access_token_secret() ); ?>" class="regular-text">
    <?php
}

function autopost_gemini_to_x_prompt_callback() {
    if(!function_exists("get_prompt_config")) include_once(__DIR__."/util.php");
    ?>
    <textarea id="autopost_gemini_to_x_prompt" name="autopost_gemini_to_x_prompt" style="width:80%;height:400px;"><?php echo esc_attr( get_prompt_config() ); ?></textarea>
    <?php
}
