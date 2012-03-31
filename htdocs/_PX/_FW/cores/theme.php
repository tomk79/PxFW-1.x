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
		$src = $smarty->fetch( $template_path.''.$this->get_theme_id().'.html' );

		return $src;
	}//bind_contents();

	public function href( $linkto ){
		$path = $this->px->site()->get_page_info($linkto,'path');
		$path = preg_replace( '/^\/+/' , '' , $path );
		return $this->px->site()->get_path_home().$path;
	}

}
?>