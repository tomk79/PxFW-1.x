<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: clearcacheを実行する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_clearcache extends px_bases_pxcommand{

	protected $pxcommand_name = 'clearcache';

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "clearcache".
	 * @access public
	 * @return null
	 */
	private function execute(){
		ob_start();
		test::var_dump( $this->px->get_conf_all() );
		$src = ob_get_clean();
		print $this->html_template( $src );
		exit;
	}
}
?>