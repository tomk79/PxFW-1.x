<?php
class px_cores_theme{
	private $px;
	private $theme_id = 'default';
	private $layout_id = 'default';

	public function __construct( &$px ){
		$this->px = &$px;
	}

	/**
	 * テーマIDをセットする。
	 */
	public function set_theme_id( $theme_id ){
		if( !strlen( $theme_id ) ){ return false; }
		$this->theme_id = $theme_id;
		return true;
	}
	public function get_theme_id(){
		return $this->theme_id;
	}

	/**
	 * レイアウトIDをセットする。
	 */
	public function set_layout_id( $layout_id ){
		if( !strlen( $layout_id ) ){ return false; }
		$this->layout_id = $layout_id;
		return true;
	}
	public function get_layout_id(){
		return $this->layout_id;
	}

	/**
	 * コンテンツソースをレイアウトにバインドして返す。
	 */
	public function bind_contents( $content ){
		@header('Content-type: text/html; charset=UTF-8');

		$template_path = $this->px->dbh()->get_realpath($this->px->get_conf('paths.px_dir').'themes/'.$this->get_theme_id()).'/';
		$page_info = $this->px->site()->get_current_page_info();
		if( is_null( $page_info ) ){
			$page_info = array(
				'title'=>'Unknown page' ,
				'title_h1'=>'Unknown page' ,
				'title_breadcrumb'=>'Unknown page' ,
				'title_label'=>'Unknown page' ,
			);
		}

		$smarty = $this->px->factory_smarty();
		$smarty->caching = false;
		$smarty->config_dir   = $template_path;
		$smarty->template_dir = $template_path;
		$smarty->assign("px",$this->px);
		$smarty->assign("page_info",$page_info);
		$smarty->assign("content",$content);

		ob_start();
		$smarty->display( $template_path.''.$this->get_theme_id().'.html' );
		$src = ob_get_clean();
		return $src;

/**
		$src = '';
		$src .= '<!doctype html>'."\n";
		$src .= '<html>'."\n";
		$src .= '<head>'."\n";
		$src .= '<meta charset="UTF-8" />'."\n";
		$src .= '<title>'.t::h(($page_info['title']?$page_info['title']:'Untitled')).' | '.t::h($this->px->get_conf('project.name')).'</title>'."\n";
		$src .= '</head>'."\n";
		$src .= '<body>'."\n";
		$src .= '<h1>'.t::h(($page_info['title_h1']?$page_info['title_h1']:'Untitled')).'</h1>'."\n";
		$src .= '<div id="content" class="contents">'."\n";
		$src .= $content;
		$src .= '</div><!-- /#content -->'."\n";
		$src .= '<div>'.t::h($_SERVER['HTTP_USER_AGENT']).'</div>'."\n";
		$src .= '</body>'."\n";
		$src .= '</html>'."\n";
//test::var_dump($this->px->site()->get_page_info_by_id('test.abc'));
/**/

		return $src;
	}


}
?>