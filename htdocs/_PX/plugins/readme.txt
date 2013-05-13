* このディレクトリは、プラグインをインストールする領域です。
* This directory is for installing plugins.

------
【プラグインの開発規則】

■プラグイン名

- プラグイン名には、次の文字を使用可能。
    a-z, A-Z, 0-9

※本ドキュメント中では、
  プラグイン名が {$plugin_name} に置き換わるものとして記述する。

■ディレクトリ

下記ディレクトリ以下を、プラグイン毎に占有する。

- <plugins>/{$plugin_name}/*

■クラス名

- 接頭辞 "pxplugin" を先頭につける。
- 以降、ディレクトリ階層とファイル名を
  アンダースコア区切りで列記する。

例： <plugins>/samplePlugin/lib/factory.php の場合、class pxplugin_samplePlugin_lib_factory


------
【共通クラス】

以下のクラスは、共通ルールに基づき、PxFWが自動的にロードし、実行します。
すべて、{$plugin_name}ディレクトリ内 register ディレクトリに格納されます。


■info

プラグインに関する情報を管理しています。
現時点では、管理対象となる情報はプラグインのバージョン情報のみです。

- 格納先: <plugins>/{$plugin_name}/register/info.php
- クラス名: pxplugin_{$plugin_name}_register_info
- コンストラクタ引数: なし
- API
-- バージョン番号を取得: $instance->get_version()

下記は実装例。

<!--- ここからサンプルコード --->
<?php

/**
 * PX Plugin "{$plugin_name}"
 */
class pxplugin_{$plugin_name}_register_info{

	/**
	 * プラグインのバージョン情報を取得する
	 * @return string バージョン番号を示す文字列
	 */
	public function get_version(){
		return '1.0.0';
	}

}

?>
<!--- / ここまでサンプルコード --->


■object

PxFWの初期セットアップ処理の中で自動的にインスタンス化され、
以降、$px内部にメンバーとして保持されます。

このオブジェクトにアクセスするには、
次のメソッドを用います。

$obj = $px->get_plugin_object($plugin_name);

- 格納先: <plugins>/{$plugin_name}/register/object.php
- クラス名: pxplugin_{$plugin_name}_register_object
- コンストラクタ引数: $px

下記は実装例。

<!--- ここからサンプルコード --->
<?php

/**
 * PX Plugin "{$plugin_name}"
 */
class pxplugin_{$plugin_name}_register_object{
	private $px;

	/**
	 * コンストラクタ
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct($px){
		$this->px = $px;
	}

}

?>
<!--- / ここまでサンプルコード --->


■initialize

PX=initialize.run の実行時に実行されます。

- 格納先: <plugins>/{$plugin_name}/register/initialize.php
- クラス名: pxplugin_{$plugin_name}_register_initialize
- コンストラクタ引数: $px
- API
-- トリガーメソッド: $instance->execute()
-- ログ出力: $instance->get_logs()
-- エラー出力: $instance->get_errors()

下記は実装例。

<!--- ここからサンプルコード --->
<?php

/**
 * PX Plugin "{$plugin_name}"
 */
class pxplugin_{$plugin_name}_register_initialize{
	private $px;
	private $errors = array();
	private $logs = array();

