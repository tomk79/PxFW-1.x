# Pickles Framework : plugins directory

このディレクトリは、プラグインをインストールする領域です。<br />
This directory is for installing plugins.



## プラグインの開発規則

###プラグイン名

プラグイン名には、次の文字を使用可能です。

- `a-z`
- `A-Z`
- `0-9`

※本ドキュメント中では、プラグイン名が `{$plugin_name}` に置き換わるものとして記述します。

### ディレクトリ

下記ディレクトリ以下を、プラグイン毎に占有します。

- &lt;plugins&gt;/{$plugin_name}/*

※`<plugins>` は、`./_PX/plugins/` を指します。

### クラス名

- 接頭辞 `pxplugin` を先頭につける。
- 以降、ディレクトリ階層とファイル名をアンダースコア区切りで列記する。

例： `<plugins>/samplePlugin/lib/factory.php` の場合、`class pxplugin_samplePlugin_lib_factory`


## 共通クラス

以下のクラスは、共通ルールに基づき、PxFW が自動的にロードし、実行します。
すべて、`{$plugin_name}` ディレクトリ内 `register` ディレクトリに格納されます。


### info

プラグインに関する情報を管理しています。

- 格納先: &lt;plugins&gt;/{$plugin_name}/register/info.php
- クラス名: `pxplugin_{$plugin_name}_register_info`
- コンストラクタ引数: なし
- API
 - バージョン番号を取得: `$instance->get_version()`
 - コンフィグ項目を定義: `$instance->config_define()` (PxFW 1.0.4以降)

下記は実装例:

<pre>&lt;?php
/**
 * class pxplugin_{$plugin_name}_register_info
 */
/**
 * PX Plugin &quot;{$plugin_name}&quot;
 */
class pxplugin_{$plugin_name}_register_info{

	/**
	 * プラグインのバージョン情報を取得する。
	 * 
	 * @return string バージョン番号を示す文字列
	 */
	public function get_version(){
		return '1.0.0';
	}

	/**
	 * コンフィグ項目を定義する。
	 * 
	 * @return array コンフィグ項目を定義する連想配列
	 */
	public function config_define(){
		return array(
			'plugin-{$plugin_name}.config_name'=&gt;
				array(
					'description'=&gt;'(設定項目の説明)',
					'type'=&gt;'string' , // string|bool|realpath
					'required'=&gt;false ,
				) ,
		);
	}

}

?&gt;</pre>


### object

PxFWの初期セットアップ処理の中で自動的にインスタンス化され、以降、`$px`内部にメンバーとして保持されます。

このオブジェクトにアクセスするには、次のメソッドを用います。

> `$obj = $px->get_plugin_object($plugin_name);`

- 格納先: &lt;plugins&gt;/{$plugin_name}/register/object.php
- クラス名: `pxplugin_{$plugin_name}_register_object`
- コンストラクタ引数: `$px`

下記は実装例です。

<pre>&lt;?php
/**
 * class pxplugin_{$plugin_name}_register_object
 */
/**
 * PX Plugin "{$plugin_name}"
 */
class pxplugin_{$plugin_name}_register_object{
	/**
	 * $pxオブジェクト
	 */
	private $px;

	/**
	 * コンストラクタ
	 * 
	 * @param object $px PxFWコアオブジェクト
	 */
	public function __construct($px){
		$this->px = $px;
	}

}

?&gt;</pre>


### initialize

PX=initialize.run の実行時に実行されます。

- 格納先: &lt;plugins&gt;/{$plugin_name}/register/initialize.php
- クラス名: `pxplugin_{$plugin_name}_register_initialize`
- コンストラクタ引数: `$px`
- API
 - トリガーメソッド: `$instance->execute()`
 - ログ出力: `$instance->get_logs()`
 - エラー出力: `$instance->get_errors()`

下記は実装例です。

<pre>&lt;?php
/**
 * class pxplugin_{$plugin_name}_register_initialize
 */
/**
 * PX Plugin &quot;{$plugin_name}&quot;
 */
class pxplugin_{$plugin_name}_register_initialize{
	/**
	 * $pxオブジェクト
	 */
	private $px;
	/**
	 * 内部エラー
	 */
	private $errors = array();
	/**
	 * 内部ログ
	 */
	private $logs = array();

	/**
	 * コンストラクタ
	 * 
	 * @param object $px PxFWコアオブジェクト
	 */
	public function __construct($px){
		$this-&gt;px = $px;
	}

