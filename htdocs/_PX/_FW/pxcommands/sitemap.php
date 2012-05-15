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
		print $this->html_template($this->print_ary_table($sitemap));
		exit;
	}
}
?>