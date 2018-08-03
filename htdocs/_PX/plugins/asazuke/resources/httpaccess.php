<?php

###################################################################################################################
#
#	PxHTTPAccess 1.0.3
#			(HTTPアクセスオブジェクト)
#			Copyright (C)Tomoya Koyanagi, All rights reserved.
#			LastUpdate : 12:07 2011/05/22
#	--------------------------------------
#	このライブラリは、ネットワークを経由したHTTP通信でコンテンツを取得するクラスです。
#	OpenSSLがインストールされている環境では、HTTPSも利用可能です。
#	他のクラスに依存することなく単体で動作することができます。
#	オリジナルのクラス名は PxHTTPAccess となっていますが、
#	環境に合わせて任意に変更しても動作します。
class pxplugin_asazuke_resources_httpaccess{

	var $http_connection_resource = null;//コネクションリソース

	#--------------------------------------
	#	設定
	var $auto_redirect_flg = true;//true=>自動的にリダイレクトを追跡; false=>リダイレクト指示を無視してダウンロード
	var $max_redirect_number = 5;//リダイレクト回数の上限値
	var $redirect_count = 0;//現在のリダイレクト処理回数
	var $stream_timeout = 5;//読み込みのタイムアウト
	var $fread_length = 5000;//fread()が一回に読み込むバイト数。
	#	/ 設定
	#--------------------------------------

