<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: pageinfoを表示する
 **/
class px_pxcommands_pageinfo extends px_bases_pxcommand{

	protected $pxcommand_name = 'pageinfo';

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "pageinfo".
	 */
	private function execute(){
		$pageinfo = $this->px->site()->get_current_page_info();
		ob_start();
		test::var_dump( $pageinfo );
		$src = ob_get_clean();
		print $this->html_template( $src );
		exit;
	}
}
?>