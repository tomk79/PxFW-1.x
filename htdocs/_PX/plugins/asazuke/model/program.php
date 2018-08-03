<?php

/**
 * モデル：プログラム
 * Copyright (C)Tomoya Koyanagi.
 */
class pxplugin_asazuke_model_program{

	private $px;

	private $pcconf;
	private $proj;

	private $crawl_error_list = array();
	private $info_program_id;


	/**
	 * コンストラクタ
	 */
	public function __construct( &$px , &$pcconf , &$proj ){
		$this->px = &$px;
		$this->pcconf = &$pcconf;
		$this->proj = &$proj;
	}


	/**
	 * プログラムをロードする
	 */
	public function load_program(){

		$path_program_dir = $this->pcconf->get_program_home_dir();
		if( !is_dir( $path_program_dir ) ){
			return	false;
		}

		// $this->info_program_id = $program_id;

		$programInfo = $this->proj->load_ini( $path_program_dir.'/programinfo.ini' );
		$programInfo['section'] = $programInfo['sec'];

		return	true;
	}

	/**
	 * プログラムの現在の状態を保存する
	 */
	public function save_program(){
		return	true;
	}

	/**
	 * プログラムが保存したコンテンツを削除する
	 */
	public function delete_program_content(){

		$path_program_dir = $this->pcconf->get_program_home_dir();
		if( !is_dir( $path_program_dir ) ){
			return	false;
		}
		$path_program_dir .= '/dl';
		if( !is_dir( $path_program_dir ) ){
			return	false;
		}

		set_time_limit(0);
		$result = $this->px->dbh()->rm( $path_program_dir );
		set_time_limit(30);
		if( $result === false ){
			return	false;
		}

		return	true;
	}



	/**
	 * クロール時に発生したエラーをログに残す
	 */
	public function crawl_error( $errormsg , $url = null , $save_to = null ){
		if( !is_array( $this->crawl_error_list ) ){
			#	PicklesCrawler 0.3.0 追加
			$this->crawl_error_list = array();
		}
		array_push( $this->crawl_error_list , array( 'errormsg'=>$errormsg , 'url'=>$url , 'saveto'=>$save_to ) );

		$path_program_dir = $this->pcconf->get_program_home_dir( $this->proj->get_project_id() , $this->get_program_id() );
		$path_crawl_error_log = $path_program_dir.'/dl/__LOGS__/crawlerror.log';
		if( !is_dir( dirname( $path_crawl_error_log ) ) || !is_writable( dirname( $path_crawl_error_log ) ) ){
			$this->px->error()->error_log( 'Faild to save crawl error log. Directory ['.dirname( $path_crawl_error_log ).'] is NOT exists, or NOT writable.' , __FILE__ , __LINE__ );
			return	false;
		}
		if( is_file( $path_crawl_error_log ) && !is_writable( $path_crawl_error_log ) ){
			$this->px->error()->error_log( 'Faild to save crawl error log. File ['.$path_crawl_error_log.'] is NOT writable.' , __FILE__ , __LINE__ );
			return	false;
		}

		$LOG_ROW = '';
		$LOG_ROW .= 	preg_replace( '/(?:\r\n|\r|\n| |\t)+/' , ' ' , $errormsg );
		$LOG_ROW .= '	'.preg_replace( '/(?:\r\n|\r|\n| |\t)+/' , ' ' , $url );
		$LOG_ROW .= '	'.preg_replace( '/(?:\r\n|\r|\n| |\t)+/' , ' ' , $save_to );

		$result = @error_log( $LOG_ROW."\n" , 3 , $path_crawl_error_log );
		@chmod( $path_crawl_error_log , 0777 );
		return	$result;
	}
	/**
	 * クロール時に発生したエラーを取得する
	 * PicklesCrawler 0.3.0 追加
	 */
	public function get_crawl_error(){
		return	$this->crawl_error_list;
	}
	/**
	 * クロール時に発生したエラーを消去する
	 * PicklesCrawler 0.3.0 追加
	 */
	public function clear_crawl_error(){
		$this->crawl_error_list = array();
		return	true;
	}


	/**
	 * プログラムIDの出力
	 */
	public function get_program_id(){
		return	$this->info_program_id;
	}


	/**
	 * プログラム名の入力
	 */
	public function set_program_name( $program_name ){
		$this->info_program_name = $program_name;
		return	true;
	}
	/**
	 * プログラム名の出力
	 */
	public function get_program_name(){
		return	$this->info_program_name;
	}


	/**
	 * 常に送信するパラメータの入力
	 * PicklesCrawler 0.3.0 追加
	 */
	public function set_program_param( $program_param ){
		$this->info_program_param = $program_param;
		return	true;
	}
	/**
	 * 常に送信するパラメータの出力
	 * PicklesCrawler 0.3.0 追加
	 */
	public function get_program_param(){
		return	$this->info_program_param;
	}


