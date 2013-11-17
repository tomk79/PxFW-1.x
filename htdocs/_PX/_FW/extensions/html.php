<?php
$this->load_px_class('/bases/extension.php');

/**
 * 拡張子 *.html のextensionクラス
 */
class px_extensions_html extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		@header('Content-type: text/html; charset=UTF-8');//デフォルトのヘッダー

		ob_start();
		$px = $this->px;
		@include( $path_content );
		$src = ob_get_clean();

		$src = preg_replace( '/^'.preg_quote(base64_decode('77u/'),'/').'/', '', $src );//	BOMを削除する

		//	BOMを削除する
		$src = $this->px->theme()->bind_contents( $src );
		$src = $this->px->theme()->output_filter($src, 'html');
		print $src;
		return true;
	}

}

?>