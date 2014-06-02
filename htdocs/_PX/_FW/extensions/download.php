<?php
/**
 * class px_extensions_download
 * 
 * ダウンロードコンテンツのextensionクラス `px_extensions_download` を定義します。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
$this->load_px_class('/bases/extension.php');

/**
 * ダウンロードコンテンツのextensionクラス
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_extensions_download extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * 
	 * @param string $path_content コンテンツファイルのパス
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		@header('Content-type: application/x-download');//デフォルトのヘッダー
		ob_start();
		$px = $this->px;
		include( $path_content );
		$rtn = ob_get_clean();
		print $rtn;
		return true;
	}

}

?>