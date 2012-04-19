<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: publishを実行する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_publish extends px_bases_pxcommand{

	protected $pxcommand_name = 'publish';

	private $path_docroot_dir;
	private $path_publish_dir;	//パブリッシュ先ディレクトリ
	private $path_tmppublish_dir;//一次書き出しディレクトリ(固定)
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
		print date('Y-m-d H:i:s')."\n";
		print '------'."\n";
		print 'path_docroot_dir => '.$this->path_docroot_dir."\n";
		print 'path_tmppublish_dir => '.$this->path_tmppublish_dir."\n";
		print 'path_publish_dir => '.$this->path_publish_dir."\n";
		print 'paths_ignore => '."\n";
		var_dump($this->paths_ignore);
		print '------'."\n";
		if(!is_dir($this->path_docroot_dir)){
			print 'path_docroot_dir is NOT exists.'."\n";
			print 'exit.'."\n";
			exit;
		}
		if(!is_dir($this->path_tmppublish_dir)){
			print 'path_tmppublish_dir is NOT exists.'."\n";
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
		print date('Y-m-d H:i:s')."\n";
		print 'exit.'."\n";
		exit;
	}

	/**
	 * セットアップ
	 * @return true
	 */
	private function setup(){
		$this->path_docroot_dir = t::realpath('.').'/';
		$this->path_publish_dir = t::realpath($this->px->get_conf('publish.path_publish_dir')).'/';
		$this->path_tmppublish_dir = t::realpath($this->px->get_conf('paths.px_dir').'_sys/publish/').'/';

		array_push( $this->paths_ignore , t::realpath($this->px->get_conf('paths.px_dir')) );
		array_push( $this->paths_ignore , t::realpath($this->path_docroot_dir.'/.htaccess') );
		array_push( $this->paths_ignore , t::realpath($this->path_docroot_dir.'/_px_execute.php') );

		return true;
	}

	/**
	 * パブリッシュディレクトリを空っぽにする
	 * @return true
	 */
	private function clear_publish_dir(){
		$files = $this->px->dbh()->ls( $this->path_tmppublish_dir );
		foreach( $files as $filename ){
			print ''.'/'.$filename."\n";
			$this->px->dbh()->rmdir_all( $this->path_tmppublish_dir.'/'.$filename );
		}
		return true;
	}

	/**
	 * パブリッシュキューを追加する
	 * @return true
	 */
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

	/**
	 * パブリッシュログを出力する
	 * @param $ary_logtexts
	 * @return true|false
	 */
	private function publish_log( $ary_logtexts ){
		$logtext = '';
		$logtext .= date('Y-m-d H:i:s').'	';
		$logtext .= ($ary_logtexts['result']?'success':'FAILED').'	';
		$logtext .= $ary_logtexts['publish_type'].'	';
		$logtext .= $ary_logtexts['path'].'	';
		return @error_log( $logtext."\r\n", 3, $this->path_tmppublish_dir.'publish_log.txt' );
	}

	/**
	 * パブリッシュエラーログを出力する
	 * @param $ary_logtexts
	 * @return true|false
	 */
	private function publish_error_log( $ary_logtexts ){
		$logtext = '';
		$logtext .= date('Y-m-d H:i:s').'	';
		$logtext .= $ary_logtexts['error'].'	';
		$logtext .= $ary_logtexts['path'];
		return @error_log( $logtext."\r\n", 3, $this->path_tmppublish_dir.'publish_error_log.txt' );
	}

	/**
	 * ディレクトリを再帰的にスキャンする
	 * @param $path
	 * @return true
	 */
	private function scan_dirs( $path ){
		$realpath_target_dir  = $this->px->dbh()->get_realpath( $this->path_docroot_dir.'/'.$path );
		$realpath_tmppublish_dir = $this->px->dbh()->get_realpath( $this->path_tmppublish_dir.'/htdocs/'.$this->px->get_install_path().'/'.$path );

		$items = $this->px->dbh()->ls( $realpath_target_dir );
		foreach( $items as $filename ){
			$current_original_path = $realpath_target_dir.'/'.$filename;
			$filename = preg_replace( '/\.html(?:\.[a-zA-Z0-9]+)?$/si' , '.html' , $filename );
			$current_path = $realpath_target_dir.'/'.$filename;
			$current_publishto = $realpath_tmppublish_dir.'/'.$filename;

			if($this->is_ignore_path($current_original_path)){
				continue;
			}
			$extension = $this->px->dbh()->get_extension( $current_path );

			if( is_dir( $current_path ) ){
				//  対象がディレクトリだったら
				$this->px->dbh()->mkdir_all( $current_publishto );
				$this->scan_dirs( $path.'/'.$filename );
			}elseif( is_file( $current_original_path ) ){
				//  対象がファイルだったら
				$this->add_queue( $this->px->dbh()->get_realpath($this->px->get_install_path().$path.'/'.$filename) );
			}

		}

		return true;
	}//scan_dirs();

	/**
	 * ファイル単体をパブリッシュする
	 * @param $path
	 * @return true
	 */
	private function publish_file( $path ){
		if( !preg_match( '/^\//' , $path ) ){
			return false;
		}
		$extension = $this->px->dbh()->get_extension( $path );
		switch( strtolower($extension) ){
			case 'html':
				$url = 'http'.($this->px->req()->is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$this->px->dbh()->get_realpath($path);

				$httpaccess = $this->factory_httpaccess();
				$httpaccess->clear_request_header();//初期化
				$httpaccess->set_url( $url );//ダウンロードするURL
				$httpaccess->set_method( 'GET' );//メソッド
				$httpaccess->set_user_agent( 'PicklesCrawler' );//HTTP_USER_AGENT
				$httpaccess->save_http_contents( $this->path_tmppublish_dir.'/htdocs/'.$path );//ダウンロードを実行する

				$relatedlink = $httpaccess->get_response('X-PXFW-RELATEDLINK');
				foreach( explode(',',$relatedlink) as $row ){
					$this->add_queue($row);
				}

				$result = $httpaccess->get_status_cd();
				$this->publish_log( array(
					'result'=>($result==200?true:false),
					'publish_type'=>'http',
					'path'=>$this->px->dbh()->get_realpath($path),
				) );
				if($result!=200){
					$this->publish_error_log( array(
						'error'=>'[ERROR] Publishing HTML was FAILED.',
						'path'=>$this->px->dbh()->get_realpath($path),
					) );
				}
				break;
			default:
				$result = $this->px->dbh()->copy( $_SERVER['DOCUMENT_ROOT'].$path , $this->path_tmppublish_dir.'/htdocs/'.$path );
				$this->publish_log( array(
					'result'=>($result?true:false),
					'publish_type'=>'copy',
					'path'=>$this->px->dbh()->get_realpath($path),
				) );
				if(!$result){
					$this->publish_error_log( array(
						'error'=>'[ERROR] Copying file was FAILED.',
						'path'=>$this->px->dbh()->get_realpath($path),
					) );
				}
				break;
		}
		return true;
	}

	/**
	 * HTTPAccessオブジェクトを生成して返す
	 */
	private function factory_httpaccess(){
		@require_once( $this->px->get_conf('paths.px_dir').'libs/PxHTTPAccess/PxHTTPAccess.php' );
		return new PxHTTPAccess();
	}

	/**
	 * 除外ファイルか調べる
	 */
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

}
?>