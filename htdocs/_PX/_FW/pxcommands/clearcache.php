<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: clearcacheを実行する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_clearcache extends px_bases_pxcommand{

	private $paths_cache_dir = array();

	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->execute();
	}//__construct()

	/**
	 * Execute PX Command "clearcache".
	 * @access public
	 * @return null
	 */
	private function execute(){
		$command = $this->get_command();
		$this->setup();
		@header('Content-type: text/plain');
		print ''.$command[0].' | Pickles Framework (version:'.$this->px->get_version().')'."\n";
		print 'project "'.$this->px->get_conf('project.name').'" ('.$this->px->get_conf('project.id').')'."\n";
		print '------'."\n";
		print 'PX command "'.$command[0].'" executed.'."\n";
		print '------'."\n";
		print 'paths_cache_dir => '."\n";
		foreach( $this->paths_cache_dir as $row ){
			print '  - '.$row."\n";
		}
		print '------'."\n";
		print 'checking status...'."\n";
		$result = $this->check_status();
		if( !$result['result'] ){
			print '[NG] '.$result['message']."\n";
			print 'Try again later.'."\n";
			print 'exit.'."\n";
			exit;
		}
		print 'OK!'."\n";
		print '------'."\n";
		foreach( $this->paths_cache_dir as $path_cache_dir ){
			print 'Cleanup a Directory "'.$path_cache_dir.'"'."\n";
			print ''."\n";
			if( !is_dir( $path_cache_dir ) ){
				print '[ERROR] Directory "'.$path_cache_dir.'" is NOT exists.'."\n";
				continue;
			}
			$items = $this->px->dbh()->ls( $path_cache_dir );
			foreach( $items as $filename ){
				$this->rmdir_all( $path_cache_dir.'/'.$filename );
			}
			print 'making readme.txt'."\n";//  gitが空ディレクトリを保持できないため
			$this->px->dbh()->save_file( $path_cache_dir.'/readme.txt' , 'This directory is for saving cache files.' );
			print '[Complete]'."\n";
			print ''."\n";
			print '---'."\n";
		}
		print '------'."\n";
		print 'publish completed.'."\n";
		print 'exit.'."\n";
		exit;
	}

	/**
	 * ディレクトリを中身ごと完全に削除する
	 * $px->dbh()からの移植。
	 * 詳細な進捗と結果を標準出力するために、独自実装することとした。
	 */
	private function rmdir_all( $path ){

		if( strlen( $this->px->get_conf('system.filesystem_encoding') ) ){
			$path = @t::convert_encoding( $path , $this->px->get_conf('system.filesystem_encoding') );
		}

		if( !$this->px->dbh()->is_writable( $path ) ){
			print 'FAILED : NOT WRITABLE "'.$path_cache_dir.'"'."\n";
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){
			#	ファイルまたはシンボリックリンクの場合の処理
			$result = @unlink( $path );
			if($result){
				print 'success: file "'.$path.'"'."\n";
			}else{
				print 'FAILED : file "'.$path.'"'."\n";
			}
			return	$result;

		}elseif( @is_dir( $path ) ){
			#	ディレクトリの処理
			$flist = $this->px->dbh()->ls( $path );
			foreach ( $flist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				$this->rmdir_all( $path.'/'.$Line );
			}
			$result = @rmdir( $path );
			if($result){
				print 'success: directory "'.$path.'"'."\n";
			}else{
				print 'FAILED : directory "'.$path.'"'."\n";
			}
			return	$result;

		}

		return false;
	}//rmdir_all()

	/**
	 * セットアップ
	 * @return true
	 */
	private function setup(){
		$this->setup_add_targetpath( $this->px->get_conf('paths.px_dir').'_sys/caches/' );
		$this->setup_add_targetpath( $this->px->get_conf('paths.px_dir').'_sys/publish/' );
		$this->setup_add_targetpath( './_caches/' );
		return true;
	}//setup()

	/**
	 * キャッシュクリア対象ディレクトリを追加する
	 * @return true
	 */
	private function setup_add_targetpath( $path ){
		$path = t::realpath($path);
		if(!is_dir($path)){
			return false;
		}
		array_push( $this->paths_cache_dir , $path );
		return true;
	}//setup_add_targetpath()

	/**
	 * ステータスをチェックする
	 * キャッシュクリアしてよい場合は true, よくない場合は false を返す。
	 * 
	 * @return array
	 */
	private function check_status(){
		$rtn = array(
			'result'=>true,
			'message'=>null,
		);
		if( is_file( $this->px->get_conf('paths.px_dir').'_sys/publish/applock.txt' ) ){
			//パブリッシュ中はキャッシュクリアしてはいけない。
			$rtn['result'] = false;
			$rtn['message'] = 'PX=publish is running on background.';
		}
		return $rtn;
	}//check_status()

}

?>