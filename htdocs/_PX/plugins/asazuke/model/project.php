<?php

/**
 * モデル：プロジェクト
 * Copyright (C)Tomoya Koyanagi.
 */
class pxplugin_asazuke_model_project{

	private $px;
	private $pcconf;

	private $info_project_id;
	private $info_path_startpage = null;
	private $info_path_docroot = null;
	private $info_accept_html_file_max_size = 0;

	private $info_select_cont_main = array();
	private $info_select_cont_subs = array();
	private $info_dom_convert = array();
	private $info_select_breadcrumb = array();
	private $info_replace_title = array();
	private $info_replace_strings = array();
	private $info_ignore_common_resources = array();


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
	public function &factory_program(){
		$objPath = '/asazuke/model/program.php';
		$className = $this->px->load_px_plugin_class( $objPath );
		if( !$className ){
			$this->px->error()->error_log( 'プログラムオブジェクトのロードに失敗しました。['.$objPath.']' , __FILE__ , __LINE__ );
		}
		$obj = new $className( $this->px , $this->pcconf , $this );
		$obj->load_program();

		return	$obj;
	}

	/**
	 * 既存のプロジェクト情報を開いて、メンバにセット。
	 */
	public function load_project(){
		// $this->info_project_id = $project_id;
		$path_project_dir = $this->get_project_home_dir();
		if( !is_dir( $path_project_dir ) ){ return false; }
			#	プロジェクトが存在しなければ、終了

		#	基本情報
		$project_ini = $this->load_ini( $path_project_dir.'/project.ini' );

		$this->set_path_startpage( $project_ini['common']['path_startpage'] );
		$this->set_path_docroot( $project_ini['common']['path_docroot'] );
		$this->set_accept_html_file_max_size( $project_ini['common']['accept_html_file_max_size'] );

		$this->px->dbh()->fclose( $path_project_dir.'/project.ini' );

		#	select_cont_main
		$csv = $this->px->dbh()->read_csv_utf8( $path_project_dir.'/select_cont_main.csv' );
		$tmpAry = array();
		if(!is_array($csv)){
			$csv = array();
		}
		foreach($csv as $csvRow){
			$tmpAryRow = array();
			$tmpAryRow['name'] = $csvRow[0];
			$tmpAryRow['selector'] = $csvRow[1];
			$tmpAryRow['index'] = $csvRow[2];
			array_push($tmpAry, $tmpAryRow);
		}
		$this->set_select_cont_main( $tmpAry );
		unset($tmpAry);

		#	select_cont_subs
		$csv = $this->px->dbh()->read_csv_utf8( $path_project_dir.'/select_cont_subs.csv' );
		$tmpAry = array();
		if(!is_array($csv)){
			$csv = array();
		}
		foreach($csv as $csvRow){
			$tmpAryRow = array();
			$tmpAryRow['name'] = $csvRow[0];
			$tmpAryRow['selector'] = $csvRow[1];
			$tmpAryRow['index'] = $csvRow[2];
			$tmpAryRow['cabinet_name'] = $csvRow[3];
			array_push($tmpAry, $tmpAryRow);
		}

		$this->set_select_cont_subs( $tmpAry );
		unset($tmpAry);


		#	dom_convert
		$csv = $this->px->dbh()->read_csv_utf8( $path_project_dir.'/dom_convert.csv' );
		$tmpAry = array();
		if(!is_array($csv)){
			$csv = array();
		}
		foreach($csv as $csvRow){
			$tmpAryRow = array();
			$tmpAryRow['name'] = $csvRow[0];
			$tmpAryRow['selector'] = $csvRow[1];
			$tmpAryRow['replace_to'] = $csvRow[2];
			array_push($tmpAry, $tmpAryRow);
		}

		$this->set_dom_convert( $tmpAry );
		unset($tmpAry);


		#	select_breadcrumb
		$csv = $this->px->dbh()->read_csv_utf8( $path_project_dir.'/select_breadcrumb.csv' );
		$tmpAry = array();
		if(!is_array($csv)){
			$csv = array();
		}
		foreach($csv as $csvRow){
			$tmpAryRow = array();
			$tmpAryRow['name'] = $csvRow[0];
			$tmpAryRow['selector'] = $csvRow[1];
			$tmpAryRow['index'] = $csvRow[2];
			array_push($tmpAry, $tmpAryRow);
		}

		$this->set_select_breadcrumb( $tmpAry );
		unset($tmpAry);

		#	replace_title
		$csv = $this->px->dbh()->read_csv_utf8( $path_project_dir.'/replace_title.csv' );
		$tmpAry = array();
		if(!is_array($csv)){
			$csv = array();
		}
		foreach($csv as $csvRow){
			$tmpAryRow = array();
			$tmpAryRow['name'] = $csvRow[0];
			$tmpAryRow['preg_pattern'] = $csvRow[1];
			$tmpAryRow['replace_to'] = $csvRow[2];
			array_push($tmpAry, $tmpAryRow);
		}

		$this->set_replace_title( $tmpAry );
		unset($tmpAry);

		#	replace_strings
		$csv = $this->px->dbh()->read_csv_utf8( $path_project_dir.'/replace_strings.csv' );
		$tmpAry = array();
		if(!is_array($csv)){
			$csv = array();
		}
		foreach($csv as $csvRow){
			$tmpAryRow = array();
			$tmpAryRow['name'] = $csvRow[0];
			$tmpAryRow['preg_pattern'] = $csvRow[1];
			$tmpAryRow['replace_to'] = $csvRow[2];
			array_push($tmpAry, $tmpAryRow);
		}
		$this->set_replace_strings( $tmpAry );
		unset($tmpAry);

		#	ignore_common_resources
		$csv = $this->px->dbh()->read_csv_utf8( $path_project_dir.'/ignore_common_resources.csv' );
		$tmpAry = array();
		if(!is_array($csv)){
			$csv = array();
		}
		foreach($csv as $csvRow){
			$tmpAryRow = array();
			$tmpAryRow['name'] = $csvRow[0];
			$tmpAryRow['path'] = $csvRow[1];
			array_push($tmpAry, $tmpAryRow);
		}

		$this->set_ignore_common_resources( $tmpAry );
		unset($tmpAry);

		return	true;
	}//load_project()

