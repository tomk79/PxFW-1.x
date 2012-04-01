<?php
class px_cores_site{
	private $px;
	private $sitemap_definition = array();
	private $sitemap_array = array();
	private $sitemap_id_map = array();

	public function __construct( &$px ){
		$this->px = &$px;
		$this->load_sitemap_csv();
	}

	/**
	 * サイトマップCSVを読み込む
	 */
	private function load_sitemap_csv(){
		$path_sitemap_definition = $this->px->get_conf('paths.px_dir').'configs/sitemapdefinition.csv';
		$path_sitemap_dir = $this->px->get_conf('paths.px_dir').'sitemaps/';
		$ary_sitemap_files = $this->px->dbh()->ls( $path_sitemap_dir );

		//  サイトマップ定義をロード
		$tmp_sitemap_definition = $this->px->dbh()->read_csv_utf8( $path_sitemap_definition );
		foreach ($tmp_sitemap_definition as $key=>$val) {
			$this->sitemap_definition[$val[0]] = array();
			$this->sitemap_definition[$val[0]]['num'] = $key;
			$this->sitemap_definition[$val[0]]['key'] = $val[0];
			$this->sitemap_definition[$val[0]]['name'] = $val[1];
		}
		unset($tmp_sitemap_definition);
		//  / サイトマップ定義をロード

		//  サイトマップをロード
		foreach( $ary_sitemap_files as $basename_sitemap_csv ){
			$tmp_sitemap = $this->px->dbh()->read_csv_utf8( $path_sitemap_dir.$basename_sitemap_csv );
			foreach ($tmp_sitemap as $row) {
				$tmp_array = array();
				foreach ($this->sitemap_definition as $defrow) {
					$tmp_array[$defrow['key']] = $row[$defrow['num']];
				}
				if( preg_match( '/^(?:\*)/is' , $tmp_array['path'] ) ){
					//アスタリスク始まりの場合はコメント行とみなす。
					continue;
				}
				if( !preg_match( '/^(?:\/)/is' , $tmp_array['path'] ) ){
					//不正な形式のチェック
					continue;
				}
				$tmp_array['path'] = preg_replace( '/\/$/si' , '/index.html' , $tmp_array['path'] );
				if( !strlen( $tmp_array['content'] ) ){
					$tmp_array['content'] = $tmp_array['path'];
				}
				$tmp_array['content'] = preg_replace( '/\/$/si' , '/index.html' , $tmp_array['content'] );
				if( !strlen( $tmp_array['id'] ) ){
					$tmp_id = $tmp_array['path'];
					$tmp_id = $this->px->dbh()->trim_extension($tmp_id);
					$tmp_id = preg_replace( '/\/index$/si' , '/' , $tmp_id );
					$tmp_id = preg_replace( '/\/+$/si' , '' , $tmp_id );
					$tmp_id = preg_replace( '/^\/+/si' , '' , $tmp_id );
					$tmp_id = preg_replace( '/\//si' , '.' , $tmp_id );
					$tmp_array['id'] = $tmp_id;
				}
				$this->sitemap_array[$tmp_array['path']] = $tmp_array;
				$this->sitemap_id_map[$tmp_array['id']] = $tmp_array['path'];
			}
		}
		//  / サイトマップをロード
		return true;
	}

	/**
	 * サイトマップ定義を取得する
	 */
	public function get_sitemap_definition(){
		return $this->sitemap_definition;
	}

	/**
	 * サイトマップ配列を取得する。
	 */
	public function get_sitemap(){
		return $this->sitemap_array;
	}

	/**
	 * 親ページのIDを取得する
	 */
	public function get_parent( $path ){
		$logical_path = $this->get_page_info( $path , 'logical_path' );
		if( !strlen($logical_path) ){return '';}
		return basename($logical_path);
	}

	/**
	 * ページ情報を取得する。
	 * @param パス または ページID
	 */
	public function get_page_info( $path ){
		if( strlen($this->sitemap_id_map[$path]) ){ $path = $this->sitemap_id_map[$path];}//←ページIDで指定された場合、パスに置き換える
		$args = func_get_args();
		$path = preg_replace( '/\/$/si' , '/index.html' , $path );
		$rtn = $this->sitemap_array[$path];
		if( !is_array($rtn) ){ return null; }
		if( !strlen( $rtn['title_breadcrumb'] ) ){ $rtn['title_breadcrumb'] = $rtn['title']; }
		if( !strlen( $rtn['title_h1'] ) ){ $rtn['title_h1'] = $rtn['title']; }
		if( !strlen( $rtn['title_label'] ) ){ $rtn['title_label'] = $rtn['title']; }
		if( count($args) >= 2 ){
			$rtn = $rtn[$args[1]];
		}
		return $rtn;
	}
	public function get_page_info_by_id( $page_id ){
		$path = $this->sitemap_id_map[$page_id];
		return $this->get_page_info($path);
	}
	public function get_current_page_info(){
		$current_path = $this->px->req()->get_request_file_path();
		return $this->get_page_info( $current_path );
	}

}
?>