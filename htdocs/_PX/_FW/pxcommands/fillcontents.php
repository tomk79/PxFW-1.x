<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: fillcontentsを実行する
 **/
class px_pxcommands_fillcontents extends px_bases_pxcommand{

	protected $pxcommand_name = 'fillcontents';

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "fillcontents".
	 */
	private function execute(){

		$dir_perm = 0777;//←8進数指定
		$page_perm = 0666;//←8進数指定
		$CONTENT = 'empty contents';

		$sitemap = $this->px->site()->get_sitemap();
		$content_path = array();

		//sitemapからcontent_pathの配列を抽出
		foreach ($sitemap as $key => $val ) {
			foreach ($val as $key2 => $val2 ) {
				if( $key2 == 'content' ) {
					//document_rootを付与
					array_push( $content_path, $val2);
				}
			}
		}

		@header('Content-type: text/plain');
		print $this->pxcommand_name.' | Pickles Framework'."\n\n";
		print '* start fillcontents.'."\n\n";

		foreach($content_path as $val) {
			if( !strlen($val) ){continue;}

			//処理用 サーバードキュメントルールからのパス
			$content_realpath_parts = pathinfo($this->px->dbh()->get_realpath( $_SERVER['DOCUMENT_ROOT'] ) . $val);
			$dir_realpath = $this->px->dbh()->get_realpath( $content_realpath_parts['dirname'].'/' );
			$file_realpath = $this->px->dbh()->get_realpath( $content_realpath_parts['dirname'].'/'.$content_realpath_parts['basename'] );

			//結果表示用 HTMLドキュメントルールからのパス
			$content_path_parts = pathinfo($val);
			$dir_path = ($content_path_parts['dirname']=='/' ? $this->px->dbh()->get_realpath($content_path_parts['dirname']) : $this->px->dbh()->get_realpath($content_path_parts['dirname']).'/'); //ルートパスを除外
			$file_path = ($content_path_parts['dirname']=='/' ? $this->px->dbh()->get_realpath($content_path_parts['dirname']) : $this->px->dbh()->get_realpath($content_path_parts['dirname']).'/').$content_path_parts['basename'];

			print '------'."\n";

			//ディレクトリ生成
			if(!$this->px->dbh()->is_dir($dir_realpath)) {
				$success_dir = $this->px->dbh()->mkdir_all( $dir_realpath , $dir_perm );

				if($success_dir) {
					print 'success make Directory : ' . $dir_path . "\n";
				} else {
					print 'ERROR make Directory FAILED: ' . $dir_path . "\n";
				}
			} else {
				print 'exists Directory: ' . $dir_path . "\n";
			}

			//ファイル生成
			if(!$this->px->dbh()->is_file($file_realpath)) {
				$success_file = $this->px->dbh()->save_file( $file_realpath , $CONTENT , $page_perm );
				if($success_file) {
					print 'success make File: ' . $file_path . "\n";
				} else {
					print 'ERROR make File FAILED: ' . $file_path . "\n";
				}
			} else {
				print 'exists File: ' . $file_path . "\n";
			}

		}

		print '------'."\n\n";
		print 'fillcontents completed.'."\n\n";
		print date('Y-m-d H:i:s')."\n\n";
		print 'exit.'."\n";
		exit;
	}

}

?>