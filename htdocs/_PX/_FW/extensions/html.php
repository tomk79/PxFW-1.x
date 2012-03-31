<?php
require_once( $this->get_conf('paths.px_dir').'_FW/bases/extension.php' );
class px_extensions_html extends px_bases_extension{

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		/*ここをオーバーライドしてください。*/
		$src = $this->execute_content($path_content);
		print $this->px->theme()->bind_contents( $src );
		return true;
	}

	/**
	 * コンテンツを実行し、出力ソースを返す
	 */
	private function execute_content( $path_content ){
		$smarty = $this->px->factory_smarty();
		$smarty->caching = false;
		$smarty->config_dir   = $this->px->dbh()->trim_extension($path_content).'.files/';
		$smarty->template_dir = $this->px->dbh()->trim_extension($path_content).'.files/';
		$smarty->assign("px",$this->px);
		$smarty->assign("page_info",$this->px->site()->get_current_page_info());

		ob_start();
		$smarty->display($path_content);
		$src = ob_get_clean();

		return $src;
	}


}
?>