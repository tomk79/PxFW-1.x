<?php
/**
 * class px_daos_user
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
$this->load_px_class('/bases/dao.php');

/**
 * ユーザー関連情報のDAO
 * 
 * `px_daos_user` は、ユーザーに関する情報を管理するオブジェクトクラスです。
 * 特に、ユーザーの登録処理、登録情報の変更処理、退会処理など、書き込み系の処理を行います。
 * 
 * このオブジェクトは、`$px->user()->factory_dao_user()` から生成、取得することができます。
 * 
 * <pre>&lt;?php
 * $dao_user = $px-&gt;user()-&gt;factory_dao_user();
 * ?&gt;</pre>
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_daos_user extends px_bases_dao{

	/**
	 * 前回挿入したユーザーID
	 */
	private $last_insert_user_id = null;

	/**
	 * 新規ユーザーを作成する。
	 * 
	 * @param array $user_info ユーザー情報を格納した連想配列
	 * @return bool 成功時 'true'、失敗時 `false` を返します。
	 */
	public function create_user( $user_info ){
		if( !$this->validate_user_account( $user_info['user_account'] ) ){
			//  ユーザーアカウントの形式が不正
			return false;
		}

		if( !strlen( $user_info['user_name'] ) ){
			//  ユーザー名が指定されていない
			return false;
		}

		$user_preset = $this->get_user_info_by_account($user_info['user_account']);
		if($user_preset['user_account']==$user_info['user_account']){
			//  アカウント名が既に登録されている場合
			return false;
		}

		if( !strlen( $user_info['user_pw'] ) ){
			//  パスワードが指定されていない
			return false;
		}

		if( !strlen( $user_info['auth_level'] ) ){
			//  auth_levelが指定されていない場合、1でフォーマット。
			$user_info['auth_level'] = 1;
		}

		$id = uniqid();//ユニークなIDを生成。

		ob_start();?>
INSERT INTO :D:table_name (
	id,
	user_account,
	user_pw,
	user_name,
	user_email,
	auth_level,
	tmp_pw,
	tmp_email,
	tmp_data,
	set_pw_date,
	create_date,
	update_date,
	login_date,
	delete_flg
)VALUES(
	:S:id,
	:S:user_account,
	:S:user_pw,
	:S:user_name,
	:S:user_email,
	:N:auth_level,
	:S:tmp_pw,
	:S:tmp_email,
	:S:tmp_data,
	:S:now,
	:S:now,
	:S:now,
	:S:login_date,
	:N:delete_flg
);
<?php
		$sql = @ob_get_clean();
		$bind_data = array(
			'table_name'=>$this->px->get_conf('dbms.prefix').'_user',
			'id'=>$id,
			'user_account'=>$user_info['user_account'],
			'user_pw'=>$this->px->user()->crypt_user_password( $user_info['user_pw'] ),
			'user_name'=>$user_info['user_name'],
			'user_email'=>$user_info['user_email'],
			'auth_level'=>intval($user_info['auth_level']),
			'tmp_pw'=>null,
			'tmp_email'=>null,
			'tmp_data'=>null,
			'now'=>$this->px->dbh()->int2datetime( time() ),
			'delete_flg'=>0,
		);
		$sql = $this->px->dbh()->bind( $sql , $bind_data );
		$res = $this->px->dbh()->send_query( $sql );
		if( !$res ){
			return false;
		}
		$value = $this->px->dbh()->get_results();

		#	INSERT した、ユーザCDを記憶
		$this->last_insert_user_id = $id;

		$this->px->dbh()->commit();

		return true;
	}

	/**
	 * 直前に作成したユーザのユーザIDを取得する。
	 *
	 * @return string|null ユーザーID。挿入していない場合 `null` を返します。
	 */
	public function get_last_insert_user_id(){
		return	$this->last_insert_user_id;
	}

	/**
	 * 全登録ユーザ数を得る。
	 * 
	 * @return int ユーザー数
	 */
	public function get_user_count(){
		$sql = 'SELECT count(*) AS count FROM :D:table_name WHERE delete_flg = 0;';
		$bind_data = array(
			'table_name'=>$this->px->get_conf('dbms.prefix').'_user',
		);
		$sql = $this->px->dbh()->bind( $sql , $bind_data );
		$res = $this->px->dbh()->send_query( $sql );
		$values = $this->px->dbh()->get_results();

		return	intval( $values[0]['count'] );
	}//get_user_count();

	/**
	 * ユーザー情報を取得する。
	 * 
	 * @param string $id ユーザーID
	 * @return array ユーザー情報。ユーザーが存在しない場合 `null` を返します。
	 */
	public function get_user_info( $id ){
		$sql = 'SELECT * FROM :D:table_name WHERE id = :S:id AND delete_flg = 0;';
		$bind_data = array(
			'table_name'=>$this->px->get_conf('dbms.prefix').'_user',
			'id'=>$id,
		);
		$sql = $this->px->dbh()->bind( $sql , $bind_data );
		$res = $this->px->dbh()->send_query( $sql );
		$value = $this->px->dbh()->get_results();
		$rtn = $value[0];

		return $rtn;
	}//get_user_info()

	/**
	 * ユーザーアカウントからユーザー情報を取得する。
	 *
	 * @param string $account ユーザーアカウント名
	 * @return array ユーザー情報を格納した連想配列
	 */
	public function get_user_info_by_account( $account ){
		$sql = 'SELECT * FROM :D:table_name WHERE user_account = :S:account AND delete_flg = 0;';
		$bind_data = array(
			'table_name'=>$this->px->get_conf('dbms.prefix').'_user',
			'account'=>$account,
		);
		$sql = $this->px->dbh()->bind( $sql , $bind_data );
		$res = $this->px->dbh()->send_query( $sql );
		$value = $this->px->dbh()->get_results();
		$rtn = $value[0];

		return $rtn;
	}//get_user_info()

	/**
	 * ユーザーが存在するかどうか調べる。
	 *
	 * @param string $id ユーザーID
	 * @return bool 存在する場合 `true`、存在しない場合 `false` を返します。
	 */
	public function is_user( $id ){
		$user_info = $this->get_user_info($id);
		if( !is_array( $user_info ) || !strlen($user_info['id']) ){
			return false;
		}
		return true;
	}//is_user()

	/**
	 * ユーザー情報を更新する。
	 *
	 * @param string $id 対象のユーザーID
	 * @param array $user_info ユーザー情報
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function update_user_info( $id , $user_info ){
		if( !$this->is_user( $id ) ){ return false; }

		if( strlen( $user_info['user_pw'] ) && !strlen( $user_info['user_account'] ) ){
			#	パスワードの変更を要求した場合は、
			#	user_account が必須になる。
			$this->px->error()->error_log( 'パスワードを変更する場合は、ユーザアカウント名を必ず指定する必要があります。' , __FILE__ , __LINE__ );
			return	false;
		}

		ob_start();
?>
UPDATE :D:table_name
SET
	user_name = :S:user_name, 
	user_account = :S:user_account,
	user_email = :S:user_email, 
<?php if( !is_null( $user_info['user_pw'] ) ){ ?>
	user_pw = :S:user_pw,
	set_pw_date = :S:now,
<?php } ?>
	update_date = :S:now
WHERE id = :S:id;
<?php
		$sql = ob_get_clean();
		$bind_data = array(
			'table_name'=>$this->px->get_conf('dbms.prefix').'_user',
			'id'=>$id,
			'user_account'=>$user_info['user_account'],
			'user_name'=>$user_info['user_name'],
			'user_email'=>$user_info['user_email'],
			'user_pw'=>$this->px->user()->crypt_user_password( $user_info['user_pw'] ),
			'now'=>$this->px->dbh()->int2datetime( time() ),
		);
		$sql = $this->px->dbh()->bind( $sql , $bind_data );
		$res = $this->px->dbh()->send_query( $sql );
		if( !$res ){ return false; }
		$value = $this->px->dbh()->get_results();
		$this->px->dbh()->commit();
		return true;
	}//update_user_info()

	/**
	 * 最終ログイン日時を更新する。
	 * 
	 * @param string $id 対象のユーザーID
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function update_user_login_date( $id ){
		if( !$this->is_user( $id ) ){ return false; }

		ob_start();
?>
UPDATE :D:table_name
SET
	login_date = :S:now
WHERE id = :S:id;
<?php
		$sql = ob_get_clean();
		$bind_data = array(
			'table_name'=>$this->px->get_conf('dbms.prefix').'_user',
			'id'=>$id,
			'now'=>$this->px->dbh()->int2datetime( time() ),
		);
		$sql = $this->px->dbh()->bind( $sql , $bind_data );
		$res = $this->px->dbh()->send_query( $sql );
		if( !$res ){ return false; }
		$value = $this->px->dbh()->get_results();
		$this->px->dbh()->commit();
		return true;
	}//update_user_login_date()

	/**
	 * ユーザーを論理削除する。
	 * 
	 * @param string $id 対象のユーザーID
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function delete_user( $id ){
		if( !$this->is_user( $id ) ){ return false; }

		ob_start();
?>
UPDATE :D:table_name
SET
	delete_flg = 1,
	delete_date = :S:now,
	update_date = :S:now
WHERE id = :S:id;
<?php
		$sql = ob_get_clean();
		$bind_data = array(
			'table_name'=>$this->px->get_conf('dbms.prefix').'_user',
			'id'=>$id,
			'now'=>$this->px->dbh()->int2datetime( time() ),
		);
		$sql = $this->px->dbh()->bind( $sql , $bind_data );
		$res = $this->px->dbh()->send_query( $sql );
		if( !$res ){ return false; }
		$value = $this->px->dbh()->get_results();
		$this->px->dbh()->commit();
		return true;
	}//delete_user()

	/**
	 * ユーザーアカウントの形式をチェックする
	 * 
	 * @param string $user_account ユーザーアカウント名
	 * @return bool 形式が正しい場合 `true`、それ以外の場合 `false` を返します。
	 */
	public function validate_user_account( $user_account ){
		if( strlen( $user_account ) > 32 ){
			//  文字数オーバー
			return false;
		}
		if( !preg_match( '/^[a-zA-Z0-9\-\_\.\@]+$/s' , $user_account ) ){
			//  使えない文字を含んでいる
			return false;
		}
		return true;
	}//validate_user_account()

}

?>