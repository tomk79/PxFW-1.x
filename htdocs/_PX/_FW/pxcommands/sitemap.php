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
		$html = '';
		if(is_array($ary)) {
			$html .= '<table class="def" style="width:100%;">' . "\n";
			foreach ($ary as $key => $val) {
				$html .= '<tr>' . "\n";
				$html .= '<th style="width:30%;">'.t::h( $key ).'</th>'."\n";
				$html .= '<td style="width:70%; word-break:break-all;">';
				if( $key == 'path' ){
					$href = $this->px->theme()->href( $val );
					if( preg_match( '/\?/', $href ) ){
						$href = preg_replace( '/^(.*?)\?(.*)$/', '$1?PX=pageinfo&$2', $href );
					}elseif( preg_match( '/\#/', $href ) ){
						$href = preg_replace( '/^(.*?)\#(.*)$/', '$1?PX=pageinfo#$2', $href );
					}else{
						$href .= '?PX=pageinfo';
					}

					$html .= '<a href="'.t::h($href).'">'.t::h($val).'</a>';
					unset($href);
				}else{
					$html .= $this->mk_ary_table($val);
				}
				$html .= '</td>'."\n";
				$html .= '</tr>' . "\n";
			}
			$html .= '</table>' . "\n";

		}else{
			$html = t::h( $ary );

		}
		return $html;
	}//mk_ary_table()

}

?>