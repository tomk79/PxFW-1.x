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
	private $paths_ignore = array();

	private $queue_items = array();//←パブリッシュ対象の一覧
	private $done_items = array();//←パブリッシュ完了した対象の一覧

	public function __construct( &$px ){
		parent::__construct( &$px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "publish".
	 * @access private
	 * @return null
	 */
	private function execute(){
		$this->setup();
		@header('Content-type: text/plain');
		print ''.$this->pxcommand_name.' | Pickles Framework'."\n";
		print '------'."\n";
		print 'PX command "'.$this->pxcommand_name.'" executed.'."\n";
		print '------'."\n";
		print 'path_docroot_dir => '.$this->path_docroot_dir."\n";
		print 'path_publish_dir => '.$this->path_publish_dir."\n";
		print 'paths_ignore => '."\n";
		var_dump($this->paths_ignore);
		print '------'."\n";
		if(!is_dir($this->path_docroot_dir)){
			print 'path_docroot_dir is NOT exists.'."\n";
			print 'exit.'."\n";
			exit;
		}
		if(!is_dir($this->path_publish_dir)){
			print 'path_publish_dir is NOT exists.'."\n";
			print 'exit.'."\n";
			exit;
		}

		flush();
		print '------'."\n";
		print '* cleaning publish dir.'."\n";
		flush();
		$this->clear_publish_dir();
		print 'done.'."\n";
		print ''."\n";
		flush();

		print '------'."\n";
		print '* start scaning directory.'."\n";
		set_time_limit(60*60);
		$this->scan_dirs( '/' );
		set_time_limit(300);
		flush();
		print '------'."\n";
		print '* add pages by sitemap.'."\n";
		foreach( $this->px->site()->get_sitemap() as $page_info ){
			$this->add_queue( $this->px->dbh()->get_realpath($this->px->get_install_path().$page_info['path']) );
		}
		flush();
		print '------'."\n";
		print '* start publishing.'."\n";
		while( 1 ){
			if( !count($this->queue_items) ){
				break;
			}
			$path = array_pop( $this->queue_items );
			print $path.''."\n";
			set_time_limit(60*60);
			$this->publish_file( $path );
			set_time_limit(30);
			flush();
		}
		print 'done.'."\n";
		print ''."\n";

		print '------'."\n";
		print 'publish completed.'."\n";
		print 'exit.'."\n";
		exit;
	}

	private function clear_publish_dir(){
		$files = $this->px->dbh()->ls( $this->path_publish_dir );
		foreach( $files as $filename ){
			print ''.'/'.$filename."\n";
			$this->px->dbh()->rmdir_all( $this->path_publish_dir.'/'.$filename );
		}
		return true;
	}

	private function add_queue( $path ){
		if( !preg_match( '/^\//' , $path ) ){
			return false;
		}
		if( $this->done_items[$path] ){ return true; }
		array_push( $this->queue_items , $path );
		$this->done_items[$path] = true;
		print '[add_queue] '.$path."\n";
		return true;
	}

	private function scan_dirs( $path ){
		$realpath_target_dir  = t::realpath( $this->path_docroot_dir.'/'.$path );
		$realpath_publish_dir = t::realpath( $this->path_publish_dir.$this->px->get_install_path().'/'.$path );

		$items = $this->px->dbh()->ls( $realpath_target_dir );
		foreach( $items as $filename ){
			$current_original_path = $realpath_target_dir.'/'.$filename;
			$filename = preg_replace( '/\.html(?:\.[a-zA-Z0-9]+)?$/si' , '.html' , $filename );
			$current_path = $realpath_target_dir.'/'.$filename;
			$current_publishto = $realpath_publish_dir.'/'.$filename;

			if($this->is_ignore_path($current_original_path)){
				continue;
			}
			$extension = $this->px->dbh()->get_extension( $current_path );

			if( is_dir( $current_path ) ){
				//  対象がディレクトリだったら
				$this->px->dbh()->mkdir( $current_publishto );
				$this->scan_dirs( $path.'/'.$filename );
			}elseif( is_file( $current_original_path ) ){
				//  対象がファイルだったら
				$this->add_queue( $this->px->dbh()->get_realpath($this->px->get_install_path().$path.'/'.$filename) );
			}

		}

		return true;
	}//scan_dirs();

	private function publish_file( $path ){
		if( !preg_match( '/^\//' , $path ) ){
			return false;
		}
		$extension = $this->px->dbh()->get_extension( $path );
		switch( strtolower($extension) ){
			case 'html':
				$url = 'http'.($this->px->req()->is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$this->px->dbh()->get_realpath($this->px->get_install_path().$path.'/'.$filename);

				$httpaccess = $this->factory_httpaccess();
				$httpaccess->clear_request_header();//初期化
				$httpaccess->set_url( $url );//ダウンロードするURL
				$httpaccess->set_method( 'GET' );//メソッド
				$httpaccess->set_user_agent( 'PicklesCrawler' );//HTTP_USER_AGENT
				$httpaccess->save_http_contents( $this->path_publish_dir.$path );//ダウンロードを実行する

				$relatedlink = $httpaccess->get_response('X-PXFW-RELATEDLINK');
				foreach( explode(',',$relatedlink) as $row ){
					$this->add_queue($row);
				}

				break;
			default:
				$this->px->dbh()->copy( $this->path_docroot_dir.$path , $this->path_publish_dir.$path );
				break;
		}
		return true;
	}

	private function factory_httpaccess(){
		@require_once( $this->px->get_conf('paths.px_dir').'libs/PxHTTPAccess/PxHTTPAccess.php' );
		return new PxHTTPAccess();
	}

	private function is_ignore_path( $path ){
		$path = t::realpath( $path );
		if( !file_exists($path) ){ return true; }
		foreach( $this->paths_ignore as $row ){
			if( t::realpath($row) == $path ){
				return true;
			}
		}
		return false;
	}//is_ignore_path();

	/**
	 * セットアップ
	 * @return true
	 */
	private function setup(){
		$this->path_docroot_dir = t::realpath('.');
		$this->path_publish_dir = t::realpath($this->px->get_conf('publish.path_publish_dir'));
		array_push( $this->paths_ignore , t::realpath($this->px->get_conf('paths.px_dir')) );
		array_push( $this->paths_ignore , t::realpath($this->path_docroot_dir.'/.htaccess') );
		array_push( $this->paths_ignore , t::realpath($this->path_docroot_dir.'/_px_execute.php') );

		return true;
	}
}
?>