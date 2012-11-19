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
	 * [API] api.ulfile.*
	 */
	private function api_ulfile(){
		header('Content-type: text/json; charset=UTF-8');
		$path = $this->get_target_file_path();
		if( is_null($path) || !is_file($path) ){
			print '{result:0}';
			exit;
		}
		if( !$this->px->dbh()->save_file( $path, $this->px->req()->get_param('bin') ) ){
			print '{result:0}';
			exit;
		}
		print '{result:1}';
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
	 * ダウン・アップロードファイルのパスを得る
	 */
	private function get_target_file_path(){
		$rtn = null;
		switch( $this->command[2] ){
			case 'config':
				$rtn = $this->px->get_path_conf();
				break;
		}
		return $rtn;
	}

}

?>