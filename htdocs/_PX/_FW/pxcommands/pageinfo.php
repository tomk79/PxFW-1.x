<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: pageinfoを表示する
 **/
class px_pxcommands_pageinfo extends px_bases_pxcommand{

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "pageinfo".
	 */
	private function execute(){
		$pageinfo = $this->px->site()->get_current_page_info();
		$src = '';
		$src .= '<div class="unit">'."\n";
		$src .= '	<p>次のパスに該当するページの情報を表示します。</p>'."\n";
		$src .= '	<ul>'."\n";
		$src .= '		<li>'.t::h( $_SERVER['PATH_INFO'] ).'</li>'."\n";
		$src .= '	</ul>'."\n";
		$src .= '</div><!-- /.unit -->'."\n";
		$src .= '<div class="unit">'."\n";
		$src .= '<table class="def" style="width:100%;">'."\n";
		foreach( $pageinfo as $key=>$val ){
			$src .= '	<tr>'."\n";
			$src .= '		<th>'.t::h($key).'</th>'."\n";
			$src .= '		<td>'.t::h($val).'</td>'."\n";
			$src .= '	</tr>'."\n";
		}
		$src .= '</table>'."\n";
		$src .= '</div><!-- /.unit -->'."\n";
		print $this->html_template( $src );
		exit;
	}

}
?>