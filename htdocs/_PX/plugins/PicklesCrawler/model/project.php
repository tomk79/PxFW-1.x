<?php

/**
 * モデル：プロジェクト
 * Copyright (C)Tomoya Koyanagi.
 * Last Update : 22:52 2011/08/15
 */
class pxplugin_PicklesCrawler_model_project{

	private $px;
	private $pcconf;

	private $info_project_id = null;
	private $info_project_name = null;
	private $info_url_startpage = null;
	private $info_url_docroot = null;
	private $info_default_filename = null;
	private $info_omit_filename = false;//22:53 2011/08/15 PxCrawler 0.4.3 追加
	private $info_urllist_outofsite = array();
	private $info_urllist_startpages = array();
	private $info_param_define = array();
	private $info_send_unknown_params_flg = false;//0:07 2008/04/17 追加
	private $info_send_form_flg = false;//23:41 2008/04/17 追加
	private $info_parse_jsinhtml_flg = false;//9:39 2011/08/11 PxCrawler 0.4.3 追加
	private $info_save404_flg = false;//23:21 2009/03/11 追加
	private $info_path_copyto = false;//0:51 2009/08/27 追加
	private $info_charset_charset = null;//23:28 2009/03/30 追加
	private $info_charset_crlf = null;//23:28 2009/03/30 追加
	private $info_charset_ext = null;//23:28 2009/03/30 追加
	private $info_localfilename_rewriterules = array();
	private $info_preg_replace_rules = array();//23:28 2009/03/30 追加
	private $info_path_conv_method = 'relative';
	private $info_outofsite2url_flg = false;//PxCrawler 0.4.2 追加

	/**
	 * コンストラクタ
	 */
	public function __construct( &$px , &$pcconf ){
		$this->px = &$px;
		$this->pcconf = &$pcconf;
	}


	/**
	 * ファクトリ：プログラムオブジェクトを生成
	 */
	public function &factory_program( $program_id = null ){
		$objPath = '/PicklesCrawler/model/program.php';
		$className = $this->px->load_px_plugin_class( $objPath );
		if( !$className ){
			$this->px->error()->error_log( 'プログラムオブジェクトのロードに失敗しました。['.$objPath.']' , __FILE__ , __LINE__ );
		}
		$obj = new $className( $this->px , $this->pcconf , $this );
		if( strlen( $program_id ) ){
			$obj->load_program( $program_id );
		}else{
			$obj->create_program();
		}
		return	$obj;
	}

	/**
	 * 既存プロジェクトの一覧を開く
	 */
	public function get_project_list(){
		$dir = $this->pcconf->get_home_dir().'/proj';
		$itemlist = $this->px->dbh()->ls( $dir );
		if(!$itemlist){
			$itemlist = array();
		}
		sort($itemlist);

		$RTN = array();
		foreach( $itemlist as $filename ){
			if( $filename == '.' || $filename == '..' ){ continue; }

			$project_ini = $this->load_ini( $dir.'/'.$filename.'/project.ini' );
			$MEMO = array();
			$MEMO['id'] = $filename;
			$MEMO['name'] = $project_ini['common']['name'];
			$MEMO['url_docroot'] = $project_ini['common']['url_docroot'];
			$MEMO['url_startpage'] = $project_ini['common']['url_startpage'];

			array_push( $RTN , $MEMO );
			unset( $MEMO );
		}

		return	$RTN;
	}

