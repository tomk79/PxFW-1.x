<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: publishを実行する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_publish extends px_bases_pxcommand{

	protected $pxcommand_name = 'publish';

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
		@header('Content-type: text/plain');
		print 'publish | Pickles Framework'."\n";
		print '------'."\n";
		print 'PX command "'.$this->pxcommand_name.'" executed.'."\n";
		print 'under construction.'."\n";
		exit;
	}
}
?>