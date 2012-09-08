<?php
require_once( $this->get_conf('paths.px_dir').'_FW/bases/extension.php' );
class px_extensions_js extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		$output_encoding = $this->px->get_conf('system.output_encoding');
		if(!strlen($output_encoding)){ $output_encoding = 'UTF-8'; }
		@header('Content-type: text/javascript; charset='.$output_encoding);

		$src = @file_get_contents( $path_content );
		if(strlen($this->px->get_conf('system.output_encoding'))){
			//出力ソースの文字コード変換
			$src = t::convert_encoding($src,$this->px->get_conf('system.output_encoding'),'utf-8');
		}
		print $src;
		return true;
	}

}
?>