<?php
/**
 * class px_extensions_txt
 * 
 * 拡張子 *.txt のextensionクラス `px_extensions_txt` を定義します。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
$this->load_px_class('/bases/extension.php');

/**
 * 拡張子 *.txt のextensionクラス
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_extensions_txt extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * 
	 * @param string $path_content コンテンツファイルのパス
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