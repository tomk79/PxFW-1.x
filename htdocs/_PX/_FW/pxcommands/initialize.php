<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: initializeを表示する
 **/
class px_pxcommands_initialize extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 */
	public function __construct( $command , &$px ){
		parent::__construct( $command , &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "initialize".
	 */
	private function execute(){
		$command = $this->get_command();
		@header('Content-type: text/plain');
		print ''.$command[0].' | Pickles Framework'."\n";
		print '------'."\n";
		$class_name_dao_init = $this->px->load_pxclass('/daos/initialize.php');
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