<?php
require_once( $this->get_conf('paths.px_dir').'_FW/bases/extension.php' );
class px_extensions_txt extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		@header('Content-type: text/html; charset=UTF-8');//デフォルトのヘッダー

		$src = @file_get_contents( $path_content );
		$src = htmlspecialchars($src);
		$src = preg_replace('/\r\n|\r|\n/','<br />'."\r\n",$src);
		$src = $this->px->theme()->bind_contents( $src );
		$src = $this->px->theme()->output_filter($src, 'html');
		print $src;
		return true;
	}

}
?>