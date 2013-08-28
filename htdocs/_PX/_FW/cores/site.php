<?php
/**
 * PxFW core object class: Site and page Manager
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_cores_site{
	private $px;
	private $sitemap_definition = array();
	private $sitemap_array = array();
	private $sitemap_id_map = array();
	private $sitemap_dynamic_paths = array();
	private $sitemap_page_tree = array();

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;

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
		$path_sitemap_cache_dir = $this->px->get_conf('paths.px_dir').'_sys/caches/sitemaps/';
		if( $this->is_sitemap_cache() ){
			//  サイトマップキャッシュが存在する場合、キャッシュからロードする。
			$this->sitemap_definition    = @include($path_sitemap_cache_dir.'sitemap_definition.array');
			$this->sitemap_array         = @include($path_sitemap_cache_dir.'sitemap.array');
			$this->sitemap_id_map        = @include($path_sitemap_cache_dir.'sitemap_id_map.array');
			$this->sitemap_dynamic_paths = @include($path_sitemap_cache_dir.'sitemap_dynamic_paths.array');
			$this->sitemap_page_tree     = @include($path_sitemap_cache_dir.'sitemap_page_tree.array');
			return true;
		}

		if( preg_match('/^clearcache(?:\\..*)?$/si', $this->px->req()->get_param('PX')) ){
			// clearcacheを実行する際にはCSVの読み込みを行わない。どうせ直後に消されるので。
			return true;
		}

		$path_sitemap_definition = $this->px->get_conf('paths.px_dir').'configs/sitemap_definition.csv';
		$path_sitemap_dir = $this->px->get_conf('paths.px_dir').'sitemaps/';
		$ary_sitemap_files = $this->px->dbh()->ls( $path_sitemap_dir );
		sort($ary_sitemap_files);

		//  サイトマップ定義をロード
		$tmp_sitemap_definition = $this->px->dbh()->read_csv_utf8( $path_sitemap_definition );
		$tmp_sitemap_col = 'a';
		foreach ($tmp_sitemap_definition as $key=>$val) {
			$this->sitemap_definition[$val[0]] = array();
			$this->sitemap_definition[$val[0]]['num'] = $key;
			$this->sitemap_definition[$val[0]]['col'] = strtoupper($tmp_sitemap_col++);
			$this->sitemap_definition[$val[0]]['key'] = $val[0];
			$this->sitemap_definition[$val[0]]['name'] = $val[1];
		}
		unset($tmp_sitemap_definition);
		unset($tmp_sitemap_col);
		//  / サイトマップ定義をロード

		//  サイトマップをロード
		$num_auto_pid = 0;
		foreach( $ary_sitemap_files as $basename_sitemap_csv ){
			if( strtolower( $this->px->dbh()->get_extension($basename_sitemap_csv) ) != 'csv' ){
				continue;
			}
			$tmp_sitemap = $this->px->dbh()->read_csv_utf8( $path_sitemap_dir.$basename_sitemap_csv );
			$tmp_sitemap_definition = $this->sitemap_definition;
			foreach ($tmp_sitemap as $row_number=>$row) {
				set_time_limit(30);//タイマー延命
				$num_auto_pid++;
				$tmp_array = array();
				if( preg_match( '/^(?:\*)/is' , $row[0] ) ){
					if( $row_number > 0 ){
						// アスタリスク始まりの場合はコメント行とみなす。
						continue;
					}
					// アスタリスク始まりでも、0行目の場合は、定義行とみなす。
					// 定義行とみなす条件: 0行目の全セルがアスタリスク始まりであること。
					$is_definition_row = true;
					foreach($row as $cell_value){
						if( !preg_match( '/^(?:\*)/is' , $cell_value ) ){
							$is_definition_row = false;
						}
					}
					if( !$is_definition_row ){
						continue;
					}
					$tmp_sitemap_definition = array();
					$tmp_col_id = 'A';
					foreach($row as $tmp_col_number=>$cell_value){
						$cell_value = trim(preg_replace('/^\*/si', '', $cell_value));
						$tmp_sitemap_definition[$cell_value] = array(
							'num'=>$tmp_col_number,
							'col'=>$tmp_col_id++,
							'key'=>$cell_value,
							'name'=>$cell_value,
						);
					}
					unset($is_definition_row);
					unset($cell_value);
					continue;
				}
				foreach ($tmp_sitemap_definition as $defrow) {
					$tmp_array[$defrow['key']] = $row[$defrow['num']];
				}
				if( !preg_match( '/^(?:\/|alias\:|javascript\:|\#|[a-zA-Z0-9]+\:\/\/)/is' , $tmp_array['path'] ) ){
					// 不正な形式のチェック
					continue;
				}
				switch( $this->get_path_type( $tmp_array['path'] ) ){
					case 'full_url':
					case 'javascript':
					case 'anchor':
						// 直リンク系のパスをエイリアス扱いにする
						$tmp_array['path'] = preg_replace('/^(?:alias:)?/s', 'alias:', $tmp_array['path']);
						break;
					default:
						// スラ止のパスに index.html を付加する。
						// ただし、JS、アンカー、外部リンクには適用しない。
						$tmp_array['path'] = preg_replace( '/\/((?:\?|\#).*)?$/si' , '/index.html$1' , $tmp_array['path'] );
						break;
				}
				if( !strlen( $tmp_array['content'] ) ){
					$tmp_array['content'] = $tmp_array['path'];
					$tmp_array['content'] = preg_replace('/(?:\?|\#).*$/s','',$tmp_array['content']);
					$tmp_array['content'] = preg_replace('/\/$/s','/index.html',$tmp_array['content']);
				}
				$tmp_array['content'] = preg_replace( '/\/$/si' , '/index.html' , $tmp_array['content'] );//index.htmlを付加する。
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

				if( strlen($this->px->get_conf('project.path_top')) ){
					$tmp_path_top = $this->px->get_conf('project.path_top');
					$tmp_path_top = preg_replace( '/\/$/si' , '/index.html' , $tmp_path_top );//index.htmlを付加する。
					if( $tmp_array['path'] == $tmp_path_top ){
						$tmp_array['id'] = '';
					}elseif( !strlen($tmp_array['id']) ){
						$tmp_array['id'] = ':auto_page_id.'.($num_auto_pid);
					}
					unset($tmp_path_top);
				}

				if($this->get_path_type( $tmp_array['path'] ) == 'dynamic'){
					//ダイナミックパスのインデックス作成
					$tmp_preg_pattern = $tmp_array['path'];
					$preg_pattern = '';
					while(1){
						if( !preg_match('/^(.*?)\{(\$|\*)([a-zA-Z0-9\-\_]*)\}(.*)$/s',$tmp_preg_pattern,$tmp_matched) ){
							$preg_pattern .= preg_quote($tmp_preg_pattern,'/');
							break;
						}
						$preg_pattern .= preg_quote($tmp_matched[1],'/');
						switch( $tmp_matched[2] ){
							case '$':
								$preg_pattern .= '([a-zA-Z0-9\-\_]+)';break;
							case '*':
								$preg_pattern .= '(.*?)';break;
						}
						$tmp_preg_pattern = $tmp_matched[4];
						continue;
					}
					preg_match_all('/\{(\$|\*)([a-zA-Z0-9\-\_]*)\}/',$tmp_array['path'],$pattern_map);
					$tmp_path_original = $tmp_array['path'];
					$tmp_array['path'] = preg_replace('/'.preg_quote('{','/').'(\$|\*)([a-zA-Z0-9\-\_]*)'.preg_quote('}','/').'/s','$2',$tmp_array['path']);
					array_push( $this->sitemap_dynamic_paths, array(
						'path'=>$tmp_array['path'],
						'path_original'=>$tmp_path_original,
						'id'=>$tmp_array['id'],
						'preg'=>'/^'.$preg_pattern.'$/s',
						'pattern_map'=>$pattern_map[2],
					) );
					$tmp_array['path'] = $tmp_path_original;
					unset($preg_pattern);
					unset($pattern_map);
					unset($tmp_path_original);
				}

				if( preg_match( '/^alias\:/s' , $tmp_array['path'] ) ){
					//エイリアスの値調整
					$tmp_array['content'] = null;
					$tmp_array['path'] = preg_replace( '/^alias\:/s' , 'alias'.$num_auto_pid.':' , $tmp_array['path'] );
				}

				//  パンくず欄の先頭が > から始まっていた場合、削除
				$tmp_array['logical_path'] = preg_replace( '/^\>+/s' , '' , $tmp_array['logical_path'] );

				$this->sitemap_array[$tmp_array['path']] = $tmp_array;
				$this->sitemap_id_map[$tmp_array['id']] = $tmp_array['path'];
			}
		}
		//  / サイトマップをロード

		//  ダイナミックパスを並び替え
		usort($this->sitemap_dynamic_paths, array($this,'sort_sitemap_dynamic_paths'));

		//  ページツリー情報を構成
		$this->sitemap_page_tree = array();
		foreach( $this->sitemap_array as $tmp_path=>$tmp_page_info ){
			set_time_limit(30);//タイマー延命
			$this->get_children( $tmp_path, array('filter'=>true) );
			$this->get_children( $tmp_path, array('filter'=>false) );//list_flgを無視して、全員持ってくる
		}
		unset($tmp_path, $tmp_page_info );

		//  キャッシュディレクトリを作成
		$this->px->dbh()->mkdir($path_sitemap_cache_dir);

		//  キャッシュファイルを作成
		$this->px->dbh()->file_overwrite( $path_sitemap_cache_dir.'sitemap_definition.array' , t::data2phpsrc($this->sitemap_definition) );
		$this->px->dbh()->file_overwrite( $path_sitemap_cache_dir.'sitemap.array' , t::data2phpsrc($this->sitemap_array) );
		$this->px->dbh()->file_overwrite( $path_sitemap_cache_dir.'sitemap_id_map.array' , t::data2phpsrc($this->sitemap_id_map) );
		$this->px->dbh()->file_overwrite( $path_sitemap_cache_dir.'sitemap_dynamic_paths.array' , t::data2phpsrc($this->sitemap_dynamic_paths) );
		$this->px->dbh()->file_overwrite( $path_sitemap_cache_dir.'sitemap_page_tree.array' , t::data2phpsrc($this->sitemap_page_tree) );

		return true;
	}//load_sitemap_csv();

	/**
	 * ダイナミックパスの検索順を並べ替える
	 */
	private function sort_sitemap_dynamic_paths($a,$b){
		$path_short_a = preg_replace( '/\{.*$/si', '', $a['path_original'] );
		$path_short_b = preg_replace( '/\{.*$/si', '', $b['path_original'] );
		if( strlen($path_short_a) > strlen($path_short_b) ){ return -1; }
		if( strlen($path_short_a) < strlen($path_short_b) ){ return  1; }
		if( $path_short_a > $path_short_b ){ return -1; }
		if( $path_short_a < $path_short_b ){ return  1; }
		return 0;
	}

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
		if(!strlen($this->get_page_info($path,'id'))){
			// トップページの親はいない。
			return false;
		}
		$logical_path = $this->get_page_info( $path , 'logical_path' );
		if( !strlen($logical_path) ){return '';}
		$logical_paths = explode('>',$logical_path);
		$rtn = $logical_paths[count($logical_paths)-1];
		if(is_null($rtn)){
			return false;
		}
		return $rtn;
	}

	/**
	 * 所属するカテゴリトップページのIDを取得する
	 */
	public function get_category_top( $path = null ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$current_page_info = $this->get_page_info($path);
		if( $current_page_info['category_top_flg'] ){
			//  自身がカテゴリトップだった場合。
			return $current_page_info['id'];
		}
		if( !strlen($current_page_info['id']) ){
			//  自身がトップページだった場合。
			return '';
		}
		while( $parent_pid = $this->get_parent($parent_pid) ){
			if(!strlen($parent_pid)){
				break;
			}
			$page_info = $this->get_page_info($parent_pid);
			if( $page_info['category_top_flg'] ){
				//  自身がカテゴリトップだった場合。
				return $page_info['id'];
			}
		}
		return '';//引っかからなかったらトップページを返す
	}//get_category_top()

	/**
	 * ページ情報を取得する。
	 * @param パス または ページID
	 * @param [省略可] 取り出す単一要素のキー。省略時はすべての要素を含む連想配列が返される。
	 */
	public function get_page_info( $path ){
		if( is_null($path) ){
			return null;
		}
		if( !is_null($this->sitemap_id_map[$path]) ){
			//ページIDで指定された場合、パスに置き換える
			$path = $this->sitemap_id_map[$path];
		}
		if( is_null( $this->sitemap_array[$path] ) ){
			//  サイトマップにズバリなければ、
			//  ダイナミックパスを検索する。
			$sitemap_dynamic_path = $this->get_dynamic_path_info( $path );
			if( is_array( $sitemap_dynamic_path ) ){
				$path = $sitemap_dynamic_path['path_original'];
			}
		}
		$args = func_get_args();

		switch( $this->get_path_type($path) ){
			case 'full_url':
			case 'javascript':
			case 'anchor':
				break;
			default:
				$path = preg_replace( '/\/$/si' , '/index.html' , $path );
				break;
		}

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

	/**
	 * ページ情報をセットする。
	 */
	public function set_page_info( $path , $page_info ){
		static $num_auto_pid = 0;
		$path_type = $this->get_path_type($path);
		if( is_string( $path_type ) ){
			//  $path がスラドメされている場合に index.html を付加
			$path = preg_replace( '/\/$/si' , '/index.html' , $path );
		}

		$before_page_info = $this->get_page_info( $path );
		if(!is_array($before_page_info) || ( $before_page_info['path'] != $path && $before_page_info['id'] != $path ) ){
			//まったく新しいページだったら
			$before_page_info = $this->get_current_page_info();
			if( is_string( $path_type ) ){
				//  パスでの指定だった場合
				$before_page_info['path'] = $path;
				if(!strlen($page_info['id'])){
					//ページIDを動的に発行
					$before_page_info['id'] = ':live_auto_page_id.'.($num_auto_pid++);
				}
			}else{
				//  ページIDでの指定だった場合
				$before_page_info['id'] = $path;
				$page_info['id'] = $path;
			}
		}elseif(!is_null($this->sitemap_id_map[$path])){
			//既存ページをページIDで指定されていたら
			$before_page_info['id'] = $path;
		}else{
			//既存ページをパスで指定されていたら
			$before_page_info['path'] = $path;
			if(!strlen($page_info['id'])){
				//ページIDを動的に発行
				$before_page_info['id'] = ':live_auto_page_id.'.($num_auto_pid++);
			}
		}
		$tmp_array = $before_page_info;

		if( strlen($page_info['title']) && $page_info['title']!=$tmp_array['title'] ){
			//タイトルの指定があったら
			//タイトル系オプション値も自動で振りたいので、あえて消す。
			unset( $tmp_array['title_breadcrumb'] );
			unset( $tmp_array['title_h1'] );
			unset( $tmp_array['title_label'] );
		}

		//  パンくず欄の先頭が > から始まっていた場合、削除
		$tmp_array['logical_path'] = preg_replace( '/^\>+/s' , '' , $tmp_array['logical_path'] );

		//  指定値を反映
		foreach( $page_info as $key=>$val ){
			$tmp_array[$key] = $val;
		}

		if( !strlen( $tmp_array['title'] ) ){
			$tmp_array['title'] = $tmp_array['path'];
		}
		if( is_null( $tmp_array['id'] ) ){
			$tmp_array['id'] = ':live_auto_page_id.'.($num_auto_pid++);
		}

		//  サイトマップに登録
		$this->sitemap_array[$tmp_array['path']] = $tmp_array;
		$this->sitemap_id_map[$tmp_array['id']] = $tmp_array['path'];

		//  ページツリーキャッシュを削除
		$parent = $this->get_page_info_by_id( $this->get_parent( $tmp_array['path'] ) );
		$this->sitemap_page_tree[$parent['path']] = null;

		//  パブリッシュ対象にリンクを追加
		$this->px->add_relatedlink( $this->px->theme()->href($tmp_array['path']) );

		return true;
	}//set_page_info()

	/**
	 * ページIDからページ情報を得る
	 */
	public function get_page_info_by_id( $page_id ){
		return $this->get_page_info($page_id);
	}

	/**
	 * パスからページIDを得る
	 */
	public function get_page_id_by_path( $path ){
		$page_info = $this->get_page_info($path);
		return $page_info['id'];
	}

	/**
	 * ページIDからパスを得る
	 */
	public function get_page_path_by_id( $page_id ){
		$page_info = $this->get_page_info($page_id);
		return $page_info['path'];
	}



	/**
	 * 現在のページの情報を得る
	 */
	public function get_current_page_info(){
		$current_path = $this->px->req()->get_request_file_path();
		return $this->get_page_info( $current_path );
	}

	/**
	 * パスがダイナミックパスにマッチするか調べる
	 */
	public function is_match_dynamic_path( $path ){
		foreach( $this->sitemap_dynamic_paths as $sitemap_dynamic_path ){
			//ダイナミックパスを検索
			if( preg_match( $sitemap_dynamic_path['preg'] , $path ) ){
				return true;
			}
		}
		return false;
	}

	/**
	 * ダイナミックパス情報を得る
	 */
	public function get_dynamic_path_info( $path ){
		foreach( $this->sitemap_dynamic_paths as $sitemap_dynamic_path ){
			//ダイナミックパスを検索
			if( $sitemap_dynamic_path['path_original'] == $path ){
				return $sitemap_dynamic_path;
			}
			if( preg_match( $sitemap_dynamic_path['preg'] , $path ) ){
				return $sitemap_dynamic_path;
			}
		}
		return false;
	}

	/**
	 * ダイナミックパスに値をバインドする
	 */
	public function bind_dynamic_path_param( $dynamic_path , $params = array() ){
		$path = '';
		while( 1 ){
			if( !preg_match( '/^(.*?)\{(\$|\*)([a-zA-Z0-9\_\-]*)\}(.*)$/s' , $dynamic_path , $tmp_matched ) ){
				$path .= $dynamic_path;
				break;
			}
			$path .= $tmp_matched[1];
				// ※注意: このメソッドでは、無名のパラメータもバインドする。
				//   (明示的に使用されるメソッドなので)
			if( !is_null( $params[$tmp_matched[3]] ) ){
				$path .= $params[$tmp_matched[3]];
			}else{
				$path .= $tmp_matched[3];
			}
			$dynamic_path = $tmp_matched[4];
			continue;
		}
		unset($dynamic_path , $tmp_matched);
		$path = preg_replace('/\/$/si','/index.html',$path); // index.htmlをつける
		return $path;
	}//bind_dynamic_path_param()

	/**
	 * 子階層のページの一覧を取得する
	 * @param $path
	 */
	public function get_children( $path = null, $opt = array() ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$filter = true;
		if(!is_null($opt['filter'])){ $filter = !empty($opt['filter']); }

		$page_info = $this->get_page_info( $path );

		if( $filter && is_array( $this->sitemap_page_tree[$page_info['path']]['children'] ) ){
			//  ページキャッシュツリーがすでに作られている場合
			return $this->sitemap_page_tree[$page_info['path']]['children'];
		}
		if( !$filter && is_array( $this->sitemap_page_tree[$page_info['path']]['children_all'] ) ){
			//  ページキャッシュツリーがすでに作られている場合
			return $this->sitemap_page_tree[$page_info['path']]['children_all'];
		}

		$tmp_children_orderby_manual = array();
		$tmp_children_orderby_auto = array();

		$current_layer = '';
		if( strlen( trim($page_info['id']) ) ){
			$tmp_breadcrumb = explode( '>', $page_info['logical_path'].'>'.$page_info['id'] );
			foreach( $tmp_breadcrumb as $tmp_path ){
				if( !strlen($tmp_path) ){continue;}
				$tmp_page_info = $this->get_page_info( trim($tmp_path) );
				$current_layer .= '>'.$tmp_page_info['id'];
			}
		}
		unset($tmp_breadcrumb,$tmp_path,$tmp_page_info);

		foreach( $this->get_sitemap() as $row ){
			if( !strlen($row['id']) ){
				continue;
			}
			if($filter){
				if( !$row['list_flg'] ){
					continue;
				}
			}

			$target_layer = '';
			if( strlen( trim($row['id']) ) ){
				$tmp_breadcrumb = explode( '>', $row['logical_path'] );
				foreach( $tmp_breadcrumb as $tmp_path ){
					if( !strlen($tmp_path) ){continue;}
					$tmp_page_info = $this->get_page_info( trim($tmp_path) );
					$target_layer .= '>'.$tmp_page_info['id'];
				}
				unset($tmp_breadcrumb,$tmp_path,$tmp_page_info);
			}

			if( $current_layer == $target_layer ){
				if(strlen($row['orderby'])){
					array_push( $tmp_children_orderby_manual , $row['id'] );
				}else{
					array_push( $tmp_children_orderby_auto , $row['id'] );
				}
			}
		}

		usort( $tmp_children_orderby_manual , array( $this , 'usort_sitemap' ) );
		$rtn = array_merge( $tmp_children_orderby_manual , $tmp_children_orderby_auto );

		//  ページキャッシュを作成しなおす
		if($filter){
			$this->sitemap_page_tree[$page_info['path']]['children'] = $rtn;
		}else{
			$this->sitemap_page_tree[$page_info['path']]['children_all'] = $rtn;
		}

		return $rtn;
	}//get_children()

	/**
	 * ページ情報の配列を並び替える
	 * @param 比較対象1のページID
	 * @param 比較対象2のページID
	 */
	private function usort_sitemap( $a , $b ){
		$page_info_a = $this->get_page_info( $a );
		$page_info_b = $this->get_page_info( $b );
		$orderby_a = $page_info_a['orderby'];
		$orderby_b = $page_info_b['orderby'];
		if( strlen( $orderby_a ) && !strlen( $orderby_b ) ){
			return	-1;
		}elseif( strlen( $orderby_b ) && !strlen( $orderby_a ) ){
			return	1;
		}elseif( $orderby_a < $orderby_b ){
			return	-1;
		}elseif( $orderby_a > $orderby_b ){
			return	1;
		}
		return	0;
	}//usort_sitemap()

	/**
	 * 同じ階層のページの一覧を取得する
	 * @param $path
	 */
	public function get_bros( $path = null, $opt = array() ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$page_info = $this->get_page_info($path);
		if( !strlen($page_info['id']) ){
			//トップページの兄弟はトップページだけ。
			return array('');
		}
		$parent = $this->get_parent( $path );
		$bros = $this->get_children( $parent, $opt );
		return $bros;
	}//get_bros()

	/**
	 * 同じ階層の次のページのIDを取得する
	 * @param $path
	 */
	public function get_bros_next( $path = null, $opt = array() ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$filter = true;
		if(!is_null($opt['filter'])){ $filter = !empty($opt['filter']); }

		$bros = $this->get_bros($path,$opt);
		$page_info = $this->get_page_info($path);
		if( !strlen($page_info['id']) ){
			//トップページの次の兄弟はいない。
			return false;
		}

		foreach($bros as $num=>$row){
			if( $row == $page_info['id'] ){
				break;
			}
		}
		for($i = $num+1; !is_null($bros[$i]); $i ++){
			if(is_null($bros[$i])){
				return false;
			}
			if($filter===false || $this->get_page_info($bros[$i], 'layout') != 'popup' && $this->get_path_type($this->get_page_info($bros[$i], 'path')) != 'alias' ){
				return $bros[$i];
			}
		}
		return false;
	}//get_bros_next()

	/**
	 * 同じ階層の前のページのIDを取得する
	 * @param $path
	 */
	public function get_bros_prev( $path = null, $opt = array() ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$filter = true;
		if(!is_null($opt['filter'])){ $filter = !empty($opt['filter']); }

		$bros = $this->get_bros($path,$opt);
		$page_info = $this->get_page_info($path);
		if( !strlen($page_info['id']) ){
			//トップページの前の兄弟はいない。
			return false;
		}

		foreach($bros as $num=>$row){
			if( $row == $page_info['id'] ){
				break;
			}
		}
		for($i = $num-1; !is_null($bros[$i]); $i --){
			if(is_null($bros[$i])){
				return false;
			}
			if($filter===false || $this->get_page_info($bros[$i], 'layout') != 'popup' && $this->get_path_type( $this->get_page_info($bros[$i], 'path') ) != 'alias' ){
				return $bros[$i];
			}
		}
		return false;
	}//get_bros_prev()

	/**
	 * 次のページのIDを取得する。
	 */
	public function get_next( $path = null, $opt = array() ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$filter = true;
		if(!is_null($opt['filter'])){ $filter = !empty($opt['filter']); }

		//  子供がいたら
		if(!$opt['skip_children']){
			$children = $this->get_children($path,$opt);
			if(is_array($children) && count($children)){
				foreach($children as $child){
					if($filter===true){
						if($this->get_page_info($child,'layout') == 'popup'){//popupページは含まない
							continue;
						}
						if($this->get_path_type($this->px->site()->get_page_info($child,'path')) == 'alias'){//エイリアスは含まない
							continue;
						}
					}
					return $child;
				}
			}
		}

		//  次の兄弟がいたら、そのひとがnext
		$page_bros_next = $this->get_bros_next($path,$opt);
		if($page_bros_next!==false){return $page_bros_next;}

		//  親の兄弟
		$parent = $this->get_parent($path);
		if($parent===false){return false;}

		$rtn = $this->get_next($parent, array('skip_children'=>true,'filter'=>$filter));
		return $rtn;
	}

	/**
	 * 前のページのIDを取得する。
	 */
	public function get_prev( $path = null, $opt = array() ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$filter = true;
		if(!is_null($opt['filter'])){ $filter = !empty($opt['filter']); }

		//  前の兄弟がいたら、そのひとがprev
		$page_bros_prev = $this->get_bros_prev($path,$opt);
		if($page_bros_prev!==false){
			// 前の兄弟の子供を調べる。 該当する子供がいたらそのひとがprev
			$prev_children = $this->get_children($page_bros_prev,$opt);
			if(is_array($prev_children) && count($prev_children)){
				if( $filter===false || $this->get_page_info($prev_children[count($prev_children)-1], 'layout') != 'popup' && $this->get_path_type($this->get_page_info($prev_children[count($prev_children)-1], 'path')) != 'alias'){
					return $prev_children[count($prev_children)-1];
				}
				$child_prev = $this->get_bros_prev($prev_children[count($prev_children)-1],$opt);
				if( $child_prev !== false ){
					return $child_prev;
				}
			}
			return $page_bros_prev;
		}

		//  親の兄弟
		$parent = $this->get_parent($path);
		if($parent===false){return false;}

		return $parent;
	}

	/**
	 * パンくず配列を取得する
	 * @param $path = 基点とするページのパス、またはID。(省略時カレントページ)
	 * @return 親ページまでのパンくず階層をあらわす配列。自身を含まない。$pathがトップページを示す場合は、空の配列。
	 */
	public function get_breadcrumb_array( $path = null ){
		if( is_null( $path ) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$page_info = $this->get_page_info( $path );
		if( !strlen($page_info['id']) ){return array();}

		$rtn = array('');
		$tmp_breadcrumb = explode( '>', $page_info['logical_path'] );
		foreach( $tmp_breadcrumb as $tmp_id ){
			if( !strlen($tmp_id) ){continue;}
			$tmp_page_info = $this->get_page_info( trim($tmp_id) );
			array_push( $rtn , $tmp_page_info['id'] );
		}

		return $rtn;
	}//get_breadcrumb_array()

	/**
	 * ページが、パンくず内に存在しているか調べる
	 * @param $page_path = 調べる対象のページのパス、またはID。
	 * @param $path = 基点とするページのパス、またはID。(省略時カレントページ)
	 */
	public function is_page_in_breadcrumb( $page_path, $path = null ){
		if( is_null($path) ){
			$path = $this->px->req()->get_request_file_path();
		}
		$breadcrumb = $this->get_breadcrumb_array($path);
		$current_page_id = $this->get_page_id_by_path($path);
		$target_page_id = $this->get_page_id_by_path($page_path);
		if( $current_page_id == $target_page_id ){
			return true;
		}
		foreach( $breadcrumb as $row ){
			if( $target_page_id == $this->get_page_id_by_path($row) ){
				return true;
			}
		}
		return false;
	}// is_page_in_breadcrumb()

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
		if( preg_match( '/^(?:alias[0-9]*\:)?javascript\:/i' , $path ) ) {
			//  javascript: から始まる場合
			//  サイトマップ上での重複を許容するために、
			//  自動的にalias扱いとなることを考慮した正規表現。
			$path_type = 'javascript';
		} else if( preg_match( '/^(?:alias[0-9]*\:)?\#/' , $path ) ) {
			//  # から始まる場合
			//  サイトマップ上での重複を許容するために、
			//  自動的にalias扱いとなることを考慮した正規表現。
			$path_type = 'anchor';
		} else if( preg_match( '/^(?:alias[0-9]*\:)?[a-zA-Z0-90-9]+\:\/\//' , $path ) ) {
			//  http:// などURLスキーマから始まる場合
			//  サイトマップ上での重複を許容するために、
			//  自動的にalias扱いとなることを考慮した正規表現。
			$path_type = 'full_url';
		} else if( preg_match( '/^alias[0-9]*\:/' , $path ) ) {
			//  alias:から始まる場合
			//  サイトマップデータ上でpathは一意である必要あるので、
			//  alias と : の間に、後から連番を降られる。
			//  このため、数字が含まれている場合を考慮した。(@tomk79)
			$path_type = 'alias';
		} else if( preg_match( '/\{(?:\$|\*)(?:[a-zA-Z0-9\_\-]*)\}/' , $path ) ) {
			//  {$xxxx}を含む場合(ダイナミックパス)
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