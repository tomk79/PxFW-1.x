<?php
require_once( $this->get_conf('paths.px_dir').'_FW/bases/extension.php' );
class px_extensions_wiki extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		ob_start();
		$px = &$this->px;
		@include( $path_content );
		$rtn = ob_get_clean();
		return $rtn;
	}

}
?>