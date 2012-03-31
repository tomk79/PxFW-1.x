<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: sitemapを表示する
 **/
class px_pxcommands_sitemap extends px_bases_pxcommand{

	protected $pxcommand_name = 'sitemap';

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "sitemap".
	 */
	private function execute(){
		$sitemap = $this->px->site()->get_sitemap();
		ob_start();
		test::var_dump( $sitemap );
		$src = ob_get_clean();
		print $this->html_template( $src );
		exit;
	}
}
?>