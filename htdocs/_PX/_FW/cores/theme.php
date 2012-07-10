<?php
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
	public function __construct( &$px ){
		$this->px = &$px;
		if( strlen( $this->px->req()->get_session('THEME') ) ){
			$this->theme_id = $this->px->req()->get_session('THEME');
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
			return false;
		}
		$this->px->req()->set_session('THEME',$theme_id);
		$this->theme_id = $this->px->req()->get_session('THEME');
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

		//  コンテンツソースの事後加工処理
		if( is_array( $this->func_data_memos['autoindex'] ) ){
			//  autoindex
			$content = $this->pull_content('');
			$content = $this->apply_autoindex( $content );
			$this->replace_content($content,'');
		}
		//  / コンテンツソースの事後加工処理

		@header('Content-type: text/html; charset=UTF-8');//デフォルトのヘッダー

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

		$px = &$this->px;
		ob_start();
		@include( $path_template_file );
		$src = ob_get_clean();

		return $src;
	}//bind_contents();

	/**
	 * リンク先を調整する。
	 * @return string href属性値
	 */
	public function href( $linkto ){
		$path = $this->px->site()->get_page_info($linkto,'path');
		if( preg_match( '/^alias[0-9]*\:(.+)/' , $path , $tmp_matched ) ){
			$path = $tmp_matched[1];
		}
		$path = preg_replace('/\/index\.html$/si','/',$path); // index.htmlを省略
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
	 */
	public function pull_content( $content_name = '' ){
		if( !strlen($content_name) ){ $content_name = ''; }
		if( !is_string($content_name) ){ return false; }
		return $this->contents_cabinet[$content_name];
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
			if( !preg_match( '/^(.*?)(<\!\-\-(?:.*?)\-\->|<script(?:\s.*?)?>(?:.*?)<\/script>|<h([2-6])(?:\s.*?)?>(.*?)<\/h\3>)(.*)$/is' , $tmp_cont , $matched ) ){
				$content .= $tmp_cont;
				break;
			}
			$i ++;
			$tmp = array();
			$tmp['label'] = $matched[4];
			$tmp['label'] = strip_tags( $tmp['label'] );//ラベルからHTMLタグを除去
			$tmp['anch'] = 'hash_'.urlencode($tmp['label']);
			if($indexCounter[$tmp['anch']]){
				$indexCounter[$tmp['anch']] ++;
				$tmp['anch'] = 'hash_'.$indexCounter[$tmp['anch']].'_'.urlencode($tmp['label']);
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

		$anchorlinks = '';
		$topheadlevel = 2;
		$headlevel = $topheadlevel;
		if( count( $index ) ){
			$anchorlinks .= '<!-- autoindex -->'."\n";
			$anchorlinks .= '<div>'."\n";
			$anchorlinks .= '<h2>目次</h2>';
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
			$anchorlinks .= '</div>'."\n";
			$anchorlinks .= '<!-- / autoindex -->'."\n";
		}

		$content = preg_replace( '/'.preg_quote($this->func_data_memos['autoindex']['metastr'],'/').'/si' , $anchorlinks , $content );
		return $content;
	}//apply_autoindex();

}
?>