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

	private function load_sitemap_csv(){
		//  サイトマップ定義をロード
		$tmp_sitemap_definition = $this->px->dbh()->read_csv_utf8( $this->px->get_conf('paths.px_dir').'configs/sitemapdefinition.csv' );
		foreach ($tmp_sitemap_definition as $key=>$val) {
			$this->sitemap_definition[$val[0]] = array();
			$this->sitemap_definition[$val[0]]['num'] = $key;
			$this->sitemap_definition[$val[0]]['key'] = $val[0];
			$this->sitemap_definition[$val[0]]['name'] = $val[1];
		}
		unset($tmp_sitemap_definition);
		//  / サイトマップ定義をロード

		//  サイトマップをロード
		$tmp_sitemap = $this->px->dbh()->read_csv_utf8( $this->px->get_conf('paths.px_dir').'sitemaps/sitemap.csv' );
		foreach ($tmp_sitemap as $row) {
			$tmp_array = array();
			foreach ($this->sitemap_definition as $defrow) {
				$tmp_array[$defrow['key']] = $row[$defrow['num']];
			}
			$tmp_array['path'] = preg_replace( '/\/$/si' , '/index.html' , $tmp_array['path'] );
			$tmp_array['content'] = preg_replace( '/\/$/si' , '/index.html' , $tmp_array['path'] );
			if( !strlen( $tmp_array['id'] ) ){
				$tmp_id = $tmp_array['path'];
				$tmp_id = preg_replace( '/\/index\.[a-zA-Z0-9]+$/si' , '/' , $tmp_id );
				$tmp_id = preg_replace( '/\/+$/si' , '' , $tmp_id );
				$tmp_id = preg_replace( '/^\/+/si' , '' , $tmp_id );
				$tmp_id = preg_replace( '/\//si' , '.' , $tmp_id );
				$tmp_array['id'] = $tmp_id;
			}
			$this->sitemap_array[$tmp_array['path']] = $tmp_array;
			$this->sitemap_id_map[$tmp_array['id']] = $tmp_array['path'];
		}
		//  / サイトマップをロード
	}

	public function get_sitemap_all(){
		return $this->sitemap_array;
	}
	public function get_page_info( $path ){
		$path = preg_replace( '/\/$/si' , '/index.html' , $path );
		$rtn = $this->sitemap_array[$path];
		if( !is_array($rtn) ){ return null; }
		if( !strlen( $rtn['title_breadcrumb'] ) ){ $rtn['title_breadcrumb'] = $rtn['title']; }
		if( !strlen( $rtn['title_h1'] ) ){ $rtn['title_h1'] = $rtn['title']; }
		if( !strlen( $rtn['title_label'] ) ){ $rtn['title_label'] = $rtn['title']; }
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