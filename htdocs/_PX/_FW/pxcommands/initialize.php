<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: initializeを表示する
 **/
class px_pxcommands_initialize extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 */
	public function __construct( $command , &$px ){
		parent::__construct( $command , &$px );

		$command = $this->get_command();

		switch( $command[1] ){
			case 'run':
				$this->execute();
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
		$src .= '<p class="center"><button>イニシャライズを実行する</button></p>'."\n";
		$src .= '<div><input type="hidden" name="PX" value="'.t::h($command[0]).'.run" /></div>'."\n";
		$src .= '</form>'."\n";
		print $this->html_template($src);
		exit;
	}

	/**
	 * Execute PX Command "initialize".
	 */
	private function execute(){
		$command = $this->get_command();
		@header('Content-type: text/plain');
		print ''.$command[0].' | Pickles Framework'."\n";
		print '------'."\n";
		$class_name_dao_init = $this->px->load_px_class('/daos/initialize.php');
		$dao_init = new $class_name_dao_init( &$this->px );

		print '[init user tables]'."\n";
		if( $dao_init->create_user_tables() ){
			print 'success'."\n";
		}else{
			print 'FAILED'."\n";
		}

		//var_dump( $this->px->dbh()->get_table_definition($this->px->get_conf('dbms.prefix').'_user') );

		print '------'."\n";
		print 'initialize completed.'."\n";
		print date('Y-m-d H:i:s')."\n";
		print 'exit.'."\n";
		exit;
	}

}
?>