	#--------------------------------------
	#	要求ヘッダ
	var $http_url = null;
	var $http_method = 'GET';
	var $http_post_data = null;//POSTメソッドで送信するデータ
	var $http_user_agent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)';//ユーザエージェント文字列
	var $http_referer = null;//リファラを送信する
	var $http_authorization = array('type'=>null,'user'=>null,'passwd'=>null);//ベーシック認証, PxHTTPAccess 1.0.2 : Digest認証を追加,プロパティ名変更
	var $http_request_header_ext = null;//追加するリクエストヘッダ
	var $cookies = array();//クッキーの記憶
	#	/ 要求ヘッダ
	#--------------------------------------

	#--------------------------------------
	#	応答ヘッダ：アクセス結果のメモ
	var $socket_open_error_num = null;//fsockopen() のエラー番号
	var $socket_open_error_msg = null;//fsockopen() のエラーメッセージ
	var $http_response_header = null;//レスポンスヘッダ
	var $http_response_content = null;//コンテンツ領域の実データ
	var $http_response_version = null;//レスポンスのHTTPバージョン
	var $http_response_status_cd = null;//レスポンスステータスコード
	var $http_response_status_msg = null;//レスポンスステータスメッセージ
	var $http_response_content_type = null;//取得したデータのコンテントタイプ
	var $http_response_content_charset = null;//取得したデータの文字コード
	var $http_response_content_length = null;//取得したデータの容量
	var $http_response_redirect_to = null;//リダイレクト先URLのメモ
	var $http_response_time = null;//リクエストから受信完了までにかかった時間(microtime)
	var $http_response_transfer_encoding = null;//HTTP1.1 Transfer-Encoding の値
	var $http_response_last_modified = null;//HTTP1.1 Last-Modified の値 (PxHTTPAccess 1.0.2 追加)
	var $http_response_connection = null;//Connection の値
	var $http_response_www_authenticate = array();//WWW-Authenticate の値 PxHTTPAccess 1.0.2 追加
	var $http_response_all = array();//その他のヘッダー情報を仕舞っておく器 PxHTTPAccess 1.0.3 追加
	#	/ 応答ヘッダ：アクセス結果のメモ
	#--------------------------------------


	###################################################################################################################
	#	ユーザインターフェイス
	#	★save_http_contents() , get_http_contents()は、実際にリモートホストからデータをダウンロードします。
	#	★get_responce() は、実際にソケット接続からデータを読み出し、
	#	　コンテンツ部分とヘッダー部分を解析します。

	#	リファラURL
	function set_http_referer( $url ){
		$this->http_referer = $url; return true;
	}

	#	fread() が1回で読み込むバイト数
	function set_fread_length( $length ){
		$length = intval($length);
		if( $length <= 0 ){ return false; }
		$this->fread_length = intval( $length );
		return true;
	}

	#	取得するURL
	function clear_url(){
		$this->http_url = null;
		return true;
	}
	function set_url( $url ){
		$this->http_url = $url;
		return true;
	}
	function get_url(){
		return $this->http_url;
	}

	#	POSTメソッドで送信するデータ
	function set_post_data( $post_data ){
		$this->http_post_data = $post_data;
		return	true;
	}

	#	コンテンツ読み込みのタイムアウト値
	function set_stream_timeout( $timeout_sec ){
		$this->stream_timeout = intval( $timeout_sec );
		return	true;
	}

	#	メソッド
	function set_method( $value )			{ $this->http_method = strtoupper($value); return true; }
	function get_method()					{ return $this->http_method; }

	#	HTTP_USER_AGENT
	function set_user_agent( $value )		{ $this->http_user_agent = $value; return true; }
	function get_user_agent()				{ return $this->http_user_agent; }

	#	追加で設定する要求ヘッダの操作
	function add_request_header( $value )	{ $this->http_request_header_ext .= $value; return true; }
	function clear_request_header(){
		$this->http_url = null;
		$this->http_method = 'GET';
		$this->http_post_data = null;
		$this->http_referer = null;
		$this->http_authorization = array('type'=>null,'user'=>null,'passwd'=>null);
		$this->http_request_header_ext = null;
		return true;
	}

	#	基本認証情報
	function set_auth_type( $type_name ){
		//PxHTTPAccess 1.0.2 追加
		if( !strlen($type_name) ){
			$this->http_authorization['type'] = null;
			return true;
		}
		switch( strtolower( $type_name ) ){
			case 'basic':
			case 'digest':
				break;
			default:
				return false;
		}
		$this->http_authorization['type'] = $type_name;
		return true;
	}
	function set_auth_user( $user_name )	{ $this->http_authorization['user'] = $user_name; return true; }
	function set_auth_pw( $passwd )			{ $this->http_authorization['passwd'] = $passwd; return true; }

	#	リダイレクト関連
	function set_auto_redirect_flg( $bool ){
		$this->auto_redirect_flg = (bool)$bool;
		return true;
	}
	function set_redirect_count( $num )		{ $this->redirect_count = intval($num); return true; }
	function set_max_redirect_number( $num ){ $this->max_redirect_number = intval($num); return true; }


	#--------------------------------------
	#	要求後にする操作系
	#	リモートホストの応答から得た値を取り出す

	#	応答ヘッダを取り出す
	function get_response_header(){
		return $this->http_response_header;
	}

	#	コンテンツを取り出す
	function get_response_content(){
		#	ただし、save_http_contents() によって、
		#	その内容をファイルに保存した場合は、
		#	このメソッドから取り出すことはできません。
		return	$this->http_response_content;
	}

	#	その他の結果取り出し系インターフェイス
	function get_content_type()				{ return $this->http_response_content_type; }
	function get_content_length()			{ return $this->http_response_content_length; }
	function get_content_charset()			{ return $this->http_response_content_charset; }
	function get_status_cd()				{ return $this->http_response_status_cd; }
	function get_status_msg()				{ return $this->http_response_status_msg; }
	function get_redirect_to()				{ return $this->http_response_redirect_to; }
	function get_response_time()			{ return floatval( $this->http_response_time ); }
	function get_transfer_encoding()		{ return $this->http_response_transfer_encoding; }
	function get_last_modified()			{ return $this->http_response_last_modified; }//PxHTTPAccess 1.0.2 追加
	function get_last_modified_timestamp()	{//PxHTTPAccess 1.0.2 追加
		if( !strlen( $this->http_response_last_modified ) ){ return null; }
		return strtotime( $this->http_response_last_modified );
	}
	function get_response( $key )			{ return $this->http_response_all[strtolower($key)]; }//PxHTTPAccess 1.0.3 追加
	function get_socket_open_error_num()	{ return $this->socket_open_error_num; }
	function get_socket_open_error_msg()	{ return $this->socket_open_error_msg; }
	function get_socket_open_error(){
		$num = $this->get_socket_open_error_num();
		$msg = $this->get_socket_open_error_msg();
		if( $num || $msg ){
			$RTN = $num.':'.$msg;
			return	$RTN;
		}
		return null;
	}



	###################################################################################################################
	#	アクセス+ダウンロード処理系

	#--------------------------------------
	#	HTTP接続リソースを取り出す
	function &get_connection_resource(){
		return	$this->http_connection_resource;
	}

	#--------------------------------------
	#	ホストに接続する
	function &http_connect( $host , $port = null , $is_ssl = false ){
		if( !strlen( $host ) ){
			return	false;
		}
		if( $is_ssl ){
			$host = 'ssl://'.$host;
		}
		if( !strlen( $port ) ){
			if( $is_ssl ){
				$port = 443;//HTTPSのデフォルトポート
			}else{
				$port = 80;//HTTPのデフォルトポート
			}
		}
		$port = intval( $port );
		$res = @fsockopen( $host , $port , $this->socket_open_error_num , $this->socket_open_error_msg , 10 );
		if( !is_resource( $res ) ){
			return	false;
		}
		stream_set_timeout( $res , intval( $this->stream_timeout ) );
		$this->http_connection_resource = &$res;
		return	$res;
	}

	#--------------------------------------
	#	接続を解除する
	function http_disconnect(){
		$res = &$this->get_connection_resource();
		if( !is_resource( $res ) ){ return false; }
		if( !fclose( $res ) ){ return false; }
		return	true;
	}

	#--------------------------------------
	#	リクエストヘッダーを生成する
	function create_http_request_header(){
		$URL_INFO = parse_url( $this->get_url() );
		$POSTDATA = $this->http_post_data;

		$request_path = $URL_INFO['path'];
		if( strlen( $URL_INFO['query'] ) ){
			$request_path = $URL_INFO['path'].'?'.$URL_INFO['query'];
		}

		$RTN = '';
		$RTN .= $this->get_method().' '.$request_path.' HTTP/1.1'."\r\n";
		$RTN .= 'Host: '.$URL_INFO['host']."\r\n";
		$RTN .= 'User-Agent: '.$this->get_user_agent()."\r\n";
		if( ( $this->http_authorization['type'] == 'digest' || !$this->http_authorization['type'] && $this->http_response_www_authenticate['type'] == 'digest' ) && strlen( $this->http_authorization['user'] ) && strlen( $this->http_authorization['passwd'] ) && strlen( $this->http_response_www_authenticate['nonce'] ) ){
			#	ダイジェスト認証
			$RTN .= 'Authorization: Digest';
			$RTN .= ' username="'.$this->http_authorization['user'].'",';
			$RTN .= ' realm="'.$this->http_response_www_authenticate['realm'].'",';
			$RTN .= ' nonce="'.$this->http_response_www_authenticate['nonce'].'",';
			$RTN .= ' uri="'.$request_path.'",';
			$RTN .= ' algorithm='.$this->http_response_www_authenticate['algorithm'].',';
			$tmp_digest_val_qop = 'auth';//UTODO:←本来は選択式。固定ではダメ。
			$RTN .= ' qop='.$tmp_digest_val_qop.',';
			$tmp_digest_val_nc = str_pad( dechex( ++ $this->http_authorization[$this->http_response_www_authenticate['nonce']]['nc'] ) , 8 , '0' , STR_PAD_LEFT );
			$RTN .= ' nc='.$tmp_digest_val_nc.',';
			$tmp_digest_val_cnonce = md5( rand(1,99999) );
			$RTN .= ' cnonce="'.$tmp_digest_val_cnonce.'",';
			$tmp_a1 = 
				$this->http_authorization['user'].':'.
				$this->http_response_www_authenticate['realm'].':'.
				$this->http_authorization['passwd']
			;
			$tmp_a2 = 
				$this->http_method.':'.
				$request_path
			;
			$RTN .= ' response="'.md5(
				md5($tmp_a1).':'.
				$this->http_response_www_authenticate['nonce'].':'.
				$tmp_digest_val_nc.':'.
				$tmp_digest_val_cnonce.':'.
				$tmp_digest_val_qop.':'.
				md5($tmp_a2)
			).'"';
			$RTN .= "\r\n";
			unset( $tmp_digest_val_nc , $tmp_digest_val_cnonce , $tmp_digest_val_qop );
		}elseif( ( $this->http_authorization['type'] == 'basic' || !$this->http_authorization['type'] && $this->http_response_www_authenticate['type'] == 'basic' ) && strlen( $this->http_authorization['user'] ) && strlen( $this->http_authorization['passwd'] ) ){
			#	基本認証
			$RTN .= 'Authorization: Basic '.base64_encode( $this->http_authorization['user'].':'.$this->http_authorization['passwd'] )."\r\n";
		}
		if( strlen( $this->http_referer ) ){
			#	リファラ
			$RTN .= 'Referer: '.$this->http_referer."\r\n";
		}
		$cookie_string = trim( $this->get_cookies4requestheader( $this->http_url ) );
		if( strlen( $cookie_string ) ){
			#	クッキー
			$RTN .= $cookie_string."\r\n";
		}
		$header_ext = trim( $this->http_request_header_ext );
		if( strlen( $header_ext ) ){
			$RTN .= trim($header_ext)."\r\n";
		}
		$RTN .= 'Connection: close'."\r\n";

		if( strlen( $POSTDATA ) ){
			#	POSTの場合にくっつける部分。
			$RTN .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
			$RTN .= 'Content-Length: '.strlen( $POSTDATA )."\r\n";
			$RTN .= "\r\n";
			$RTN .= trim( $POSTDATA )."\r\n";
		}

		$RTN .= "\r\n";

		return	$RTN;
	}

	#--------------------------------------
	#	リクエストを送信する
	function send_request( $request_header = null ){
		$res = &$this->get_connection_resource();
		if( !is_resource($res) ){ return false; }

		if( !strlen( $request_header ) ){
			$request_header = $this->create_http_request_header();
		}

		fputs( $res , $request_header );

		return	true;
	}

	#--------------------------------------
	#	ホストの返答を受け取り、内容を返す
	function get_responce( $save_to_path = null ){
		#	このメソッドは、リモートホストからコンテンツを取得します。
		#	ヘッダー部分はプロパティに記憶するのみとし、
		#	コンテンツセクションのみを取り出します。
		#	$save_to_path に有効なローカルパスがわたった場合、
		#	そのファイルに保存し、真偽を返す。

		#	初期化
		$status = 0;
		$this->http_response_content = '';
		#	/ 初期化

		if( strlen( $save_to_path ) ){
			if( is_file( $save_to_path ) ){
				if( !is_writable( $save_to_path ) ){ return false; }
				unlink( $save_to_path );
			}elseif( is_dir( dirname( $save_to_path ) ) ){
				if( !is_writable( dirname( $save_to_path ) ) ){ return false; }
			}else{
				return	false;
			}
			touch( $save_to_path );
			chmod( $save_to_path , 0777 );
			$save_to_path = realpath( $save_to_path );
			if( !is_file($save_to_path) ){ return false; }
		}else{
			$save_to_path = null;
		}

		$res = &$this->get_connection_resource();
		if( !is_resource($res) ){ return false; }

		$this->clear_response_header();//前回のレスポンスヘッダを削除(初期化)


		$downloaded_content_size = 0;
		$this->http_response_content_length = null;

		list( $microtime , $time ) = explode( ' ' , microtime() ); 
		$start_mtime = ( floatval( $time ) + floatval( $microtime ) );

		while( !feof( $res ) ){

			if( !strlen( $this->http_response_status_cd ) ){
				#	HTTPステータスコードを受け取ってないのに次へ行こうとしたら
				#	ここではじく。
				$status = 0;
			}

			if( $status >= 1 && $this->http_response_transfer_encoding != 'chunked' && !preg_match( '/^(?:text|application)\/.*$/i' , $this->get_content_type() ) ){
				#	バイナリだったらこっちが速い？
				$line = fread( $res , $this->fread_length );
			}else{
				#	テキストのデータはこっちが速い？
				$line = fgets( $res );
			}

			if( $status == 1 || $status == 2 ){
				#--------------------------------------
				#	コンテンツ領域
				if( $this->http_response_transfer_encoding == 'chunked' && $status == 1 ){
					#	コンテンツ領域の最初に全体の容量が入っている場合がある。(Transfer-Encoding: chunked の場合)
					#	このときは 16進数 で表現される模様。
					#	see: http://www.tohoho-web.com/ex/http.htm
					if( preg_match( '/^([0-9a-f]+\s*?(?:\r\n|\r|\n))(.*)$/si' , $line , $matches ) ){
						$chunk_length = intval( hexdec( trim($matches[1]) ) );
						if( $chunk_length === 0 ){ continue; }
						$this->http_response_content_length = $this->http_response_content_length + $chunk_length;
						$status = 2;
						continue;
					}
					continue;
				}

				if( is_int( $this->http_response_content_length ) && $downloaded_content_size + strlen( $line ) > $this->http_response_content_length ){
					#	もしも、予定容量よりも多くのデータを取り出してしまったら、丸めなければならない。
					$sabun = strlen( $line ) - ( $this->http_response_content_length - $downloaded_content_size );
					$line = preg_replace( '/^(.{'.intval(strlen($line)-$sabun).'}).*$/si' , "$1" , $line );
				}

				#--------------------------------------
				#	コンテンツ取り出し
				$downloaded_content_size += strlen( $line );
				if( @is_file( $save_to_path ) ){
					if( !$this->savefile_push( $save_to_path , $line ) ){
						#	保存に失敗したらおしまいにする
						break;
					}
				}else{
					$this->http_response_content .= $line;
				}
				#	/ コンテンツ取り出し
				#--------------------------------------

				if( is_int( $this->http_response_content_length ) && $downloaded_content_size >= $this->http_response_content_length ){
					if( $this->http_response_transfer_encoding == 'chunked' ){
						$status = 1;
					}
				}
				continue;

			}elseif( $status == 0 ){
				#--------------------------------------
				#	ヘッダー領域
				if( preg_match( '/HTTP\/([0-9]+?\.[0-9]+?) ([0-9]{3}) (.*?)(?:\r\n|\r|\n)?$/si' , $line , $matched ) ){
					#	HTTP Status
					$this->http_response_version = floatval( $matched[1] );
					$this->http_response_status_cd = intval( $matched[2] );
					$this->http_response_status_msg = $matched[3];
				}elseif( preg_match( '/Content-Type:\s*([a-zA-Z\-\_\.]+\/[a-zA-Z\-\_\.]+)(?:;\s*charset\=([a-zA-Z0-9\-\_\.]+))?/si' , $line , $matched ) ){
					#	Content-type & charset
					$this->http_response_content_type = trim( $matched[1] );
					$this->http_response_content_charset = trim( $matched[2] );
				}elseif( preg_match( '/content-length:\s*?([0-9]+)/si' , $line , $matched ) ){
					#	Content-Length
					$this->http_response_content_length = intval( $matched[1] );
				}elseif( preg_match( '/Transfer-Encoding:\s*?([a-z0-9]+)/si' , $line , $matched ) ){
					#	Transfer-Encoding: chunked
					$this->http_response_transfer_encoding = strtolower( $matched[1] );
				}elseif( preg_match( '/Last-Modified:\s*?([a-zA-Z0-9\-\_\,\: ]+)/si' , $line , $matched ) ){
					#	Last-Modified
					#	PxHTTPAccess 1.0.2 追加
					$this->http_response_last_modified = trim( $matched[1] );
				}elseif( preg_match( '/Connection:\s*?([a-z0-9]+)/si' , $line , $matched ) ){
					#	Connection: chunked
					$this->http_response_connection = strtolower( $matched[1] );
				}elseif( preg_match( '/Location:\s*(.*?)(?:\r\n|\r|\n)?$/si' , $line , $matched ) ){
					#	リダイレクトの処理
					$this->http_response_redirect_to = trim( $matched[1] );
					if( $this->auto_redirect_flg ){
						#	自動的にリダイレクトを解決する設定だった場合、
						#	再帰的にリダイレクト先を巡回する。
						if( $this->max_redirect_number <= $this->redirect_count ){
							#	リダイレクト回数に達していたら、再帰的アクセスをしない。
							continue;
						}
						$this->http_disconnect();//自分の接続を解除する。0:01 2009/09/20

						$thisClassName = get_class( $this );
						$http4redirect = new $thisClassName();
						$http4redirect->set_max_redirect_number( $this->max_redirect_number );
						$http4redirect->set_redirect_count( $this->redirect_count + 1 );
						$http4redirect->set_user_agent( $this->http_user_agent );
						$http4redirect->set_url( $this->http_response_redirect_to );
						$http4redirect->set_method( 'GET' );
						if( is_file( $save_to_path ) ){
							$http4redirect->save_http_contents( $save_to_path );
						}else{
							$this->http_response_content = $http4redirect->get_http_contents();
						}
						$this->clear_response_header();//初期化 23:58 2009/09/19
						$this->put_response_header( $http4redirect->get_response_header() );
						unset( $http4redirect );
						return	$this->http_response_content;
					}
				}

				if( !strlen( trim( $line ) ) ){
					$status = 1;//次へ
				}else{
					$this->put_response_header( $line );
				}
				continue;
			}

		}

		list( $microtime , $time ) = explode( ' ' , microtime() ); 
		$end_mtime = ( floatval( $time ) + floatval( $microtime ) );
		$this->http_response_time = floatval( $end_mtime - $start_mtime );

		#--------------------------------------
		#	受け取ったクッキーの処理
		$this->parse_cookie( $this->http_url , $this->get_response_header() );
		#	/ 受け取ったクッキーの処理
		#--------------------------------------

		#--------------------------------------
		#	受け取った認証情報の処理
		$this->parse_www_authenticate( $this->get_response_header() );
		#	/ 受け取った認証情報の処理
		#--------------------------------------

		if( is_file( $save_to_path ) ){
			return	true;
		}

		return	$this->http_response_content;
	}//get_responce();

	#--------------------------------------
	#	★リクエストを送信して結果を取得する
	function get_http_contents( $path_save_to = null , $try = 0 ){
		$URL_INFO = parse_url( $this->get_url() );
#		if( !strlen( $URL_INFO['port'] ) ){ $URL_INFO['port'] = 80; }

		$is_ssl = false;
		if( strtolower( $URL_INFO['scheme'] ) == 'https' ){
			$is_ssl = true;
		}

		if( !$this->http_connect( $URL_INFO['host'] , $URL_INFO['port'] , $is_ssl ) ){
			return	false;
		}
		if( !$this->send_request() ){
			$this->http_disconnect();
			return	false;
		}

		$reqult = $this->get_responce( $path_save_to );
		$this->http_disconnect();

		if( !$try && $this->get_status_cd() == '401' &&//←認証に失敗した場合
			(
				//↓ダイジェスト認証を求められたら
				( $this->http_response_www_authenticate['type'] == 'digest' && strlen( $this->http_response_www_authenticate['nonce'] ) )
				//↓ベーシック認証を求められたら(ユーザが指定していない場合のみ)
				|| ( $this->http_response_www_authenticate['type'] == 'basic' && !strlen( $this->http_authorization['type'] ) )
			)
			&& strlen( $this->http_authorization['user'] ) && strlen( $this->http_authorization['passwd'] )
		){
			//ダイジェスト認証で失敗したらもう一度アクセスする//PxHTTPAccess 1.0.2 追加
			$reqult = $this->get_http_contents( $path_save_to , 1 );
		}

		return	$reqult;
	}

	#--------------------------------------
	#	★結果をファイルに保存する
	function save_http_contents( $path_save_to ){
		return	$this->get_http_contents( $path_save_to );
	}


	###################################################################################################################
	#	アクセス事後処理

	#	応答ヘッダを削除する
	function clear_response_header(){
		$this->http_response_header = null;
		$this->http_response_transfer_encoding = null;
		$this->http_response_last_modified = null;//PxHTTPAccess 1.0.2 追加
		$this->http_response_version = null;
		$this->http_response_status_cd = null;
		$this->http_response_status_msg = null;
		$this->http_response_content_type = null;
		$this->http_response_content_charset = null;
		$this->http_response_content_length = null;
		$this->http_response_redirect_to = null;
		$this->http_response_connection = null;
		$this->http_response_all = array();
		return	true;
	}
	#	応答ヘッダに追記する
	function put_response_header( $value ){
		$this->http_response_header .= $value;
		if( preg_match( '/(.*?):\s*(.*?)(?:\r\n|\r|\n)?$/si' , $value , $matched ) ){
			#	ヘッダーを記憶
			$this->http_response_all[strtolower($matched[1])] = trim( $matched[2] );
		}
		return	true;
	}



	###################################################################################################################
	#	認証関連操作

	#--------------------------------------
	#	WWW-Authenticate を解析する
	function parse_www_authenticate( $http_header_string ){
		//PxHTTPAccess 1.0.2 追加
		if( preg_match( '/WWW\-Authenticate\:\s*(.*?)(?:\r\n|\r|\n|$)/si' , $http_header_string , $matched ) ){
			$value = $matched[1];
			preg_match( '/^(Basic|Digest)(.*)$/si' , trim($value) , $matched );
			$this->http_response_www_authenticate['type'] = strtolower( $matched[1] );
			$value = $matched[2];
			$tmp = array();
			while( 1 ){
				if( !preg_match( '/^(?:\,\s*)?([a-zA-Z0-9\-\_]+)\=(\"?)(.*?)\2((?:\s*\,).*)?$/' , trim($value) , $matched ) ){
					break;
				}
				$tmp[$matched[1]] = $matched[3];
				$value = $matched[4];
				continue;
			}

			$this->http_response_www_authenticate['realm'] = $tmp['realm'];
			if( $this->http_response_www_authenticate['type'] == 'digest' ){
				$this->http_response_www_authenticate['nonce'] = $tmp['nonce'];
				$this->http_response_www_authenticate['algorithm'] = $tmp['algorithm'];
				$this->http_response_www_authenticate['qop'] = $tmp['qop'];
			}
		}

		return	true;
	}

	###################################################################################################################
	#	クッキー関連操作

	#--------------------------------------
	#	クッキーを削除する
	function clear_cookies(){
		$this->cookies = array();
		return	true;
	}

	#--------------------------------------
	#	クッキーを解析する
	function parse_cookie( $sender_url , $http_header_string ){
		$url_info = parse_url( $sender_url );

		preg_match_all( '/Set-Cookie:(.*?)(?:\r\n|\r|\n|$)/i' , $http_header_string , $preg_result );//クッキーの行を解析・取得

		if( !is_array( $preg_result[1] ) ){ $preg_result[1] = array(); }
		foreach( $preg_result[1] as $cookie_line ){
			$cookie_elm = explode( ';' , $cookie_line );
			$cookie_current_key = null;
			$cookie_current_parse_memo = array();
			if( !is_array( $cookie_elm ) ){ $cookie_elm = array(); }
			foreach( $cookie_elm as $LINE ){
				list( $cookie_key , $cookie_value ) = explode('=',$LINE);

				switch( $cookie_key ){
					case 'expires';
					case 'path';
					case 'domain';
					case 'secure';
						break;
					default:
						if( is_null( $cookie_current_key ) ){
							$cookie_current_key = trim($cookie_key);
						}
						break;
				}

				$cookie_current_parse_memo[trim($cookie_key)] = trim( $cookie_value );

			}

			if( !strlen( $cookie_current_parse_memo['path'] ) ){
				$cookie_current_parse_memo['path'] = $url_info['path'];
			}
			if( !strlen( $cookie_current_parse_memo['domain'] ) ){
				$cookie_current_parse_memo['domain'] = $url_info['host'];
			}

			$this->accept_cookie(
				$cookie_current_parse_memo['domain'] ,
				$cookie_current_key ,
				$cookie_current_parse_memo[$cookie_current_key] ,
				$cookie_current_parse_memo['path'] ,
				$cookie_current_parse_memo['expires'] ,
				$cookie_current_parse_memo['secure']
			);

			unset($cookie_current_parse_memo);
		}

		return	true;
	}

	#--------------------------------------
	#	クッキーを受け付ける
	function accept_cookie( $domain , $cookie_key , $cookie_value , $path = '/' , $expires = null , $secure = null ){

		$this->cookies[$domain][$cookie_key]['value'] = $cookie_value;
		$this->cookies[$domain][$cookie_key]['path'] = $path;
		$this->cookies[$domain][$cookie_key]['expires'] = $expires;
		$this->cookies[$domain][$cookie_key]['secure'] = $secure;

		return	true;
	}

	#--------------------------------------
	#	リクエストHEADERに付け加えるクッキーを取得
	function get_cookies4requestheader( $sendto_url ){
		$sendto_url_info = parse_url( $sendto_url );
		$current_cookies = $this->cookies[$sendto_url_info['host']];
		if( !is_array( $current_cookies ) ){ $current_cookies = array(); }

		$RTN_ARY = array();
		if( !is_array( $current_cookies ) ){ $current_cookies = array(); }
		foreach( $current_cookies as $key=>$value ){
			array_push( $RTN_ARY , $key.'='.addslashes($value['value']).';' );
		}
		$RTN = implode( ' ' , $RTN_ARY );
		if( !strlen( $RTN ) ){
			return	null;
		}

		return	'Cookie: '.$RTN;
	}


	#--------------------------------------
	#	ファイルに追記する
	function savefile_push( $filepath , $CONTENT , $perm = null ){
		if( !strlen( $CONTENT ) ){ return false; }
		if( !is_int($perm) ){ $perm = 0777; }

		if( is_dir( $filepath ) ){ return false; }
		if( is_file( $filepath ) && !is_writable( $filepath ) ){ return false; }

		$res = fopen($filepath,'a');
		if( !is_resource( $res ) ){ return	false; }
		fwrite( $res , $CONTENT );
		fclose( $res );
		chmod( $filepath , $perm );
		clearstatcache();

		return filesize( $filepath );

	}

}

?>