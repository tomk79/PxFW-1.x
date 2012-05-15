<?php
class px_bases_pxcommand{
	protected $px;
	protected $pxcommand_name = 'pxcommand';

	public function __construct( &$px ){
		$this->px = &$px;
	}//__construct()

	protected function html_template( $content ){
		@header( 'Content-type: text/html; charset="UTF-8"' );
		$src = '';
		$src .= '<!doctype html>'."\n";
		$src .= '<html>'."\n";
		$src .= '<head>'."\n";
		$src .= '<title>'.htmlspecialchars( $this->pxcommand_name ).' | Pickles Framework</title>'."\n";
		$src .= '</head>'."\n";
		$src .= '<body>'."\n";
		$src .= '<h1>'.htmlspecialchars( $this->pxcommand_name ).' | Pickles Framework</h1>'."\n";
		$src .= '<div id="content" class="contents">'."\n";
		$src .= $content;
		$src .= '</div>'."\n";
		$src .= '</body>'."\n";
		$src .= '</html>';
		return $src;
	}

	//配列をtableのhtmlソースに変換
	protected function print_ary_table( $ary ) {
		
		//連想配列(true)か添付配列(false)か調べる
		function is_hash( $ary ) {
			$i = 0;
			foreach($ary as $key => $dummy) {
				if ( $key !== $i++ ) return true;
			}
			return false;
		}
		
		function make_style_ary_table() {	
			ob_start(); ?>
			<style type="text/css">
			table.def {
				border:none;
				border-collapse: collapse;
				text-align: left;
				width: 800px;
			}
			table.def th,
			table.def td {
				border: 1px solid #D6D6D6;
				padding: 10px;
			}
			table.def th {
				background: #E7E7E7;
			}
			</style>
<?php
			$style = ob_get_clean();
			
			return $style;
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
		
		return make_style_ary_table().make_html_ary_table($ary);
		
		/*
		// 配列の次元数を調べる
		function depth($ary, $cnt = 0){
		   if(!is_array($ary)) return $cnt; else $cnt++;
		   $max = $cnt; $i = 0;
		   foreach($ary as $v){
				if(is_array($v)){
					 $i = depth($v, $cnt);
					 if($max < $i) $max = $i;
				}
		   }
		   return $max;
		}*/
		
	}
}
?>