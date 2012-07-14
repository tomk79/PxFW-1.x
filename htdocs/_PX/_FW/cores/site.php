<?php
class px_cores_site{
	private $px;
	private $sitemap_definition = array();
	private $sitemap_array = array();
	private $sitemap_id_map = array();
	private $sitemap_dynamic_paths = array();

	public function __construct( &$px ){
		$this->px = &$px;

		//サイトマップCSVを読み込む
		$this->load_sitemap_csv();

		//ダイナミックパスを検索、パラメータを取り出す
		foreach( $this->sitemap_dynamic_paths as $sitemap_dynamic_path ){
			if( preg_match( $sitemap_dynamic_path['preg'] , $this->px->req()->get_request_file_path() , $tmp_matched ) ){
				$page_info = $this->get_page_info( $this->px->req()->get_request_file_path() );
				foreach( $sitemap_dynamic_path['pattern_map'] as $key=>$val ){
					$this->px->req()->set_path_param( $val , $tmp_matched[$key+1] );
				}
				break;
			}
		}
	}

	/**
	 * サイトマップCSVを読み込む
	 */
	private function load_sitemap_csv(){
		$path_sitemap_definition = $this->px->get_conf('paths.px_dir').'configs/sitemap_definition.csv';
		$path_sitemap_dir = $this->px->get_conf('paths.px_dir').'sitemaps/';
		$ary_sitemap_files = $this->px->dbh()->ls( $path_sitemap_dir );
		$path_sitemap_cache_dir = $this->px->get_conf('paths.px_dir').'_sys/caches/sitemaps/';

		if( $this->is_sitemap_cache() ){
			//  サイトマップキャッシュが存在する場合、キャッシュからロードする。
			$this->sitemap_definition    = @include($path_sitemap_cache_dir.'sitemap_definition.array');
			$this->sitemap_array         = @include($path_sitemap_cache_dir.'sitemap.array');
			$this->sitemap_id_map        = @include($path_sitemap_cache_dir.'sitemap_id_map.array');
			$this->sitemap_dynamic_paths = @include($path_sitemap_cache_dir.'sitemap_dynamic_paths.array');
			return true;
		}

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
		$num_auto_pid = 0;
		foreach( $ary_sitemap_files as $basename_sitemap_csv ){
			$tmp_sitemap = $this->px->dbh()->read_csv_utf8( $path_sitemap_dir.$basename_sitemap_csv );
			foreach ($tmp_sitemap as $row) {
				$num_auto_pid++;
				$tmp_array = array();
				foreach ($this->sitemap_definition as $defrow) {
					$tmp_array[$defrow['key']] = $row[$defrow['num']];
				}
				if( preg_match( '/^(?:\*)/is' , $tmp_array['path'] ) ){
					//アスタリスク始まりの場合はコメント行とみなす。
					continue;
				}
				if( !preg_match( '/^(?:\/|alias\:)/is' , $tmp_array['path'] ) ){
					//不正な形式のチェック
					continue;
				}
				$tmp_array['path'] = preg_replace( '/\/$/si' , '/index.html' , $tmp_array['path'] );//index.htmlを付加する。
				if( !strlen( $tmp_array['content'] ) ){
					$tmp_array['content'] = $tmp_array['path'];
				}
				$tmp_array['content'] = preg_replace( '/\/$/si' , '/index.html' , $tmp_array['content'] );
				if( !strlen( $tmp_array['id'] ) ){
					//ページID文字列を自動生成
					$tmp_id = '';
					if( preg_match( '/^alias\:/s' , $tmp_array['path'] ) ){
						//エイリアス
						$tmp_id = ':auto_page_id.'.($num_auto_pid);
					}else{
						//物理ページ
						$tmp_id = $tmp_array['path'];
						$tmp_id = $this->px->dbh()->trim_extension($tmp_id);
						$tmp_id = preg_replace( '/\/index$/si' , '/' , $tmp_id );
						$tmp_id = preg_replace( '/\/+$/si' , '' , $tmp_id );
						$tmp_id = preg_replace( '/^\/+/si' , '' , $tmp_id );
						$tmp_id = preg_replace( '/\//si' , '.' , $tmp_id );
					}
					$tmp_array['id'] = $tmp_id;
					unset($tmp_id);
				}

				if( preg_match( '/\{\$[a-zA-Z0-9\_\-]+\}/s' , $tmp_array['path'] ) ){
					//ダイナミックパスのインデックス作成
					$preg_pattern = preg_quote($tmp_array['path'],'/');
					$preg_pattern = preg_replace('/'.preg_quote(preg_quote('{$','/'),'/').'[a-zA-Z0-9\-\_]+'.preg_quote(preg_quote('}','/'),'/').'/s','([a-zA-Z0-9\-\_]+?)',$preg_pattern);
					preg_match_all('/\{\$([a-zA-Z0-9\-\_]+)\}/',$tmp_array['path'],$pattern_map);
					$tmp_array['path'] = preg_replace('/'.preg_quote('{$','/').'([a-zA-Z0-9\-\_]+)'.preg_quote('}','/').'/s','$1',$tmp_array['path']);
					array_push( $this->sitemap_dynamic_paths, array(
						'path'=>$tmp_array['path'],
						'id'=>$tmp_array['id'],
						'preg'=>'/^'.$preg_pattern.'$/s',
						'pattern_map'=>$pattern_map[1],
					) );
					unset($preg_pattern);
					unset($pattern_map);
				}

				if( preg_match( '/^alias\:/s' , $tmp_array['path'] ) ){
					//エイリアスの値調整
					$tmp_array['content'] = null;
					$tmp_array['path'] = preg_replace( '/^alias\:/s' , 'alias'.$num_auto_pid.':' , $tmp_array['path'] );
				}

				$this->sitemap_array[$tmp_array['path']] = $tmp_array;
				$this->sitemap_id_map[$tmp_array['id']] = $tmp_array['path'];
			}
		}
		//  / サイトマップをロード

		//  キャッシュディレクトリを作成
		$this->px->dbh()->mkdir($path_sitemap_cache_dir);

		//  キャッシュファイルを作成
		$this->px->dbh()->file_overwrite( $path_sitemap_cache_dir.'sitemap_definition.array' , t::data2phpsrc($this->sitemap_definition) );
		$this->px->dbh()->file_overwrite( $path_sitemap_cache_dir.'sitemap.array' , t::data2phpsrc($this->sitemap_array) );
		$this->px->dbh()->file_overwrite( $path_sitemap_cache_dir.'sitemap_id_map.array' , t::data2phpsrc($this->sitemap_id_map) );
		$this->px->dbh()->file_overwrite( $path_sitemap_cache_dir.'sitemap_dynamic_paths.array' , t::data2phpsrc($this->sitemap_dynamic_paths) );

		return true;
	}//load_sitemap_csv();