	/**
	 * プログラムタイプの入力
	 */
	public function set_program_type( $program_type ){
		$this->info_program_type = $program_type;
		return	true;
	}
	/**
	 * プログラムタイプの出力
	 */
	public function get_program_type(){
		return	$this->info_program_type;
	}


	/**
	 * HTTP_USER_AGENTの入力
	 */
	public function set_program_useragent( $value ){
		$this->info_program_useragent = $value;
		return	true;
	}
	/**
	 * HTTP_USER_AGENTの出力
	 */
	public function get_program_useragent(){
		return	$this->info_program_useragent;
	}

	#--------------------------------------
	#	複製先パスの入出力
	#	PicklesCrawler 0.3.3 追加
	function set_path_copyto( $path ){
		if( strlen( $path ) ){ $path = realpath( $path ); }
		$this->info_path_copyto = $path;
		return	true;
	}
	function get_path_copyto(){
		return	$this->info_path_copyto;
	}

	#--------------------------------------
	#	削除ファイル反映フラグの入出力
	#	PicklesCrawler 0.3.3 追加
	function set_copyto_apply_deletedfile_flg( $flg ){
		if( $flg ){
			$this->info_copyto_apply_deletedfile_flg = 1;
		}else{
			$this->info_copyto_apply_deletedfile_flg = 0;
		}
		return	true;
	}
	function get_copyto_apply_deletedfile_flg(){
		return	$this->info_copyto_apply_deletedfile_flg;
	}

	#--------------------------------------
	#	対象範囲URLリストの入出力
	function set_urllist_scope( $str_scope ){
		$this->clear_urllist_scope();//一旦リセット
		return	$this->put_urllist_scope( $str_scope );
	}
	function clear_urllist_scope(){
		$this->info_urllist_scope = array();
		return	true;
	}
	function put_urllist_scope( $url_scope ){
		if( is_array( $url_scope ) ){
			#	配列をもらったら、全部処理
			foreach( $url_scope as $url ){
				$this->put_urllist_scope( $url );
			}
			return	true;
		}
		if( !is_string( $url_scope ) ){
			#	文字列じゃないと突っ込めない。
			return	false;
		}

		if( !is_array( $this->info_urllist_scope ) ){ $this->info_urllist_scope = array(); }

		$urllist_scope = preg_split( '/\r\n|\r|\n/' , $url_scope );
		foreach( $urllist_scope as $url ){
			if( !strlen( $url ) ){ continue; }
			array_push( $this->info_urllist_scope , $url );
		}

		return	true;
	}
	function get_urllist_scope(){
		return	$this->info_urllist_scope;
	}
	function is_scope( $url ){
		#	アンカーを削除
		$url = preg_replace( '/^(.*?)#.*$/si' , '\1' , $url );
		#	パラメータを削除
		$url = preg_replace( '/^(.*?)\?.*$/si' , '\1' , $url );

		#	スコープ内のURLかどうか評価
		$urllist = $this->get_urllist_scope();
		if( !is_array( $urllist ) ){ $urllist = array(); }
		if( !count( $urllist ) ){
			#	指定が1件もなければ、
			#	全URLを対象とみなす。
			return	true;
		}
		foreach( $urllist as $urlline ){
			$url_preg_ptn = '/^'.preg_quote( $urlline , '/' ).'$/i';//PxCrawler 0.3.7 : 大文字小文字を区別しなくなった。
			$url_preg_ptn = preg_replace( '/'.preg_quote('\*').'/' , '(?:.*?)' , $url_preg_ptn );	// ワイルドカードをPREGパターンに反映
			if( preg_match( $url_preg_ptn , $url ) ){
				// マッチしたら、それは対象範囲である。
				return	true;
			}
		}
		return	false;
	}

