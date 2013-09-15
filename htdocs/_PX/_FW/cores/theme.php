<?php
/**
 * PxFW core object class: Theme and style Manager
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_cores_theme{
	private $px;
	private $theme_id = 'default';
	private $layout_id = 'default';
	private $contents_cabinet = array(
		''=>'',    //  メインコンテンツ
		'head'=>'' //  ヘッドセクションに追記
	);

	private $func_data_memos = array(//機能別に値を記憶する領域
		'autoindex'=>null ,//autoindex機能
	);

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
		if(strlen($this->px->get_conf('system.default_theme_id'))){
			$this->theme_id = trim($this->px->get_conf('system.default_theme_id'));
		}
		if( strlen( $this->px->req()->get_session('THEME') ) ){
			$this->set_theme_id( $this->px->req()->get_session('THEME') );
		}
	}//__construct()

	/**
	 * テーマIDをセットする。
	 * @return bool
	 */
	public function set_theme_id( $theme_id ){
		if( !strlen( $theme_id ) ){ return false; }
		if( !preg_match( '/^[a-zA-Z0-9\_\-]+$/si' , $theme_id ) ){ return false; }
		if( !is_file( $this->px->dbh()->get_realpath($this->px->get_conf('paths.px_dir').'themes/'.$theme_id).'/default.html' ) ){
			//  指定のテーマディレクトリが存在しなかったら。
			//	※レイアウト default.html は必須です。
			$this->px->error()->error_log('存在しないテーマ['.$theme_id.']を選択しました。',__FILE__,__LINE__);
			$theme_id = 'default';
		}
		$this->theme_id = $theme_id;

		$default_theme_id = trim( $this->px->get_conf('system.default_theme_id') );
		if(!strlen($default_theme_id)){ $default_theme_id = 'default'; }
		if($this->theme_id == $default_theme_id){
			$this->px->req()->delete_session('THEME');
		}else{
			$this->px->req()->set_session('THEME',$this->theme_id);
		}
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
		if( !preg_match( '/^[a-zA-Z0-9\_\-\/]+$/si' , $layout_id ) ){ return false; }
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

		//------------
		//  コンテンツソースの事後加工処理

		//  autoindex
		if( is_array( $this->func_data_memos['autoindex'] ) ){
			$content = $this->pull_content('',false);
			$content = $this->apply_autoindex( $content );
			$this->replace_content($content,'');
		}

		//  / コンテンツソースの事後加工処理
		//------------

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

		$path_template_file = $template_path.''.$this->get_layout_id().'.html';
		if( !is_file($path_template_file) ){
			$path_template_file = $template_path.'default.html';
		}

		unset($template_path);
		unset($page_info);
		$px = $this->px;
		ob_start();
		@include( $path_template_file );
		$src = ob_get_clean();

		return $src;
	}//bind_contents();

	/**
	 * 最終出力処理
	 * この処理は、標準出力の直前にextensionsによって呼び出されます。
	 */
	public function output_filter( $src, $extension ){

		//  プラグインの outputfilter を適用
		$tmp_path_plugins_base_dir = $this->px->get_conf('paths.px_dir').'plugins/';
		$tmp_plugin_list = $this->px->dbh()->ls( $tmp_path_plugins_base_dir );
		foreach( $tmp_plugin_list as $tmp_plugin_name ){
			if( is_file( $tmp_path_plugins_base_dir.$tmp_plugin_name.'/register/outputfilter.php' ) ){
				$tmp_class_name = $this->px->load_px_plugin_class($tmp_plugin_name.'/register/outputfilter.php');
				if($tmp_class_name){
					$tmp_plugin_output = new $tmp_class_name($this->px);
					$src = $tmp_plugin_output->execute($src, $extension);
				}
			}
		}
		unset($tmp_path_plugins_base_dir,$tmp_plugin_list,$tmp_plugin_name,$tmp_class_name,$tmp_plugin_output);

		//  テーマ個別の outputfilter 処理
		$class_name = $this->px->load_pxtheme_class('/styles/outputfilter.php');
		if( $class_name !== false ){
			$obj_outputfilter = new $class_name( $this->px );
			$src = $obj_outputfilter->execute( $src, $extension );
		}
		unset($class_name, $obj_outputfilter);

		if(strlen($this->px->get_conf('system.output_encoding'))){
			$output_encoding = $this->px->get_conf('system.output_encoding');
			if(!strlen($output_encoding)){ $output_encoding = 'UTF-8'; }
			switch(strtolower($extension)){
				case 'css':
					@header('Content-type: text/css; charset='.$output_encoding);//デフォルトのヘッダー

					//出力ソースの文字コード変換
					$src = preg_replace('/\@charset\s+"[a-zA-Z0-9\_\-\.]+"\;/si','@charset "'.t::h($output_encoding).'";',$src);
					$src = t::convert_encoding($src,$output_encoding,'utf-8');
					break;
				case 'js':
					@header('Content-type: text/javascript; charset='.$output_encoding);//デフォルトのヘッダー

					//出力ソースの文字コード変換
					$src = t::convert_encoding($src,$output_encoding,'utf-8');
					break;
				default:
					@header('Content-type: text/html; charset='.$output_encoding);//デフォルトのヘッダー

					//出力ソースの文字コード変換(HTML)
					$src = preg_replace('/<meta\s+charset\="[a-zA-Z0-9\_\-\.]+"\s*\/?'.'>/si','<meta charset="'.t::h($output_encoding).'" />',$src);
					$src = preg_replace('/<meta\s+http\-equiv\="Content-Type"\s+content\="text\/html\;\s+charset\=[a-zA-Z0-9\_\-\.]+"\s*\/?'.'>/si','<meta http-equiv="Content-Type" content="text/html; charset='.t::h($output_encoding).'" />',$src);
					switch(strtolower($output_encoding)){
						case 'sjis':
						case 'sjis-win':
						case 'shift_jis':
							$src = t::convert_encoding($src,'SJIS-win','utf-8');
							break;
						case 'eucjp':
						case 'eucjp-win':
						case 'euc-jp':
							$src = t::convert_encoding($src,'eucJP-win','utf-8');
							break;
						default:
							$src = t::convert_encoding($src,$output_encoding,'utf-8');
							break;
					}
					break;
			}

		}
		if(strlen($this->px->get_conf('system.output_eof_coding'))){
			//出力ソースの改行コード変換
			$eof_code = "\r\n";
			switch( strtolower( $this->px->get_conf('system.output_eof_coding') ) ){
				case 'cr':     $eof_code = "\r"; break;
				case 'lf':     $eof_code = "\n"; break;
				case 'crlf':
				default:       $eof_code = "\r\n"; break;
			}
			$src = preg_replace('/\r\n|\r|\n/si',$eof_code,$src);
		}

		return $src;
	}//output_filter()

	/**
	 * リンク先を調整する。
	 * @return string href属性値
	 */
	public function href( $linkto ){
		$tmp_page_info_by_id = $this->px->site()->get_page_info_by_id($linkto);
		if( $tmp_page_info_by_id['path'] ){
			$linkto = $tmp_page_info_by_id['path'];
		}
		unset($tmp_page_info_by_id);

		$path = $linkto;
		if( preg_match( '/^alias[0-9]*\:(.+)/' , $path , $tmp_matched ) ){
			//  エイリアスを解決
			$path = $tmp_matched[1];
		}elseif( $this->px->site()->get_path_type( $path ) == 'dynamic' ){
			//  ダイナミックパスをバインド
			$sitemap_dynamic_path = $this->px->site()->get_dynamic_path_info( $path );
			$tmp_path = $sitemap_dynamic_path['path_original'];
			$path = '';
			while( 1 ){
				if( !preg_match( '/^(.*?)\{(\$|\*)([a-zA-Z0-9\_\-]*)\}(.*)$/s' , $tmp_path , $tmp_matched ) ){
					$path .= $tmp_path;
					break;
				}
				$path .= $tmp_matched[1];
				if(!strlen($tmp_matched[3])){
					//無名のパラメータはバインドしない。
				}elseif( !is_null( $this->px->req()->get_path_param($tmp_matched[3]) ) ){
					$path .= $this->px->req()->get_path_param($tmp_matched[3]);
				}else{
					$path .= $tmp_matched[3];
				}
				$tmp_path = $tmp_matched[4];
				continue;
			}
			unset($tmp_path , $tmp_matched);
		}
		switch( $this->px->site()->get_path_type( $path ) ){
			case 'full_url':
			case 'javascript':
			case 'anchor':
				break;
			default:
				// index.htmlを省略
				$path = preg_replace('/\/index\.html((?:\?|\#).*)?$/si','/$1',$path);
				break;
		}

		if( preg_match( '/^\//' , $path ) ){
			//  スラッシュから始まる絶対パスの場合、
			//  インストールパスを起点としたパスに書き変えて返す。
			$path = preg_replace( '/^\/+/' , '' , $path );
			$path = $this->px->get_install_path().$path;
		}
		return $path;
	}//href()

	/**
	 * リンクタグ(aタグ)を生成する。
	 * @param $linkto リンク先のパス。PxFWのインストールパスを基点にした絶対パスで指定。
	 * @param options: [as string] Link label, [as array] Any options.
	 * @return string aタグ
	 */
	public function mk_link( $linkto ){
		$args = func_get_args();
		$page_info = $this->px->site()->get_page_info($linkto);
		$href = $this->href($linkto);
		$hrefc = $this->href($this->px->req()->get_request_file_path());
		$label = $page_info['title_label'];
		$page_id = $page_info['id'];
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
		$breadcrumb = $this->px->site()->get_breadcrumb_array($hrefc);
		$is_current = false;
		if( !is_null( $options['current'] ) ){
			$is_current = !empty($options['current']);
		}elseif($href==$hrefc){
			$is_current = true;
		}elseif( $this->px->site()->is_page_in_breadcrumb($linkto) ){
			$is_current = true;
		}
		$is_popup = false;
		if( $this->px->site()->get_page_info($linkto,'layout') == 'popup' ){
			$is_popup = true;
		}
		$label = (!is_null($label)?$label:$href); // labelがnullの場合、リンク先をラベルとする

		$classes = array();
		// CSSのクラスを付加
		if( is_string($options['class']) ){
			$options['class'] = preg_split( '/\s+/', trim($options['class']) );
		}
		if( is_array($options['class']) ){
			foreach($options['class'] as $class_row){
				array_push($classes, trim($class_row));
			}
		}
		if($is_current){
			array_push($classes, 'current');
		}

		if( !$options['no_escape'] ){
			// no_escape(エスケープしない)指示がなければ、
			// HTMLをエスケープする。
			$label = t::h($label);
		}

		$rtn = '<a href="'.t::h($href).'"'.(count($classes)?' class="'.t::h(implode(' ', $classes)).'"':'').''.($is_popup?' onclick="window.open(this.href);return false;"':'').'>'.$label.'</a>';
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
			$href = $this->href($linkto_page_info['path']);
			if( $href == $current_path ){
				$rtn .= '<li> &gt; '.t::h($linkto_page_info['title_breadcrumb']).'</li>';
			}else{
				$rtn .= '<li> &gt; <a href="'.t::h($href).'">'.t::h($linkto_page_info['title_breadcrumb']).'</a></li>';
			}
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
	 * コンテンツキャビネットのコンテンツを置き換える
	 */
	public function replace_content( $src , $content_name = '' ){
		if( !strlen($content_name) ){ $content_name = ''; }
		if( !is_string($content_name) ){ return false; }
		$this->contents_cabinet[$content_name] = $src;
		return true;
	}

	/**
	 * コンテンツキャビネットからコンテンツを引き出す
	 * @param string $content_name : キャビネット上のコンテンツ名
	 * @param bool $do_finalize : ファイナライズ処理を有効にするか(default: true)
	 */
	public function pull_content( $content_name = '', $do_finalize = true ){
		if( !strlen($content_name) ){ $content_name = ''; }
		if( !is_string($content_name) ){ return false; }

		$content = $this->contents_cabinet[$content_name];

		//  コンテンツソースのファイナライズ
		if( $do_finalize === true ){
			//  テーマ個別のfinalizer処理
			$class_name = $this->px->load_pxtheme_class('/styles/finalizer.php');
			if( $class_name !== false ){
				$obj_finalizer = new $class_name( $this->px );
				$content = $obj_finalizer->finalize_contents( $content );
			}
			unset($class_name);
			unset($obj_finalizer);

			//  共通のfinalizer処理
			$class_name = $this->px->load_px_class('/styles/finalizer.php');
			if( $class_name !== false ){
				$obj_finalizer = new $class_name( $this->px );
				$content = $obj_finalizer->finalize_contents( $content );
			}
			unset($class_name);
			unset($obj_finalizer);
		}

		return $content;
	}

	/**
	 * ページ内の目次を自動生成する
	 */
	public function autoindex(){
		if( !is_array( $this->func_data_memos['autoindex'] ) ){
			$this->func_data_memos['autoindex'] = array();
			$this->func_data_memos['autoindex']['metastr'] = '[__autoindex_'.md5( time() ).'__]';
		}
		return $this->func_data_memos['autoindex']['metastr'];
	}//autoindex();

	/**
	 * ページ内の目次をソースに反映する
	 */
	private function apply_autoindex( $content ){
		$tmp_cont = $content;
		$content = '';
		$index = array();
		$indexCounter = array();
		$i = 0;
		while( 1 ){
			set_time_limit(60*30);
			if( !preg_match( '/^(.*?)(<\!\-\-(?:.*?)\-\->|<script(?:\s.*?)?>(?:.*?)<\/script>|<h([2-6])(?:\s.*?)?>(.*?)<\/h\3>)(.*)$/is' , $tmp_cont , $matched ) ){
				$content .= $tmp_cont;
				break;
			}
			$i ++;
			$tmp = array();
			$tmp['label'] = $matched[4];
			$tmp['label'] = strip_tags( $tmp['label'] );//ラベルからHTMLタグを除去
			$tmp['anch'] = 'hash_'.($tmp['label']);
			if($indexCounter[$tmp['anch']]){
				$indexCounter[$tmp['anch']] ++;
				$tmp['anch'] = 'hash_'.$indexCounter[$tmp['anch']].'_'.($tmp['label']);
			}else{
				$indexCounter[$tmp['anch']] = 1;
			}
			$tmp['headlevel'] = intval($matched[3]);
			if( $tmp['headlevel'] ){# 引っかかったのが見出しの場合
				array_push( $index , $tmp );
			}

			$content .= $matched[1];
			if( $tmp['headlevel'] ){# 引っかかったのが見出しの場合
				#$content .= $this->back2top();
				$content .= '<span';
				$content .= ' id="'.htmlspecialchars($tmp['anch']).'"';
				$content .= '></span>';
			}
			$content .= $matched[2];
			$tmp_cont = $matched[5];
		}
		set_time_limit(30);

		$anchorlinks = '';
		$topheadlevel = 2;
		$headlevel = $topheadlevel;
		if( count( $index ) ){
			$anchorlinks .= '<!-- autoindex -->'."\n";
			$anchorlinks .= '<div class="anchor_links">'."\n";
			$anchorlinks .= '<p class="anchor_links-heading">目次</p>';
			foreach($index as $key=>$row){
				$csa = $row['headlevel'] - $headlevel;
				$nextLevel = $index[$key+1]['headlevel'];
				$nsa = null;
				if( strlen( $nextLevel ) ){
					$nsa = $nextLevel - $row['headlevel'];
				}
				$headlevel = $row['headlevel'];
				if( $csa>0 ){
					#	いま下がるとき
					if( $key == 0 ){
						$anchorlinks .= '<ul><li>';
					}
					for( $i = $csa; $i>0; $i -- ){
						$anchorlinks .= '<ul><li>';
					}
				}elseif( $csa<0 ){
					#	いま上がるとき
					if( $key == 0 ){
						$anchorlinks .= '<ul><li>';
					}
					for( $i = $csa; $i<0; $i ++ ){
						$anchorlinks .= '</li></ul>';
					}
					$anchorlinks .= '</li><li>';
				}else{
					#	いま現状維持
					if( $key == 0 ){
						$anchorlinks .= '<ul>';
					}
					$anchorlinks .= '<li>';
				}
				$anchorlinks .= '<a href="#'.htmlspecialchars($row['anch']).'">'.($row['label']).'</a>';
				if( is_null($nsa) ){
					break;
				}elseif( $nsa>0 ){
					#	つぎ下がるとき
#					for( $i = $nsa; $i>0; $i -- ){
#						$anchorlinks .= '</li></ul></li>';
#					}
				}elseif( $nsa<0 ){
					#	つぎ上がるとき
					for( $i = $nsa; $i<0; $i ++ ){
//						$anchorlinks .= '</li></ul>'."\n";
					}
				}else{
					#	つぎ現状維持
					$anchorlinks .= '</li>'."\n";
				}
			}
			while($headlevel >= $topheadlevel){
				$anchorlinks .= '</li></ul>'."\n";
				$headlevel --;
			}
			$anchorlinks .= '</div><!-- /.anchor_links -->'."\n";
			$anchorlinks .= '<!-- / autoindex -->'."\n";
		}

		$content = preg_replace( '/'.preg_quote($this->func_data_memos['autoindex']['metastr'],'/').'/si' , $anchorlinks , $content );
		return $content;
	}//apply_autoindex();

	/**
	 * ページャー情報を計算して答える
	 */
	public function get_pager_info( $total_count , $current_page_num , $display_per_page = 10 , $option = array() ){
		#	Pickles Framework 0.1.3 で追加

		#	総件数
		$total_count = intval( $total_count );
		if( $total_count <= 0 ){ return false; }

		#	現在のページ番号
		$current_page_num = intval( $current_page_num );
		if( $current_page_num <= 0 ){ $current_page_num = 1; }

		#	ページ当たりの表示件数
		$display_per_page = intval( $display_per_page );
		if( $display_per_page <= 0 ){ $display_per_page = 10; }

		#	インデックスの範囲
		$index_size = 0;
		if( !is_null( $option['index_size'] ) ){
			$index_size = intval( $option['index_size'] );
		}
		if( $index_size < 1 ){
			$index_size = 5;
		}

		$RTN = array(
			'tc'=>$total_count,
			'dpp'=>$display_per_page,
			'current'=>$current_page_num,
			'total_page_count'=>null,
			'first'=>null,
			'prev'=>null,
			'next'=>null,
			'last'=>null,
			'limit'=>$display_per_page,
			'offset'=>0,
			'index_start'=>0,
			'index_end'=>0,
			'errors'=>array(),
		);

		if( $total_count%$display_per_page ){
			$RTN['total_page_count'] = intval($total_count/$display_per_page) + 1;
		}else{
			$RTN['total_page_count'] = intval($total_count/$display_per_page);
		}

		if( $RTN['total_page_count'] != $current_page_num ){
			$RTN['last'] = $RTN['total_page_count'];
		}
		if( 1 != $current_page_num ){
			$RTN['first'] = 1;
		}

		if( $RTN['total_page_count'] > $current_page_num ){
			$RTN['next'] = intval($current_page_num) + 1;
		}
		if( 1 < $current_page_num ){
			$RTN['prev'] = intval($current_page_num) - 1;
		}

		$RTN['offset'] = ($RTN['current']-1)*$RTN['dpp'];

		if( $current_page_num > $RTN['total_page_count'] ){
			array_push( $RTN['errors'] , 'Current page num ['.$current_page_num.'] is over the Total page count ['.$RTN['total_page_count'].'].' );
		}

		#	インデックスの範囲
		#		23:50 2007/08/29 Pickles Framework 0.1.8 追加
		$RTN['index_start'] = 1;
		$RTN['index_end'] = $RTN['total_page_count'];
		if( ( $index_size*2+1 ) >= $RTN['total_page_count'] ){
			#	範囲のふり幅全開にしたときに、
			#	総ページ数よりも多かったら、常に全部出す。
			$RTN['index_start'] = 1;
			$RTN['index_end'] = $RTN['total_page_count'];
		}elseif( ( $index_size < $RTN['current'] ) && ( $index_size < ( $RTN['total_page_count']-$RTN['current'] ) ) ){
			#	範囲のふり幅全開にしたときに、
			#	すっぽり収まるようなら、前後に $index_size 分だけ出す。
			$RTN['index_start'] = $RTN['current']-$index_size;
			$RTN['index_end'] = $RTN['current']+$index_size;
		}elseif( $index_size >= $RTN['current'] ){
			#	前方が収まらない場合は、
			#	あまった分を後方に回す
			$surplus = ( $index_size - $RTN['current'] + 1 );
			$RTN['index_start'] = 1;
			$RTN['index_end'] = $RTN['current']+$index_size+$surplus;
		}elseif( $index_size >= ( $RTN['total_page_count']-$RTN['current'] ) ){
			#	後方が収まらない場合は、
			#	あまった分を前方に回す
			$surplus = ( $index_size - ($RTN['total_page_count']-$RTN['current']) );
			$RTN['index_start'] = $RTN['current']-$index_size-$surplus;
			$RTN['index_end'] = $RTN['total_page_count'];
		}

		return	$RTN;
	}

}

?>