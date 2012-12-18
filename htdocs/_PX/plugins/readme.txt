* このディレクトリは、プラグインをインストールする領域です。
* This directory is for installing plugins.

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


【共通クラス】

以下のクラスは、共通ルールに基づき、PxFWが自動的にロードし、実行します。
すべて、{$plugin_name}ディレクトリ内 register ディレクトリに格納されます。

■initialize

PX=initialize.run の実行時に実行されます。

- 格納先: <plugins>/{$plugin_name}/register/initialize.php
- クラス名: pxplugin_{$plugin_name}_register_initialize
- コンストラクタ引数: $px
- API
-- トリガーメソッド: $instance->execute()
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
	 * @return 正常終了した場合に true , 異常が発生した場合に false を返します。
	 */
	public function execute(){
		//  エラーが発生した場合は、
		//  エラーメッセージを出力し、falseを返す。
		/*
		if( $error ){
			$this->error_log('エラーが発生したため、処理を中止しました。',__LINE__);
			return false;
		}
		*/

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

}

?>
<!--- / ここまでサンプルコード --->

