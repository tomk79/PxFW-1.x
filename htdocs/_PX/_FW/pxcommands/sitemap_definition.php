<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: sitemap_definitionを表示する
 **/
class px_pxcommands_sitemap_definition extends px_bases_pxcommand{

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "sitemap_definition".
	 */
	private function execute(){
		$sitemap_definition = $this->px->site()->get_sitemap_definition();
		$src = '';
		$src .= '<table class="def" style="width:100%;">'."\n";
		$src .= '	<thead>'."\n";
		$src .= '		<tr>'."\n";
		$src .= '			<th>物理名</th>'."\n";
		$src .= '			<th>インデックス番号</th>'."\n";
		$src .= '			<th>列</th>'."\n";
//		$src .= '			<th>物理名</th>'."\n";
		$src .= '			<th>論理名</th>'."\n";
		$src .= '		</tr>'."\n";
		$src .= '	</thead>'."\n";
		$src .= '	<tbody>'."\n";
		$col = 'A';
		foreach( $sitemap_definition as $name=>$value ){
			$src .= '		<tr>'."\n";
			$src .= '			<th>'.t::h($name).'</th>'."\n";
			$src .= '			<td class="center">'.t::h($value['num']).'</td>'."\n";
			$src .= '			<td class="center">'.t::h($col).'</td>'."\n";
//			$src .= '			<td>'.t::h($value['key']).'</td>'."\n";
			$src .= '			<td>'.t::h($value['name']).'</td>'."\n";
			$src .= '		</tr>'."\n";
			$col ++;
		}
		$src .= '	</tbody>'."\n";
		$src .= '</table>'."\n";

//		ob_start();
//		test::var_dump( $sitemap_definition );
//		$src = ob_get_clean();
		print $this->html_template( $src );
		exit;
	}

}

?>