<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: searchを表示する
 **/
class px_pxcommands_search extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 */
	public function __construct( $command , &$px ){
		parent::__construct( $command , &$px );

		$command = $this->get_command();

		switch( $command[1] ){
			case 'json':
				$this->print_search_results_as_json( $px->req()->get_param('KEY') );
				break;
			default:
				$this->homepage();
				break;
		}
	}//__construct()

	/**
	 * ホームページを表示する。
	 */
	private function homepage(){
		$command = $this->get_command();
		$src = '';
		ob_start();
?><script type="text/javascript">
$(function(){
	alert('開発中です。');
});
</script>
<?php
		$src .= ob_get_clean();
		$src .= '<p>コンテンツ、サイトマップ、テーマを検索します。</p>'."\n";
		$src .= '<p><strong>これは開発中の機能です。現在動作していません。</strong></p>'."\n";
		$src .= '<form action="?" method="get" target="_top">'."\n";
		$src .= '<div><input type="hidden" name="PX" value="search" /></div>'."\n";
		$src .= '<p class="center"><input type="text" name="KEY" value="'.t::h($this->px->req()->get_param('KEY')).'" /><button>検索する</button></p>'."\n";
		$src .= '</form>'."\n";
		print $this->html_template($src);
		exit;
	}//homepage()

	/**
	 * 検索結果をJSON形式で出力する。
	 */
	private function print_search_results_as_json( $keyword ){
		//  UTODO: 開発中です。
		print '{}';
	}//print_json()

}
?>