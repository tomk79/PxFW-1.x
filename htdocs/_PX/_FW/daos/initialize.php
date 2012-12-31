<?php
$this->load_px_class('/bases/dao.php');

/**
 * 初期化処理のDAO
 **/
class px_daos_initialize extends px_bases_dao{

	private $errors = array();
	private $logs = array();

	/**
	 * ユーザー関連テーブルを作成する。
	 * @params int $behavior: 振る舞い。0(既定値)=SQLを実行する|1=SQL文全体を配列として返す|2=SQL全体を文字列として返す
	 * @return $behavior=0 の場合、SQLを実行した結果の成否(bool), $behavior=1 の場合、1つのSQL文を1要素として持つ配列, $behavior=2 の場合、全SQL文を結合した文字列としてのSQL
	 */
	public function create_user_tables($behavior=0){
		$behavior = intval($behavior);

		$sql = array();

		//--------------------------------------
		//  user: ユーザーマスターテーブル
		ob_start();?>
<?php if( $this->px->get_conf('dbms.dbms') == 'postgresql' ){ ?>
CREATE TABLE :D:table_name(
    id    VARCHAR NOT NULL UNIQUE,
    user_account    VARCHAR NOT NULL UNIQUE,
    user_pw    VARCHAR NOT NULL,
    user_name    VARCHAR,
    user_email    VARCHAR,
    auth_level    INT2 NOT NULL DEFAULT '0',
    tmp_pw    VARCHAR,
    tmp_email    VARCHAR,
    tmp_data    TEXT,
    login_date    TIMESTAMP DEFAULT 'NOW',
    set_pw_date    TIMESTAMP DEFAULT 'NOW',
    create_date    TIMESTAMP DEFAULT 'NOW',
    update_date    TIMESTAMP DEFAULT 'NOW',
    delete_date    TIMESTAMP DEFAULT 'NOW',
    delete_flg    INT2 NOT NULL DEFAULT '0'
);
<?php }else{ ?>
CREATE TABLE :D:table_name(
    id    VARCHAR(64) NOT NULL UNIQUE,
    user_account    VARCHAR(64) NOT NULL UNIQUE,
    user_pw    VARCHAR(32) NOT NULL,
    user_name    VARCHAR(128),
    user_email    VARCHAR(128),
    auth_level    INT(1) NOT NULL DEFAULT '0',
    tmp_pw    VARCHAR(32),
    tmp_email    VARCHAR(128),
    tmp_data    TEXT,
    login_date    DATETIME DEFAULT NULL,
    set_pw_date    DATETIME DEFAULT NULL,
    create_date    DATETIME DEFAULT NULL,
    update_date    DATETIME DEFAULT NULL,
    delete_date    DATETIME DEFAULT NULL,
    delete_flg    INT(1) NOT NULL DEFAULT '0'
);
<?php } ?>
<?php
		$sql['user'] = array();
		array_push( $sql['user'] , @ob_get_clean() );

		if( !$behavior ){
			//  トランザクション：スタート
			$this->px->dbh()->start_transaction();
		}

		$rtn_sql = array();
		foreach( $sql as $table_name=>$sql_row ){
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
					array_push( $rtn_sql , $sql_final );
				}
				unset($sql_final);
			}
		}

		if( !$behavior ){
			//  トランザクション：コミット
			$this->px->dbh()->commit();
			return true;
		}
		if( $behavior === 1 ){
			return $rtn_sql;
		}
		if( $behavior === 2 ){
			return implode( "\r\n\r\n\r\n", $rtn_sql );
		}

		//  想定外の$behavior
		return false;
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