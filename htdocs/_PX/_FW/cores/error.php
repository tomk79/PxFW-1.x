<?php
/**
 * class px_cores_error
 * 
 * PxFWのコアオブジェクトの1つ `$error` のオブジェクトクラスを定義します。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
/**
 * PxFW core object class: Error Handler
 * 
 * PxFWのコアオブジェクトの1つ `$error` のオブジェクトクラスです。
 * このオブジェクトは、PxFWの初期化処理の中で自動的に生成され、`$px` の内部に格納されます。
 * 
 * メソッド `$px->error()` を通じてアクセスします。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_cores_error{
	/**
	 * $pxオブジェクト
	 */
	private $px;

	/**
	 * コンストラクタ
	 * 
	 * @param object $px $pxオブジェクト
	 */
	public function __construct( $px ){
		$this->px = $px;
	}

	/**
	 * エラーメッセージをログに追記する。
	 * 
	 * @param string $error_message エラーメッセージ
	 * @param string $error_file エラー発生スクリプトファイルのパス
	 * @param int $error_line エラー発生ファイルの行番号
	 * @return bool 成功時 `true`、失敗時 `false`
	 */
	public function error_log( $error_message = null , $error_file = null , $error_line = null ){
		if( !strlen( $this->px->get_conf('paths.error_log') ) ){ return false; }
		return error_log(
			date('Y-m-d H:i:s')
			.'	'.$error_message
			.'	'.$error_file
			.'	'.$error_line
			."\r\n" , 3 , $this->px->get_conf('paths.error_log') );
	}//error_log()

}

?>