	/**
	 * コンストラクタ
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct($px){
		$this->px = $px;
	}

	/**
	 * トリガーメソッド
	 * PxFWはインスタンスを作成した後、このメソッドをキックします。
	 * @params int $behavior: 振る舞い。0(既定値)=SQLを実行する|1=SQL文全体を配列として返す|2=SQL全体を文字列として返す
	 * @return 正常終了した場合に true , 異常が発生した場合に false を返します。
	 */
	public function execute($behavior=0){
		$behavior = intval($behavior);
		$sql_srcs = array();

		//  エラーが発生した場合は、
		//  エラーメッセージを出力し、falseを返す。
		/*
		if( $error ){
			$this->error_log('エラーが発生したため、処理を中止しました。',__LINE__);
			$this->log('エラーが発生したため、処理を中止しました。');
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
			$this->px->dbh()->start_transaction();
		}

		$sqls = array();
		foreach( $sql_srcs as $table_name=>$sql_row ){
			foreach( $sql_row as $sql_content ){
				$bind_data = array(
					'table_name'=>$this->px->get_conf('dbms.prefix').'_'.$table_name,
				);
				$sql_final = $this->px->dbh()->bind( $sql_content , $bind_data );
				if( !strlen( $sql_final ) ){ continue; }

				if( !$behavior ){
					if( !$this->px->dbh()->send_query( $sql_final ) ){
						$this->px->error()->error_log('database query error ['.$sql_final.']');
						$this->log('[ERROR] database query error. (see error log)',__LINE__);
						$this->error_log('database query error ['.$sql_final.']',__LINE__);

						//トランザクション：ロールバック
						$this->px->dbh()->rollback();
						return false;
					}else{
						$this->log('database query done.  ['.$sql_final.']',__LINE__);
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
			return implode( "\r\n\r\n\r\n", $sqls );
		}

		//  トランザクション：コミット
		$this->px->dbh()->commit();
		return true;
	}

	/**
	 * エラー取得メソッド
	 * PxFWはinitialize処理が終了した後(=execute()がreturnした後)、
	 * このメソッドを通じてエラー内容を受け取ります。
	 * @return 配列。配列の要素は、message, file, line の3つを持った連想配列。
	 */
	public function get_errors(){
		return $this->errors;
	}

	/**
	 * 内部エラー発行メソッド
	 * 本オブジェクト内部で発生したエラーを受け取り、メンバー変数に記憶します。
	 * ここで記憶したエラー情報は、最終的に get_errors() により引き出されます。
	 */
	private function error_log( $error_message , $line ){
		array_push( $this->errors, array(
			'message'=>$error_message ,
			'file'=>__FILE__ ,
			'line'=>$line ,
		) );
		return true;
	}

	/**
	 * ログ取得メソッド
	 * PxFWはinitialize処理が終了した後(=execute()がreturnした後)、
	 * このメソッドを通じて実行された処理の内容を受け取ります。
	 * @return 配列。
	 */
	public function get_logs(){
		return $this->logs;
	}

	/**
	 * 内部ログ記録メソッド
	 * 本オブジェクト内部で処理した内容をテキストで受け取り、メンバー変数に記憶します。
	 * ここで記憶した情報は、最終的に get_logs() により引き出されます。
	 */
	private function log( $message ){
		array_push( $this->logs, $message );
		return true;
	}

}

?>
<!--- / ここまでサンプルコード --->


■pxcommand

PX=plugins.{$plugin_name} で呼び出せるGUIを実装できます。

- 格納先: <plugins>/{$plugin_name}/register/pxcommand.php
- クラス名: pxplugin_{$plugin_name}_register_pxcommand
- コンストラクタ引数: $px
- API
-- コンストラクタのみ

下記は実装例。

<!--- ここからサンプルコード --->
<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Plugin "{$plugin_name}"
 */
class pxplugin_{$plugin_name}_register_pxcommand extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 * @param $command = PXコマンド配列
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->px = $px;

		$this->homepage();
	}

	/**
	 * ホームページを表示する。
	 */
	private function homepage(){
		$command = $this->get_command();

		$src = '';
		$src .= '<p>サンプル</p>'."\n";

		print $this->html_template($src);
		exit;
	}

}

?>
<!--- / ここまでサンプルコード --->


■outputfilter

最終的なHTMLの出力時に一定の加工処理を加えることができます。

- 格納先: <plugins>/{$plugin_name}/register/outputfilter.php
- クラス名: pxplugin_{$plugin_name}_register_outputfilter
- コンストラクタ引数: $px
- API
-- トリガーメソッド: $instance->execute()