	#--------------------------------------
	#	ダウンロードしないURLリストの入出力
	function set_urllist_nodownload( $str_nodownload ){
		$this->clear_urllist_nodownload();//一旦リセット
		return	$this->put_urllist_nodownload( $str_nodownload );
	}
	function clear_urllist_nodownload(){
		$this->info_urllist_nodownload = array();
		return	true;
	}
	function put_urllist_nodownload( $url_nodownload ){
		if( is_array( $url_nodownload ) ){
			#	配列をもらったら、全部処理
			foreach( $url_nodownload as $url ){
				$this->put_urllist_nodownload( $url );
			}
			return	true;
		}
		if( !is_string( $url_nodownload ) ){
			#	文字列じゃないと突っ込めない。
			return	false;
		}

		if( !is_array( $this->info_urllist_nodownload ) ){ $this->info_urllist_nodownload = array(); }

		$urllist_nodownload = preg_split( '/\r\n|\r|\n/' , $url_nodownload );
		foreach( $urllist_nodownload as $url ){
			if( !strlen( $url ) ){ continue; }
			array_push( $this->info_urllist_nodownload , $url );
		}

		return	true;
	}
	function get_urllist_nodownload(){
		return	$this->info_urllist_nodownload;
	}
	function is_nodownload( $url ){
		#	アンカーを削除
		$url = preg_replace( '/^(.*?)#.*$/si' , '\1' , $url );
		#	パラメータを削除
		$url = preg_replace( '/^(.*?)\?.*$/si' , '\1' , $url );

		#	ダウンロードしないURLリストを評価
		$urllist = $this->get_urllist_nodownload();
		if( !is_array( $urllist ) ){ $urllist = array(); }
		foreach( $urllist as $urlline ){
			$url_preg_ptn = '/^'.preg_quote( $urlline , '/' ).'$/i';//PxCrawler 0.3.7 : 大文字小文字を区別しなくなった。
			$url_preg_ptn = preg_replace( '/'.preg_quote('\*').'/' , '(?:.*?)' , $url_preg_ptn );	// ワイルドカードをPREGパターンに反映
			if( preg_match( $url_preg_ptn , $url ) ){
				// マッチしたら、それは追加しない。
				return	true;
			}
		}
		return	false;
	}

	#--------------------------------------
	#	受け取ったURLに、常に送信するパラメータをマージする
	#	PicklesCrawler 0.3.0 追加
	function merge_param( $URL ){
		#	$URL は、POSTデータである場合があります

		$param = $this->get_program_param();
		if( !strlen( $param ) ){
			#	パラメータ設定がない場合、
			#	従来どおり、そのまま返す。
			return	$URL;
		}

		#--------------------------------------
		#	常設パラメータを連想配列に分解
		$ary_param = array();
		$tmp_paramlist = explode( '&' , $param );
		foreach( $tmp_paramlist as $tmp_paramline ){
			if( !strlen( $tmp_paramline ) ){ continue; }
			list( $key , $val ) = explode( '=' , $tmp_paramline );
			$ary_param[urldecode( $key )] = urldecode( $val );
		}
		unset( $tmp_paramlist );
		unset( $tmp_paramline );
		unset( $key );
		unset( $val );
		#	/ 常設パラメータを連想配列に分解
		#--------------------------------------

		#--------------------------------------
		#	$URL を、パラメータとURLに分解
		if( preg_match( '/^(?:https?|ftp)\:\/\//si' , $URL ) ){
			#	GETデータの場合
			if( preg_match( '/\?/' , $URL ) ){
				preg_match( '/^(.*?)\?(.*)$/si' , $URL , $matched );
				$URL = $matched[1];
				$presetparam = $matched[2];
			}
		}else{
			#	POSTデータの場合
			$presetparam = $URL;
			$URL = null;
		}
		#	/ $URL を、パラメータとURLに分解
		#--------------------------------------

		#--------------------------------------
		#	サイト指定のパラメータを連想配列に分解
		$ary_presetparam = array();
		$tmp_paramlist = explode( '&' , $presetparam );
		foreach( $tmp_paramlist as $tmp_paramline ){
			if( !strlen( $tmp_paramline ) ){ continue; }
			list( $key , $val ) = explode( '=' , $tmp_paramline );
			$ary_presetparam[urldecode( $key )] = urldecode( $val );
		}
		unset( $tmp_paramlist );
		unset( $tmp_paramline );
		unset( $key );
		unset( $val );
		#	/ サイト指定のパラメータを連想配列に分解
		#--------------------------------------

		#--------------------------------------
		#	マージ実行
		#	・$ary_param を $ary_presetparam にマージ。
		#	・重複する場合、$ary_param が優先。
		foreach( $ary_presetparam as $key=>$val ){
			if( !is_null( $ary_param[$key] ) ){
				$ary_presetparam[$key] = $ary_param[$key];
				unset( $ary_param[$key] );
			}
		}
		foreach( $ary_param as $key=>$val ){
			if( !is_null( $val ) ){
				$ary_presetparam[$key] = $val;
				unset( $ary_param[$key] );
			}
		}
		#	/ マージ実行
		#--------------------------------------

		#--------------------------------------
		#	パラメータの連想配列をGET形式に結合
		$PARAM_ARY = array();
		foreach( $ary_presetparam as $key=>$val ){
			array_push( $PARAM_ARY , urlencode($key).'='.urlencode($val) );
		}
		$PARAM_FIN = implode( '&' , $PARAM_ARY );
		unset( $PARAM_ARY );
		#	/ パラメータの連想配列をGET形式に結合
		#--------------------------------------


		#--------------------------------------
		#	パラメータのマージ完成
		$RTN = $PARAM_FIN;
		if( strlen( $URL ) ){
			$RTN = $URL.'?'.$RTN;
		}
		#	/ パラメータのマージ完成
		#--------------------------------------
		return	$RTN;
	}

}

?>