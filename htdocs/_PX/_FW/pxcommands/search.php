<?php
/**
 * class px_pxcommands_search
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: ソースを検索する。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_search extends px_bases_pxcommand{

	/**
	 * 検索キーワード
	 */
	private $search_keyword = null;

	/**
	 * コンストラクタ
	 * 
	 * @param array $command PXコマンド名
	 * @param object $px $pxオブジェクト
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );

		$command = $this->get_command();
		$this->search_keyword = $this->px->req()->get_param('KEY');

		switch( @$command[1] ){
			case 'api':
				$results = $this->execute_search( $this->search_keyword );
				$this->print_search_results_as_json( $results );
				break;
			default:
				$this->homepage();
				break;
		}
	}//__construct()

	/**
	 * ホームページを表示する。
	 * 
	 * HTMLを標準出力した後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @return void
	 */
	private function homepage(){
		$command = $this->get_command();
		$src = '';
		ob_start();
?><script type="text/javascript">
$(function(){
	contSearch.init();
	contSearch.search();
});
var contSearch = new (function(){
	var elmKeyword = null;
	function init(){
		elmKeyword = $('#cont_search_form input[name=KEY]');
	}
	this.init = init;

	function getSearchKeyword(){
		var keyword = elmKeyword[0].value;
		return keyword;
	}
	function search(){
		var keyword = getSearchKeyword();
		var elm = $('#cont_result');

		elm.html('<p>検索しています。</p>');
		$.ajax({
			url:'?' ,
			data: {
				PX: 'search.api' ,
				KEY: keyword
			} ,
			dataType:'json' ,
			success: function( data ){
				var htmlSrc = '';
				function drawResultSell( ary ){
					var src = '';
					if(!ary.length){return '';}
					ary.sort(function(a,b){
						if(a.path > b.path){return 1;}
						if(a.path < b.path){return -1;}
						return 0;
					});
					src += '<div class="unit"><table class="def" style="width:100%;">';
					src += '<thead>';
					src += '<tr>';
					src += '<th style="width:80%">path</th>';
					src += '<th style="width:20%">type</th>';
					src += '</tr>';
					src += '</thead>';
					for( row in ary ){
						src += '<tr>';
						src += '<td style="word-break:break-all;">'+ary[row].path+'</td>';
						src += '<td>'+ary[row].type+'</td>';
						src += '</tr>';
					}
					src += '</table></div>';
					return src;
				}
				htmlSrc += '<p>検索結果: '+data['count']['total']+'件</p>';
				htmlSrc += '<h2>コンテンツの検索結果</h2>';
				htmlSrc += '<p>'+data['count']['contents']+'件</p>';
				htmlSrc += drawResultSell( data['results']['contents'] );
				htmlSrc += '<h2>サイトマップの検索結果</h2>';
				htmlSrc += '<p>'+data['count']['sitemap']+'件</p>';
				htmlSrc += drawResultSell( data['results']['sitemap'] );
				htmlSrc += '<h2>テーマの検索結果</h2>';
				htmlSrc += '<p>'+data['count']['themes']+'件</p>';
				htmlSrc += drawResultSell( data['results']['themes'] );
				elm.html(htmlSrc);
			} ,
			error: function(){
				elm.html('<p>AJAX エラーが発生しました。</p>');
			} ,
			complete: function(){
			}
		});
	}
	this.search = search;
})();
</script>
<?php
		$src .= ob_get_clean();
		$src .= '<p>コンテンツ、サイトマップ、テーマを検索します。</p>'."\n";
		$src .= '<form action="?" method="get" target="_top" onsubmit="contSearch.search(); return false;" id="cont_search_form">'."\n";
		$src .= '<div><input type="hidden" name="PX" value="search" /></div>'."\n";
		$src .= '<p class="center"><input type="text" name="KEY" value="'.t::h($this->px->req()->get_param('KEY')).'" /><button>検索する</button></p>'."\n";
		$src .= '</form>'."\n";
		$src .= '<div id="cont_result">'."\n";
		$src .= '</div>'."\n";
		print $this->html_template($src);
		exit;
	}//homepage()

	/**
	 * 検索結果をJSON形式で出力する。
	 * 
	 * JSONを標準出力した後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @param array $results 検索結果を格納する連想配列
	 * @return void
	 */
	private function print_search_results_as_json( $results ){
		while( @ob_end_clean() ){}
		@header( 'Content-type: text/plain; charset=UTF-8' );
		print '{ ';
		print '"KEY":'.t::data2jssrc($this->search_keyword).', ';
		print '"count":{';
		print '"total":'.t::data2jssrc(count($results['contents'])+count($results['sitemap'])+count($results['themes'])).',';
		print '"contents":'.t::data2jssrc(count($results['contents'])).',';
		print '"sitemap":'.t::data2jssrc(count($results['sitemap'])).',';
		print '"themes":'.t::data2jssrc(count($results['themes'])).'';
		print '}, ';
		print '"results":'.t::data2jssrc($results).' ';
		print '}';
		exit;
	}//print_json()

	/**
	 * 検索を実行する。
	 * 
	 * このメソッドは検索処理の窓口となります。
	 * 直接検索処理を実行せず、引数 `$keyword` を
	 * `$this->execute_search_contents()`、
	 * `$this->execute_search_sitemap()`、
	 * `$this->execute_search_themes()` へ引き継ぎ、
	 * 得られた検索結果をまとめて返します。
	 * 
	 * @param string $keyword キーワード
	 * @return array 検索結果を格納した連想配列
	 */
	private function execute_search( $keyword ){
		$results = array(
			'contents'=>array() ,
			'sitemap'=>array() ,
			'themes'=>array() ,
		);
		if( !strlen(trim($keyword)) ){
			return $results;
		}
		$results['contents'] = $this->execute_search_contents( $keyword );
		$results['sitemap'] = $this->execute_search_sitemap( $keyword );
		$results['themes'] = $this->execute_search_themes( $keyword );
		return $results;
	}
	/**
	 * コンテンツを検索する。
	 * 
	 * @param string $keyword キーワード
	 * @param string $path コンテンツディレクトリを起点とした検索対象パス
	 * @return array 検索結果を格納した配列
	 */
	private function execute_search_contents( $keyword , $path = null ){
		$results = array();
		$ignores = array(
			$this->px->dbh()->get_realpath( $this->px->get_conf('paths.px_dir') ).'/'
		);
		$base_path = $this->px->dbh()->get_realpath('./').'/';
		$items = $this->px->dbh()->ls( $base_path.$path );
		foreach( $items as $item ){
			foreach( $ignores as $ignore ){
				//  除外指定のパスを除外
				if( $ignore == $base_path.$path.$item.'/' ){
					continue 2;
				}
			}
			if( is_dir( $base_path.$path.$item ) ){
				if( preg_match( '/'.preg_quote($keyword,'/').'/si' , $item ) ){
					array_push( $results , array(
						'path'=>'/'.$path.$item ,
						'type'=>'dir' ,
					) );
				}
				$results = array_merge( $results , $this->execute_search_contents( $keyword , $path.$item.'/' ) );
			}elseif( is_file( $base_path.$path.$item ) ){
				$file_bin = $this->px->dbh()->file_get_contents( $base_path.$path.'/'.$item );
				if( preg_match( '/'.preg_quote($keyword,'/').'/si' , $file_bin ) ){
					array_push( $results , array(
						'path'=>'/'.$path.$item ,
						'type'=>'file' ,
					) );
				}
			}
		}
		return $results;
	}
	/**
	 * サイトマップを検索する。
	 * 
	 * @param string $keyword キーワード
	 * @param string $path サイトマップディレクトリを起点とした検索対象パス
	 * @return array 検索結果を格納した配列
	 */
	private function execute_search_sitemap( $keyword , $path = null ){
		$results = array();
		$ignores = array(
		);
		$base_path = $this->px->dbh()->get_realpath( $this->px->get_conf('paths.px_dir') ).'/sitemaps/';
		$items = $this->px->dbh()->ls( $base_path.$path );
		foreach( $items as $item ){
			foreach( $ignores as $ignore ){
				//  除外指定のパスを除外
				if( $ignore == $base_path.$path.$item.'/' ){
					continue 2;
				}
			}
			if( is_dir( $base_path.$path.$item ) ){
				if( preg_match( '/'.preg_quote($keyword,'/').'/si' , $item ) ){
					array_push( $results , array(
						'path'=>'/'.$path.$item ,
						'type'=>'dir' ,
					) );
				}
				$results = array_merge( $results , $this->execute_search_sitemap( $keyword , $path.$item.'/' ) );
			}elseif( is_file( $base_path.$path.$item ) ){
				$file_bin = $this->px->dbh()->file_get_contents( $base_path.$path.'/'.$item );
				if( preg_match( '/'.preg_quote($keyword,'/').'/si' , $file_bin ) ){
					array_push( $results , array(
						'path'=>'/'.$path.$item ,
						'type'=>'file' ,
					) );
				}
			}
		}
		return $results;
	}
	/**
	 * テーマを検索する。
	 * 
	 * @param string $keyword キーワード
	 * @param string $path テーマコレクションディレクトリを起点とした検索対象パス
	 * @return array 検索結果を格納した配列
	 */
	private function execute_search_themes( $keyword , $path = null ){
		$results = array();
		$ignores = array(
		);
		$base_path = $this->px->dbh()->get_realpath( $this->px->get_conf('paths.px_dir') ).'/themes/';
		$items = $this->px->dbh()->ls( $base_path.$path );
		foreach( $items as $item ){
			foreach( $ignores as $ignore ){
				//  除外指定のパスを除外
				if( $ignore == $base_path.$path.$item.'/' ){
					continue 2;
				}
			}
			if( is_dir( $base_path.$path.$item ) ){
				if( preg_match( '/'.preg_quote($keyword,'/').'/si' , $item ) ){
					array_push( $results , array(
						'path'=>'/'.$path.$item ,
						'type'=>'dir' ,
					) );
				}
				$results = array_merge( $results , $this->execute_search_themes( $keyword , $path.$item.'/' ) );
			}elseif( is_file( $base_path.$path.$item ) ){
				$file_bin = $this->px->dbh()->file_get_contents( $base_path.$path.'/'.$item );
				if( preg_match( '/'.preg_quote($keyword,'/').'/si' , $file_bin ) ){
					array_push( $results , array(
						'path'=>'/'.$path.$item ,
						'type'=>'file' ,
					) );
				}
			}
		}
		return $results;
	}

}

?>