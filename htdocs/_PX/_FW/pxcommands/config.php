<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: configを表示する
 **/
class px_pxcommands_config extends px_bases_pxcommand{

	protected $pxcommand_name = 'config';

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "config".
	 */
	private function execute(){
		ob_start();
		test::var_dump( $this->px->get_conf_all() );
		$src = ob_get_clean();
		print $this->html_template( $src );
		exit;
	}
	
	//test ahomegane4
	//test tomk79 added.1
}
?>