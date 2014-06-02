<?php
/**
 * class px_cores_user
 * 
 * PxFWのコアオブジェクトの1つ `$user` のオブジェクトクラスを定義します。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
/**
 * PxFW core object class: User Manager
 * 
 * PxFWのコアオブジェクトの1つ `$user` のオブジェクトクラスです。
 * このオブジェクトは、PxFWの初期化処理の中で自動的に生成され、`$px` の内部に格納されます。
 * 
 * メソッド `$px->user()` を通じてアクセスします。
 * 
 * このオブジェクトはユーザー情報を管理するためにRDBMSを利用します。
 * この機能を使うために、次の初期設定を行ってください。
 * 
 * 1. コンフィグの `dbms` ディレクティブに、データベースサーバーの情報を設定してください。
 * 2. ?PX=initialize を実行し、データベースの初期構築を行ってください。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_cores_user{
	/**
	 * $pxオブジェクト
	 */
	private $px;

	/**
	 * ログイン中のユーザーの情報
	 */
	private $login_user_info = null;

	/**
	 * コンストラクタ
	 * 
	 * @param object $px $pxオブジェクト
	 */
	public function __construct( $px ){
		$this->px = $px;
	}

	/**
	 * インスタンス $dao_user を生成する。
	 *
	 * @return object px_daos_user のインスタンス
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
	 * ログイン状態を更新する。
	 * 
	 * @param string $user_account ユーザーアカウント名
	 * @param string $user_pw ユーザーパスワード
	 * @return bool 常に `true` を返します。
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
	 * ユーザーがログインしているか否か調べる。
	 * 
	 * @return bool ログイン状態のとき `true`、ログインしていない場合に `false` を返します。
	 */
	public function is_login(){
		if( strlen( $this->px->req()->get_session('USER_ID') ) && $this->px->req()->get_session('USER_EXPIRE') > time() ){
			return true;
		}
		return false;
	}//is_login()

	/**
	 * ログインユーザーのIDを取得する。
	 * 
	 * @return string ユーザーID
	 */
	public function get_login_user_id(){
		return $this->login_user_info['id'];
	}//get_login_user_id()

	/**
	 * ログインユーザーのアカウント名を取得する。
	 * 
	 * @return string ユーザーアカウント名
	 */
	public function get_login_user_account(){
		return $this->login_user_info['user_account'];
	}//get_login_user_account()

	/**
	 * ログインユーザーのユーザー名を取得する。
	 * 
	 * @return string ユーザー名
	 */
	public function get_login_user_name(){
		return $this->login_user_info['user_name'];
	}//get_login_user_name()

	/**
	 * ログインユーザーのメールアドレスを取得する。
	 * 
	 * @return string ユーザーのメールアドレス
	 */
	public function get_login_user_email(){
		return $this->login_user_info['user_email'];
	}//get_login_user_email()

	/**
	 * ログインユーザーの認証レベルを取得する。
	 * 
	 * @return int ユーザーの認証レベル
	 */
	public function get_login_user_auth_level(){
		return intval($this->login_user_info['auth_level']);
	}//get_login_user_auth_level()

	/**
	 * ユーザーが最後にパスワードを変更した日時を取得する。
	 * @return int ユーザーが最後にパスワードを変更した日時のUNIXタイムスタンプ
	 */
	public function get_login_user_set_pw_date(){
		return $this->login_user_info['set_pw_date'];
	}//get_login_user_set_pw_date()

	/**
	 * 明示的にログアウトする。
	 * 
	 * @return bool 常に `true` を返します。
	 */
	public function logout(){
		$this->px->req()->delete_session('USER_ID');
		$this->px->req()->delete_session('USER_EXPIRE');
		$this->login_user_info = null;
		return true;
	}//logout()

	/**
	 * パブリッシュツールか否か調べる。
	 * 
	 * @return bool ユーザーがパブリッシュツールの場合に `true`、それ以外の場合に `false` を返します。
	 */
	public function is_publishtool(){
		$val = strpos( $_SERVER['HTTP_USER_AGENT'] , 'PicklesCrawler' );
		if( $val !== false && $val >= 0 ){
			return true;
		}
		return false;
	}//is_publishtool()

	/**
	 * ユーザパスワードを暗号化する。
	 *
	 * @param string $password ユーザーパスワードの平文
	 * @return string 暗号化されたパスワード
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
	 * ワンタイムハッシュを発行する。
	 *
	 * 発行したワンタイムハッシュは、戻り値として受け取れると同時に、ユーザーのセッション領域に保存されます。
	 * 発行されたハッシュは、`$user->use_onetime_hash()` を通じて、1度だけ `true` を得ることができます。
	 * 
	 * @return string ハッシュ文字列
	 */
	public function get_onetime_hash(){
		$str_hash = uniqid();//ハッシュ値を生成
		$this->px->req()->set_session('ONETIMEHASH', $str_hash);//セッションに保存
		return $str_hash;
	}//get_onetime_hash()

	/**
	 * ワンタイムハッシュを使用する。
	 *
	 * `$user->get_onetime_hash()` が発行したワンタイムハッシュを使用します。
	 * この値は、ユーザーのセッションに記憶されています。一度使うと、戻り値として `true` を返し、セッションから削除されます。
	 * 従って、同じワンタイムハッシュでは1度しか `true` を得られません。
	 * 
	 * @param string $str_hash ハッシュ文字列
	 * @return bool 成功時に `true`、ワンタイムハッシュが無効な場合や、使用済みなどで、失敗した場合に `false` を返します。
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