	/**
	 * トリガーメソッド
	 * 
	 * PxFWはインスタンスを作成した後、このメソッドをキックします。
	 * 
	 * @params int $behavior 振る舞い。0(既定値)=SQLを実行する|1=SQL文全体を配列として返す|2=SQL全体を文字列として返す
	 * @return bool 正常終了した場合に `true` , 異常が発生した場合に `false` を返します。
	 */
	public function execute($behavior=0){
		$behavior = intval($behavior);
		$sql_srcs = array();

		//  エラーが発生した場合は、
		//  エラーメッセージを出力し、falseを返す。
		/*
		if( $error ){
			$this-&gt;error_log('エラーが発生したため、処理を中止しました。',__LINE__);
			$this-&gt;log('エラーが発生したため、処理を中止しました。');
			return false;
		}
		*/

		//  テーブル sample_table を作成
		/*
		$sql_srcs['sample_table'] = array();
		array_push( $sql_srcs['sample_table'], 'CREATE TABLE :D:table_name( ...... );' );
		*/


		if( !$behavior ){
			//  トランザクション：スタート
			$this-&gt;px-&gt;dbh()-&gt;start_transaction();
		}

		$sqls = array();
		foreach( $sql_srcs as $table_name=&gt;$sql_row ){
			foreach( $sql_row as $sql_content ){
				$bind_data = array(
					'table_name'=&gt;$this-&gt;px-&gt;get_conf('dbms.prefix').'_'.$table_name,
				);
				$sql_final = $this-&gt;px-&gt;dbh()-&gt;bind( $sql_content , $bind_data );
				if( !strlen( $sql_final ) ){ continue; }

				if( !$behavior ){
					if( !$this-&gt;px-&gt;dbh()-&gt;send_query( $sql_final ) ){
						$this-&gt;px-&gt;error()-&gt;error_log('database query error ['.$sql_final.']');
						$this-&gt;log('[ERROR] database query error. (see error log)',__LINE__);
						$this-&gt;error_log('database query error ['.$sql_final.']',__LINE__);

						//トランザクション：ロールバック
						$this-&gt;px-&gt;dbh()-&gt;rollback();
						return false;
					}else{
						$this-&gt;log('database query done.  ['.$sql_final.']',__LINE__);
					}
				}else{
					array_push( $sqls , $sql_final );
				}
				unset($sql_final);
			}
		}

		foreach( $sqls as $sql ){
		}

		if( $behavior === 1 ){
			return $sqls;
		}
		if( $behavior === 2 ){
			return implode( &quot;\r\n\r\n\r\n&quot;, $sqls );
		}

		//  トランザクション：コミット
		$this-&gt;px-&gt;dbh()-&gt;commit();
		return true;
	}

	/**
	 * エラー取得メソッド
	 * 
	 * PxFWはinitialize処理が終了した後(=execute()がreturnした後)、
	 * このメソッドを通じてエラー内容を受け取ります。
	 * 
	 * @return array message, file, line の3つの値を持った連想配列。
	 */
	public function get_errors(){
		return $this-&gt;errors;
	}

	/**
	 * 内部エラー発行メソッド
	 * 
	 * 本オブジェクト内部で発生したエラーを受け取り、メンバー変数に記憶します。
	 * ここで記憶したエラー情報は、最終的に `get_errors()` により引き出されます。
	 * 
	 * @param string $error_message エラーメッセージ
	 * @param int $line エラーが発生した行番号
	 * @return bool 常に `true` を返します。
	 */
	private function error_log( $error_message , $line ){
		array_push( $this-&gt;errors, array(
			'message'=&gt;$error_message ,
			'file'=&gt;__FILE__ ,
			'line'=&gt;$line ,
		) );
		return true;
	}

	/**
	 * ログ取得メソッド
	 * 
	 * PxFWはinitialize処理が終了した後(=execute()がreturnした後)、
	 * このメソッドを通じて実行された処理の内容を受け取ります。
	 * 
	 * @return array ログ配列
	 */
	public function get_logs(){
		return $this-&gt;logs;
	}

	/**
	 * 内部ログ記録メソッド
	 * 
	 * 本オブジェクト内部で処理した内容をテキストで受け取り、メンバー変数に記憶します。
	 * ここで記憶した情報は、最終的に `get_logs()` により引き出されます。
	 * 
	 * @param string $message ログメッセージ
	 * @return bool 常に `true` を返します。
	 */
	private function log( $message ){
		array_push( $this-&gt;logs, $message );
		return true;
	}

}

?&gt;</pre>


### pxcommand

PX=plugins.{$plugin_name} で呼び出せるGUIを実装できます。

- 格納先: &lt;plugins&gt;/{$plugin_name}/register/pxcommand.php
- クラス名: `pxplugin_{$plugin_name}_register_pxcommand`
- コンストラクタ引数: `$px`
- API
 - コンストラクタのみ

下記は実装例です。

<pre>&lt;?php
/**
 * class pxplugin_{$plugin_name}_register_pxcommand
 */
$this-&gt;load_px_class('/bases/pxcommand.php');

/**
 * PX Plugin &quot;{$plugin_name}&quot;
 */
