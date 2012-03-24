<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: clearcacheを実行する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_clearcache extends px_bases_pxcommand{

	protected $pxcommand_name = 'clearcache';

	private $paths_cache_dir = array();

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "clearcache".
	 * @access public
	 * @return null
	 */
	private function execute(){
		$this->setup();
		@header('Content-type: text/plain');
		print ''.$this->pxcommand_name.' | Pickles Framework'."\n";
		print '------'."\n";
		print 'PX command "'.$this->pxcommand_name.'" executed.'."\n";
		print '------'."\n";
		print 'paths_cache_dir => '."\n";
		var_dump($this->paths_cache_dir);
		print '------'."\n";
		foreach( $this->paths_cache_dir as $path_cache_dir ){
			if( !is_dir( $path_cache_dir ) ){
				print '[ERROR] Directory "'.$path_cache_dir.'" is NOT exists.'."\n";
				continue;
			}
			$items = $this->px->dbh->ls( $path_cache_dir );
			foreach( $items as $filename ){
				$this->px->dbh->rmdir_all( $path_cache_dir.'/'.$filename );
			}
			$this->px->dbh->save_file( $path_cache_dir.'/readme.txt' , 'This directory is for saving cache files.' );
			print '[Complete] Directory "'.$path_cache_dir.'"'."\n";
		}
		print '------'."\n";
		print 'publish completed.'."\n";
		print 'exit.'."\n";
		exit;
	}

	/**
	 * セットアップ
	 * @return true
	 */
	private function setup(){
		array_push( $this->paths_cache_dir , t::realpath($this->px->get_conf('paths.px_dir').'_sys/caches/') );
		array_push( $this->paths_cache_dir , t::realpath($this->px->get_conf('paths.publish_dir')) );
		array_push( $this->paths_cache_dir , t::realpath('./_caches/') );

		return true;
	}
}
?>