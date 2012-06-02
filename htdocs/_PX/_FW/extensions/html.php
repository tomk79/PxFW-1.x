<?php
require_once( $this->get_conf('paths.px_dir').'_FW/bases/extension.php' );
class px_extensions_html extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		@header('Content-type: text/html; charset=UTF-8');

		$src = $this->execute_content($path_content);
		print $this->px->theme()->bind_contents( $src );
		return true;
	}

	/**
	 * コンテンツを実行し、出力ソースを返す
	 */
	private function execute_content( $path_content ){
		$path_px_dir = $this->px->get_conf('paths.px_dir');

		$px = &$this->px;
		ob_start();
		@include( $path_content );
		$src = ob_get_clean();

		return $src;
	}


}
?>