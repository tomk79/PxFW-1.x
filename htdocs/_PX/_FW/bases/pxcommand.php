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
}
?>