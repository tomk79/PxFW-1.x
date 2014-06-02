<?php
/**
 * class px_extensions_js
 * 
 * 拡張子 *.js のextensionクラス `px_extensions_js` を定義します。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
$this->load_px_class('/bases/extension.php');

/**
 * 拡張子 *.js のextensionクラス
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_extensions_js extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * 
	 * @param string $path_content コンテンツファイルのパス
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		@header('Content-type: text/javascript; charset=UTF-8');//デフォルトのヘッダー

		ob_start();
		$px = $this->px;
		include( $path_content );
		$src = ob_get_clean();

		$src = preg_replace( '/^'.preg_quote(base64_decode('77u/'),'/').'/', '', $src );//	BOMを削除する

		$src = $this->px->theme()->output_filter($src, 'js');
		print $src;
		return true;
	}

}

?>