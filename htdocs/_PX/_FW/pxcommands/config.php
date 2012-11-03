<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: configを表示する
 **/
class px_pxcommands_config extends px_bases_pxcommand{

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "config".
	 */
	private function execute(){
		$src = '';

		$src .= '<div class="unit">'."\n";
		$src .= '	<p>'."\n";
		$src .= '		コンフィグに設定された内容を表示します。<br />'."\n";
		$src .= '		コンフィグは、次のファイルを編集すると変更することができます。<br />'."\n";
		$src .= '	</p>'."\n";
		$src .= '	<ul>'."\n";
		$src .= '		<li>'.t::h( realpath( $this->px->get_conf('paths.px_dir').'configs/mainconf.ini' ) ).'</li>'."\n";
		$src .= '	</ul>'."\n";
		$src .= '</div><!-- /.unit -->'."\n";
		$src .= '<div class="unit">'."\n";
		$config = $this->px->get_conf_all();
		$src .= $this->mk_ary_table($config);
		$src .= '</div><!-- /.unit -->'."\n";

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
				$html .= '<table class="def">' . "\n";
				$html .= '<colgroup><col width="30%" /><col width="70%" /></colgroup>' . "\n";
				foreach ($ary as $key => $val) {
					$html .= '<tr>' . "\n";
					$html .= '<th>' .t::h( $key ). '</th>' . "\n";
					$html .= '<td>' .$this->mk_ary_table($val). '</td>' . "\n";
					$html .= '</tr>' . "\n";
				}
				$html .= '</table>' . "\n";
			} elseif(!$this->is_hash($ary)) {
				$html = '';
				$html .= '<table class="def">' . "\n";
				$html .= '<colgroup><col width="30%" /><col width="70%" /></colgroup>' . "\n";
				foreach ($ary as $val) {
					$html .= '<tr>' . "\n";
					$html .= '<td>' .t::h( $val ). '</td>' . "\n";
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