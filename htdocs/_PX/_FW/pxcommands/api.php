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
		if( is_null($path) || !is_file($path) ){
			header('Status: 404 NotFound.');
			print 'target file is Not Found.';
			exit;
		}
		$content = $this->px->dbh()->file_get_contents($path);
		print $content;
		exit;
	}

	/**
	 * [API] api.ulfile.*
	 */
	private function api_ulfile(){
		$path = $this->get_target_file_path();
		if( is_null($path) || !is_file($path) ){
			print $this->data_convert( array('result'=>0) );
			exit;
		}
		if( !$this->px->dbh()->save_file( $path, $this->px->req()->get_param('bin') ) ){
			print $this->data_convert( array('result'=>0) );
			exit;
		}
		print $this->data_convert( array('result'=>1) );
		exit;
	}

	/**
	 * ダウン・アップロードファイルのパスを得る
	 * api.ulfile, api.dlfile が使用する。
	 */
	private function get_target_file_path(){
		$rtn = null;

		$is_path = true;
		if( !strlen( $this->px->req()->get_param('path') ) ){
			$is_path = false;
		}
		if( preg_match( '/(?:\/|^)\.\.(?:\/|$)/si', $this->px->req()->get_param('path') ) ){
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
				$rtn = './'.$this->px->req()->get_param('path');
				break;
			case 'sitemap':
				if(!$is_path){ return null; }
				$rtn = $this->px->get_conf('paths.px_dir').'sitemaps/'.$this->px->req()->get_param('path');
				break;
			case 'theme':
				$rtn = $this->px->get_conf('paths.px_dir').'themes/'.$this->command[3].'/'.$this->px->req()->get_param('path');
				break;
		}
		return $rtn;
	}

	/**
	 * [API] api.get.*
	 */
	private function api_get(){
		switch( $this->command[2] ){
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