	/**
	 * 既存のプロジェクト情報を開いて、メンバにセット。
	 */
	public function load_project( $project_id ){
		$this->info_project_id = $project_id;
		$path_project_dir = $this->get_project_home_dir();
		if( !is_dir( $path_project_dir ) ){ return false; }
			#	プロジェクトが存在しなければ、終了

		#	基本情報
		$project_ini = $this->load_ini( $path_project_dir.'/project.ini' );
		$this->set_project_name( $project_ini['common']['name'] );
		$this->set_url_startpage( $project_ini['common']['url_startpage'] );
		$this->set_url_docroot( $project_ini['common']['url_docroot'] );
		$this->set_default_filename( $project_ini['common']['default_filename'] );
		$this->set_omit_filename( $project_ini['common']['omit_filename'] );
		$this->set_path_conv_method( $project_ini['common']['path_conv_method'] );
		$this->set_outofsite2url_flg( $project_ini['common']['outofsite2url_flg'] );//PxCrawler 0.4.2 追加
		$this->set_send_unknown_params_flg( $project_ini['common']['send_unknown_params_flg'] );
		$this->set_send_form_flg( $project_ini['common']['send_form_flg'] );
		$this->set_parse_jsinhtml_flg( $project_ini['common']['parse_jsinhtml_flg'] );
		$this->set_save404_flg( $project_ini['common']['save404_flg'] );
		$this->set_path_copyto( $project_ini['common']['path_copyto'] );
		$this->set_charset_charset( $project_ini['common']['charset_charset'] );
		$this->set_charset_crlf( $project_ini['common']['charset_crlf'] );
		$this->set_charset_ext( $project_ini['common']['charset_ext'] );

		#	基本認証情報
		$this->set_authentication_type( $project_ini['common']['authentication_type'] );
		$this->set_basic_authentication_id( $project_ini['common']['basic_authentication_id'] );
		$this->set_basic_authentication_pw( $project_ini['common']['basic_authentication_pw'] );

		#	対象外URLリスト
		$this->clear_urllist_outofsite();//一旦リセット
		if( is_array( $project_ini['sec']['url_outofsite'] ) ){
			foreach( $project_ini['sec']['url_outofsite'] as $url=>$status ){
				if( !$status ){ continue; }
				$this->put_urllist_outofsite( $url );
			}
		}
		if( is_file( $path_project_dir.'/url_outofsite.txt' ) ){
			#	PxCrawler 0.3.7 追加
			foreach( $this->px->dbh()->file_get_lines( $path_project_dir.'/url_outofsite.txt' ) as $url ){
				$url = trim( $url );
				if( !strlen( $url ) ){ continue; }
				$this->put_urllist_outofsite( $url );
			}
		}

		#	追加スタートURLリスト
		$this->clear_urllist_startpages();//一旦リセット
		if( is_array( $project_ini['sec']['url_startpages'] ) ){
			foreach( $project_ini['sec']['url_startpages'] as $url=>$status ){
				if( !$status ){ continue; }
				$this->put_urllist_startpages( $url );
			}
		}
		if( is_file( $path_project_dir.'/url_startpages.txt' ) ){
			#	PxCrawler 0.3.7 追加
			foreach( $this->px->dbh()->file_get_lines( $path_project_dir.'/url_startpages.txt' ) as $url ){
				$url = trim( $url );
				if( !strlen( $url ) ){ continue; }
				$this->put_urllist_startpages( $url );
			}
		}

		#	URLパラメータ定義情報
		$this->clear_param_define();//一旦リセット
		$param_define_ini = $this->load_ini( $path_project_dir.'/param_define.ini' );
		if( is_array( $param_define_ini['sec'] ) ){
			foreach( $param_define_ini['sec'] as $param_key=>$defines ){
				foreach( $defines as $define_key=>$value ){
					$this->set_param_define( $param_key , $define_key , $value );
				}
			}
		}

		#	保存ファイル名のリライトルール情報
		$this->clear_localfilename_rewriterules();//一旦リセット
		$rewriterules_ini = $this->load_ini( $path_project_dir.'/localfilename_rewriterules.ini' );
		if( is_array( $rewriterules_ini['sec'] ) ){
			foreach( $rewriterules_ini['sec'] as $rules_key=>$rules_value ){
				$MEMO = array();
				$priorityNum = intval( preg_replace( '/[^0-9]/' , '' , $rules_key ) );
				$MEMO['priority'] = $priorityNum;
				$MEMO['before'] = $rules_value['before'];
				$MEMO['requiredparam'] = $rules_value['requiredparam'];
				$MEMO['after'] = $rules_value['after'];
				$this->info_localfilename_rewriterules[$priorityNum] = $MEMO;
				unset($MEMO);
			}
		}
		uasort( $this->info_localfilename_rewriterules , create_function( '$a,$b' , 'if($a[\'priority\']>$b[\'priority\']){return 1;}elseif($a[\'priority\']<$b[\'priority\']){return -1;}return 0;' ) );

		#	一括置換設定情報
		$this->clear_preg_replace_rules();//一旦リセット
		$preg_replace_ini = $this->load_ini( $path_project_dir.'/preg_replace.ini' );
		if( is_array( $preg_replace_ini['sec'] ) ){
			foreach( $preg_replace_ini['sec'] as $rules_key=>$rules_value ){
				$MEMO = array();
				$priorityNum = intval( preg_replace( '/[^0-9]/' , '' , $rules_key ) );
				$MEMO['priority'] = $priorityNum;
				$MEMO['pregpattern'] = $rules_value['pregpattern'];
				$MEMO['replaceto'] = $rules_value['replaceto'];
				$MEMO['path'] = $rules_value['path'];
				$MEMO['dirflg'] = $rules_value['dirflg'];
				$MEMO['ext'] = $rules_value['ext'];
				$this->info_preg_replace_rules[$priorityNum] = $MEMO;
				unset($MEMO);
			}
		}
		uasort( $this->info_localfilename_rewriterules , create_function( '$a,$b' , 'if($a[\'priority\']>$b[\'priority\']){return 1;}elseif($a[\'priority\']<$b[\'priority\']){return -1;}return 0;' ) );

		$this->px->dbh()->fclose( $path_project_dir.'/project.ini' );
		$this->px->dbh()->fclose( $path_project_dir.'/param_define.ini' );
		$this->px->dbh()->fclose( $path_project_dir.'/localfilename_rewriterules.ini' );
		$this->px->dbh()->fclose( $path_project_dir.'/preg_replace.ini' );

		return	true;
	}//load_project()

