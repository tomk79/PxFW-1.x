<?php
/**
 * class px_pxcommands_phpinfo
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: phpinfoを表示する
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_phpinfo extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 * 
	 * @param array $command PXコマンド名
	 * @param object $px $pxオブジェクト
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "phpinfo".
	 * 
	 * HTMLを標準出力した後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @return void
	 */
	private function execute(){
		phpinfo();
		exit;
	}
}
?>