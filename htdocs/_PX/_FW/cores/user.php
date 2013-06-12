<?php
/**
 * PxFW core object class: User Manager
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_cores_user{
	private $px;

	private $login_user_info = null;

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
	}

	/**
	 * $dao_userを生成する
	 */
	public function &factory_dao_user(){
		$class_name_dao_user = $this->px->load_px_class( '/daos/user.php' );
		if( !$class_name_dao_user ){
			return false;
		}
		$dao_user = new $class_name_dao_user( $this->px );
		return $dao_user;
	}

	/**
	 * ログイン状態を更新する
	 * @return true
	 */
	public function update_login_status( $user_account , $user_pw ){
		$expire = intval($this->px->get_conf('system.session_expire'));
		$is_login = false;
		$is_starting = false;
		$dao_user = $this->factory_dao_user();

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
			$this->px->req()->set_session('USER_EXPIRE',time()+$expire);
			$this->login_user_info = $tmp_user_info;
			if( $is_starting ){
				//  ログインが成立したてなら、IDとPWを外してリダイレクトする
				$redirect_to = 'http'.($this->px->req()->is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$this->px->theme()->href( $this->px->req()->get_request_file_path() );
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
	 * ログインユーザーのIDを取得する
	 * @return string
	 */
	public function get_login_user_id(){
		return $this->login_user_info['id'];
	}//get_login_user_id()

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
	 * ログインユーザーの認証レベルを取得する
	 * @return int
	 */
	public function get_login_user_auth_level(){
		return intval($this->login_user_info['auth_level']);
	}//get_login_user_auth_level()

	/**
	 * ユーザーが最後にパスワードを変更した日時を取得する
	 * @return int
	 */
	public function get_login_user_set_pw_date(){
		return $this->login_user_info['set_pw_date'];
	}//get_login_user_set_pw_date()

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
		// [2013-05-31]
		// ハッシュ化アルゴリズムを md5 から、
		// より信頼性の高い sha1 に変更した。
		// 次のようにコンフィグにアルゴリズム名を設定できるようにすることも考えたが、
		// 一旦見送った。
		// system.password_hash_algorithm = "md5" ; パスワードのハッシュアルゴリズム
		return	sha1( $password );
	}

	/**
	 * ワンタイムハッシュを発行する
	 */
	public function get_onetime_hash(){
		$str_hash = uniqid();//ハッシュ値を生成
		$this->px->req()->set_session('ONETIMEHASH', $str_hash);//セッションに保存
		return $str_hash;
	}//get_onetime_hash()

	/**
	 * ワンタイムハッシュを使用する
	 */
	public function use_onetime_hash($str_hash){
		if(!strlen($str_hash)){
			return false;
		}
		if( $this->px->req()->get_session('ONETIMEHASH') !== $str_hash ){
			return false;
		}
		$this->px->req()->delete_session( 'ONETIMEHASH' );//ハッシュをクリアする
		return true;
	}//use_onetime_hash()

}

?>