<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: initializeを表示する
 **/
class px_pxcommands_initialize extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );

		$command = $this->get_command();

		switch( $command[1] ){
			case 'run':
				$this->execute();
				break;
			case 'download':
				$this->download_sql();
				break;
			default:
				$this->homepage();
				break;
		}
	}//__construct()

	/**
	 * ホームページを表示する。
	 */
	private function homepage(){
		$command = $this->get_command();
		$src = '';
		$src .= '<p>サイトの設定、データベース作成等の初期セットアップ処理を行います。</p>'."\n";
		$src .= '<p>この操作は、サイトのセットアップ時に<strong>1度だけ実行します</strong>。すでに1度実行している場合は、この操作は行わないでください。</p>'."\n";
		$src .= '<form action="?" method="get" target="_blank">'."\n";
		$src .= '<p class="center"><button class="xlarge">イニシャライズを実行する</button></p>'."\n";
		$src .= '<div><input type="hidden" name="PX" value="'.t::h($command[0]).'.run" /></div>'."\n";
		$src .= '</form>'."\n";
		$src .= '<p>または、セットアップ用のSQL文をダウンロードしたい場合は、次のリンクをクリックしてください。</p>'."\n";
		$src .= '<ul>'."\n";
		$src .= '	<li><a href="?PX='.t::h($command[0]).'.download">SQLをダウンロード</a></li>'."\n";
		$src .= '</ul>'."\n";
		print $this->html_template($src);
		exit;
	}

	/**
	 * Execute PX Command "initialize".
	 */
	private function execute(){
		$command = $this->get_command();
		@header('Content-type: text/plain');
		print ''.$command[0].' | Pickles Framework (version:'.$this->px->get_version().')'."\n";
		print 'project "'.$this->px->get_conf('project.name').'" ('.$this->px->get_conf('project.id').')'."\n";
		print '------'."\n";
		$class_name_dao_init = $this->px->load_px_class('/daos/initialize.php');
		$dao_init = new $class_name_dao_init( $this->px );

		print ''."\n";
		print '[----  init PxFW user tables  ----]'."\n";
		if( $dao_init->create_user_tables(0) ){
			print 'result: success'."\n";
			foreach( $dao_init->get_logs() as $log_text ){
				print '- '.$log_text."\n";
			}
			print ''."\n";
		}else{
			print 'result: FAILED'."\n";
			foreach( $dao_init->get_logs() as $log_text ){
				print '- '.$log_text."\n";
			}
			print ''."\n";
			$errors = $dao_init->get_errors();
			foreach( $errors as $error ){
				print '[ERROR] '.$error['message'].' (Line: '.$error['line'].')'."\n";
			}
			print ''."\n";
		}
		print ''."\n";
		print '------'."\n";

		$plugins = $this->scan_plugins();
		foreach( $plugins as $plugin_name ){
			print ''."\n";
			print '[----  init plugin "'.$plugin_name.'"  ----]'."\n";
			$class_name = $this->px->load_px_plugin_class( $plugin_name.'/register/initialize.php' );
			$plugin_initializer = new $class_name($this->px);
			if( $plugin_initializer->execute(0) ){
				print 'result: success'."\n";
				foreach( $plugin_initializer->get_logs() as $log_text ){
					print '- '.$log_text."\n";
				}
				print ''."\n";
			}else{
				print 'result: FAILED'."\n";
				foreach( $plugin_initializer->get_logs() as $log_text ){
					print '- '.$log_text."\n";
				}
				print ''."\n";
				$errors = $plugin_initializer->get_errors();
				foreach( $errors as $error ){
					print '[ERROR] '.$error['message'].' (Line: '.$error['line'].')'."\n";
				}
				print ''."\n";
			}
			print ''."\n";
			print '------'."\n";
		}

		//var_dump( $this->px->dbh()->get_table_definition($this->px->get_conf('dbms.prefix').'_user') );

		print ''."\n";
		print 'initialize completed.'."\n";
		print date('Y-m-d H:i:s')."\n";
		print 'exit.'."\n";
		exit;
	}

	/**
	 * SQL文をダウンロードする
	 */
	private function download_sql(){
		$command = $this->get_command();
		$sql = '';
		$sql .= '-- '.$command[0].' | Pickles Framework (version:'.$this->px->get_version().')'."\r\n";
		$sql .= '-- SQL for "'.$this->px->get_conf('dbms.dbms').'"'."\r\n";
		$sql .= '-- '.date('Y-m-d H:i:s')."\r\n";
		$sql .= '-- ----'."\r\n";
		$sql .= ''."\r\n";
		$sql .= '-- ----------------'."\r\n";
		$sql .= '-- PxFW User Table(s)'."\r\n";
		$sql .= ''."\r\n";
		$class_name_dao_init = $this->px->load_px_class('/daos/initialize.php');
		$dao_init = new $class_name_dao_init( $this->px );
		$sql .= $dao_init->create_user_tables(2);
		$sql .= ''."\r\n";
		$sql .= ''."\r\n";

		$plugins = $this->scan_plugins();
		foreach( $plugins as $plugin_name ){
			$sql .= '-- ----------------'."\r\n";
			$sql .= '-- plugin "'.$plugin_name.'"'."\r\n";
			$class_name = $this->px->load_px_plugin_class( $plugin_name.'/register/initialize.php' );
			$plugin_initializer = new $class_name($this->px);
			$sql .= $plugin_initializer->execute(2);
			$sql .= ''."\r\n";
			$sql .= ''."\r\n";
		}

		$sql = preg_replace( '/\r\n|\r|\n/', "\r\n", $sql );//←CRLFに統一

		$this->px->download( $sql, array('filename'=>'pxfw_initialize_'.$this->px->get_conf('project.id').'_'.date('Ymd_Hi').'.sql') );
		exit;
	}//download_sql()

	/**
	 * プラグインディレクトリをスキャンして、initializeクラスの一覧を作成する
	 * @return initialize.php を持ったプラグイン名の一覧
	 */
	private function scan_plugins(){
		$rtn = array();
		$path_base_dir = $this->px->get_conf('paths.px_dir').'plugins/';
		$plugins = $this->px->dbh()->ls( $path_base_dir );
		foreach( $plugins as $plugin_name ){
			if( is_file( $path_base_dir.$plugin_name.'/register/initialize.php' ) ){
				array_push($rtn, $plugin_name);
			}
		}
		return $rtn;
	}

}

?>