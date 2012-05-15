<?php
$this->load_pxclass('/bases/pxcommand.php');  //ahomemo:ファイルを呼び出されると呼び出されたファイルの呼び出された箇所に処理が入る。ので、thisはそこのthis

/**
 * PX Command: configを表示する
 **/
class px_pxcommands_config extends px_bases_pxcommand{

	protected $pxcommand_name = 'config';

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "config".
	 */
	private function execute(){
		//ob_start(); //ahomemo: ob_startからob_get_cleanの間の情報を標準出力ではなく変数に渡す
		//test::var_dump( $this->px->get_conf_all() ); //ahomemo: "::"はクラスの中のメソッドをインスタンス化せず直接呼び出す。
		//$src = ob_get_clean();
		
		$config = $this->px->get_conf_all();
		
		//sample source
		$food = array(
			"vegetable"
			, // カンマ , で区切る
			"fruit" => array(
				"apple" => "りんご",
				"orange" => "オレンジ",
				"grape" => "ぶどう"
			)
		);

		//sample source2
		$food2 = array(
			"vegetable"
			, // カンマ , で区切る
			"fruit"
		);

		print $this->html_template($this->print_ary_table($config));
		
		exit;
	}

}
?>