	/**
	 * プロジェクトの現在の状態を保存する
	 */
	public function save_project(){
		if( !strlen( $this->get_project_id() ) ){ return false; }

		$path_project_dir = $this->get_project_home_dir();

		if( !is_dir( $path_project_dir ) ){ return false; }
			#	プロジェクトが存在しなければ、終了

		#======================================
		#	project.ini

		#	基本情報
		$project_ini_src = '';
		$project_ini_src .= 'name='.$this->get_project_name()."\n";
		$project_ini_src .= 'url_startpage='.$this->get_url_startpage()."\n";
		$project_ini_src .= 'url_docroot='.$this->get_url_docroot()."\n";
		$project_ini_src .= 'default_filename='.$this->get_default_filename()."\n";
		$project_ini_src .= 'omit_filename='.implode(',',$this->get_omit_filename())."\n";
		$project_ini_src .= 'path_conv_method='.$this->get_path_conv_method()."\n";
		if( $this->get_outofsite2url_flg() ){//PxCrawler 0.4.2 追加
			$project_ini_src .= 'outofsite2url_flg=1'."\n";
		}else{
			$project_ini_src .= 'outofsite2url_flg=0'."\n";
		}
		if( $this->get_send_unknown_params_flg() ){
			$project_ini_src .= 'send_unknown_params_flg=1'."\n";
		}else{
			$project_ini_src .= 'send_unknown_params_flg=0'."\n";
		}
		if( $this->get_send_form_flg() ){
			$project_ini_src .= 'send_form_flg=1'."\n";
		}else{
			$project_ini_src .= 'send_form_flg=0'."\n";
		}
		if( $this->get_parse_jsinhtml_flg() ){
			$project_ini_src .= 'parse_jsinhtml_flg=1'."\n";
		}else{
			$project_ini_src .= 'parse_jsinhtml_flg=0'."\n";
		}
		if( $this->get_save404_flg() ){
			$project_ini_src .= 'save404_flg=1'."\n";
		}else{
			$project_ini_src .= 'save404_flg=0'."\n";
		}
		$project_ini_src .= 'path_copyto='.$this->get_path_copyto()."\n";
		if( $this->get_charset_charset() ){
			$project_ini_src .= 'charset_charset='.$this->get_charset_charset()."\n";
		}
		if( $this->get_charset_crlf() ){
			$project_ini_src .= 'charset_crlf='.$this->get_charset_crlf()."\n";
		}
		if( $this->get_charset_ext() ){
			$project_ini_src .= 'charset_ext='.$this->get_charset_ext()."\n";
		}

		#	基本認証情報
		$project_ini_src .= 'authentication_type='.$this->get_authentication_type()."\n";
		$project_ini_src .= 'basic_authentication_id='.$this->get_basic_authentication_id()."\n";
		$project_ini_src .= 'basic_authentication_pw='.$this->get_basic_authentication_pw()."\n";
		$project_ini_src .= ''."\n";

		#	対象外URLリスト
		$project_ini_src .= '[url_outofsite]'."\n";
#		#	PxCrawler 0.3.7 別ファイル化
#		$urllist_outofsite = $this->get_urllist_outofsite();
#		if( is_array( $urllist_outofsite ) ){
#			foreach( $urllist_outofsite as $url ){
#				if( !strlen( trim( $url ) ) ){ continue; }
#				$project_ini_src .= trim($url).'=1'."\n";
#			}
#		}
		$project_ini_src .= ''."\n";

		#	追加スタートURLリスト
		$project_ini_src .= '[url_startpages]'."\n";
#		#	PxCrawler 0.3.7 別ファイル化
#		$urllist_startpages = $this->get_urllist_startpages();
#		if( is_array( $urllist_startpages ) ){
#			foreach( $urllist_startpages as $url ){
#				if( !strlen( trim( $url ) ) ){ continue; }
#				$project_ini_src .= trim($url).'=1'."\n";
#			}
#		}
		$project_ini_src .= ''."\n";

		if( !$this->px->dbh()->save_file( $path_project_dir.'/project.ini' , $project_ini_src ) ){
			return	false;
		}
		$this->px->dbh()->fclose($path_project_dir.'/project.ini');

		#======================================
		#	url_outofsite.txt
		#	【対象外URLリスト】
		$project_ini_src = '';
		$urllist_outofsite = $this->get_urllist_outofsite();
		if( is_array( $urllist_outofsite ) ){
			foreach( $urllist_outofsite as $url ){
				if( !strlen( trim( $url ) ) ){ continue; }
				$project_ini_src .= trim($url)."\n";
			}
		}
		if( !$this->px->dbh()->save_file( $path_project_dir.'/url_outofsite.txt' , $project_ini_src ) ){
			return	false;
		}
		$this->px->dbh()->fclose($path_project_dir.'/url_outofsite.txt');

		#======================================
		#	url_startpages.txt
		#	【追加スタートURLリスト】
		$project_ini_src = '';
		$urllist_startpages = $this->get_urllist_startpages();
		if( is_array( $urllist_startpages ) ){
			foreach( $urllist_startpages as $url ){
				if( !strlen( trim( $url ) ) ){ continue; }
				$project_ini_src .= trim($url)."\n";
			}
		}
		if( !$this->px->dbh()->save_file( $path_project_dir.'/url_startpages.txt' , $project_ini_src ) ){
			return	false;
		}
		$this->px->dbh()->fclose($path_project_dir.'/url_startpages.txt');


		#======================================
		#	param_define.ini

		#	URLパラメータ定義情報
		$param_list = $this->get_param_define_list();
		$param_define_ini_src = '';
		if( is_array( $param_list ) ){
			foreach( $param_list as $param_key ){
				$param_define_ini_src .= '['.$param_key.']'."\n";
				$param_define_ini_src .= 'name='.$this->get_param_define( $param_key , 'name' ).''."\n";
				$param_define_ini_src .= 'request='.$this->get_param_define( $param_key , 'request' ).''."\n";
				$param_define_ini_src .= "\n";
			}
		}
		if( !$this->px->dbh()->save_file( $path_project_dir.'/param_define.ini' , $param_define_ini_src ) ){
			return	false;
		}
		$this->px->dbh()->fclose($path_project_dir.'/param_define.ini');

		#======================================
		#	localfilename_rewriterules.ini

		#	保存ファイル名のリライトルール情報
		$rewriterules = $this->get_localfilename_rewriterules();
		$rewriterules_src = '';
		$i = 0;
		if( is_array( $rewriterules ) ){
			foreach( $rewriterules as $Line ){
				$i ++;
				$rewriterules_src .= '[priority'.$i.']'."\n";
				$rewriterules_src .= 'before='.$Line['before'].''."\n";
				$rewriterules_src .= 'requiredparam='.$Line['requiredparam'].''."\n";
				$rewriterules_src .= 'after='.$Line['after'].''."\n";
				$rewriterules_src .= "\n";
			}
		}
		if( !$this->px->dbh()->save_file( $path_project_dir.'/localfilename_rewriterules.ini' , $rewriterules_src ) ){
			return	false;
		}
		$this->px->dbh()->fclose($path_project_dir.'/localfilename_rewriterules.ini');

		#======================================
		#	preg_replace.ini

		#	一括置換設定情報
		$preg_replace_rules = $this->get_preg_replace_rules();
		$preg_replace_rules_src = '';
		$i = 0;
		if( is_array( $preg_replace_rules ) ){
			foreach( $preg_replace_rules as $Line ){
				$i ++;
				$preg_replace_rules_src .= '[priority'.$i.']'."\n";
				$preg_replace_rules_src .= 'pregpattern='.$Line['pregpattern'].''."\n";
				$preg_replace_rules_src .= 'replaceto='.$Line['replaceto'].''."\n";
				$preg_replace_rules_src .= 'path='.$Line['path'].''."\n";
				$preg_replace_rules_src .= 'dirflg='.$Line['dirflg'].''."\n";
				$preg_replace_rules_src .= 'ext='.$Line['ext'].''."\n";
				$preg_replace_rules_src .= "\n";
			}
		}
		if( !$this->px->dbh()->save_file( $path_project_dir.'/preg_replace.ini' , $preg_replace_rules_src ) ){
			return	false;
		}
		$this->px->dbh()->fclose($path_project_dir.'/preg_replace.ini');

		return	true;
	}//save_project()

