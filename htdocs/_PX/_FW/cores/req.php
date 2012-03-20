<?php
class px_cores_req{
	private $px;
	private $param = array();
	private $flg_cmd = false;
	private $request_file_path;

	/*
	 *  初期化
	 */
	public function __construct( &$px ){
		$this->px = &$px;
		$this->parse_input();
		$this->request_file_path = $_SERVER['PATH_INFO'];
		if (preg_match('/\/$/', $this->request_file_path)) {
			$this->request_file_path .= 'index.html';
		}
	}

	/*
	 *	$_POSTと$_GETで受け取った情報を、ハッシュ$inに結合する。
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

	#----------------------------------------------------------------------------
	#	入力値に対する標準的な変換事項
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

	public function get_param( $key ){
		return $this->param[$key];
	}//get_param()

	public function set_param( $key , $val ){
		$this->param[$key] = $val;
		return true;
	}//set_param()

	public function get_request_file_path(){
		return $this->request_file_path;
	}//request_file_path()

	//--------------------
	//  SSL通信か調べる
	public function is_ssl(){
		if( $_SERVER['HTTP_SSL'] || $_SERVER['HTTPS'] ){
			#	SSL通信が有効か否か判断
			return true;
		}
		return false;
	}

}
?>