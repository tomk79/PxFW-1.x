<?php
/**
 * class px_pxcommands_fillcontents
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: fillcontentsを実行する
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_fillcontents extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 * 
	 * @param array $command PXコマンド名
	 * @param object $px $pxオブジェクト
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );

		$command = $this->get_command();

		switch( @$command[1] ){
			case 'run':
				$this->execute();
				break;
			default:
				$this->homepage();
				break;
		}
	}//__construct()


	/**
	 * ホームページを表示する。
	 * 
	 * HTMLを標準出力した後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @return void
	 */
	private function homepage(){
		$command = $this->get_command();
		$src = '';
		$src .= '<p>サイトマップCSVの内容に従って、コンテンツファイルを生成します。</p>'."\n";
		$src .= '<p>この操作は、サイトのセットアップ時に<strong>1度だけ実行します</strong>。すでに1度実行している場合は、この操作は行わないでください。</p>'."\n";
		$src .= '<form action="?" method="get" target="_blank">'."\n";
		$src .= '<p class="center"><button class="xlarge">fillcontentsを実行する</button></p>'."\n";
		$src .= '<div><input type="hidden" name="PX" value="'.t::h($command[0]).'.run" /></div>'."\n";
		$src .= '</form>'."\n";
		print $this->html_template($src);
		exit;
	}

	/**
	 * Execute PX Command "fillcontents".
	 * 
	 * @return void
	 */
	private function execute(){
		$command = $this->get_command();

		@header('Content-type: text/plain');
		print $command[0].' | Pickles Framework (version:'.$this->px->get_version().')'."\n";
		print 'project "'.$this->px->get_conf('project.name').'" ('.$this->px->get_conf('project.id').')'."\n";
		print "\n";
		print '* start fillcontents.'."\n";
		print "\n";

		//  ダミーのコンテンツソースを生成
		$CONTENT = $this->mk_dummy_contents_src();

		$sitemap = $this->px->site()->get_sitemap();
		$content_path = array();

		//sitemapからcontent_pathの配列を抽出
		foreach ($sitemap as $key => $val ) {
			if( !strlen($val['content']) ){
				//コンテンツのパスが指定されていない場合は作成しない
				continue;
			}
			if( !preg_match('/^\//si',$val['content']) ){
				//コンテンツのパスがスラッシュから始まっていない場合は作成しない
				continue;
			}
			$tmp_path_type = $this->px->site()->get_path_type($val['path']);
			if( $tmp_path_type == 'alias' ){
				//エイリアスのコンテンツは作成しない
				continue;
			}
			array_push( $content_path, $val['content']);
		}

		foreach($content_path as $val) {
			if( !strlen($val) ){continue;}

			//処理用 サーバードキュメントルールからのパス
			$content_realpath_parts = pathinfo($this->px->dbh()->get_realpath( $_SERVER['DOCUMENT_ROOT'].$this->px->get_install_path() ) . $val);
			$dir_realpath = $this->px->dbh()->get_realpath( $content_realpath_parts['dirname'].'/' );
			$file_realpath = $this->px->dbh()->get_realpath( $content_realpath_parts['dirname'].'/'.$content_realpath_parts['basename'] );

			//結果表示用 HTMLドキュメントルールからのパス
			$content_path_parts = pathinfo($val);
			$dir_path = ($content_path_parts['dirname']=='/' ? $this->px->dbh()->get_realpath($content_path_parts['dirname']) : $this->px->dbh()->get_realpath($content_path_parts['dirname']).'/'); //ルートパスを除外
			$file_path = ($content_path_parts['dirname']=='/' ? $this->px->dbh()->get_realpath($content_path_parts['dirname']) : $this->px->dbh()->get_realpath($content_path_parts['dirname']).'/').$content_path_parts['basename'];

			print '------'."\n";

			//ディレクトリ生成
			if(!$this->px->dbh()->is_dir($dir_realpath)) {
				$success_dir = $this->px->dbh()->mkdir_all( $dir_realpath );

				if($success_dir) {
					print 'success make Directory : ' . $dir_realpath . "\n";
				} else {
					print '[ERROR] make Directory FAILED: ' . $dir_realpath . "\n";
				}
			} else {
				print 'Directory already exists: ' . $dir_realpath . "\n";
			}

			//ファイル生成
			if(!$this->is_content_file($file_realpath)) {
				$success_file = $this->px->dbh()->save_file( $file_realpath , $CONTENT );
				if($success_file) {
					print 'success make File: ' . $file_realpath . "\n";
				} else {
					print '[ERROR] make File FAILED: ' . $file_realpath . "\n";
				}
			} else {
				print 'File already exists: ' . $file_realpath . "\n";
			}

		}

		print '------'."\n\n";
		print 'fillcontents completed.'."\n\n";
		print date('Y-m-d H:i:s')."\n\n";
		print 'exit.'."\n";
		exit;
	}

	/**
	 * コンテンツファイルがすでに存在するか確認する。
	 * 
	 * @param string $file_realpath 調査対象のファイルパス
	 * @return bool 存在する場合に `true`、存在しない場合に `false` を返します。
	 */
	private function is_content_file($file_realpath){
		if( $this->px->dbh()->is_file($file_realpath) ){
			return true;
		}
		$extensions = $this->px->get_extensions_list();
		foreach( $extensions as $ext ){
			if( $this->px->dbh()->is_file($file_realpath.'.'.$ext) ){
				return true;
			}
		}
		return false;
	}

	/**
	 * ダミーのコンテンツソースを生成する。
	 * 
	 * @return string HTMLソース
	 */
	private function mk_dummy_contents_src(){
		$command = $this->get_command();
		$src = '';
		$src .= '<h2>Dummy content</h2>'."\r\n";
		$src .= '<p>このコンテンツファイルは、PX='.t::h($command[0]).' によって自動生成されたダミーファイルです。</p>'."\r\n";
		$src .= "\r\n";
		$src .= ''."\r\n";
		$src .= '<'.'?php'."\r\n";
		$src .= '$children = $px->site()->get_children();'."\r\n";
		$src .= 'if(count($children)){'."\r\n";
		$src .= '	print \'<ul>\'."\\r\\n";'."\r\n";
		$src .= '	foreach($children as $child){'."\r\n";
		$src .= '		print \'	<li>\'.$px->theme()->mk_link($child).\'</li>\'."\\r\\n";'."\r\n";
		$src .= '	}'."\r\n";
		$src .= '	print \'</ul>\'."\\r\\n";'."\r\n";
		$src .= '}'."\r\n";
		$src .= '?'.'>'."\r\n";
		$src .= ''."\r\n";

		return $src;
	}

}

?>