	/**
	 * プロジェクトを削除する
	 */
	public function destroy_project(){
		if( !strlen( $this->get_project_id() ) ){ return false; }

		$path_project_dir = $this->get_project_home_dir();
		if( !is_dir( $path_project_dir ) ){
			return false;
		}
		$result = $this->px->dbh()->rm( $path_project_dir );
		if( !$result ){
			return	false;
		}

		return	true;
	}//destroy_project()


	/**
	 * プロジェクトIDの入出力
	 */
	public function get_project_id(){
		return	$this->info_project_id;
	}

	#--------------------------------------
	#	プロジェクト名の入出力
	public function set_project_name( $name ){
		$this->info_project_name = $name;
		return	true;
	}
	public function get_project_name(){
		return	$this->info_project_name;
	}

	#--------------------------------------
	#	スタートページURLの入出力
	public function set_url_startpage( $url_startpage ){
		$this->info_url_startpage = $url_startpage;
		return	true;
	}
	public function get_url_startpage(){
		return	$this->info_url_startpage;
	}

	#--------------------------------------
	#	ドキュメントルートURLの入出力
	public function set_url_docroot( $url_docroot ){
		$this->info_url_docroot = $url_docroot;
		return	true;
	}
	public function get_url_docroot(){
		return	$this->info_url_docroot;
	}

	#--------------------------------------
	#	デフォルトファイル名の入出力
	public function get_default_filename(){
		if( !strlen( $this->info_default_filename ) ){
			return	'index.html';
		}
		return	$this->info_default_filename;
	}
	public function set_default_filename( $default_filename ){
		$this->info_default_filename = $default_filename;
		return	true;
	}

	#--------------------------------------
	#	URL変換時に省略するファイル名の入出力
	public function get_omit_filename(){
		if( !is_array( $this->info_omit_filename ) ){
			return	array();
		}
		return	$this->info_omit_filename;
	}
	public function set_omit_filename( $omit_filename ){
		if( is_array( $omit_filename ) ){
			//配列ならOK
		}elseif( is_string( $omit_filename ) ){
			//文字列ならカンマで区切る
			$omit_filename = explode( ',' , $omit_filename );
		}else{
			return false;
		}
		foreach( $omit_filename as $key=>$val ){
			$omit_filename[$key] = trim($val);//←入力値整形
			if( !strlen( $omit_filename[$key] ) ){
				//空っぽだったらトルツメる。
				unset($omit_filename[$key]);
			}
		}
		$this->info_omit_filename = $omit_filename;
		return	true;
	}

