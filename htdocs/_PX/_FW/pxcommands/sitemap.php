<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: sitemapを表示する
 **/
class px_pxcommands_sitemap extends px_bases_pxcommand{

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "sitemap".
	 */
	private function execute(){
		$src = '';

		$src .= '<div class="unit">'."\n";
		$src .= '	<p>'."\n";
		$src .= '		サイトマップ全体を表示します。<br />サイトマップは、次のディレクトリに格納されるCSVファイルから生成されます。<br />'."\n";
		$src .= '	</p>'."\n";
		$src .= '	<ul>'."\n";
		$src .= '		<li style="word-break:break-all;">'.t::h( realpath( $this->px->get_conf('paths.px_dir').'sitemaps/' ) ).'</li>'."\n";
		$src .= '	</ul>'."\n";
		$src .= '</div><!-- /.unit -->'."\n";

		$sitemap = $this->px->site()->get_sitemap();
		foreach ($sitemap as $key => $val) {
			$src .= '<div class="unit">'."\n";
			$src .= '<h2 style="word-break:break-all;">' .t::h( $key ). '</h2>'."\n";
			$src .= ''.$this->mk_ary_table($val).''."\n";
			$src .= '</div><!-- /.unit -->'."\n";
		}

		// $src .= $this->mk_ary_table($sitemap);

		print $this->html_template($src);
		exit;
	}

	/**
	 * 配列をtableのhtmlソースに変換
	 */
	private function mk_ary_table( $ary ) {
		if(is_array($ary)) {
			if($this->is_hash($ary)) {
				$html = '';
				$html .= '<table class="def" style="width:100%;">' . "\n";
				foreach ($ary as $key => $val) {
					$html .= '<tr>' . "\n";
					$html .= '<th style="width:30%;">' .t::h( $key ). '</th>' . "\n";
					$html .= '<td style="width:70%; word-break:break-all;">' .$this->mk_ary_table($val). '</td>' . "\n";
					$html .= '</tr>' . "\n";
				}
				$html .= '</table>' . "\n";
			} elseif(!$this->is_hash($ary)) {
				$html = '';
				$html .= '<table class="def" style="width:100%;">' . "\n";
				foreach ($ary as $val) {
					$html .= '<tr>' . "\n";
					$html .= '<td style="word-break:break-all;">' .t::h( $val ). '</td>' . "\n";
					$html .= '</tr>' . "\n";
				}
				$html .= '</table>' . "\n";
			}

		} elseif(!is_array($ary)) {
			$html = t::h( $ary );
		}
		return $html;
	}//mk_ary_table()

	/**
	 * 連想配列(true)か添付配列(false)か調べる
	 */
	private function is_hash( $ary ) {
		$i = 0;
		foreach($ary as $key => $dummy) {
			if ( $key !== $i++ ) return true;
		}
		return false;
	}

}

?>