	/**
	 * メインコンテンツセレクタ設定を読み込む
	 */
	public function get_select_cont_main(){ return $this->info_select_cont_main; }
	public function set_select_cont_main( $ary ){ $this->info_select_cont_main = $ary; return true; }

	/**
	 * サブコンテンツセレクタ設定を読み込む
	 */
	public function get_select_cont_subs(){ return $this->info_select_cont_subs; }
	public function set_select_cont_subs( $ary ){ $this->info_select_cont_subs = $ary; return true; }

	/**
	 * DOM変換設定を読み込む
	 */
	public function get_dom_convert(){ return $this->info_dom_convert; }
	public function set_dom_convert( $ary ){ $this->info_dom_convert = $ary; return true; }

	/**
	 * パンくず解析ルール設定を読み込む
	 */
	public function get_select_breadcrumb(){ return $this->info_select_breadcrumb; }
	public function set_select_breadcrumb( $ary ){ $this->info_select_breadcrumb = $ary; return true; }

	/**
	 * タイトル置換ルール設定を読み込む
	 */
	public function get_replace_title(){ return $this->info_replace_title; }
	public function set_replace_title( $ary ){ $this->info_replace_title = $ary; return true; }

	/**
	 * 文字列置換ルール設定を読み込む
	 */
	public function get_replace_strings(){ return $this->info_replace_strings; }
	public function set_replace_strings( $ary ){ $this->info_replace_strings = $ary; return true; }

	/**
	 * 除外共通リソース設定を読み込む
	 */
	public function get_ignore_common_resources(){ return $this->info_ignore_common_resources; }
	public function set_ignore_common_resources( $ary ){ $this->info_ignore_common_resources = $ary; return true; }
	public function is_ignore_common_resources($path){
		$this->matched_ignore_common_resources = null;
		$rules = $this->get_ignore_common_resources();
		foreach( $rules as $rule ){
			$preg_pattern = preg_quote($rule['path'], '/');
			$preg_pattern = preg_replace('/'.preg_quote('\*','/').'/s', '(?:.*?)', $preg_pattern);
			if( preg_match( '/^'.$preg_pattern.'$/s', $path ) ){
				// 報告
				$this->matched_ignore_common_resources = $rule['name'];
				return true;
			}
		}
		return false;
	}
	public function last_matched_ignore_common_resources(){
		return $this->matched_ignore_common_resources;
	}



	/**
	 * プロジェクトの現在の状態を保存する
	 */
	public function save_project(){
		// if( !strlen( $this->get_project_id() ) ){ return false; }

		$path_project_dir = $this->get_project_home_dir();

		if( !is_dir( $path_project_dir ) ){ return false; }
			#	プロジェクトが存在しなければ、終了

		#======================================
		#	project.ini

		#	基本情報
		$project_ini_src = '';

		$project_ini_src .= 'path_startpage='.$this->get_path_startpage()."\n";
		$project_ini_src .= 'path_docroot='.$this->get_path_docroot()."\n";
		$project_ini_src .= 'accept_html_file_max_size='.$this->get_accept_html_file_max_size()."\n";

		$project_ini_src .= ''."\n";

		if( !$this->px->dbh()->save_file( $path_project_dir.'/project.ini' , $project_ini_src ) ){
			return	false;
		}
		$this->px->dbh()->fclose($path_project_dir.'/project.ini');

		return	true;
	}//save_project()


	/**
	 * スタートページURLの入出力
	 */
	public function set_path_startpage( $path_startpage ){
		$this->info_path_startpage = $path_startpage;
		return	true;
	}
	public function get_path_startpage(){
		return	$this->info_path_startpage;
	}

	/**
	 * ドキュメントルートURLの入出力
	 */
	public function set_path_docroot( $path_docroot ){
		$this->info_path_docroot = $path_docroot;
		return	true;
	}
	public function get_path_docroot(){
		return	$this->info_path_docroot;
	}

	/**
	 * 許容するオリジナルファイルの最大サイズ の入出力
	 */
	public function set_accept_html_file_max_size( $accept_html_file_max_size ){
		$this->info_accept_html_file_max_size = intval($accept_html_file_max_size);
		return	true;
	}
	public function get_accept_html_file_max_size(){
		return	$this->info_accept_html_file_max_size;
	}


	/**
	 * プロジェクトのホームディレクトリを取得する
	 */
	public function get_project_home_dir(){
		// if( !strlen( $this->info_project_id ) ){ return false; }
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