	#--------------------------------------
	#	パス変換方法の入出力
	public function set_path_conv_method( $path_conv_method ){
		$path_conv_method = strtolower( $path_conv_method.'' );
		switch( $path_conv_method ){
			case 'relative':
			case 'absolute':
			case 'url':
			case 'none':
				$this->info_path_conv_method = $path_conv_method;
				break;
			default:
				return	false;
				break;
		}
		return	true;
	}
	public function get_path_conv_method(){
		if( !strlen( $this->info_path_conv_method ) ){
			return	'relative';
		}
		return	$this->info_path_conv_method;
	}

	#--------------------------------------
	#	サイト外扱いのパスをURLに変換するか否か
	#	PxCrawler 0.4.2 追加
	public function set_outofsite2url_flg( $flg ){
		if( $flg ){
			$this->info_outofsite2url_flg = true;
		}else{
			$this->info_outofsite2url_flg = false;
		}
		return	true;
	}
	public function get_outofsite2url_flg(){
		return	$this->info_outofsite2url_flg;
	}

	#--------------------------------------
	#	未定義パラメータの送信可否フラグ
	public function set_send_unknown_params_flg( $flg ){
		if( $flg ){
			$this->info_send_unknown_params_flg = true;
		}else{
			$this->info_send_unknown_params_flg = false;
		}
		return	true;
	}
	public function get_send_unknown_params_flg(){
		return	$this->info_send_unknown_params_flg;
	}

	#--------------------------------------
	#	フォーム送信可否フラグ
	public function set_send_form_flg( $flg ){
		if( $flg ){
			$this->info_send_form_flg = true;
		}else{
			$this->info_send_form_flg = false;
		}
		return	true;
	}
	public function get_send_form_flg(){
		return	$this->info_send_form_flg;
	}

	#--------------------------------------
	#	HTML内埋め込みのJavaScriptを解析するフラグ
	public function set_parse_jsinhtml_flg( $flg ){
		if( $flg ){
			$this->info_parse_jsinhtml_flg = true;
		}else{
			$this->info_parse_jsinhtml_flg = false;
		}
		return	true;
	}
	public function get_parse_jsinhtml_flg(){
		return	$this->info_parse_jsinhtml_flg;
	}

	#--------------------------------------
	#	Not Found ページ収集フラグ
	public function set_save404_flg( $flg ){
		if( $flg ){
			$this->info_save404_flg = true;
		}else{
			$this->info_save404_flg = false;
		}
		return	true;
	}
	public function get_save404_flg(){
		return	$this->info_save404_flg;
	}

	#--------------------------------------
	#	複製先パス
	public function set_path_copyto( $path ){
		if( strlen( $path ) ){
			$path = realpath( $path );
		}
		$this->info_path_copyto = $path;
		return	true;
	}
	public function get_path_copyto(){
		return	$this->info_path_copyto;
	}

	#--------------------------------------
	#	文字コード・改行コード：文字コード
	public function set_charset_charset( $charset ){
		if( strlen( $charset ) ){
			$this->info_charset_charset = $charset;
		}else{
			$this->info_charset_charset = null;
		}
		return	true;
	}
	public function get_charset_charset(){
		return	$this->info_charset_charset;
	}

	#--------------------------------------
	#	文字コード・改行コード：改行コード
	public function set_charset_crlf( $crlf ){
		if( strlen( $crlf ) ){
			$this->info_charset_crlf = $crlf;
		}else{
			$this->info_charset_crlf = null;
		}
		return	true;
	}
	public function get_charset_crlf(){
		return	$this->info_charset_crlf;
	}

	#--------------------------------------
	#	文字コード・改行コード：拡張子
	public function set_charset_ext( $ext ){
		if( strlen( $ext ) ){
			$this->info_charset_ext = $ext;
		}else{
			$this->info_charset_ext = null;
		}
		return	true;
	}
	public function get_charset_ext(){
		return	$this->info_charset_ext;
	}

	#--------------------------------------
	#	パラメータの扱い定義の入出力
	public function set_param_define( $param_key , $define_key , $value ){
		$this->info_param_define[$param_key][$define_key] = $value;
		return	true;
	}
	public function get_param_define( $param_key , $define_key = null ){
		if( is_null( $define_key ) ){
			return	$this->info_param_define[$param_key];
		}
		return	$this->info_param_define[$param_key][$define_key];
	}
	public function get_param_define_list(){
		return	array_keys( $this->info_param_define );
	}
	public function is_param_allowed( $key ){
		#	送信していいパラメータ名か調べる
		if( !array_key_exists( $key , $this->info_param_define ) ){
			#	未定義のパラメータなら
			if( $this->get_send_unknown_params_flg() ){
				return	true;
			}
			return	false;
		}
		$def = $this->get_param_define( $key , 'request' );
		if( !$def ){
			return	false;
		}
		return	true;
	}
	public function clear_param_define(){
		return	$this->info_param_define = array();
	}

