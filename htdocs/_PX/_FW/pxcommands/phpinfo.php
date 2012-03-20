<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: phpinfoを表示する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_phpinfo extends px_bases_pxcommand{

	protected $pxcommand_name = 'phpinfo';

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "phpinfo".
	 */
	private function execute(){
		phpinfo();
		exit;
	}
}
?>