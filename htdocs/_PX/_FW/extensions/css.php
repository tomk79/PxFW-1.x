<?php
$this->load_px_class('/bases/extension.php');

/**
 * 拡張子 *.css のextensionクラス
 */
class px_extensions_css extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		@header('Content-type: text/css; charset=UTF-8');//デフォルトのヘッダー

		ob_start();
		@include( $path_content );
		$src = ob_get_clean();
		$src = $this->px->theme()->output_filter($src, 'css');
		print $src;
		return true;
	}

}

?>