	#--------------------------------------
	#	保存ファイル名のリライトルール情報の入出力
	public function get_localfilename_rewriterules(){
		return	$this->info_localfilename_rewriterules;
	}
	public function set_localfilename_rewriterules( $rules ){
		if( !is_array( $rules ) ){
			return	false;
		}
		$this->info_localfilename_rewriterules = $rules;
		return	true;
	}
	public function clear_localfilename_rewriterules(){
		$this->info_localfilename_rewriterules = array();
		return	true;
	}

	#--------------------------------------
	#	一括置換設定情報の入出力
	public function get_preg_replace_rules(){
		return	$this->info_preg_replace_rules;
	}
	public function set_preg_replace_rules( $rules ){
		if( !is_array( $rules ) ){
			return	false;
		}
		$this->info_preg_replace_rules = $rules;
		return	true;
	}
	public function clear_preg_replace_rules(){
		$this->info_preg_replace_rules = array();
		return	true;
	}

	#--------------------------------------
	#	対象外URLリストの入出力
	public function set_urllist_outofsite( $str_outofsite ){
		$this->clear_urllist_outofsite();//一旦リセット
		return	$this->put_urllist_outofsite( $str_outofsite );
	}
	public function clear_urllist_outofsite(){
		$this->info_urllist_outofsite = array();//一旦リセット
		return	true;
	}
	public function put_urllist_outofsite( $url_outofsite ){
		if( is_array( $url_outofsite ) ){
			#	配列をもらったら、全部処理
			foreach( $url_outofsite as $url ){
				$this->put_urllist_outofsite( $url );
			}
			return	true;
		}
		if( !is_string( $url_outofsite ) ){
			#	文字列じゃないと突っ込めない。
			return	false;
		}

		if( !is_array( $this->info_urllist_outofsite ) ){ $this->info_urllist_outofsite = array(); }

		$urllist_outofsite = preg_split( '/\r\n|\r|\n/' , $url_outofsite );
		if( is_array( $urllist_outofsite ) ){
			foreach( $urllist_outofsite as $url ){
				if( !strlen( $url ) ){ continue; }
				array_push( $this->info_urllist_outofsite , $url );
			}
		}

		return	true;
	}
	public function get_urllist_outofsite(){
		return	$this->info_urllist_outofsite;
	}
	public function is_outofsite( $url ){
		#	アンカーを削除
		$url = preg_replace( '/^(.*?)#.*$/si' , '\1' , $url );
		#	パラメータを削除
		$url = preg_replace( '/^(.*?)\?.*$/si' , '\1' , $url );

		$url_docroot = $this->get_url_docroot();
		if( !preg_match( '/^'.preg_quote( $url_docroot , '/' ).'/is' , $url ) ){
			#	ドキュメントルートURLから始まっていなければ、サイト外URLとみなす。
			return	true;
		}

		$notlist = $this->get_urllist_outofsite();
		if( !is_array( $notlist ) ){ $notlist = array(); }
		foreach( $notlist as $notline ){
			$not_preg_ptn = '/^'.preg_quote( $notline , '/' ).'$/i';//PxCrawler 0.3.7 : 大文字小文字を区別しなくなった。
			$not_preg_ptn = preg_replace( '/'.preg_quote('\*').'/' , '(?:.*?)' , $not_preg_ptn );	// ワイルドカードをPREGパターンに反映
			if( preg_match( $not_preg_ptn , $url ) ){
				// マッチしたら、それは追加しない。
				return	true;
			}
		}
		return	false;
	}

	#--------------------------------------
	#	追加スタートページURLリストの入出力
	public function set_urllist_startpages( $str_startpages ){
		$this->clear_urllist_startpages();//一旦リセット
		return	$this->put_urllist_startpages( $str_startpages );
	}
	public function clear_urllist_startpages(){
		$this->info_urllist_startpages = array();//一旦リセット
		return	true;
	}
	public function put_urllist_startpages( $url_startpages ){
		if( is_array( $url_startpages ) ){
			#	配列をもらったら、全部処理
			foreach( $url_startpages as $url ){
				$this->put_urllist_startpages( $url );
			}
			return	true;
		}
		if( !is_string( $url_startpages ) ){
			#	文字列じゃないと突っ込めない。
			return	false;
		}

		if( !is_array( $this->info_urllist_startpages ) ){ $this->info_urllist_startpages = array(); }

		$urllist_startpages = preg_split( '/\r\n|\r|\n/' , $url_startpages );
		if( !is_array( $urllist_startpages ) ){ $urllist_startpages = array(); }
		foreach( $urllist_startpages as $url ){
			if( !strlen( $url ) ){ continue; }
			array_push( $this->info_urllist_startpages , $url );
		}

		return	true;
	}
	public function get_urllist_startpages(){
		return	$this->info_urllist_startpages;
	}

	#--------------------------------------
	#	認証情報
	public function set_authentication_type( $val ){
		if( !strlen( $val ) ){
			$this->authentication_type = null;
			return	true;
		}
		switch( strtolower($val) ){
			case 'basic':
			case 'digest':
				$this->authentication_type = $val;
				break;
			default:
				return false; break;
		}
		return	true;
	}
	public function set_basic_authentication_id( $val ){
		$this->basic_authentication_id = $val;
		return	true;
	}
	public function set_basic_authentication_pw( $val ){
		$this->basic_authentication_pw = $val;
		return	true;
	}
	public function get_authentication_type(){
		return	$this->authentication_type;
	}
	public function get_basic_authentication_id(){
		return	$this->basic_authentication_id;
	}
	public function get_basic_authentication_pw(){
		return	$this->basic_authentication_pw;
	}
	public function isset_basic_authentication_info(){
		if( strlen( $this->basic_authentication_id ) && strlen( $this->basic_authentication_pw ) ){
			return	true;
		}
		return	false;
	}

