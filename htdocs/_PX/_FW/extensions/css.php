<?php
require_once( $this->get_conf('paths.px_dir').'_FW/bases/extension.php' );
class px_extensions_css extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		$output_encoding = $this->px->get_conf('system.output_encoding');
		if(!strlen($output_encoding)){ $output_encoding = 'UTF-8'; }
		@header('Content-type: text/css; charset='.$output_encoding);

		$src = @file_get_contents( $path_content );
		if(strlen($this->px->get_conf('system.output_encoding'))){
			//出力ソースの文字コード変換
			$src = preg_replace('/\@charset\s+"[a-zA-Z0-9\_\-\.]+"\;/si','@charset "'.t::h($output_encoding).'";',$src);
			$src = t::convert_encoding($src,$this->px->get_conf('system.output_encoding'),'utf-8');
		}
		print $src;
		return true;
	}

}
?>