	/**
	 * サイトマップキャッシュが読み込み可能か調べる
	 */
	private function is_sitemap_cache(){
		$path_sitemap_cache_dir = $this->px->get_conf('paths.px_dir').'_sys/caches/sitemaps/';
		$path_sitemap_dir = $this->px->get_conf('paths.px_dir').'sitemaps/';
		if(
			!is_file($path_sitemap_cache_dir.'sitemap_definition.array') || 
			!is_file($path_sitemap_cache_dir.'sitemap.array') || 
			!is_file($path_sitemap_cache_dir.'sitemap_id_map.array') || 
			!is_file($path_sitemap_cache_dir.'sitemap_dynamic_paths.array')
		){
			return false;
		}
		if( $this->px->dbh()->is_newer_a_than_b( $this->px->get_conf('paths.px_dir').'configs/sitemap_definition.csv' , $path_sitemap_cache_dir.'sitemap_definition.array' ) ){
			return false;
		}
		foreach( $this->px->dbh()->ls( $path_sitemap_dir ) as $filename ){
			if( $this->px->dbh()->is_newer_a_than_b( $path_sitemap_dir.$filename , $path_sitemap_cache_dir.'sitemap.array' ) ){
				return false;
			}
		}
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
	public function get_parent( $path = null ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$logical_path = $this->get_page_info( $path , 'logical_path' );
		if( !strlen($logical_path) ){return '';}
		$logical_paths = explode('>',$logical_path);
		return $logical_paths[count($logical_paths)-1];
	}

	/**
	 * ページ情報を取得する。
	 * @param パス または ページID
	 */
	public function get_page_info( $path ){
		foreach( $this->sitemap_dynamic_paths as $sitemap_dynamic_path ){
			//ダイナミックパスを検索
			if( preg_match( $sitemap_dynamic_path['preg'] , $path , $tmp_matched ) ){
				$path = $sitemap_dynamic_path['path'];
				break;
			}
		}
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

	/**
	 * 子階層のページの一覧を取得する
	 * @param $path
	 */
	public function get_children( $path = null ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$page_info = $this->get_page_info( $path );
		$rtn = array();
		foreach( $this->get_sitemap() as $row ){
			if( !strlen($row['id']) ){
				continue;
			}
			if( !$row['list_flg'] ){
				continue;
			}
			if( ($page_info['logical_path']?$page_info['logical_path'].'>':'').$page_info['id'] == $row['logical_path'] ){
				array_push( $rtn , $row['id'] );
			}
		}
		return $rtn;
	}//get_children()

	/**
	 * 同じ階層のページの一覧を取得する
	 * @param $path
	 */
	public function get_bros( $path = null ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$parent = $this->get_parent( $path );
		$bros = $this->get_children( $parent );
		return $bros;
	}//get_bros()

	/**
	 * パス文字列を受け取り、種類を判定する。
	 * alias: から始まる場合 => 'alias'
	 * {$xxxx} を含む場合 => 'dynamic'
	 * / から始まる場合 => 'normal'
	 * どれにも当てはまらない不明な形式の場合に、falseを返す。
	 * 
	 * @param $path
	 */
	public function get_path_type( $path ) {
		if( preg_match( '/^alias[0-9]*\:/' , $path ) ) {
			//  alias:から始まる場合
			//  サイトマップデータ上でpathは一意である必要あるので、
			//  alias と : の間に、後から連番を降られる。
			//  このため、数字が含まれている場合を考慮した。(@tomk79)
			$path_type = 'alias';
		} else if( preg_match( '/\{\$([a-zA-Z0-9]+)\}/' , $path ) ) {
			//  {$xxxx}を含む場合
			$path_type = 'dynamic';
		} else if( preg_match( '/^\//' , $path ) ) {
			//  /から始まる場合
			$path_type = 'normal';
		} else {
			//  どれにも当てはまらない場合はfalseを返す
			$path_type = false;
		}
		return $path_type;
	}//get_path_type()

}
?>