下記は実装例。

<!--- ここからサンプルコード --->
<?php

/**
 * PX Plugin "{$plugin_name}"
 */
class pxplugin_{$plugin_name}_register_outputfilter{
	private $px;

	/**
	 * コンストラクタ
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct( $px ){
		$this->px = $px;
	}

	/**
	 * 変換処理を実行する
	 */
	public function execute($src, $extension){
		/*
			$src には、テーマの処理が完了したあとの
			完成されたHTMLソースが渡されます。
			(ただし、文字コード変換処理の前の状態です)
			このメソッドに、変換処理を実装し、
			変換後のソースを返してください。
			すべてのextensionについて、このメソッドを通ります。
			対象となるextensionの種類は、
			$extensionで受け取ることができます。
		*/
		return $src;
	}

}

?>
<!--- / ここまでサンプルコード --->


■publish

パブリッシュ時、取得したファイルに最終的な加工を加えるAPIです。
この加工処理は、パブリッシュ時に走るので、
パブリッシュ前に効果を確認することはできません。

- 格納先: <plugins>/{$plugin_name}/register/publish.php
- クラス名: pxplugin_{$plugin_name}_register_publish
- コンストラクタ引数: $px, $publish
- API
-- パブリッシュ前処理: $instance->before_execute()
-- ファイル単位の変換処理: $instance->execute()
-- パブリッシュ後処理: $instance->after_execute()
-- パブリッシュ先ディレクトリへのコピー後の処理: $instance->after_copying()

下記は実装例。

<!--- ここからサンプルコード --->
<?php

/**
 * PX Plugin "{$plugin_name}"
 */
class pxplugin_{$plugin_name}_register_publish{
	private $px;
	private $publish;

	/**
	 * コンストラクタ
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct( $px, $publish ){
		$this->px = $px;
		$this->publish = $publish;
	}

	/**
	 * パブリッシュ前処理
	 */
	public function before_execute($path_tmppublish_dir){
		/*
			パブリッシュキューの処理が始まる直前に1回だけコールされます。
			初期のキューのセットが完了した後です。
			引数 $path_tmppublish_dir は、一時パブリッシュディレクトリの絶対パスです。
		*/
		return true;
	}

	/**
	 * ファイル単位の変換処理を実行する
	 */
	public function execute($path, $extension, $publish_type){
		/*
			$path には、パブリッシュしたファイルの絶対パスが渡されます。
			加工処理として、
			まずこのファイルを開き、
			加工を施し、
			保存して閉じるプロセスを実装してください。

			$extension は、ファイルの拡張子が渡されます。
			おそらく、想定される多くのプラグイン処理は、
			特定の拡張子にのみ効果を与えるものでしょう。
			$extension の値を確認して、処理の振り分けを行なってください。

			$publish_type は、パブリッシュの種類が格納されます。
			パブリッシュの種類は、拡張子によって次の何れかが選択されます。
				'http'
					ウェブサーバーを経由して出力されます。
				'include_text'
					インクルードされるテキストファイルに適用されます。
				'copy'
					ウェブサーバーを介さず、単にコピーするのみで処理されます。
		*/
		return true;
	}

	/**
	 * パブリッシュ後処理
	 */
	public function after_execute($path_tmppublish_dir){
		/*
			すべてのキューの処理を完了した後に1回だけコールされます。
			引数 $path_tmppublish_dir は、一時パブリッシュディレクトリの絶対パスです。
		*/
		return true;
	}

	/**
	 * パブリッシュ先ディレクトリへのコピー後の処理
	 */
	public function after_copying($path_publish_dir){
		/*
			すべてのキューの処理を完了し、
			パブリッシュ先ディレクトリへのコピー処理が完了した後に1回だけコールされます。
			引数 $path_publish_dir は、パブリッシュ先ディレクトリの絶対パスです。
		*/
		return true;
	}

}

?>
<!--- / ここまでサンプルコード --->


