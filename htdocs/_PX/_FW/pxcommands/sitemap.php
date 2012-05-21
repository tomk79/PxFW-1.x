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
		print $this->html_template($this->mk_ary_table($sitemap));
		exit;
	}

	/**
	 * 配列をtableのhtmlソースに変換
	 */
	private function mk_ary_table( $ary ) {
		
		//連想配列(true)か添付配列(false)か調べる
		function is_hash( $ary ) {
			$i = 0;
			foreach($ary as $key => $dummy) {
				if ( $key !== $i++ ) return true;
			}
			return false;
		}

		function make_html_ary_table( $ary ) {
			if(is_array($ary)) {
				if(is_hash($ary)) {
					$html = "\n" . '<table class="def"><col width="30%" /><col width="70%" />' . "\n";
					foreach ($ary as $key => $val) {
						$html .= '<tr>' . "\n";
						$html .= '<th>' .$key. '</th>' . "\n";
						$html .= '<td>' .make_html_ary_table($val). '</td>' . "\n";
						$html .= '</tr>' . "\n";
					}
					$html .= '</table>' . "\n";
				} elseif(!is_hash($ary)) {
					$html = "\n" . '<table class="def"><col width="30%" /><col width="70%" />' . "\n";
					foreach ($ary as $val) {
						$html .= '<tr>' . "\n";
						$html .= '<td>' .$val. '</td>' . "\n";
						$html .= '</tr>' . "\n";
					}
					$html .= '</table>' . "\n";
				}

				} elseif(!is_array($ary)) {
					$html = $ary;
				}
			return $html;
		}

		return make_html_ary_table($ary);

	}//mk_ary_table()

}

?>