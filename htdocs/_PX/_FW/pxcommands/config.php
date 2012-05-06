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
		
		$src = $this->px->get_conf_all();
		
		// 配列の次元数を調べる
		function depth($ary, $cnt = 0){
			if(!is_array($ary)) return $cnt; else $cnt++;
			$max = $cnt; $i = 0;
			foreach($ary as $v){
				if(is_array($v)){
					$i = depth($v, $cnt);
					if($max < $i) $max = $i;
				}
			}
			return $max;
		}
		
		ob_start(); ?>
		<style type="text/css">
		table.def {
			border:none;
			border-collapse: collapse;
			text-align: left;
			width: 800px;
		}
		table.def th,
		table.def td {
			border: 1px solid #D6D6D6;
			padding: 10px;
		}
		table.def th {
			background: #E7E7E7;
		}
		</style>
<?php
		$html = ob_get_clean();

		$html .= '<table class="def">' . "\n";
		foreach ($src as $key => $val) {
			$html .= '<tr>' . "\n";
			$html .= '<th>' .$key. '</th>' . "\n";
			$html .= '<td>' .$val. '</td>' . "\n";
			$html .= '</tr>' . "\n";
		}
		$html .= '</table>' . "\n";
		print $this->html_template( $html );
				
		function print_td($val, $cnt = 0, $html = '') {
			if(!is_array($val)) {
				$html .= '<td>' .$val. '</td>' . "\n";
				return $html;
			} else {
				$cnt = count($val);
				foreach ($val as $key => $val_next) {
					$html .= '<td>' .$key. '</td>' . "\n";
					print_td($val_next, $cnt, $html);
				}
			}
		}

		function is_hash($array) {
			$i = 0;
			foreach($array as $k => $dummy) {
				if ( $k !== $i++ ) return true;
			}
			return false;
		}

		$food = array(
		  "vegetable"
		  , // カンマ , で区切る
		  "fruit" => array(
			  "apple" => "りんご",
			  "orange" => "オレンジ",
			  "grape" => "ぶどう"
		  )
		);

		$food2 = array(
		  "vegetable"
		  , // カンマ , で区切る
		  "fruit"
		);

		$keys = array_keys($food);
		$depth = depth($food);
		$hash = is_hash($food);
		$hash2 = is_hash($food2);

		foreach($keys as $val) {
			echo $val . "\n";
		}

		echo '<br />';
		echo $depth . '<br />';
		echo $hash . '<br />';
		echo $hash2 . '<br />';
		
		exit;
	}

}
?>