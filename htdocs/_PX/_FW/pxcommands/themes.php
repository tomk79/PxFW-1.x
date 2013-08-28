<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: themesを表示する
 **/
class px_pxcommands_themes extends px_bases_pxcommand{
	private $config_ary = array();

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "config".
	 */
	private function execute(){
		$src = '';
		$current_theme_id = $this->px->theme()->get_theme_id();

		$path_theme_dir = $this->px->dbh()->ls( $this->px->get_conf('paths.px_dir').'themes' );
		if( !count($path_theme_dir) ){
			$src .= '<p>テーマは登録されていません。</p>'."\n";
		}else{
			$src .= '<div class="unit">'."\n";
			$src .= '<table class="def" style="width:100%;">'."\n";
			$src .= '	<thead>'."\n";
			$src .= '	<tr>'."\n";
			$src .= '		<th>テーマID</th>'."\n";
			$src .= '		<th>テーマが定義しているアウトライン名</th>'."\n";
			$src .= '		<th>---</th>'."\n";
			$src .= '	</tr>'."\n";
			$src .= '	</thead>'."\n";
			foreach( $path_theme_dir as $theme_id ){
				$src .= '	<tr>'."\n";
				$src .= '		<th>'.($current_theme_id==$theme_id?'<strong>'.t::h($theme_id).'</strong>':'<a href="?THEME='.t::h($theme_id).'">'.t::h($theme_id).'</a>').'</th>'."\n";
				$src .= '		<td>'."\n";
				$outline_list = $this->px->dbh()->ls( $this->px->get_conf('paths.px_dir').'themes/'.$theme_id.'/' );
				foreach( $outline_list as $number=>$filename ){
					if( $this->px->dbh()->get_extension($filename) != 'html' ){
						unset($outline_list[$number]);
					}else{
						$outline_list[$number] = $this->px->dbh()->trim_extension( $outline_list[$number] );
					}
				}
				if( !count($outline_list) ){
					$src .= '		<div class="center">---</div>'."\n";
				}else{
					$src .= '		<div>'.implode(', ', $outline_list).'</div>'."\n";
				}
				$src .= '		</td>'."\n";
				$src .= '		<td class="center">'.($current_theme_id==$theme_id?'---':'<a href="?THEME='.t::h($theme_id).'">このテーマを適用する</a>').'</td>'."\n";
				$src .= '	</tr>'."\n";
			}
			$src .= '</table>'."\n";
			$src .= '</div>'."\n";
		}

		print $this->html_template($src);
		exit;
	}

}

?>