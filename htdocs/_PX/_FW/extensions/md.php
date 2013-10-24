<?php
$this->load_px_class('/bases/extension.php');

/**
 * 拡張子 *.md のextensionクラス
 */
class px_extensions_md extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		@header('Content-type: text/html; charset=UTF-8');//デフォルトのヘッダー

		$path_cache = $this->px->get_conf('paths.px_dir').'_sys/caches/contents/'.urlencode($path_content);
		if(!is_dir(dirname($path_cache))){
			$this->px->dbh()->mkdir( dirname($path_cache) );
		}

		if( !is_file( $path_cache ) || $this->px->dbh()->is_newer_a_than_b( $path_content, $path_cache ) ){
			// キャッシュがない、またはオリジナルコンテンツよりも古い場合
			$src = @file_get_contents( $path_content );

			//  PHP Markdownライブラリをロード
			//  see: http://michelf.ca/projects/php-markdown/
			@require_once( $this->px->get_conf('paths.px_dir').'libs/PHPMarkdown/markdown.php' );

			$src = Markdown($src);
			$this->px->dbh()->file_overwrite($path_cache, $src);
		}

		$src = '';
		ob_start();
		$px = $this->px;
		include( $path_cache );
		$src = ob_get_clean();

		$src = $this->px->theme()->bind_contents( $src );
		$src = $this->px->theme()->output_filter($src, 'html');
		print $src;
		return true;
	}

}

?>