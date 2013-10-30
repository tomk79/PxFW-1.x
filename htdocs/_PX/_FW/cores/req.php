<?php
/**
 * PxFW core object class: Request Manager
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_cores_req{
	private $px;
	private $param = array();
	private $dynamic_path_param = array();
	private $flg_cmd = false;
	private $request_file_path;

	/**
	 *  コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
		$this->parse_input();
		$this->request_file_path = $_SERVER['PATH_INFO'];
		if (!strlen($this->request_file_path)) {
			$this->request_file_path = '/';
		}
		if (preg_match('/\/$/', $this->request_file_path)) {
			$this->request_file_path .= $this->px->get_directory_index_primary();
		}
		$this->session_start();
	}//__construct()

	/**
	 *	入力パラメータを解析する。
	 */
	private function parse_input(){
		if( !array_key_exists( 'REMOTE_ADDR' , $_SERVER ) ){
			//  コマンドラインからの実行か否か判断
			$this->flg_cmd = true;//コマンドラインから実行しているかフラグ
			if( is_array( $_SERVER['argv'] ) && count( $_SERVER['argv'] ) ){
				foreach( $_SERVER['argv'] as $argv_line ){
					foreach( explode( '&' , $argv_line ) as $argv_unit ){
						preg_match( '/^(.*?)=(.*)$/ism' , $argv_unit , $argv_preg_result );
						if( array_key_exists( 1 , $argv_preg_result ) && strlen( $argv_preg_result[1] ) ){
							$_GET[urldecode($argv_preg_result[1])] = urldecode($argv_preg_result[2]);
						}else{
							//↓PicklesFramework0.2.4 追記
							$_GET[$argv_unit] = '';
						}
					}
				}
				unset( $argv_line , $argv_preg_result );
			}
		}

		if( ini_get('magic_quotes_gpc') ){
			#	PHPINIのmagic_quotes_gpc設定がOnだったら、
			#	エスケープ文字を削除。
			foreach( array_keys( $_GET ) as $Line ){
				$_GET[$Line] = t::stripslashes( $_GET[$Line] );
			}
			foreach( array_keys( $_POST ) as $Line ){
				$_POST[$Line] = t::stripslashes( $_POST[$Line] );
			}
		}

		$_GET = t::convert_encoding( $_GET );
		$_POST = t::convert_encoding( $_POST );
		$param = array_merge( $_GET , $_POST );
		$param = $this->input_default_convert( $param );//PxFW 0.6.1 別メソッドに分離

		if( is_array( $_FILES ) ){
			$FILES_KEYS = array_keys( $_FILES );
			foreach($FILES_KEYS as $Line){
				$_FILES[$Line]['name'] = t::convert_encoding( $_FILES[$Line]['name'] );
				$_FILES[$Line]['name'] = mb_convert_kana( $_FILES[$Line]['name'] , 'KV' , mb_internal_encoding() );
				$param[$Line] = $_FILES[$Line];
			}
		}

		$this->param = $param;
		unset($param);
		return	true;
	}//parse_input()

	/**
	 *	入力値に対する標準的な変換事項
	 */
	private function input_default_convert( $param ){
		#	PxFW 0.6.1 追加。0:04 2009/05/30
		$is_callable_mb_check_encoding = is_callable( 'mb_check_encoding' );
		foreach( $param as $key=>$val ){
			#	URLパラメータを加工
			if( is_array( $val ) ){
				#	配列なら
				$param[$key] = $this->input_default_convert( $param[$key] );
			}elseif( is_string( $param[$key] ) ){
				#	文字列なら
				$param[$key] = mb_convert_kana( $param[$key] , 'KV' , mb_internal_encoding() );
					//半角カナは全角に統一
				$param[$key] = preg_replace( '/\r\n|\r|\n/' , "\n" , $param[$key] );
					//改行コードはLFに統一
				if( $is_callable_mb_check_encoding ){
					#	PxFW 0.6.6 : 追加
					#	不正なバイトコードのチェック
					if( !mb_check_encoding( $key , mb_internal_encoding() ) ){
						#	キーの中に見つけたらパラメータごと削除
						unset( $param[$key] );
					}
					if( !mb_check_encoding( $param[$key] , mb_internal_encoding() ) ){
						#	値の中に見つけたら false に置き換える
						$param[$key] = false;
					}
				}
			}
		}
		return $param;
	}//input_default_convert()

	/**
	 * ダイナミックパスからパラメータを受け取る
	 */
	public function get_path_param( $key ){
		return $this->dynamic_path_param[$key];
	}//get_path_param()

	/**
	 * ダイナミックパスからのパラメータをセットする
	 */
	public function set_path_param( $key , $val ){
		$this->dynamic_path_param[$key] = $val;
		return true;
	}//set_path_param()

	/**
	 * パラメータを取得する
	 */
	public function get_param( $key ){
		return $this->param[$key];
	}//get_param()

	/**
	 * パラメータをセットする
	 */
	public function set_param( $key , $val ){
		$this->param[$key] = $val;
		return true;
	}//set_param()

	/**
	 * パラメータをすべて取得する
	 */
	public function get_all_params(){
		return $this->param;
	}

	/**
	 * クッキー情報を取得
	 */
	public function get_cookie( $key ){
		return	$_COOKIE[$key];
	}//get_cookie()

	/**
	 * クッキー情報をセット
	 */
	public function set_cookie( $key , $val , $expire = null , $path = null , $domain = null , $secure = false ){
		if( is_null( $path ) ){
			$path = $this->px->get_install_path();
			if( !strlen( $path ) ){
				$path = '/';
			}
		}
		if( !@setcookie( $key , $val , $expire , $path , $domain , $secure ) ){
			return false;
		}

		$_COOKIE[$key] = $val;//現在の処理からも呼び出せるように
		return true;
	}//set_cookie()

	/**
	 * クッキー情報を削除
	 */
	public function delete_cookie( $key ){
		if( !@setcookie( $key , null ) ){
			return false;
		}
		unset( $_COOKIE[$key] );
		return true;
	}//delete_cookie()

	/**
	 * セッションを開始
	 */
	private function session_start( $sid = null ){
		$expire = intval($this->px->get_conf('system.session_expire'));
		$cache_limiter = 'nocache';
		$session_name = 'PXSID';
		if( strlen( $this->px->get_conf('system.session_name') ) ){
			$session_name = $this->px->get_conf('system.session_name');
		}
		$path = $this->px->get_install_path();

		session_name( $session_name );
		session_cache_limiter( $cache_limiter );
		session_cache_expire( intval($expire/60) );

		if( intval( ini_get( 'session.gc_maxlifetime' ) ) < $expire + 10 ){
			#	ガベージコレクションの生存期間が
			#	$expireよりも短い場合は、上書きする。
			#	バッファは固定値で10秒。
			ini_set( 'session.gc_maxlifetime' , $expire + 10 );
		}

		session_set_cookie_params( 0 , $path );
			//  セッションクッキー自体の寿命は定めない(=0)
			//  そのかわり、SESSION_LAST_MODIFIED を新設し、自分で寿命を管理する。

		if( strlen( $sid ) ){
			#	セッションIDに指定があれば、有効にする。
			session_id( $sid );
		}

		#	セッションを開始
		$RTN = @session_start();

		#	セッションの有効期限を評価
		if( strlen( $this->get_session( 'SESSION_LAST_MODIFIED' ) ) && intval( $this->get_session( 'SESSION_LAST_MODIFIED' ) ) < intval( time() - $expire ) ){
			#	セッションの有効期限が切れていたら、セッションキーを再発行。
			if( is_callable('session_regenerate_id') ){
				@session_regenerate_id( true );
			}
		}
		$this->set_session( 'SESSION_LAST_MODIFIED' , time() );
		return $RTN;
	}//session_start()

	/**
	 * セッションIDを取得
	 */
	public function get_session_id(){
		return session_id();
	}//get_session_id()

	/**
	 * セッション情報を取得
	 */
	public function get_session( $key ){
		return $_SESSION[$key];
	}//get_session()

	/**
	 * セッション情報をセット
	 */
	public function set_session( $key , $val ){
		$_SESSION[$key] = $val;
		return true;
	}//set_session()

	/**
	 * セッション情報を削除
	 */
	public function delete_session( $key ){
		unset( $_SESSION[$key] );
		return true;
	}//delete_session()


	/**
	 * アップロードされたファイルをセッションに保存
	 */
	public function save_uploadfile( $key , $ulfileinfo ){
		// base64でエンコードして、バイナリデータを持ちます。
		// $ulfileinfo['content'] にバイナリを格納して渡すか、
		// $ulfileinfo['tmp_name'] または $ulfileinfo['path'] のいずれかに、
		// アップロードファイルのパスを指定してください。
		$fileinfo = array();
		$fileinfo['name'] = $ulfileinfo['name'];
		$fileinfo['type'] = $ulfileinfo['type'];

		if( $ulfileinfo['content'] ){
			$fileinfo['content'] = base64_encode( $ulfileinfo['content'] );
		}else{
			$filepath = '';
			if( @is_file( $ulfileinfo['tmp_name'] ) ){
				$filepath = $ulfileinfo['tmp_name'];
			}elseif( @is_file( $ulfileinfo['path'] ) ){
				$filepath = $ulfileinfo['path'];
			}else{
				return false;
			}
			$fileinfo['content'] = base64_encode( file_get_contents( $filepath ) );
		}
		$_SESSION['FILE'][$key] = $fileinfo;
		return	true;
	}
	/**
	 * セッションに保存されたファイル情報を取得
	 */
	public function get_uploadfile( $key , $option = array() ){
		if(!strlen($key)){ return false; }

		$RTN = $_SESSION['FILE'][$key];
		if( is_null( $RTN ) ){ return false; }

		$RTN['content'] = base64_decode( $RTN['content'] );
		return	$RTN;
	}
	/**
	 * セッションに保存されたファイル情報の一覧を取得
	 */
	public function get_uploadfile_list(){
		return	array_keys( $_SESSION['FILE'] );
	}
	/**
	 * セッションに保存されたファイルを削除
	 */
	public function delete_uploadfile( $key ){
		unset( $_SESSION['FILE'][$key] );
		return	true;
	}
	/**
	 * セッションに保存されたファイルを全て削除
	 */
	public function delete_uploadfile_all(){
		return	$this->delete_session( 'FILE' );
	}


	/**
	 * リクエストパスを取得する
	 */
	public function get_request_file_path(){
		return $this->request_file_path;
	}//get_request_file_path()

	/**
	 *  SSL通信か調べる
	 */
	public function is_ssl(){
		if( $_SERVER['HTTP_SSL'] || $_SERVER['HTTPS'] ){
			#	SSL通信が有効か否か判断
			return true;
		}
		return false;
	}

	/**
	 * コマンドラインによる実行か確認する。
	 */
	public function is_cmd(){
		if( array_key_exists( 'REMOTE_ADDR' , $_SERVER ) ){
			return false;
		}
		return	true;
	}

}

?>