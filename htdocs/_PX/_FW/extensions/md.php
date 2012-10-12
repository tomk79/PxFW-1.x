<?php
require_once( $this->get_conf('paths.px_dir').'_FW/bases/extension.php' );

/**
 * 拡張子 *.md のextensionクラス
 */
class px_extensions_md extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		$src = @file_get_contents( $path_content );

		//  PHP Markdownライブラリをロード
		//  see: http://michelf.ca/projects/php-markdown/
		@require_once( $this->px->get_conf('paths.px_dir').'libs/PHPMarkdown/markdown.php' );

		$src = Markdown($src);
		print $this->px->theme()->bind_contents( $src );
		return true;
	}

}

?>