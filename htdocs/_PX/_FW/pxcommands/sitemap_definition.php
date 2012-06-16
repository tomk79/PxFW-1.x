<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: sitemap_definitionを表示する
 **/
class px_pxcommands_sitemap_definition extends px_bases_pxcommand{

	public function __construct( $command , &$px ){
		parent::__construct( $command , &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "sitemap_definition".
	 */
	private function execute(){
		$sitemap_definition = $this->px->site()->get_sitemap_definition();
		ob_start();
		test::var_dump( $sitemap_definition );
		$src = ob_get_clean();
		print $this->html_template( $src );
		exit;
	}
}
?>