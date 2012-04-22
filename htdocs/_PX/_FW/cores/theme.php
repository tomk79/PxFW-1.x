<?php
class px_cores_theme{
	private $px;
	private $theme_id = 'default';
	private $layout_id = 'default';
	private $contents_cabinet = array(
		''=>'',    //  メインコンテンツ
		'head'=>'' //  ヘッドセクションに追記
	);

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
		$this->send_content($content,'');
		@header('Content-type: text/html; charset=UTF-8');

		$template_path = $this->px->dbh()->get_realpath($this->px->get_conf('paths.px_dir').'themes/'.$this->get_theme_id()).'/';
		$path_px_dir = $this->px->get_conf('paths.px_dir');
		$page_info = $this->px->site()->get_current_page_info();
		if( is_null( $page_info ) ){
			$page_info = array(
				'title'=>'Unknown page' ,
				'title_h1'=>'Unknown page' ,
				'title_breadcrumb'=>'Unknown page' ,
				'title_label'=>'Unknown page' ,
			);
		}

		$path_template_file = $template_path.''.$this->get_layout_id().'.html';
		if( !is_file($path_template_file) ){
			$path_template_file = $template_path.'default.html';
		}

		$smarty = $this->px->factory_smarty();
		$smarty->compile_dir  = $path_px_dir.'_sys/caches/smarty/theme_compiles/';
		$smarty->cache_dir    = $path_px_dir.'_sys/caches/smarty/theme_caches/';
		$smarty->caching = false;
		$smarty->config_dir   = $template_path;
		$smarty->template_dir = $template_path;
		$smarty->assign("px",$this->px);
		$smarty->assign("page_info",$page_info);
		$smarty->assign("content",$this->pull_content(''));
		$src = $smarty->fetch( $path_template_file );

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
	 * リンクタグ(aタグ)を生成する。
	 * @param $linkto リンク先のパス。PxFWのインストールパスを基点にした絶対パスで指定。
	 * @param options: [as string] Link label, [as array] Any options.
	 * @return string aタグ
	 */
	public function mk_link( $linkto ){
		$args = func_get_args();
		$href = $this->href($linkto);
		$hrefc = $this->href($this->px->req()->get_request_file_path());
		$label = $this->px->site()->get_page_info($linkto,'title_label');
		$page_id = $this->px->site()->get_page_info($linkto,'id');
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
		$breadcrumb = explode('>',$this->px->site()->get_page_info($hrefc,'logical_path'));
		$is_current = false;
		if($href==$hrefc){
			$is_current = true;
		}else{
			foreach( $breadcrumb as $tmp_page_id ){
				if(!strlen($tmp_page_id)){continue;}
				if( $page_id == $tmp_page_id ){
					$is_current = true;
					break;
				}
			}
		}
		$href = preg_replace('/\/index\.html$/si','/',$href); // index.htmlを省略

		$rtn = '<a href="'.t::h($href).'"'.($is_current?' class="current"':'').'>'.t::h($label).'</a>';
		return $rtn;
	}

	/**
	 * パンくずのHTMLソースを生成する。
	 * @return HTML source
	 */
	public function mk_breadcrumb(){
		$args = func_get_args();
		$current_path = $this->px->req()->get_request_file_path();
		if(strlen($args[0])){
			//オプションで指定があれば、カレントページを仮定する。
			$current_path = $args[0];
		}
		$page_info = $this->px->site()->get_page_info($current_path);
		$page_info['logical_path'] = trim($page_info['logical_path']);
		if( $page_info['id'] == '' ){
			//  ホームの場合
			return '<ul><li><strong>'.t::h($this->px->site()->get_page_info('','title_breadcrumb')).'</strong></li></ul>';
		}
		$array_breadcrumb = explode('>',$page_info['logical_path']);
		if( !strlen( $page_info['logical_path'] ) ){
			$array_breadcrumb = array();
		}
		$rtn = '';
		$rtn .= '<ul>';
		$rtn .= '<li><a href="'.t::h($this->href('')).'">'.t::h($this->px->site()->get_page_info('','title_breadcrumb')).'</a></li>';
		foreach( $array_breadcrumb as $page_id ){
			$linkto_page_info = $this->px->site()->get_page_info($page_id);
			$rtn .= '<li> &gt; <a href="'.t::h($this->href($linkto_page_info['path'])).'">'.t::h($linkto_page_info['title_breadcrumb']).'</a></li>';
		}
		$rtn .= '<li> &gt; <strong>'.t::h($page_info['title_breadcrumb']).'</strong></li>';
		$rtn .= '</ul>';
		return $rtn;
	}

	/**
	 * コンテンツキャビネットにコンテンツを送る
	 */
	public function send_content( $src , $content_name = '' ){
		if( !strlen($content_name) ){ $content_name = ''; }
		if( !is_string($content_name) ){ return false; }
		$this->contents_cabinet[$content_name] .= $src;
		return true;
	}

	/**
	 * コンテンツキャビネットからコンテンツを引き出す
	 */
	public function pull_content( $content_name = '' ){
		if( !strlen($content_name) ){ $content_name = ''; }
		if( !is_string($content_name) ){ return false; }
		return $this->contents_cabinet[$content_name];
	}

}
?>