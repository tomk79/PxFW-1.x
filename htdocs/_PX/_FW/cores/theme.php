<?php
class px_cores_theme{
	private $px;

	public function __construct( &$px ){
		$this->px = &$px;
	}

	public function bind_contents( $content ){
		@header('Content-type: text/html; charset=UTF-8');

		$page_info = $this->px->site()->get_current_page_info();

		$RTN = '';
		$RTN .= '<!doctype html>'."\n";
		$RTN .= '<html>'."\n";
		$RTN .= '<head>'."\n";
		$RTN .= '<meta charset="UTF-8" />'."\n";
		$RTN .= '<title>'.t::h(($page_info['title']?$page_info['title']:'Untitled')).' | '.t::h($this->px->get_conf('project.name')).'</title>'."\n";
		$RTN .= '</head>'."\n";
		$RTN .= '<body>'."\n";
		$RTN .= '<h1>'.t::h(($page_info['title_h1']?$page_info['title_h1']:'Untitled')).'</h1>'."\n";
		$RTN .= '<div id="content" class="contents">'."\n";
		$RTN .= $content;
		$RTN .= '</div><!-- /#content -->'."\n";
		$RTN .= '<div>'.t::h($_SERVER['HTTP_USER_AGENT']).'</div>'."\n";
		$RTN .= '</body>'."\n";
		$RTN .= '</html>'."\n";
//test::var_dump($this->px->site()->get_page_info_by_id('test.abc'));

		return $RTN;
	}


}
?>