	#--------------------------------------
	#	プログラムIDの一覧を得る
	public function get_program_list(){
		$program_dir = $this->pcconf->get_program_home_dir( $this->info_project_id );
		if( !is_dir( $program_dir ) ){ return array(); }

		$itemlist = $this->px->dbh()->ls( $program_dir );
		if( !is_array( $itemlist ) ){ return array(); }

		$RTN = array();
		if( !is_array( $itemlist ) ){ $itemlist = array(); }
		foreach( $itemlist as $filename ){
			if( $filename == '.' || $filename == '..' ){ continue; }
			if( is_dir( $program_dir.'/'.$filename ) ){
				array_push( $RTN , $filename );
			}
		}

		sort($RTN);

		return	$RTN;
	}




	/**
	 * 新しいプロジェクトを作成する
	 */
	public function create_new_project( $project_id ){
		$this->info_project_id = $project_id;
		$path_project_dir = $this->get_project_home_dir();
		if( is_dir( $path_project_dir ) ){
			#	既にディレクトリが存在していたら、ダメ。
			return	false;
		}
		if( !$this->px->dbh()->mkdir_all( $path_project_dir ) ){
			#	ディレクトリの作成に失敗したら、ダメ。
			return	false;
		}
		return	true;

	}

	/**
	 * プロジェクトのホームディレクトリを取得する
	 */
	public function get_project_home_dir(){
		if( !strlen( $this->info_project_id ) ){ return false; }
		$projHome = $this->pcconf->get_proj_dir( $this->info_project_id );
		return	$projHome;
	}

	/**
	 * iniファイルを読み込んで、配列にして返す。
	 */
	public function load_ini( $path_ini ){
		if( !$this->px->dbh()->is_readable( $path_ini ) ){
			return	false;
		}
		$ini_lines = $this->px->dbh()->file_get_lines( $path_ini );
		if( !is_array( $ini_lines ) ){
			return	false;
		}

		$RTN = array( 'common'=>array() , 'sec'=>array() );
		$current_section = '';
		if( !is_array( $ini_lines ) ){ $ini_lines = array(); }
		foreach( $ini_lines as $Line ){
			$Line = trim( $Line );
			if( preg_match( '/^;/' , $Line ) ){
				#	コメント行
				continue;
			}
			if( !strlen( $Line ) ){
				#	空白行
				continue;
			}

			if( preg_match( '/^\[(.*)\]$/' , $Line , $result ) ){
				$current_section = $result[1];
				$RTN['sec'][$current_section] = array();
				continue;
			}

			if( preg_match( '/^(.*?)=(.*)$/' , $Line , $result ) ){
				if( strlen( $current_section ) ){
					$RTN['sec'][$current_section][trim($result[1])] = trim($result[2]);
				}else{
					$RTN['common'][trim($result[1])] = trim($result[2]);
				}
				continue;
			}

		}
		return	$RTN;
	}




	/**
	 * URLをhttp://から始まる絶対URLに調整する
	 */
	public function optimize_url( $url ){
		if( preg_match( '/#/' , $url ) ){
			#	アンカーは消しとく。
			$url = preg_replace( '/^(.*?)#.*$/si' , "$1" , $url );
		}

		if( preg_match( '/^([a-z0-9]+)\:\/\/([a-z0-9\-\_\.]+?(?:\:[0-9]+)?)\/(.*?)(?:\?(.*))?$/i' , $url , $result ) ){
			$PROTOCOL = $result[1];
			$DOMAIN = $result[2];
			$PATH = $result[3];
			$PARAM = $result[4];
			unset( $result );

			if( strlen( $PARAM ) ){
				$param_list = explode( '&' , $PARAM );
				$GET = array();
				foreach( $param_list as $param_cont ){
					if( !strlen( $param_cont ) ){ continue; }
					list( $prm_key , $prm_val ) = explode( '=' , $param_cont );
					$GET[urldecode( $prm_key )] = urldecode( $prm_val );
				}

				$request_vals = array();
				foreach( $GET as $param_key=>$param_val ){
					if( !$this->is_param_allowed( $param_key ) ){
						continue;
					}
					array_push( $request_vals , urlencode( $param_key ).'='.urlencode( $param_val ) );
				}
				$PARAM = '';
				if( count( $request_vals ) ){
					$PARAM = '?'.implode( '&' , $request_vals );
				}

			}

			$url = strtolower( $PROTOCOL ).'://'.strtolower( $DOMAIN ).'/'.$PATH.$PARAM;
		}
		return	$url;
	}//optimize_url()

