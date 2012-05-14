<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: sitemap_definitionを表示する
 **/
class px_pxcommands_sitemap_definition extends px_bases_pxcommand{

	protected $pxcommand_name = 'sitemap_definition';

	public function __construct( &$px ){
		parent::__construct( &$px );
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