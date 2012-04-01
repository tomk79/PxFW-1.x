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
	 * @return bool
	 */
	public function set_theme_id( $theme_id ){
		if( !strlen( $theme_id ) ){ return false; }
		if( !preg_match( '/^[a-zA-Z0-9\_\-]+$/si' , $theme_id ) ){ return false; }
		$this->theme_id = $theme_id;
		return true;
	}
	/**
	 * テーマIDを取得する。
	 * @return string
	 */
	public function get_theme_id(){
		return $this->theme_id;
	}

	/**
	 * レイアウトIDをセットする。
	 * @return bool
	 */
	public function set_layout_id( $layout_id ){
		if( !strlen( $layout_id ) ){ return false; }
		if( !preg_match( '/^[a-zA-Z0-9\_\-]+$/si' , $layout_id ) ){ return false; }
		$this->layout_id = $layout_id;
		return true;
	}
	/**
	 * レイアウトIDを取得する。
	 * @return string
	 */
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

	/**
	 * リンク先を調整する。
	 * @return string href属性値
	 */
	public function href( $linkto ){
		$path = $this->px->site()->get_page_info($linkto,'path');
		$path = preg_replace( '/^\/+/' , '' , $path );
		return $this->px->get_install_path().$path;
	}

	/**
	 * リンクタグを生成する。
	 * @param $linkto リンク先のパス。PxFWのインストールパスを基点にした絶対パスで指定。
	 * @return string aタグ
	 */
	public function mk_link( $linkto ){
		$args = func_get_args();
		$href = $this->href($linkto);
		$hrefc = $this->href($this->px->req()->get_request_file_path());
		$label = $this->px->site()->get_page_info($linkto,'title_label');
		if( is_string($args[1]) ){
			//  第2引数が文字列なら
			//  リンクのラベルとして採用
			$label = $args[1];
		}
		if( count($args) >= 2 && is_array($args[count($args)-1]) ){
			//  最後の引数が配列なら
			//  オプション連想配列として読み込む
			$options = $args[count($args)-1];
			if(strlen($options['label'])){
				$label = $options['label'];
			}
		}
		$rtn = '<a href="'.t::h($href).'"'.($href==$hrefc?' class="current"':'').'>'.t::h($label).'</a>';
		return $rtn;
	}



}
?>