	/**
	 * URLを /http/～～ で始まる内部パス(保存先パス)に変換する
	 */
	public function url2localpath( $url , $post_data = null ){
		if( strpos( $url , '#' ) ){
			#	アンカーは削除する。
			list( $url , $anchor ) = explode( '#' , $url, 2 );
			unset( $anchor );
		}

		if( !preg_match( '/^([a-z0-9]+)\:\/\/([a-z0-9\-\_\.]+?(?:\:[0-9]+)?)\/(.*)$/i' , $url , $result ) ){
			#	解析不能なURLだったら
			return '/http/'.urlencode( $url );
		}
		$PROTOCOL = $result[1];
		$DOMAIN = $result[2];
		$PATH = '/'.$result[3];

		if( preg_match( '/^\/(.*?)(?:\?(.*))??$/i' , $PATH , $result ) ){
			$PATH = '/'.$result[1];
			$PARAM = $result[2];
			if( preg_match( '/\/$/' , $PATH ) ){
				$PATH .= $this->get_default_filename();
			}
		}

		#	パラメータをパース
		$GET = array();
		if( strlen( $post_data ) ){
			$post_data_list = explode( '&' , $post_data );
			foreach( $post_data_list as $post_data_line ){
				if( !strlen( $post_data_line ) ){ continue; }
				list( $prm_key , $prm_val ) = explode( '=' , $post_data_line );
				$GET[urldecode( $prm_key )] = urldecode( $prm_val );
			}
		}
		if( strlen( $PARAM ) ){
			$param_list = explode( '&' , $PARAM );
			foreach( $param_list as $param_line ){
				if( !strlen( $param_line ) ){ continue; }
				list( $prm_key , $prm_val ) = explode( '=' , $param_line );
				$GET[urldecode( $prm_key )] = urldecode( $prm_val );
			}
		}

		#--------------------------------------
		#	保存ファイル名の変換ルール
		$rewrite_rules = $this->get_localfilename_rewriterules();
		if( !is_array( $rewrite_rules ) ){ $rewrite_rules = array(); }
		foreach( $rewrite_rules as $rule ){

			#--------------------------------------
			#	実行ファイルパスの条件を調べる
			if( !strlen( $rule['before'] ) ){
				$rule['before'] = '*';
			}

			$before_preg = '/^'.preg_quote( $rule['before'] , '/' ).'$/';
			$before_preg = preg_replace( '/'.preg_quote( '\*' , '/' ).'/' , '(.*?)' , $before_preg );//ワイルドカードの正規表現化
			if( !@preg_match( $before_preg , $PATH , $wc_before_preg ) ){
				#	条件にマッチしなければ、スルー。
				continue;
			}

			#--------------------------------------
			#	必須URLパラメータの条件を調べる
			if( strlen( $rule['requiredparam'] ) ){
				$required_param = $rule['requiredparam'];
				$andlist = explode( '&' , $required_param );
				$urlparam_result = true;
				foreach( $andlist as $andline ){
					$is_current_and_ok = false;
					if( !strlen( $andline ) ){ continue; }
					$orlist = explode( '|' , $andline );
					foreach( $orlist as $orline ){
						if( !strlen( $orline ) ){ continue; }
						if( strlen( $GET[$orline] ) ){
							$is_current_and_ok = true;
							break;
						}
					}
					if( !$is_current_and_ok ){
						$urlparam_result = false;
						break;
					}
				}
				if( !$urlparam_result ){
					#	条件にマッチしなければ、スルー。
					continue;
				}
			}

			#--------------------------------------
			#	変換
			$CURRENT_RULE_SRC = $rule['after'];
			preg_match_all( '/\{\$(param|dirname|basename|extension|basename_body|wildcard)(?:\.(.*?))?\}/' , $rule['after'] , $rule_result );
			for( $i = 0; $rule_result[0][$i]; $i ++ ){
				$replace_to = null;
				switch( $rule_result[1][$i] ){
					case 'param':
						$replace_to = urlencode( $GET[$rule_result[2][$i]] );
						break;
					case 'dirname':
						$replace_to = dirname( $PATH );
						break;
					case 'basename':
						$replace_to = basename( $PATH );
						break;
					case 'extension':
						$replace_to = urlencode( preg_replace( '/^.*\.(.*?)$/' , '$1' , $PATH ) );
						break;
					case 'basename_body':
						$replace_to = basename( t::trimext( $PATH ) );
						break;
					case 'wildcard':
						if( intval($rule_result[2][$i]) > 0 ){
							$replace_to = $wc_before_preg[intval($rule_result[2][$i])];
						}
						break;
					default:
						break;
				}
				if( is_null( $replace_to ) ){ break; }//補填できない要素があったら、不適用。
				$CURRENT_RULE_SRC = preg_replace( '/'.preg_quote( $rule_result[0][$i] ).'/' , $replace_to , $CURRENT_RULE_SRC );
			}
			#	/ 変換
			#--------------------------------------

			$PATH = $CURRENT_RULE_SRC;
			break;
		}

		$DOMAIN = preg_replace( '/[^a-zA-Z0-9\_\-\.]/' , '_' , $DOMAIN );
		$RTN = '/'.$PROTOCOL.'/'.$DOMAIN.'/'.$PATH;
		$RTN = preg_replace( '/\/+/' , '/' , $RTN );
		return	$RTN;
	}//url2localpath()

}

?>