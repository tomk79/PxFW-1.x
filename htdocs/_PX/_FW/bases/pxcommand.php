<?php
class px_bases_pxcommand{
	protected $px;
	protected $pxcommand_name = null;

	/**
	 * コンストラクタ
	 */
	public function __construct( $pxcommand_name , &$px ){
		$this->px = &$px;
		$this->pxcommand_name = $pxcommand_name;
	}//__construct()

	/**
	 * コマンド名を取得する
	 */
	public function get_command(){
		return $this->pxcommand_name;
	}

	/**
	 * コンテンツをHTMLテンプレートに包んで返す
	 */
	protected function html_template( $content ){
		@header( 'Content-type: text/html; charset="UTF-8"' );
		$src = '';
		$src .= '<!doctype html>'."\n";
		$src .= '<html>'."\n";
		$src .= '<head>'."\n";
		$src .= '<title>'.htmlspecialchars( $this->pxcommand_name[0] ).' | Pickles Framework</title>'."\n";
		$src .= '<style type="text/css">'."\n";
		$src .= '/* ------ table.def ------ */'."\n";
		$src .= 'table.def {'."\n";
		$src .= '	border:none;'."\n";
		$src .= '	border-collapse: collapse;'."\n";
		$src .= '	text-align: left;'."\n";
		$src .= '	width: 800px;'."\n";
		$src .= '}'."\n";
		$src .= 'table.def th,'."\n";
		$src .= 'table.def td {'."\n";
		$src .= '	border: 1px solid #d6d6d6;'."\n";
		$src .= '	padding: 10px;'."\n";
		$src .= '}'."\n";
		$src .= 'table.def th {'."\n";
		$src .= '	background: #e7e7e7;'."\n";
		$src .= '}'."\n";
		$src .= '/* ------ / table.def ------ */'."\n";
		$src .= '</style>'."\n";
		$src .= '</head>'."\n";
		$src .= '<body>'."\n";
		$src .= '<h1>'.htmlspecialchars( $this->pxcommand_name[0] ).' | Pickles Framework</h1>'."\n";
		$src .= '<div id="content" class="contents">'."\n";
		$src .= $content;
		$src .= '</div>'."\n";
		$src .= '</body>'."\n";
		$src .= '</html>';
		return $src;
	}//html_template()

}

?>