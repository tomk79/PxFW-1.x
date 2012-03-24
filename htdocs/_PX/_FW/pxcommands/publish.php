<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: publishを実行する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_publish extends px_bases_pxcommand{

	protected $pxcommand_name = 'publish';

	private $path_docroot_dir;
	private $path_publish_dir;
	private $paths_ignore_dir = array();

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "publish".
	 * @access public
	 * @return null
	 */
	private function execute(){
		$this->setup();
		@header('Content-type: text/plain');
		print 'publish | Pickles Framework'."\n";
		print '------'."\n";
		print 'PX command "'.$this->pxcommand_name.'" executed.'."\n";
		print '------'."\n";
		print 'path_docroot_dir => '.$this->path_docroot_dir."\n";
		print 'path_publish_dir => '.$this->path_publish_dir."\n";
		print 'paths_ignore_dir => '."\n";
		var_dump($this->paths_ignore_dir);
		print '------'."\n";
		if(!is_dir($this->path_docroot_dir)){
			print 'path_docroot_dir is NOT exists.'."\n";
			exit;
		}
		if(!is_dir($this->path_publish_dir)){
			print 'path_publish_dir is NOT exists.'."\n";
			exit;
		}
		print 'under construction.'."\n";
		exit;
	}
	/**
	 * セットアップ
	 * @return true
	 */
	private function setup(){
		$this->path_docroot_dir = realpath('.');
		$this->path_publish_dir = realpath($this->px->get_conf('paths.publish_dir'));
		array_push( $this->paths_ignore_dir , realpath($this->px->get_conf('paths.px_dir')) );
		return true;
	}
}
?>