class pxplugin_{$plugin_name}_register_pxcommand extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 * 
	 * @param array $command PXコマンド名
	 * @param object $px PxFWコアオブジェクト
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this-&gt;px = $px;

		$this-&gt;homepage();
	}

	/**
	 * ホームページを表示する。
	 * 
	 * HTMLを標準出力した後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @return void
	 */
	private function homepage(){
		$command = $this-&gt;get_command();

		$src = '';
		$src .= '&lt;p&gt;サンプル&lt;/p&gt;'.&quot;\n&quot;;

		print $this-&gt;html_template($src);
		exit;
	}

}

?&gt;</pre>


### outputfilter

最終的なHTMLの出力時に一定の加工処理を加えることができます。

- 格納先: &lt;plugins&gt;/{$plugin_name}/register/outputfilter.php
- クラス名: `pxplugin_{$plugin_name}_register_outputfilter`
- コンストラクタ引数: `$px`
- API
 - トリガーメソッド: `$instance->execute()`

下記は実装例です。

<pre>&lt;?php
/**
 * class pxplugin_{$plugin_name}_register_outputfilter
 */
/**
 * PX Plugin &quot;{$plugin_name}&quot;
 */
class pxplugin_{$plugin_name}_register_outputfilter{
	/**
	 * $pxオブジェクト
	 */
	private $px;

	/**
	 * コンストラクタ
	 * 
	 * @param object $px PxFWコアオブジェクト
	 */
	public function __construct( $px ){
		$this-&gt;px = $px;
	}

	/**
	 * 変換処理を実行する。
	 * 
	 * $src には、テーマの処理が完了したあとの
	 * 完成されたHTMLソースが渡されます。
	 * (ただし、文字コード変換処理の前の状態です)
	 * このメソッドに、変換処理を実装し、
	 * 変換後のソースを返してください。
	 * すべてのextensionについて、このメソッドを通ります。
	 * 対象となるextensionの種類は、
	 * $extensionで受け取ることができます。
	 *
	 * @param string $src テーマの処理が完了したあとの完成されたHTMLソース
	 * @param string $extension 拡張子
	 * @return string 加工後のHTMLソース
	 */
	public function execute($src, $extension){

		// ここに処理を記述します。

		return $src;
	}

}

?&gt;</pre>


### extensions

コンテンツの拡張子によって処理内容を切り替える extensions の処理を、プラグインとして定義するAPIです。
Pickles Framework に予め実装されている extension も、プラグインに定義がある場合、優先して適用されます。

- 格納先: &lt;plugins&gt;/{$plugin_name}/register/extensions/{$extension_name}.php
- クラス名: `pxplugin_{$plugin_name}_register_extensions_{$extension_name}`
- コンストラクタ引数: `$px`
- API
 - 拡張子別の出力処理: `$instance->execute($path_content)`

下記は、拡張子 `*.md` の処理をプラグインに移植した実装例です。

<pre>&lt;?php
/**
 * class pxplugin_{$plugin_name}_register_extensions_md
 */
$this-&gt;load_px_class('bases/extension.php');

/**
 * PX Plugin &quot;{$plugin_name}&quot;
 */
class pxplugin_{$plugin_name}_register_extensions_md extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * 
	 * @param string $path_content コンテンツのパス
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		@header('Content-type: text/html; charset=UTF-8');//デフォルトのヘッダー

		$path_cache = $this-&gt;px-&gt;get_conf('paths.px_dir').'_sys/caches/contents/'.urlencode($path_content);
		if(!is_dir(dirname($path_cache))){
			$this-&gt;px-&gt;dbh()-&gt;mkdir( dirname($path_cache) );
		}

		if( !is_file( $path_cache ) || $this-&gt;px-&gt;dbh()-&gt;is_newer_a_than_b( $path_content, $path_cache ) ){
			// キャッシュがない、またはオリジナルコンテンツよりも古い場合
			$src = @file_get_contents( $path_content );

			//  PHP Markdownライブラリをロード
			//  see: http://michelf.ca/projects/php-markdown/
			@require_once( $this-&gt;px-&gt;get_conf('paths.px_dir').'libs/PHPMarkdown/markdown.php' );

			$src = Markdown($src);
			$this-&gt;px-&gt;dbh()-&gt;file_overwrite($path_cache, $src);
		}

		$src = '';
		ob_start();
		$px = $this-&gt;px;
		include( $path_cache );
		$src = ob_get_clean();

		$src = $this-&gt;px-&gt;theme()-&gt;bind_contents( $src );
		$src = $this-&gt;px-&gt;theme()-&gt;output_filter($src, 'html');
		print $src;
		return true;
	}

}

?&gt;</pre>


### publish

パブリッシュ時、取得したファイルに最終的な加工を加えるAPIです。
この加工処理は、パブリッシュ時に走るので、パブリッシュ前に効果を確認することはできません。

