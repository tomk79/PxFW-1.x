<?php
class px_cores_user{
	private $px;

	private $login_user_info = null;

	/**
	 * コンストラクタ
	 */
	public function __construct( &$px ){
		$this->px = &$px;
	}

	/**
	 * $dao_userを生成する
	 */
	private function &factory_dao_user(){
		$class_name_dao_user = $this->px->load_pxclass( '/daos/user.php' );
		if( !$class_name_dao_user ){
			return false;
		}
		$dao_user = new $class_name_dao_user( &$this->px );
		return $dao_user;
	}

	/**
	 * ログイン状態を更新する
	 * @return true
	 */
	public function update_login_status( $user_account , $user_pw ){
		$is_login = false;
		$is_starting = false;
		$dao_user = &$this->factory_dao_user();

		//  ログイン判定
		if( strlen( $user_account ) && strlen( $user_pw ) ){
			//  IDとパスワードを入力して新しくログインを試みる場合
			$tmp_user_info = $dao_user->get_user_info_by_account( $user_account );
			if( $this->crypt_user_password( $user_pw ) == $tmp_user_info['user_pw'] ){
				$is_login = true;
				$is_starting = true;
			}
		}elseif( strlen( $this->px->req()->get_session('USER_ID') ) && $this->px->req()->get_session('USER_EXPIRE') > time() ){
			$is_login = true;
			$tmp_user_info = $dao_user->get_user_info( $this->px->req()->get_session('USER_ID') );
		}

		if( $is_login ){
			//  ログインが成立したら
			$this->px->req()->set_session('USER_ID',$tmp_user_info['id']);
			$this->px->req()->set_session('USER_EXPIRE',time()+1800);
			$this->login_user_info = $tmp_user_info;
			if( $is_starting ){
				//  ログインが成立したてなら、IDとPWを外してリダイレクトする
				$redirect_to = 'http'.($this->px->req()->is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$this->px->dbh()->get_realpath( $this->px->get_install_path().$this->px->req()->get_request_file_path() );
				@header( 'Location: '.$redirect_to );
			}
			$dao_user->update_user_login_date( $this->px->req()->get_session('USER_ID') );
		}else{
			//  ログインが不成立なら
			$this->logout();
		}

		return true;
	}//login()

	/**
	 * ユーザーがログインしているか否か調べる
	 * @return true|false
	 */
	public function is_login(){
		if( strlen( $this->px->req()->get_session('USER_ID') ) && $this->px->req()->get_session('USER_EXPIRE') > time() ){
			return true;
		}
		return false;
	}//is_login()

	/**
	 * ログインユーザーのアカウント名を取得する
	 * @return string
	 */
	public function get_login_user_account(){
		return $this->login_user_info['user_account'];
	}//get_login_user_account()

	/**
	 * ログインユーザーのユーザー名を取得する
	 * @return string
	 */
	public function get_login_user_name(){
		return $this->login_user_info['user_name'];
	}//get_login_user_name()

	/**
	 * ログインユーザーのメールアドレスを取得する
	 * @return string
	 */
	public function get_login_user_email(){
		return $this->login_user_info['user_email'];
	}//get_login_user_email()

	/**
	 * 明示的にログアウトする
	 * @return true|false
	 */
	public function logout(){
		$this->px->req()->delete_session('USER_ID');
		$this->px->req()->delete_session('USER_EXPIRE');
		$this->login_user_info = null;
		return true;
	}//logout()

	/**
	 * パブリッシュツールか否か調べる
	 * @return true|false
	 */
	public function is_publishtool(){
		$val = strpos( $_SERVER['HTTP_USER_AGENT'] , 'PicklesCrawler' );
		if( $val !== false && $val >= 0 ){
			return true;
		}
		return false;
	}//is_publishtool()

	/**
	 * ユーザパスワードを暗号化する
	 */
	public function crypt_user_password( $password ){
		return	md5( $password );
	}

}
?>