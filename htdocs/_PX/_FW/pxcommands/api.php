<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: apiを実行する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_api extends px_bases_pxcommand{

	private $paths_cache_dir = array();
	private $command = array();

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->command = $this->get_command();

		switch( $this->command[1] ){
			case 'get':
				//各種情報の取得
				$this->api_get();
				break;
			case 'dlfile':
				//ファイルダウンロード
				$this->api_dlfile();
				break;
			case 'ulfile':
				//ファイルアップロード
				$this->api_ulfile();
				break;
			case 'ls':
				//ファイルの一覧を取得
				$this->api_ls();
				break;
			case 'delete':
				//ファイル削除
				$this->api_delete();
				break;
			case 'hash':
				//ハッシュ値を取得
				$this->api_hash();
				break;
		}

		if( !strlen($this->command[1]) ){
			$this->homepage();
		}
		$this->error();
	}//__construct()

	/**
	 * ホームページを表示する。
	 */
	private function homepage(){
		$src = '';
		$src .= '<p>このコマンドは、外部アプリケーションへのAPIを提供します。</p>'."\n";
		print $this->html_template($src);
		exit;
	}

	/**
	 * エラーメッセージ
	 */
	private function error(){
		$src = '';
		$src .= '<p class="error">未定義のコマンドを受け付けました。</p>'."\n";
		print $this->html_template($src);
		exit;
	}



	/**
	 * [API] api.dlfile.config
	 */
	private function api_dlfile(){
		header('Content-type: text/plain; charset=UTF-8');
		$path = $this->get_target_file_path();
		if( is_null($path) ){
			header('Status: 404 NotFound.');
			print 'Unknown or illegal path was given.';
			exit;
		}
		if( is_dir($path) ){
			header('Status: 404 NotFound.');
			print 'Target path is a directory.';
			exit;
		}
		if( !is_file($path) ){
			header('Status: 404 NotFound.');
			print 'Target path is Not a file.';
			exit;
		}
		$content = $this->px->dbh()->file_get_contents($path);
		print $content;
		exit;
	}//api_dlfile()

	/**
	 * [API] api.ulfile.*
	 */
	private function api_ulfile(){
		$path = $this->get_target_file_path();
		if( is_null($path) ){
			//nullならNG
			print $this->data_convert( array('result'=>0, 'message'=>'Unknown or illegal path was given.') );
			exit;
		}
		if( is_dir($path) ){
			//対象パスにディレクトリが既に存在していたらNG
			print $this->data_convert( array('result'=>0, 'message'=>'A directory exists.') );
			exit;
		}
		if( !is_dir(dirname($path)) ){
			//ディレクトリがなければ作る。
			if( !$this->px->dbh()->mkdir_all(dirname($path)) ){
				print $this->data_convert( array('result'=>0, 'message'=>'Disable to make parent directory.') );
				exit;
			}
		}
		if( !$this->px->dbh()->save_file( $path, $this->px->req()->get_param('bin') ) ){
			print $this->data_convert( array('result'=>0, 'message'=>'Disable to save this file.') );
			exit;
		}
		print $this->data_convert( array('result'=>1) );
		exit;
	}//api_ulfile()

	/**
	 * [API] api.ls.config
	 */
	private function api_ls(){
		switch( $this->command[2] ){
			case 'content':
			case 'sitemap':
			case 'theme':
			case 'px':
				break;
			default:
				print $this->data_convert( array('result'=>0, 'message'=>'Illegal command "'.$this->command[2].'" was given.') );
				exit;
				break;
		}

		$path = $this->get_target_file_path();
		if( is_null($path) ){
			//  ターゲットがただしく指示されていない。
			print $this->data_convert( array('result'=>0, 'message'=>'Unknown or illegal path was given.') );
			exit;
		}
		if( is_file($path) ){
			//  ターゲットがファイル。ディレクトリを指示しなければならない。
			print $this->data_convert( array('result'=>0, 'message'=>'Target is a file. Give a directory path.') );
			exit;
		}
		if( !is_dir($path) ){
			//  ターゲットがディレクトリではない。
			print $this->data_convert( array('result'=>0, 'message'=>'Directory is not exists.') );
			exit;
		}
		if($this->command[2]=='content'){
			//コンテンツディレクトリ内の除外ファイル
			$tmp_path = $this->px->dbh()->get_realpath($path);
			if( preg_match( '/^'.preg_quote($this->px->dbh()->get_realpath($this->px->get_conf('paths.px_dir')),'/').'/' , $this->px->dbh()->get_realpath($path) ) ){
				// _PX の中身はこのAPIからは操作できない。
				print $this->data_convert( array('result'=>0, 'message'=>'Directory "_PX" is not accepted to access with command "content". Please use command "px".') );
				exit;
			}
		}

		$rtn = array();
		$ls = $this->px->dbh()->ls($path);
		foreach( $ls as $file_name ){
			if($this->command[2]=='content'){
				//コンテンツディレクトリ内の除外ファイル
				if( preg_match( '/^'.preg_quote($this->px->dbh()->get_realpath($this->px->get_conf('paths.px_dir')),'/').'/' , $this->px->dbh()->get_realpath($path.'/'.$file_name) ) ){
					// _PX の中身はこのAPIからは操作できない。
					continue;
				}
				if( $this->px->dbh()->get_realpath($path.'/'.$file_name) == $this->px->dbh()->get_realpath(dirname($_SERVER['SCRIPT_FILENAME']).'/_px_execute.php') ){
					//  /_px_execute.php は除外
					continue;
				}
				if( $this->px->dbh()->get_realpath($path.'/'.$file_name) == $this->px->dbh()->get_realpath(dirname($_SERVER['SCRIPT_FILENAME']).'/.htaccess') ){
					//  /.htaccess は除外
					continue;
				}
			}

			$file_info = array(
				'type'=>null,
				'name'=>$file_name,
			);
			if( is_dir($path.'/'.$file_name) ){
				$file_info['type'] = 'directory';
			}elseif( is_file($path.'/'.$file_name) ){
				$file_info['type'] = 'file';
			}
			array_push( $rtn, $file_info );
		}
		print $this->data_convert( $rtn );
		exit;

	}//api_ls()

	/**
	 * [API] api.delete.*
	 */
	private function api_delete(){
		switch( $this->command[2] ){
			case 'content':
			case 'sitemap':
			case 'theme':
			case 'px':
				//これ以外は削除の機能は提供しない
				break;
			default:
				print $this->data_convert( array('result'=>0, 'message'=>'Illegal command "'.$this->command[2].'" was given.') );
				exit;
				break;
		}

		//  フールプルーフ
		$input_path = trim( $this->px->req()->get_param('path') );
		$input_path = preg_replace('/\\\\/','/',$input_path);
		$input_path = preg_replace('/\/+/','/',$input_path);
		if( !strlen( $input_path ) ){
			print $this->data_convert( array('result'=>0, 'message'=>'Unknown or illegal path was given.') );
			exit;
		}
		if( $input_path == '/' ){
			print $this->data_convert( array('result'=>0, 'message'=>'Unknown or illegal path was given.') );
			exit;
		}
		//  / フールプルーフ

		$path = $this->get_target_file_path();
		if( is_null($path) || !file_exists($path) ){
			print $this->data_convert( array('result'=>0, 'message'=>'Target file or directory is not exists.') );
			exit;
		}
		if( !$this->px->dbh()->rm( $path ) ){
			print $this->data_convert( array('result'=>0, 'message'=>'Disable to delete file or directory.') );
			exit;
		}
		print $this->data_convert( array('result'=>1) );
		exit;
	}//api_delete()

	/**
	 * ダウン・アップロードファイルのパスを得る
	 * api.ulfile, api.dlfile が使用する。
	 */
	private function get_target_file_path(){
		$rtn = null;
		$path = $this->px->req()->get_param('path');

		$is_path = true;
		if( !strlen( $path ) ){
			$is_path = '/';
		}
		if( preg_match( '/(?:\/|^)\.\.(?:\/|$)/si', $path ) ){
			return null;
			$is_path = false;
		}

		switch( $this->command[2] ){
			case 'config':
				$rtn = $this->px->get_path_conf();
				break;
			case 'sitemap_definition':
				$rtn = $this->px->get_conf('paths.px_dir').'configs/sitemap_definition.csv';
				break;
			case 'content':
				if(!$is_path){ return null; }
				$rtn = './'.$path;
				break;
			case 'sitemap':
				if(!$is_path){ return null; }
				$rtn = $this->px->get_conf('paths.px_dir').'sitemaps/'.$path;
				break;
			case 'theme':
				$rtn = $this->px->get_conf('paths.px_dir').'themes/'.$this->command[3].'/'.$path;
				break;
			case 'px':
				if(!$is_path){ return null; }
				$rtn = $this->px->get_conf('paths.px_dir').$path;
				break;
		}
		return $this->px->dbh()->get_realpath($rtn);
	}

	/**
	 * [API] api.get.*
	 */
	private function api_get(){
		switch( $this->command[2] ){
			case 'version':
				$val = $this->px->get_version();
				print $this->data_convert( $val );
				break;
			case 'config':
				$val = $this->px->get_conf_all();
				print $this->data_convert( $val );
				break;
			case 'sitemap':
				$val = $this->px->site()->get_sitemap();
				print $this->data_convert( $val );
				break;
			case 'sitemap_definition':
				$val = $this->px->site()->get_sitemap_definition();
				print $this->data_convert( $val );
				break;
			default:
				print $this->data_convert( array('result'=>0) );
				break;
		}
		exit;
	}

	/**
	 * [API] api.hash
	 */
	private function api_hash(){
		header('Content-type: text/csv');
		if( strpos( $_SERVER['HTTP_USER_AGENT'] , 'MSIE' ) ){
			#	MSIE対策
			#	→こんな問題 http://support.microsoft.com/kb/323308/ja
			header( 'Cache-Control: public' );
			header( 'Pragma: public' );
		}
		header( 'Content-Disposition: attachment; filename=PxFW_apiHash_'.date('Ymd_His').'.csv' );


		#	出力バッファをすべてクリア
		while( @ob_end_clean() );

		print '"[Pickles Framework (version:'.$this->px->get_version().')]"'."\n";
		print '"--docroot"'."\n";
		$this->mk_hash_list('.');
		print '"--_PX"'."\n";
		$this->mk_hash_list($this->px->get_conf('paths.px_dir'));
		exit;
	}
	/**
	 * ディレクトリ内のファイルの一覧とそのMD5ハッシュ値を再帰的に標準出力する。
	 * [API] api.hash 内で使用。
	 */
	private function mk_hash_list($base_dir,$local_path=''){
		$file_list = $this->px->dbh()->ls($base_dir.$local_path);
		sort($file_list);
		foreach($file_list as $file_name){
			if($this->px->dbh()->get_realpath($base_dir.$local_path.'/'.$file_name) == $this->px->dbh()->get_realpath($this->px->get_conf('paths.px_dir'))){
				continue;
			}
			if(is_file($base_dir.$local_path.'/'.$file_name)){
				print '"file","'.$local_path.'/'.$file_name.'","'.md5_file($base_dir.$local_path.'/'.$file_name).'"'."\n";
			}elseif(is_dir($base_dir.$local_path.'/'.$file_name)){
				print '"dir","'.$local_path.'/'.$file_name.'"'."\n";
				$this->mk_hash_list($base_dir,$local_path.'/'.$file_name);
			}
			flush();
		}
	}

	// -------------------------------------

	/**
	 * データを自動的に加工して返す
	 */
	private function data_convert($val){
		$data_type = $this->px->req()->get_param('type');
		header('Content-type: application/xml; charset=UTF-8');
		if( $data_type == 'json' ){
			header('Content-type: application/json; charset=UTF-8');
		}elseif( $data_type == 'jsonp' ){
			header('Content-type: application/javascript; charset=UTF-8');
		}
		switch( $data_type ){
			case 'jsonp':
				return $this->data2jsonp($val);
				break;
			case 'json':
				return $this->data2json($val);
				break;
			case 'xml':
			default:
				return $this->data2xml($val);
				break;
		}
		return t::data2jssrc($val);
	}

	/**
	 * データをXMLに加工して返す
	 */
	private function data2xml($val){
		return '<api>'.t::data2xml($val).'</api>';
	}

	/**
	 * データをJSONに加工して返す
	 */
	private function data2json($val){
		return t::data2jssrc($val);
	}

	/**
	 * データをJSONPに加工して返す
	 */
	private function data2jsonp($val){
		//JSONPのコールバック関数名は、パラメータ callback に受け取る。
		$cb = trim( $this->px->req()->get_param('callback') );
		if( !strlen($cb) ){
			$cb = 'callback';
		}
		return $cb.'('.t::data2jssrc($val).');';
	}

}

?>