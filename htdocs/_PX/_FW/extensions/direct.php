<?php
/**
 * class px_extensions_direct
 * 
 * 無加工で出力するextensionクラス `px_extensions_direct` を定義します。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
$this->load_px_class('/bases/extension.php');

/**
 * 無加工で出力するextensionクラス
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_extensions_direct extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * 
	 * @param string $path_content コンテンツファイルのパス
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		ob_start();
		$px = $this->px;
		include( $path_content );
		$rtn = ob_get_clean();
		return $rtn;
	}

}

?>