- 格納先: &lt;plugins&gt;/{$plugin_name}/register/publish.php
- クラス名: `pxplugin_{$plugin_name}_register_publish`
- コンストラクタ引数: `$px`, `$publish`
- API
 - パブリッシュ前処理: `$instance->before_execute()`
 - ファイル単位の変換処理: `$instance->execute()`
 - パブリッシュ後処理: `$instance->after_execute()`
 - パブリッシュ先ディレクトリへのコピー後の処理: `$instance->after_copying()`

下記は実装例です。

<pre>&lt;?php
/**
 * class pxplugin_{$plugin_name}_register_publish
 */
/**
 * PX Plugin &quot;{$plugin_name}&quot;
 */
class pxplugin_{$plugin_name}_register_publish{
	/**
	 * $pxオブジェクト
	 */
	private $px;
	/**
	 * PXコマンド publish オブジェクト
	 */
	private $publish;

	/**
	 * コンストラクタ
	 * 
	 * @param object $px PxFWコアオブジェクト
	 * @param object $publish PXコマンド publish オブジェクト
	 */
	public function __construct( $px, $publish ){
		$this-&gt;px = $px;
		$this-&gt;publish = $publish;
	}

	/**
	 * パブリッシュ前処理
	 * 
	 * パブリッシュキューの処理が始まる直前に1回だけコールされます。
	 * 初期のキューのセットが完了した後です。
	 * 
	 * @param string $path_tmppublish_dir 一時パブリッシュディレクトリの絶対パス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function before_execute($path_tmppublish_dir){

		//  ここに処理を記述します。

		return true;
	}

	/**
	 * ファイル単位の変換処理を実行する。
	 * 
	 * @param string $path パブリッシュしたファイルの絶対パスが渡されます。
	 * 加工処理として、まずこのファイルを開き、加工を施し、
	 * 保存して閉じるプロセスを実装してください。
	 * 
	 * @param string $extension ファイルの拡張子が渡されます。
	 * おそらく、想定される多くのプラグイン処理は、特定の拡張子にのみ効果を与えるものでしょう。
	 * `$extension` の値を確認して、処理の振り分けを行なってください。
	 * 
	 * @param array $publish_type パブリッシュの種類が格納されます。
	 * パブリッシュの種類は、拡張子によって次の何れかが選択されます。
	 * 
	 * - `http`: ウェブサーバーを経由して出力されます。
	 * - `include_text`: インクルードされるテキストファイルに適用されます。
	 * - `copy`: ウェブサーバーを介さず、単にコピーするのみで処理されます。
	 * 
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function execute($path, $extension, $publish_type){

		//  ここに処理を記述します。

		return true;
	}

	/**
	 * パブリッシュ後処理
	 * 
	 * すべてのキューの処理を完了した後に1回だけコールされます。
	 * 
	 * @param string $path_tmppublish_dir 一時パブリッシュディレクトリの絶対パス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function after_execute($path_tmppublish_dir){

		//  ここに処理を記述します。

		return true;
	}

	/**
	 * パブリッシュ先ディレクトリへのコピー後の処理
	 * 
	 * すべてのキューの処理を完了し、
	 * パブリッシュ先ディレクトリへのコピー処理が完了した後に1回だけコールされます。
	 * 
	 * @param string $path_publish_dir パブリッシュ先ディレクトリの絶対パス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function after_copying($path_publish_dir){

		//  ここに処理を記述します。

		return true;
	}

}

?&gt;</pre>


### funcs

単機能的なAPIを集めたクラスです。

- 格納先: &lt;plugins&gt;/{$plugin_name}/register/funcs.php
- クラス名: `pxplugin_{$plugin_name}_register_funcs`
- コンストラクタ引数: `$px`
- API
 - SSIタグ生成: `$instance->ssi_static_tag()`

下記は実装例です。

<pre>&lt;?php
/**
 * class pxplugin_{$plugin_name}_register_funcs
 */
/**
 * PX Plugin &quot;{$plugin_name}&quot;
 */
class pxplugin_{$plugin_name}_register_funcs{
	/**
	 * $pxオブジェクト
	 */
	private $px;

	/**
	 * コンストラクタ
	 * 
	 * @param object $px PxFWコアオブジェクト
	 */
	public function __construct( $px ){
		$this-&gt;px = $px;
	}

	/**
	 * パブリッシュ時のSSIタグを出力する。
	 * 
	 * `$px->ssi()` からコールされます。
	 * 
	 * @param string $path インクルードするファイルパス(DOCUMENT_ROOT を起点とした絶対パス)
	 * @return string インクルードタグ
	 */
	public function ssi_static_tag( $path ){
		return '&lt;!--#include virtual=&quot;'.htmlspecialchars( $path ).'&quot; --&gt;';
	}//ssi_static_tag()

}

?&gt;</pre>


