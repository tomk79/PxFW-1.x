<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: phpinfoを表示する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_phpinfo extends px_bases_pxcommand{

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
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