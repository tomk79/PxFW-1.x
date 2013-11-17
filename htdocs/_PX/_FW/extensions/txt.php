<?php
$this->load_px_class('/bases/extension.php');

/**
 * 拡張子 *.txt のextensionクラス
 */
class px_extensions_txt extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		@header('Content-type: text/html; charset=UTF-8');//デフォルトのヘッダー

		$src = @file_get_contents( $path_content );
		$src = preg_replace( '/^'.preg_quote(base64_decode('77u/'),'/').'/', '', $src );//	BOMを削除する
		$src = htmlspecialchars($src);
		$src = preg_replace('/\r\n|\r|\n/','<br />'."\r\n",$src);
		$src = $this->px->theme()->bind_contents( $src );
		$src = $this->px->theme()->output_filter($src, 'html');
		print $src;
		return true;
	}

}

?>