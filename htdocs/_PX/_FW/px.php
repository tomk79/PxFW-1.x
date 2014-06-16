<?php
/**
 * class px_px
 * 
 * PxFWのコアオブジェクト `$px` のオブジェクトクラスを定義します。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
/**
 * $pxオブジェクトクラス
 * 
 * `$px` は、PxFWのあらゆる処理の中心となるオブジェクトです。
 * PxFWの処理の冒頭でインスタンス化され、以降はこのオブジェクトの内部で処理が進行します。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_px{
	/**
	 * コンフィグ内容
	 */
	private $conf = array();
	/**
	 * コアオブジェクト $dbh
	 */
	private $obj_dbh  ;
	/**
	 * コアオブジェクト $error
	 */
	private $obj_error;
	/**
	 * コアオブジェクト $req
	 */
	private $obj_req  ;
	/**
	 * コアオブジェクト $site
	 */
	private $obj_site ;
	/**
	 * コアオブジェクト $theme
	 */
	private $obj_theme;
	/**
	 * コアオブジェクト $user
	 */
	private $obj_user ;

	/**
	 * PX Command
	 */
	private $pxcommand;
	/**
	 * 関連ファイルのURL情報
	 */
	private $relatedlinks = array();
	/**
	 * コンフィグファイル `mainconf.ini` の格納パス
	 */
	private $path_mainconf;
	/**
	 * プラグインオブジェクトのコレクション
	 */
	private $plugin_objects = array();
	/**
	 * ディレクトリインデックスの一覧
	 */
	private $directory_index = array();

	/**
	 * PxFWのバージョン情報を取得する。
	 * 
	 * <pre> [バージョン番号のルール]
	 *    基本
	 *      メジャーバージョン番号.マイナーバージョン番号.リリース番号
	 *        例：1.0.0
	 *        例：1.8.9
	 *        例：12.19.129
	 *      - 大規模な仕様の変更や追加を伴う場合にはメジャーバージョンを上げる。
	 *      - 小規模な仕様の変更や追加の場合は、マイナーバージョンを上げる。
	 *      - バグ修正、ドキュメント、コメント修正等の小さな変更は、リリース番号を上げる。
	 *    開発中プレビュー版
	 *      基本バージョンの後ろに、a(=α版)またはb(=β版)を付加し、その連番を記載する。
	 *        例：1.0.0a1 ←最初のα版
	 *        例：1.0.0b12 ←12回目のβ版
	 *      開発中およびリリースバージョンの順序は次の通り
	 *        1.0.0a1 -> 1.0.0a2 -> 1.0.0b1 ->1.0.0b2 -> 1.0.0 ->1.0.1a1 ...
	 *    ナイトリービルド
	 *      ビルドの手順はないので正確には "ビルド" ではないが、
	 *      バージョン番号が振られていない、開発途中のリビジョンを
	 *      ナイトリービルドと呼ぶ。
	 *      ナイトリービルドの場合、バージョン情報は、
	 *      ひとつ前のバージョン文字列の末尾に、'-nb' を付加する。
	 *        例：1.0.0b12-nb (=1.0.0b12リリース後のナイトリービルド)
	 *      普段の開発においてコミットする場合、
	 *      必ずこの get_version() がこの仕様になっていることを確認すること。
	 * </pre>
	 * 
	 * @return string バージョン番号を示す文字列
	 */
	public function get_version(){
		return '1.0.4-nb';
	}

	/**
	 * コンストラクタ
	 * 
	 * @param string $path_mainconf コンフィグファイル `mainconf.ini` の格納パス
	 */
	public function __construct( $path_mainconf ){
		$this->path_mainconf = $path_mainconf;

		//  PHP設定のチューニング
		$this->php_setup();

		//  コンフィグ値のロード
		$this->conf = $this->load_conf( $path_mainconf );

		//  コアライブラリのインスタンス生成
		$this->create_core_instances();

		//  PXコマンドを解析
		$this->pxcommand = $this->parse_pxcommand( $this->req()->get_param('PX') );

		return true;
	}//__construct()

	/**
	 * フレームワークを実行する。
	 * 
	 * フレームワークの実行開始直後に呼び出し元から明示的にキックされます(コンストラクタはこのメソッドを実行しません)。一度キックされたあとは、もう呼び出す必要はありません。
	 * 
	 * 2度目に呼び出した場合、`false` が返されます。
	 * 
	 * @return bool 正常時 `true`、失敗した場合に `false`
	 */
	public function execute(){
		static $executed_flg = false;
		if($executed_flg){
			// 2度目は実行できないようにするロック。
			return false;
		}
		$executed_flg = true;

		$this->access_log();//アクセスログを記録

		if( $this->user()->is_publishtool() ){
			// パブリッシュツールのアクセスだったら、PHPのエラーを標準出力しないようにする。
			@ini_set('display_errors', 'Off');
		}


		//  PX Commands を実行
		$tmp_px_class_name = $this->load_px_class( 'pxcommands/'.$this->pxcommand[0].'.php' );
		if( $tmp_px_class_name ){
			$obj_pxcommands = new $tmp_px_class_name( $this->pxcommand , $this );
		}
		unset( $tmp_px_class_name );

		@header('Content-type: text/html; charset='.(strlen($this->get_conf('system.output_encoding'))?$this->get_conf('system.output_encoding'):'UTF-8'));//←デフォルトのContent-type。$theme->bind_contents() 内で必要があれば上書き可能。

		//  テーマIDの変更を反映
		if( strlen($this->req()->get_param('THEME')) ){
			$this->theme()->set_theme_id( $this->req()->get_param('THEME') );
		}
		if( !is_dir( $_SERVER['DOCUMENT_ROOT'].$this->get_install_path().''.$this->get_conf('system.public_cache_dir').'/themes/'.$this->theme()->get_theme_id().'/' ) ){
			// テーマリソースキャッシュの一次生成
			$this->path_theme_files('/');
		}

		//  ユーザーログイン処理
		$this->user()->update_login_status( $this->req()->get_param('ID') , $this->req()->get_param('PW') );

		//  カレントページの情報を取得
		$page_info = $this->site()->get_page_info( $this->req()->get_request_file_path() );

		//  レイアウトIDの変更を反映
		if( strlen( $page_info['layout'] ) ){
			$this->theme()->set_layout_id($page_info['layout']);
		}

		//  auth_levelの分岐処理
		if( $page_info['auth_level'] ){
			if( !$this->user()->is_login() ){
				//  ログインしていなかったらログインを促す。
				$this->page_login();
				return true;
			}elseif( $page_info['auth_level'] > $this->user()->get_login_user_auth_level() ){
				//  ユーザーのauth_levelが満たなかったら、forbidden
				$this->page_forbidden();
				return true;
			}
		}

		//  コンテンツファイル(内部パス)を決める
		$localpath_current_content = $this->site()->get_page_info( $this->req()->get_request_file_path() , 'content' );
		if( !strlen($localpath_current_content) ){
			$localpath_current_content = $_SERVER['PATH_INFO'];
			if( preg_match('/\/$/s',$localpath_current_content) ){
				$localpath_current_content .= $this->get_directory_index_primary();
			}
		}
		$path_content = $this->dbh()->get_realpath( dirname($_SERVER['SCRIPT_FILENAME']).$localpath_current_content );
		if( is_file(dirname($_SERVER['SCRIPT_FILENAME']).$_SERVER['PATH_INFO']) ){
			// 物理ファイルが存在する場合はそっちが優先
			$path_content = $this->dbh()->get_realpath( dirname($_SERVER['SCRIPT_FILENAME']).$_SERVER['PATH_INFO'] );
		}

		//------
		//  拡張子違いのコンテンツを検索
		//  リクエストはmod_rewriteの設定上、*.html でしかこない。
		//  a.html のクエリでも、a.html.php があれば、a.html.php を採用できるようにしている。
		$list_extensions = $this->get_extensions_list();
		foreach( $list_extensions as $row_extension ){
			if( @is_file($path_content.'.'.$row_extension) ){
				$path_content = $path_content.'.'.$row_extension;
				break;
			}
		}
		//  / 拡張子違いのコンテンツを検索
		//------

		ob_start();
		if( @is_file( $path_content ) ){
			$extension = strtolower( $this->dbh()->get_extension( $path_content ) );
			if( strlen($page_info['extension']) ){
				$extension = $page_info['extension'];
			}
			$class_name = $this->load_px_class( 'extensions/'.$extension.'.php' );
			$plugins_list = $this->get_plugin_list();
			foreach( $plugins_list as $tmp_plugin_name=>$tmp_plugin_info ){
				// プラグイン内のextensionを検索
				$tmp_class_name = $this->load_px_plugin_class( $tmp_plugin_name.'/register/extensions/'.$extension.'.php' );
				if( strlen($tmp_class_name) ){
					$class_name = $tmp_class_name;
					break;
				}
			}
			unset($tmp_class_name, $tmp_plugin_name, $tmp_plugin_info);
			if( $class_name ){
				$obj_extension = new $class_name( $this );
				$obj_extension->execute( $path_content );
			}else{
				print $this->theme()->output_filter($this->theme()->bind_contents( '<p>Unknow extension.</p>' ), 'html');
			}
		}else{
			if( is_null($this->site()->get_current_page_info())){
				return $this->page_notfound();
			}
			print $this->theme()->output_filter($this->theme()->bind_contents( '<p>Content file is not found.</p>' ), 'html');
		}
		$final_html = @ob_get_clean();
		if( count($this->relatedlinks) ){
			@header('X-PXFW-RELATEDLINK: '.implode(',',$this->relatedlinks).'');
		}
		print $final_html;
		return true;
	}//execute()

	/**
	 * 拡張ヘッダ X-PXFW-RELATEDLINK にリンクを追加する。
	 * 
	 * 拡張ヘッダ `X-PXFW-RELATEDLINK` は、サイトマップや物理ディレクトリから発見できないファイルを、PxFWのパブリッシュツールに知らせます。
	 * 
	 * 通常、PxFWのパブリッシュツールは 動的に生成されたページなどを知ることができず、パブリッシュされません。このメソッドを通じて、明示的に知らせる必要があります。
	 * 
	 * @param string $path リンクのパス
	 * @return bool 正常時 `true`、失敗した場合 `false`
	 */
	public function add_relatedlink( $path ){
		$path = trim($path);
		if(!strlen($path)){
			return false;
		}
		array_push( $this->relatedlinks , $path );
		return true;
	}

	/**
	 * PxFWのインストールパスを取得する。
	 * 
	 * PxFW は、ドキュメントルート直下以外のディレクトリにインストールすることもできます。インストールパスは、`_px_execute.php` が置かれているディレクトリです。
	 * このメソッドは、ドキュメントルートからインストールパスまでの階層を文字列で返します。
	 * 
	 * @return string ドキュメントルートからのパス(スラッシュ閉じ)
	 */
	public function get_install_path(){
		//  環境変数から自動的に判断。
		$rtn = dirname( $_SERVER['SCRIPT_NAME'] );
		if( !array_key_exists( 'REMOTE_ADDR' , $_SERVER ) ){
			//  CUIから起動された場合
			//  ドキュメントルートが判定できないので、
			//  ドキュメントルート直下にあるものとする。
			$rtn = '/';
		}
		$rtn = str_replace('\\','/',$rtn);
		$rtn .= ($rtn!='/'?'/':'');
		return $rtn;
	}//get_install_path()

	/**
	 * ローカルリソースディレクトリのパスを得る。
	 * 
	 * @param string $localpath_resource ローカルリソースのパス
	 * @return string ローカルリソースの実際の絶対パス
	 */
	public function path_files( $localpath_resource = null ){
		$tmp_page_info = $this->site()->get_page_info($this->req()->get_request_file_path());
		$path_content = $tmp_page_info['content'];
		if( is_null($path_content) ){
			$path_content = $this->req()->get_request_file_path();
		}
		unset($tmp_page_info);

		$rtn = $this->get_install_path().$path_content;
		$rtn = $this->dbh()->get_realpath($this->dbh()->trim_extension($rtn).'.files/'.$localpath_resource);
		if( is_dir($_SERVER['DOCUMENT_ROOT'].$rtn) ){
			$rtn .= '/';
		}
		return $rtn;
	}//path_files()

	/**
	 * ローカルリソースディレクトリのサーバー内部パスを得る。
	 * 
	 * @param string $localpath_resource ローカルリソースのパス
	 * @return string ローカルリソースのサーバー内部パス
	 */
	public function realpath_files( $localpath_resource = null ){
		$rtn = $this->path_files( $localpath_resource );
		$rtn = $this->dbh()->get_realpath( $_SERVER['DOCUMENT_ROOT'].$rtn );
		if( is_dir($rtn) ){
			$rtn .= '/';
		}
		return $rtn;
	}//realpath_files()

	/**
	 * ローカルリソースのキャッシュディレクトリのパスを得る。
	 * 
	 * @param string $localpath_resource ローカルリソースのパス
	 * @return string ローカルリソースキャッシュのパス
	 */
	public function path_files_cache( $localpath_resource = null ){
		$tmp_page_info = $this->site()->get_page_info($this->req()->get_request_file_path());
		$path_content = $tmp_page_info['content'];
		if( is_null($path_content) ){
			$path_content = $this->req()->get_request_file_path();
		}
		unset($tmp_page_info);

		$path_original = $this->get_install_path().$path_content;
		$path_original = $this->dbh()->get_realpath($this->dbh()->trim_extension($path_original).'.files/'.$localpath_resource);
		$rtn = $this->get_install_path().'/'.$this->get_conf('system.public_cache_dir').'/contents'.$path_content;
		$rtn = $this->dbh()->get_realpath($this->dbh()->trim_extension($rtn).'.files/'.$localpath_resource);
		if( file_exists( $_SERVER['DOCUMENT_ROOT'].$path_original ) ){
			if( is_dir($_SERVER['DOCUMENT_ROOT'].$path_original) ){
				$rtn .= '/';
				$this->dbh()->mkdir_all( $_SERVER['DOCUMENT_ROOT'].$rtn );
			}else{
				$this->dbh()->mkdir_all( dirname( $_SERVER['DOCUMENT_ROOT'].$rtn ) );
			}
			$this->dbh()->copy_all( $_SERVER['DOCUMENT_ROOT'].$path_original, $_SERVER['DOCUMENT_ROOT'].$rtn );
		}
		$this->add_relatedlink($rtn);
		return $rtn;
	}//path_files_cache()

	/**
	 * ローカルリソースのキャッシュディレクトリのサーバー内部パスを得る。
	 * 
	 * @param string $localpath_resource ローカルリソースのパス
	 * @return string ローカルリソースキャッシュのサーバー内部パス
	 */
	public function realpath_files_cache( $localpath_resource = null ){
		$rtn = $this->path_files_cache( $localpath_resource );
		$rtn = $this->dbh()->get_realpath( $_SERVER['DOCUMENT_ROOT'].$rtn );
		if( is_dir($rtn) ){
			$rtn .= '/';
		}
		return $rtn;
	}//realpath_files_cache()

	/**
	 * テーマリソースディレクトリのパスを得る。
	 * 
	 * @param string $localpath_theme_resource テーマリソースのパス
	 * @return string テーマリソースのパス
	 */
	public function path_theme_files( $localpath_theme_resource = null ){
		$localpath_theme_resource = preg_replace('/^\/+/', '', $localpath_theme_resource);

		$realpath_original = $this->realpath_theme_files().'/'.$localpath_theme_resource;
		$realpath_copyto = $_SERVER['DOCUMENT_ROOT'].$this->get_install_path().''.$this->get_conf('system.public_cache_dir').'/themes/'.$this->theme()->get_theme_id().'/'.$localpath_theme_resource;
		if( is_file($realpath_original) ){
			// 対象がファイルだったら
			if( strtolower($this->dbh()->get_extension($realpath_copyto)) == 'nopublish' ){
				// 拡張子 *.nopublish のファイルはコピーしない
			}elseif( !is_file($realpath_copyto) || $this->dbh()->is_newer_a_than_b( $realpath_original, $realpath_copyto ) ){
				// キャッシュが存在しないか、オリジナルの方が新しい場合。
				// キャッシュを作成・更新。
				$this->dbh()->mkdir_all( dirname($realpath_copyto) );
				$this->dbh()->copy( $realpath_original, $realpath_copyto );
				$this->add_relatedlink( $this->get_install_path().''.$this->get_conf('system.public_cache_dir').'/themes/'.$this->theme()->get_theme_id().'/'.$localpath_theme_resource );
			}
		}elseif( is_dir($realpath_original) ){
			// 対象がディレクトリだったら
			$this->dbh()->mkdir_all( $realpath_copyto );
			foreach( $this->dbh()->ls($realpath_original) as $tmp_basename ){
				$this->path_theme_files( $localpath_theme_resource.'/'.$tmp_basename );
			}
		}

		$rtn = $this->get_install_path().''.$this->get_conf('system.public_cache_dir').'/themes/'.$this->theme()->get_theme_id().'/'.$localpath_theme_resource;
		return $rtn;
	}//path_theme_files()

	/**
	 * テーマリソースのサーバー内部パスを得る。
	 * 
	 * @param string $localpath_theme_resource テーマリソースのパス
	 * @return string テーマリソースのサーバー内部パス
	 */
	public function realpath_theme_files( $localpath_theme_resource = null ){
		$lib_realpath = $this->get_conf('paths.px_dir').'themes/'.$this->theme()->get_theme_id().'/theme.files/';
		$rtn = $this->dbh()->get_realpath( $lib_realpath.$localpath_theme_resource );
		if( is_dir($rtn) ){
			$rtn .= '/';
		}
		return $rtn;
	}//realpath_theme_files()

	/**
	 * カレントコンテンツのramdataディレクトリのサーバー内部パスを得る。
	 * 
	 * @return string RAMデータディレクトリのサーバー内部パス
	 */
	public function realpath_ramdata_dir(){
		$tmp_page_info = $this->site()->get_page_info($this->req()->get_request_file_path());
		$path_content = $tmp_page_info['content'];

		$lib_realpath = $this->get_conf('paths.px_dir').'_sys/ramdata/contents/'.$this->dbh()->trim_extension($path_content).'.files/';
		$rtn = $this->dbh()->get_realpath( $lib_realpath ).'/';
		if( !is_dir($rtn) ){
			$this->dbh()->mkdir_all($rtn);
		}
		return $rtn;
	}

	/**
	 * 選択されたテーマのramdataディレクトリのサーバー内部パスを得る。
	 * 
	 * @return string RAMデータディレクトリのサーバー内部パス
	 */
	public function realpath_theme_ramdata_dir(){
		$lib_realpath = $this->get_conf('paths.px_dir').'_sys/ramdata/themes/'.$this->theme()->get_theme_id().'/';
		$rtn = $this->dbh()->get_realpath( $lib_realpath ).'/';
		if( !is_dir($rtn) ){
			$this->dbh()->mkdir_all($rtn);
		}
		return $rtn;
	}

	/**
	 * プラグインのramdataディレクトリのサーバー内部パスを得る。
	 * 
	 * @param string $plugin_name プラグイン名
	 * @return string RAMデータディレクトリのサーバー内部パス
	 */
	public function realpath_plugin_ramdata_dir($plugin_name){
		$lib_realpath = $this->get_conf('paths.px_dir').'_sys/ramdata/plugins/'.$plugin_name.'/';
		$rtn = $this->dbh()->get_realpath( $lib_realpath ).'/';
		if( !is_dir($rtn) ){
			$this->dbh()->mkdir_all($rtn);
		}
		return $rtn;
	}

	/**
	 * カレントコンテンツのプライベートキャッシュディレクトリのサーバー内部パスを得る。
	 * 
	 * @return string プライベートキャッシュディレクトリのサーバー内部パス
	 */
	public function realpath_private_cache_dir(){
		$tmp_page_info = $this->site()->get_page_info($this->req()->get_request_file_path());
		$path_content = $tmp_page_info['content'];

		$lib_realpath = $this->get_conf('paths.px_dir').'_sys/caches/contents/'.$this->dbh()->trim_extension($path_content).'.files/';
		$rtn = $this->dbh()->get_realpath( $lib_realpath ).'/';
		if( !is_dir($rtn) ){
			$this->dbh()->mkdir_all($rtn);
		}
		return $rtn;
	}

	/**
	 * 選択されたテーマのプライベートキャッシュディレクトリのサーバー内部パスを得る。
	 * 
	 * @return string プライベートキャッシュディレクトリのサーバー内部パス
	 */
	public function realpath_theme_private_cache_dir(){
		$lib_realpath = $this->get_conf('paths.px_dir').'_sys/caches/themes/'.$this->theme()->get_theme_id().'/';
		$rtn = $this->dbh()->get_realpath( $lib_realpath ).'/';
		if( !is_dir($rtn) ){
			$this->dbh()->mkdir_all($rtn);
		}
		return $rtn;
	}

	/**
	 * プラグインのプライベートキャッシュディレクトリのサーバー内部パスを得る。
	 * 
	 * @param string $plugin_name プラグイン名
	 * @return string プライベートキャッシュディレクトリのサーバー内部パス
	 */
	public function realpath_plugin_private_cache_dir($plugin_name){
		$lib_realpath = $this->get_conf('paths.px_dir').'_sys/caches/plugins/'.$plugin_name.'/';
		$rtn = $this->dbh()->get_realpath( $lib_realpath ).'/';
		if( !is_dir($rtn) ){
			$this->dbh()->mkdir_all($rtn);
		}
		return $rtn;
	}

	/**
	 * 外部ソースをインクルードする。(ServerSideInclude)
	 * 
	 * このメソッドは、SSI(サーバーサイドインクルード)のように、指定したファイルを埋め込むときに使用します。
	 * 
	 * 引数の <code>$path_incfile</code> は、`DOCUMENT_ROOT` を起点とした絶対パスで指定します。PxFWのインストールパスではありません。
	 * 
	 * PHP の `include()` 関数も使用できますが、`include()` で読み込んだソースは、
	 * パブリッシュ時に静的に記述されたものとして出力されます。 `$px->ssi()` は、
	 * ブラウザでプレビューするときはインクルードされた状態で、
	 * パブリッシュするときは、Apache の SSI の記述として出力する点が、`include()` と大きく挙動が異なる点です。
	 * 
	 * デフォルトでは、 `$px->ssi()` は、インクルードファイルをスタティックな文字列として取り扱います。
	 * 従って、インクルードファイル内でさらに別のファイルをインクルードしたい場合に、期待通りに動作しません。
	 * 
	 * PxFW 1.0.3 以降、コンフィグ項目 `system.ssi_method` を設定して、いくつかの方法で多重インクルードを実装することができるようになりました。
	 * 
	 * `system.ssi_method` の設定値と挙動の対応は次の通りです。
	 * 
	 * - `http` : 
	 * インクルードファイルをHTTP通信経由で取りに行きます。
	 * あくまで内部処理ではないため、インクルードファイル内でインクルードを動かしたい場合には、
	 * インクルードファイルの拡張子をApacheで設定する必要があります。
	 *  
	 * - `php_include` : 
	 * インクルードファイルはPHPスクリプトとして動的に読み込まれます。
	 * "http" 設定では、IP制限など基本認証以外の制限があったり、
	 * Apacheのプロセスが増えて動作が重くなる場合などに使えるかもしれません。
	 * ただしその場合、
	 * 拡張子 `*.html` 以外のインクルードファイルでは、プレビュー時にインクルードが処理されない点と、
	 * プレビュー時とパブリッシュ時で処理の流れが異なるため、設定ミスなどに気づきにくい点が欠点です。
	 * 
	 * - `php_virtual` : 
	 * PHPの virtual() メソッドは、Apacheのサブクエリを発行するので、
	 * インクルードファイル内でのSSIが処理されます。
	 * しかし、output bufferを無効にしてしまう副作用があるため、
	 * PxFWの `output_filter` などの後処理を通らなくなる欠点があります。
	 * 
	 * - `emulate_ssi` (PxFW 1.0.4以降) : 
	 * Apache SSI 形式をエミュレートし、擬似的にインクルードを解決します。
	 * SSI が持つ機能のうち、`<!--#include virtual="〜〜" -->` 以外の命令は無視されます。
	 * 
	 * - `static` (デフォルト) : 
	 * インクルードファイルはスタティックなテキストとして読み込まれます。
	 * インクルードファイル内でのインクルードはできません。
	 * 
	 * @param string $path_incfile インクルードするファイルパス(DOCUMENT_ROOT を起点とした絶対パス)
	 * @return string インクルードファイルのコンテンツ、パブリッシュ時は インクルードタグ
	 */
	public function ssi( $path_incfile ){
		//	パブリッシュツール(PxCrawlerなど)による静的パブリッシュを前提としたSSI処理機能。
		//	ブラウザで確認した場合は、インクルードを解決したソースを出力し、
		//	パブリッシュツールに対しては、ApacheのSSIタグを出力する。

		if( !strlen( $path_incfile ) ){ return false; }
		$RTN = '';
		$path_incfile = $this->dbh()->get_realpath( $path_incfile );
		if( $this->user()->is_publishtool() ){
			// パブリッシュツールだったら、SSIタグを出力する。
			$RTN .= $this->ssi_static_tag( $path_incfile );
		}else{
			if( $this->dbh()->is_file( $_SERVER['DOCUMENT_ROOT'].$path_incfile ) && $this->dbh()->is_readable( $_SERVER['DOCUMENT_ROOT'].$path_incfile ) ){

				$ssi_method = $this->get_conf('system.ssi_method');
				if( !strlen($ssi_method) ){ $ssi_method = 'static'; }
				$done = false;

				// ------ PxFW 1.0.3 で追加したオプション ------
				if( $ssi_method == 'http' ){
					$done = true;
					$url = 'http'.($this->req()->is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$this->dbh()->get_realpath($path_incfile);

					@require_once( $this->get_conf('paths.px_dir').'libs/PxHTTPAccess/PxHTTPAccess.php' );
					$httpaccess = new PxHTTPAccess();
					$httpaccess->clear_request_header();//初期化
					$httpaccess->set_url( $url );//ダウンロードするURL
					$httpaccess->set_method( 'GET' );//メソッド
					$httpaccess->set_user_agent( $_SERVER['HTTP_USER_AGENT'] );//HTTP_USER_AGENT
					if( strlen( $this->get_conf('project.auth_name') ) ){
						// 基本認証、またはダイジェスト認証が設定されている場合
						if( strlen( $this->get_conf('project.auth_type') ) ){
							$httpaccess->set_auth_type( $this->get_conf('project.auth_type') );//認証タイプ
						}
						$httpaccess->set_auth_user( $this->get_conf('project.auth_name') );//認証ID
						$httpaccess->set_auth_pw( $this->get_conf('project.auth_password') );//認証パスワード
					}
					$this->dbh()->mkdir_all( dirname($this->path_tmppublish_dir.'/htdocs/'.$path) );
					$RTN .= $httpaccess->get_http_contents();//ダウンロードを実行する
				}

				// ------ PxFW 1.0.3 で追加したオプション ------
				if( $ssi_method == 'php_include' ){
					$done = true;
					$px = &$this;
					$memo_page_info = $px->site()->get_current_page_info();

					ob_start();
					@include( $_SERVER['DOCUMENT_ROOT'].$path_incfile );
					$RTN .= ob_get_clean();

					$px->site()->set_page_info(null, array('layout'=>$memo_page_info['layout']) );
				}

				// ------ PxFW 1.0.3 で追加したオプション ------
				if( $ssi_method == 'php_virtual' ){
					$done = true;
					ob_start();
					virtual($path_incfile);
					$RTN .= ob_get_clean();
				}

				// ------ PxFW 1.0.4 で追加したオプション ------
				if( $ssi_method == 'emulate_ssi' ){
					$done = true;
					$px = $this;
					$tmp_src = $this->dbh()->file_get_contents( $_SERVER['DOCUMENT_ROOT'].$path_incfile );
					while(1){
						$tmp_preg_pattern = '/^(.*?)'.preg_quote('<!--#include','/').'\s+virtual\=\"(.*?)\"\s*'.preg_quote('-->','/').'(.*)$/s';
						if( !preg_match($tmp_preg_pattern, $tmp_src, $tmp_matched) ){
							$RTN .= $tmp_src;
							break;
						}
						$RTN .= $tmp_matched[1];
						$RTN .= $this->ssi($this->theme()->href($tmp_matched[2]));
						$tmp_src = $tmp_matched[3];
						continue;
					}
					$RTN .= $tmpSrc;
				}

				// ------ PxFW 1.0.2 までの実装 ------
				if( $ssi_method == 'static' ){
					$done = true;
					// デフォルトの処理
					$RTN .= $this->dbh()->file_get_contents( $_SERVER['DOCUMENT_ROOT'].$path_incfile );
				}

				$RTN = t::convert_encoding($RTN);

				if( !$done ){
					// ERROR: 設定が正しくありません。
					$RTN .= '<!-- ERROR: unknown config "system.ssi_method" '.t::h($ssi_method).' -->';
				}
			}
		}
		return	$RTN;
	}//ssi();

	/**
	 * パブリッシュ時のSSIタグを出力する。
	 * 
	 * このメソッドは、`$px->ssi()` から内部的にコールされます。
	 * 
	 * @param string $path_incfile インクルードするファイルパス(DOCUMENT_ROOT を起点とした絶対パス)
	 * @return string インクルードタグ
	 */
	private function ssi_static_tag( $path_incfile ){
		$plugins_list = $this->dbh()->ls( $this->get_conf('paths.px_dir').'plugins/' );
		foreach( $plugins_list as $tmp_plugin_name ){
			// プラグイン内のextensionを検索
			$tmp_class_name = $this->load_px_plugin_class( $tmp_plugin_name.'/register/funcs.php' );
			if( strlen($tmp_class_name) && method_exists( $tmp_class_name, 'ssi_static_tag' ) ){
				$obj = new $tmp_class_name($this);
				return $obj->ssi_static_tag( $path_incfile );
				break;
			}
		}
		unset($tmp_class_name, $tmp_plugin_name);

		// $ssi_method = $this->get_conf('system.ssi_method');
		// if( !strlen($ssi_method) ){ $ssi_method = 'static'; }
		// if( $ssi_method == 'php_include' ){
		// 	return '<'.'?php include( $_SERVER[\'DOCUMENT_ROOT\'].'.t::data2phpsrc( $path_incfile ).' ); ?'.'>';
		// }
		return '<!--#include virtual="'.htmlspecialchars( $path_incfile ).'" -->';
	}//ssi_static_tag()

	/**
	 * PXコマンドを解析する。
	 * 
	 * PXコマンドは、URLパラメータに `?PX=xxxxx` として受け取ります。このメソッドは、`PX` パラメータを解析し、コマンドを配列に格納して返します。
	 *
	 * 設定 `system.allow_pxcommands` が `0` に設定されていて、かつウェブからのアクセス(コマンドラインの実行ではなく)の場合は、PXコマンドは利用できません。 PXコマンドが利用できない場合、このメソッドは `null` を返します。
	 * 
	 * @param string $param URLパラメータ PX に受け取った値
	 * @return array|null 先頭にPXコマンド名を含むパラメータの配列(入力値をドットで区切ったもの)。PXコマンドが利用できない場合は、`null`
	 */
	private function parse_pxcommand( $param ){
		if( !$this->get_conf('system.allow_pxcommands') ){
			// 設定で許可されていない場合は、null
			if( !$this->req()->is_cmd() ){
				// コマンドラインの場合、この設定は無効
				return null;
			}
		}
		if( !strlen( $param ) ){
			//  パラメータ値が付いていなければ、null
			return null;
		}
		return explode( '.' , $param );
	}//parse_pxcommand()

	/**
	 * PHP設定をチューニングする。
	 * 
	 * @return bool 常に `true`
	 */
	private function php_setup(){
		if( !extension_loaded( 'mbstring' ) ){
			trigger_error('mbstringがロードされていません。');
		}

		if( is_callable('mb_internal_encoding') ){
			mb_internal_encoding('UTF-8');
		}
		@ini_set( 'default_charset' , 'UTF-8' );
		@ini_set( 'mbstring.internal_encoding' , 'UTF-8' );
		@ini_set( 'mbstring.http_input' , 'UTF-8' );
		@ini_set( 'mbstring.http_output' , 'UTF-8' );
		if( is_callable('mb_detect_order') ){
			@ini_set( 'mbstring.detect_order' , 'UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII' );
			@mb_detect_order( 'UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII' );
		}

		return true;
	}//php_setup();

	/**
	 * コンフィグ値をロードする。
	 *
	 * @param string $path_mainconf コンフィグファイルの格納パス
	 * @param array $default 読み込み前のコンフィグ値。(`_include` により再帰的にファイルを読み込む場合にセットされる)
	 * @return array 読み込んだ設定値を格納した連想配列
	 */
	private function load_conf( $path_mainconf, $default = array() ){
		if( !is_file($path_mainconf) ){
			if( count($default) ){ return $default; }
			print '[error] your config file &quot;'.htmlspecialchars( $path_mainconf ).'&quot; is not exits. set your true config file path.';
			exit();
		}
		if( !is_readable($path_mainconf) ){
			if( count($default) ){ return $default; }
			print '[error] your config file &quot;'.htmlspecialchars( $path_mainconf ).'&quot; is not readable. set your config file as &quot;readable&quot;.';
			exit();
		}

		$conf = array(
			// デフォルト値セット
			'project.id'=>"pxfw",
			'paths.px_dir'=>"./_PX/",
			'colors.main'=>"#00a0e6",
			'publish_extensions.html'=>"http",
			'publish_extensions.css'=>"http",
			'publish_extensions.js'=>"http",
			'publish_extensions.php'=>"copy",
			'publish_extensions.nopublish'=>"nopublish",
			'publish_extensions.inc'=>"include_text",
			'system.allow_pxcommands'=>"0",
			'system.session_name'=>"PXSID",
			'system.session_expire'=>"1800",
			'system.filesystem_encoding'=>"UTF-8",
			'system.default_theme_id'=>"default",
			'system.file_default_permission'=>"775",
			'system.dir_default_permission'=>"775",
			'system.public_cache_dir'=>"_caches",
			'system.ssi_method'=>"static",
		);
		if( is_array($default) && count($default) ){
			// デフォルトの配列を受け取ったら、それでリセット
			$conf = $default;
		}

		$tmp_conf = parse_ini_file( $path_mainconf , true );
		foreach ($tmp_conf as $key1=>$row1) {
			if( is_array($row1) ){
				foreach ($row1 as $key2=>$val) {
					if( $key2 == '_include' ){
						$conf = $this->load_conf( $val, $conf );
					}else{
						$conf[$key1.'.'.$key2] = $val;
					}
				}
			}else{
				if( $key1 == '_include' ){
					$conf = $this->load_conf( $row1, $conf );
				}else{
					$conf[$key1] = $row1;
				}
			}
		}
		unset( $tmp_conf , $key1 , $row1 , $key2 , $val );

		// 安全装置
		if( strlen( $conf['system.file_default_permission'] ) != 3 ){ $conf['system.file_default_permission'] = '775'; }
		if( strlen( $conf['system.dir_default_permission'] ) != 3 ){ $conf['system.dir_default_permission'] = '775'; }
		if( !strlen( $conf['project.id'] ) ){ $conf['project.id'] = 'pxfw'; }
		if( !strlen( $conf['system.default_theme_id'] ) ){ $conf['system.default_theme_id'] = 'default'; }
		if( !strlen( $conf['system.public_cache_dir'] ) ){ $conf['system.public_cache_dir'] = '_caches'; }

		if( !is_dir( $conf['paths.px_dir'] ) ){
			print '[error] paths.px_dir is not a directory. check your config file &quot;'.htmlspecialchars( realpath($path_mainconf) ).'&quot;.';
			exit();
		}
		if( is_dir( $conf['paths.px_dir'].'_sys/' ) ){
			if( !is_dir( $conf['paths.px_dir'].'_sys/applock/' ) ){ @mkdir($conf['paths.px_dir'].'_sys/applock/'); }
			if( !is_dir( $conf['paths.px_dir'].'_sys/caches/' ) ){ @mkdir($conf['paths.px_dir'].'_sys/caches/'); }
			if( !is_dir( $conf['paths.px_dir'].'_sys/publish/' ) ){ @mkdir($conf['paths.px_dir'].'_sys/publish/'); }
			if( !is_dir( $conf['paths.px_dir'].'_sys/ramdata/' ) ){ @mkdir($conf['paths.px_dir'].'_sys/ramdata/'); }
		}

		return $conf;
	}//load_conf()

	/**
	 * コンフィグ値を取得する。
	 * 
	 * コンフィグ配列から値を取り出します。
	 * 
	 * 実装例:
	 * <pre>&lt;?php
	 * // プロジェクト名(=サイト名) を取り出す
	 * $project_name = $px-&gt;get_conf('project.name');
	 * ?&gt;</pre>
	 * 
	 * @param string $key コンフィグ名
	 * @return mixed `$key` に対応する値
	 */
	public function get_conf( $key ){
		if(!array_key_exists($key, $this->conf)){ return null; }
		return $this->conf[$key];
	}//get_conf()

	/**
	 * コンフィグファイルのパスを取得する。
	 * 
	 * @return string コンフィグファイルのパス
	 */
	public function get_path_conf(){
		return $this->path_mainconf;
	}

	/**
	 * 全てのコンフィグ値を取得する。
	 * 
	 * @return すべての値が入ったコンフィグの連想配列
	 */
	public function get_conf_all(){
		return $this->conf;
	}//get_conf_all()

	/**
	 * directory_index の一覧を得る。
	 * 
	 * @return array ディレクトリインデックスの一覧
	 */
	public function get_directory_index(){
		if( count($this->directory_index) ){
			return $this->directory_index;
		}
		$tmp_di = preg_split( '/\,| |\;|\r\n|\r|\n/', $this->get_conf('project.directory_index') );
		$this->directory_index = array();
		foreach( $tmp_di as $file_name ){
			$file_name = trim($file_name);
			if( !strlen($file_name) ){ continue; }
			array_push( $this->directory_index, $file_name );
		}
		if( !count( $this->directory_index ) ){
			array_push( $this->directory_index, 'index.html' );
		}
		return $this->directory_index;
	}//get_directory_index()

	/**
	 * directory_index のいずれかにマッチするためのpregパターン式を得る。
	 * 
	 * @param string $delimiter pregパターンのデリミタ。省略時は `/` (`preg_quote()` の実装に従う)。
	 * @return string pregパターン
	 */
	public function get_directory_index_preg_pattern( $delimiter = null ){
		$directory_index = $this->get_directory_index();
		foreach( $directory_index as $key=>$row ){
			$directory_index[$key] = preg_quote($row, $delimiter);
		}
		$rtn = '(?:'.implode( '|', $directory_index ).')';
		return $rtn;
	}//get_directory_index_preg_pattern()

	/**
	 * 最も優先されるインデックスファイル名を得る。
	 * 
	 * @return string 最も優先されるインデックスファイル名
	 */
	public function get_directory_index_primary(){
		$directory_index = $this->get_directory_index();
		return $directory_index[0];
	}//get_directory_index_primary()

	/**
	 * コアライブラリのインスタンスを生成する。
	 * 
	 * @return bool 常に `true`
	 */
	private function create_core_instances(){
		// composer ライブラリをロード (PxFW 1.0.4 追加)
		if( is_file($this->get_conf('paths.px_dir').'libs/composer/vendor/autoload.php') ){
			require_once( $this->get_conf('paths.px_dir').'libs/composer/vendor/autoload.php' );
		}

		//  スタティックメソッドをロード
		require_once( $this->get_conf('paths.px_dir').'_FW/statics/t.php' );
		require_once( $this->get_conf('paths.px_dir').'_FW/statics/test.php' );

		//  コアオブジェクトのインスタンス生成
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/error.php' );
		$this->obj_error = new px_cores_error( $this );
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/dbh.php' );
		$this->obj_dbh = new px_cores_dbh( $this );
		$this->obj_dbh->set_db_conf( array(
			'dbms'         =>$this->get_conf('dbms.dbms'         ) ,
			'host'         =>$this->get_conf('dbms.host'         ) ,
			'port'         =>$this->get_conf('dbms.port'         ) ,
			'database_name'=>$this->get_conf('dbms.database_name') ,
			'user'         =>$this->get_conf('dbms.user'         ) ,
			'password'     =>$this->get_conf('dbms.password'     ) ,
			'charset'      =>$this->get_conf('dbms.charset'      ) ,
		) );

		require_once( $this->get_conf('paths.px_dir').'_FW/cores/req.php' );
		$this->obj_req = new px_cores_req( $this );
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/site.php' );
		$this->obj_site = new px_cores_site( $this );
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/user.php' );
		$this->obj_user = new px_cores_user( $this );
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/theme.php' );
		$this->obj_theme = new px_cores_theme( $this );

		return true;
	}//create_core_instances()

	/**
	 * extensionsの一覧を取得する。
	 *
	 * このメソッドは、フレームワークディレクトリ `_FW` の `extensions` を検索し、定義された拡張子の一覧を返します。
	 * 
	 * PxFW 1.0.4 以降、プラグインが定義する extension も検出するようになりました。
	 * 
	 * @return array extensions の一覧
	 */
	public function get_extensions_list(){
		$ary = $this->dbh()->ls( $this->get_conf('paths.px_dir').'_FW/extensions/' );
		$rtn = array();
		foreach( $ary as $row ){
			$ext = t::trimext($row);
			if(!strlen($ext)){continue;}
			if(in_array($ext, $rtn)){continue;}
			array_push( $rtn , $ext );
		}

		// プラグインが定義する独自の拡張子も探す (PxFW 1.0.4以降)
		$plugins_list = $this->get_plugin_list();
		foreach( $plugins_list as $tmp_plugin_name=>$tmp_plugin_info ){
			$ary = $this->dbh()->ls( $this->get_conf('paths.px_dir').'plugins/'.urlencode($tmp_plugin_name).'/register/extensions/' );
			if( !is_array($ary) || !count($ary) ){ continue; }
			foreach( $ary as $row ){
				$ext = t::trimext($row);
				if(!strlen($ext)){continue;}
				if(in_array($ext, $rtn)){continue;}
				array_push( $rtn , $ext );
			}
		}

		return $rtn;
	}//get_extensions_list()

	/**
	 * コアオブジェクト $dbh にアクセスする。
	 * @return object $dbhオブジェクト
	 */
	public function &dbh(){ return $this->obj_dbh; }

	/**
	 * コアオブジェクト $error にアクセスする。
	 * @return object $errorオブジェクト
	 */
	public function &error(){ return $this->obj_error; }

	/**
	 * コアオブジェクト $req にアクセスする。
	 * @return object $reqオブジェクト
	 */
	public function &req(){ return $this->obj_req; }

	/**
	 * コアオブジェクト $site にアクセスする。
	 * @return object $siteオブジェクト
	 */
	public function &site(){ return $this->obj_site; }

	/**
	 * コアオブジェクト $theme にアクセスする。
	 * @return object $themeオブジェクト
	 */
	public function &theme(){ return $this->obj_theme; }

	/**
	 * コアオブジェクト $user にアクセスする。
	 * @return object $userオブジェクト
	 */
	public function &user(){ return $this->obj_user; }

	/**
	 * PxFWのクラスファイルをロードする。
	 * 
	 * 実装例:
	 * <pre>&lt;?php
	 * $class_name = $px-&gt;load_px_class('/styles/finalizer.php');
	 * $finalizer = new $class_name($px);
	 * ?&gt;</pre>
	 * 
	 * @param string $path `_FW` ディレクトリを起点としたクラスファイルのパス
	 * @return string ロードしたクラスのクラス名
	 */
	public function load_px_class($path){
		//戻り値は、ロードしたクラス名
		$path = preg_replace( '/^\/+/si' , '' , $path );
		$class_name = 'px_'.preg_replace(  '/\//si' , '_' , $path  );
		$class_name = preg_replace(  '/\.php$/si' , '' , $class_name  );
		if( class_exists( $class_name ) ){
			//ロード済みならそのまま返す
			return $class_name;
		}

		$lib_realpath = $this->get_conf('paths.px_dir').'_FW/'.$path;
		if( !is_file( $lib_realpath ) || !is_readable( $lib_realpath ) ){ return false; }
		if( !@include_once( $lib_realpath ) ){
			return false;
		}
		if( !class_exists( $class_name ) ){
			return false;
		}
		return $class_name;
	}//load_px_class()

	/**
	 * PX Plugin のクラスファイルをロードする。
	 * 
	 * 実装例:
	 * <pre>&lt;?php
	 * $class_name = $px-&gt;load_px_plugin_class('/($plugin_name)/hoge/fuga.php');
	 * $foo = new $class_name();
	 * ?&gt;</pre>
	 * 
	 * @param string $path `plugins` ディレクトリを起点としたクラスファイルのパス
	 * @return string ロードしたクラスのクラス名
	 */
	public function load_px_plugin_class($path){
		//戻り値は、ロードしたクラス名
		$path = preg_replace( '/^\/+/si' , '' , $path );
		$class_name = 'pxplugin_'.preg_replace(  '/\//si' , '_' , $path  );
		$class_name = preg_replace(  '/\.php$/si' , '' , $class_name  );
		if( class_exists( $class_name ) ){
			//ロード済みならそのまま返す
			return $class_name;
		}

		$lib_realpath = $this->get_conf('paths.px_dir').'plugins/'.$path;
		if( !is_file( $lib_realpath ) || !is_readable( $lib_realpath ) ){ return false; }
		if( !@include_once( $lib_realpath ) ){
			return false;
		}
		if( !class_exists( $class_name ) ){
			return false;
		}
		return $class_name;
	}//load_px_plugin_class()

	/**
	 * プラグインオブジェクトを取り出す。
	 * 
	 * @param string $plugin_name プラグイン名
	 * @return object|bool 成功した場合にプラグインオブジェクト、失敗時に `false`
	 */
	public function get_plugin_object( $plugin_name ){
		if( !strlen($plugin_name) ){return false;}
		if( !@is_object($this->plugin_objects[$plugin_name]) ){
			//  プラグインオブジェクトを生成
			$tmp_path_plugins_base_dir = $this->get_conf('paths.px_dir').'plugins/';
			if( !is_file( $tmp_path_plugins_base_dir.$plugin_name.'/register/object.php' ) ){
				return false;
			}
			$tmp_class_name = $this->load_px_plugin_class($plugin_name.'/register/object.php');
			if(!$tmp_class_name){
				return false;
			}
			$this->plugin_objects[$plugin_name] = new $tmp_class_name($this);
		}
		return $this->plugin_objects[$plugin_name];
	}//get_plugin_object()

	/**
	 * プラグインの一覧を得る。
	 * 
	 * @return array プラグイン名の一覧
	 */
	public function get_plugin_list(){
		static $rtn = null;
		if( is_array($rtn) ){
			return $rtn;
		}
		$rtn = array();
		$tmp_path_plugins_base_dir = $this->get_conf('paths.px_dir').'plugins/';
		$items = $this->dbh()->ls($tmp_path_plugins_base_dir);
		usort($items, "strnatcmp");//名前順に並び替え。検索の順番を保証するため。
		foreach( $items as $base_name ){
			if( !is_dir($tmp_path_plugins_base_dir.$base_name) ){
				continue;
			}
			$rtn[$base_name] = array();
			$rtn[$base_name]['name'] = $base_name;
			$rtn[$base_name]['path'] = $tmp_path_plugins_base_dir.$base_name.'/';
		}
		return $rtn;
	}//get_plugin_list()

	/**
	 * PxFWのテーマが定義するクラスファイルをロードする。
	 * 
	 * @param string $path テーマ固有の `_FW` ディレクトリを起点としたクラスファイルのパス
	 * @return string ロードしたクラスのクラス名
	 */
	public function load_pxtheme_class($path){
		//戻り値は、ロードしたクラス名
		$path = preg_replace( '/^\/+/si' , '' , $path );
		$class_name = 'pxtheme_'.preg_replace(  '/\//si' , '_' , $path  );
		$class_name = preg_replace(  '/\.php$/si' , '' , $class_name  );
		if( class_exists( $class_name ) ){
			//ロード済みならそのまま返す
			return $class_name;
		}

		$theme_id = $this->theme()->get_theme_id();
		$lib_realpath = $this->get_conf('paths.px_dir').'themes/'.$theme_id.'/_FW/'.$path;
		if( !is_file( $lib_realpath ) || !is_readable( $lib_realpath ) ){ return false; }
		if( !@include_once( $lib_realpath ) ){
			return false;
		}
		if( !class_exists( $class_name ) ){
			return false;
		}
		return $class_name;
	}//load_pxtheme_class()

	/**
	 * 現在のアドレスへのhrefを得る。
	 * 
	 * @param array|string $params GETパラメータとして付加する値。連想配列(例：`array('key'=>'val','key2'=>'val2')`)または文字列(例:`'key=val&key2=val2'`)で指定。
	 * @return string href属性値
	 */
	public function href_self( $params = null ){
		$rtn = $this->theme()->href($this->req()->get_request_file_path());
		if( is_array($params) && count($params) ){
			$tmp_params = array();
			foreach( $params as $key=>$val ){
				array_push($tmp_params, urlencode($key).'='.urlencode($val));
			}
			$params = implode('&',$tmp_params);
		}
		if( is_string($params) && strlen($params) ){
			if( preg_match('/\?/',$rtn) ){
				$rtn .= '&'.$params;
			}else{
				$rtn .= '?'.$params;
			}
		}
		return $rtn;
	}//href_self()

	/**
	 * リダイレクトする。
	 * 
	 * このメソッドは、`Location` HTTPヘッダーを出力します。
	 * リダイレクトヘッダーを出力したあと、`exit()`を発行してスクリプトを終了します。
	 * 
	 * @param string $redirect_to リダイレクト先のURL
	 * @return void
	 */
	public function redirect( $redirect_to ){
		while( @ob_end_clean() );

		@header( 'Content-type: text/html; charset=UTF-8');
		@header( 'Location: '.$redirect_to );
		$fin = '';
		$fin .= '<!doctype html>'."\n";
		$fin .= '<html>'."\n";
		$fin .= '<head>'."\n";
		$fin .= '<meta charset="UTF-8" />'."\n";
		$fin .= '<title>redirect...</title>'."\n";
		$fin .= '<meta http-equiv="refresh" content="0;url='.t::h( $redirect_to ).'" />'."\n";
		$fin .= '</head>'."\n";
		$fin .= '<body>'."\n";
		$fin .= '<p>'."\n";
		$fin .= '画面が切り替わらない場合は、次のリンクを押してください。<br />'."\n";
		$fin .= '[<a href="'.t::h( $redirect_to ).'">次へ</a>]<br />'."\n";
		$fin .= '</p>'."\n";
		$fin .= '</body>'."\n";
		$fin .= '</html>'."\n";
		print $fin;
		exit();
	}//redirect()

	/**
	 * Not Found画面を出力する。
	 * 
	 * このメソッドは、404 Not Found 画面を出力します。
	 * 画面出力後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @return void
	 */
	public function page_notfound(){
		while( @ob_end_clean() );

		header('HTTP/1.1 404 NotFound');
		$this->site()->set_page_info( $this->req()->get_request_file_path(), array('title'=>'404 Not found.', 'list_flg'=>0, 'category_top_flg'=>0) );
		$fin = '';
		$fin .= '<p>'."\n";
		$fin .= 'お探しのページは見つかりませんでした。<br />'."\n";
		$fin .= '</p>'."\n";
		print $this->theme()->output_filter($this->theme()->bind_contents( $fin ), 'html');
		exit();
	}//page_notfound()

	/**
	 * Forbidden画面を出力する。
	 * 
	 * このメソッドは、403 Forbidden 画面を出力します。
	 * 画面出力後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @return void
	 */
	public function page_forbidden(){
		while( @ob_end_clean() );

		header('HTTP/1.1 403 Forbidden');
		$fin = '';
		$fin .= '<p>'."\n";
		$fin .= 'このページの閲覧権がありません。<br />'."\n";
		$fin .= '</p>'."\n";
		print $this->theme()->output_filter($this->theme()->bind_contents( $fin ), 'html');
		exit();
	}//page_forbidden()

	/**
	 * ログイン画面を出力する。
	 * 
	 * このメソッドは、PxFW固有のログイン画面を出力します。
	 * 画面出力後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @return void
	 */
	public function page_login(){
		while( @ob_end_clean() );

		$fin = '';
		if( strlen($this->req()->get_param('ID')) || strlen($this->req()->get_param('PW')) ){
			$fin .= '<div class="unit form_error_box">'."\n";
			$fin .= '	<p>ユーザーIDまたはパスワードが違います。</p>'."\n";
			$fin .= '</div><!-- /.form_error_box -->'."\n";
		}
		$fin .= '<p>'."\n";
		$fin .= '	ログインしてください。<br />'."\n";
		$fin .= '</p>'."\n";
		$fin .= '<form action="'.t::h($this->theme()->href( $this->req()->get_request_file_path() )).'" method="post">'."\n";
		$fin .= '	<table class="def">'."\n";
		$fin .= '		<tr><th>ユーザーID</th><td><input type="text" name="ID" value="'.t::h($this->req()->get_param('ID')).'" /></td></tr>'."\n";
		$fin .= '		<tr><th>パスワード</th><td><input type="password" name="PW" value="" /></td></tr>'."\n";
		$fin .= '	</table>'."\n";
		$fin .= '	<p><input type="submit" value="ログインする" /></p>'."\n";
		$fin .= '</form>'."\n";
		print $this->theme()->output_filter($this->theme()->bind_contents( $fin ), 'html');
		exit();
	}//page_login()

	/**
	 * ダウンロードファイルを出力する。
	 * 
	 * このメソッドは、403 Forbidden 画面を出力します。
	 * ファイル出力後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * Content-type は $options で変更できます。デフォルトはファイルの種類や拡張子に関わらず `application/octet-stream` が出力されます。
	 * 
	 * @param string $bin ダウンロードするファイルのバイナリ
	 * @param array $options オプション
	 * @return void
	 */
	public function download( $bin , $options = array() ){
		if( is_bool( $bin ) ){ $bin = 'bool( '.text::data2text( $bin ).' )'; }
		if( is_resource( $bin ) ){ $bin = 'A Resource.'; }
		if( is_array( $bin ) ){ $bin = 'An Array.'; }
		if( is_object( $bin ) ){ $bin = 'An Object.'; }
		if( !strlen( $bin ) ){ $bin = ''; }

		#	出力バッファをすべてクリア
		while( @ob_end_clean() );

		if( strpos( $_SERVER['HTTP_USER_AGENT'] , 'MSIE' ) ){
			#	MSIE対策
			#	→こんな問題 http://support.microsoft.com/kb/323308/ja
			@header( 'Cache-Control: public' );
			@header( 'Pragma: public' );
		}

		if( strlen( $options['content-type'] ) ){
			$contenttype = $options['content-type'];
		}else{
			$contenttype = 'application/octet-stream';
		}
		if( strlen( $contenttype ) ){
			if( strlen( $options['charset'] ) ){
				$contenttype .= '; charset='.$options['charset'];
			}
			@header( 'Content-type: '.$contenttype );
		}

		if( strlen( $bin ) ){
			#	ダウンロードの容量
			@header( 'Content-Length: '.strlen( $bin ) );
		}

		if( strlen( $options['filename'] ) ){
			#	ダウンロードファイル名
			@header( 'Content-Disposition: attachment; filename='.$options['filename'] );
		}

		print $bin;
		exit();
	}//download()

	/**
	 * ディスク上のファイルを標準出力する。
	 * 
	 * Content-type は $options で変更できます。デフォルトはファイルの種類や拡張子に関わらず `application/octet-stream` が出力されます。
	 * 
	 * @param string $filepath 出力するファイルのパス
	 * @param array $options オプション
	 * @return void
	 */
	public function flush_file( $filepath , $options = array() ){
		#--------------------------------------
		#	$filepath => 出力するファイルのパス
		#	$options => オプションを示す連想配列
		#		'content-type'=>Content-type ヘッダー文字列。(第二引数よりも弱い。ほか関数との互換性のため実装)
		#		'charset'=>Content-type ヘッダー文字列に、文字コード文字列を追加
		#		'filename'=>ダウンロードさせるファイル名。
		#--------------------------------------

		if( !$this->dbh()->is_file( $filepath ) ){
			#	対象のファイルがなければfalseを返す。
			return	false;
		}
		if( !$this->dbh()->is_readable( $filepath ) ){
			#	対象のファイルに読み込み権限がなければfalseを返す。
			return	false;
		}

		#	絶対パスに変換
		$filepath = @realpath( $filepath );

		#	出力バッファをすべてクリア
		while( @ob_end_clean() );

		if( @strpos( $_SERVER['HTTP_USER_AGENT'] , 'MSIE' ) ){
			#	MSIE対策
			#	→こんな問題 http://support.microsoft.com/kb/323308/ja
			@header( 'Cache-Control: public' );
			@header( 'Pragma: public' );
		}

		if( @strlen( $options['content-type'] ) ){
			$contenttype = $options['content-type'];
		}else{
			$contenttype = 'application/octet-stream';
		}
		if( @strlen( $contenttype ) ){
			if( @strlen( $options['charset'] ) ){
				$contenttype .= '; charset='.$options['charset'];
			}
			@header( 'Content-type: '.$contenttype );
		}

		#	ダウンロードの容量
		@header( 'Content-Length: '.filesize( $filepath ) );

		if( @strlen( $options['filename'] ) ){
			#	ダウンロードファイル名
			@header( 'Content-Disposition: attachment; filename='.$options['filename'] );
		}

		#	ファイルを出力
		if( !@readfile( $filepath ) ){
			$this->errors->error_log( 'Disable to readfile( [ '.$filepath.' ] )' , __FILE__ , __LINE__ );
			return	false;
		}

		if( $options['delete'] ){
			#	deleteオプションが指定されていたら、
			#	ダウンロード後のファイルを削除する。
			$this->dbh()->rm( $filepath );
		}

		exit();
	}//flush_file()

	/**
	 * アクセスログを記録する。
	 *
	 * このメソッドはアクセスログを記録します。PxFWにより自動的にキックされます。
	 * 
	 * アクセスログは、コンフィグ `paths.access_log` に設定されたファイルに追記されます。
	 * 
	 * この設定が空白の場合、
	 * 設定されたファイルが存在せず親ディレクトリに書き込み権限がない場合、
	 * 設定されたファイルおよび親ディレクトリも存在しない場合、
	 * 設定されたファイルに上書き権限がない場合に、
	 * 記録は失敗し、`false` が返されます。
	 * 
	 * @return bool 成功時 `true`、失敗時 `false`
	 */
	private function access_log(){
		if( !strlen( $this->get_conf('paths.access_log') ) ){
			return false;
		}
		return @error_log(
			date('Y-m-d H:i:s')
			.'	'.session_id()
			.'	'.$this->req()->get_request_file_path()
			.'	'.$_SERVER['HTTP_USER_AGENT']
			.'	'.$_SERVER['HTTP_REFERER']
			."\r\n" , 3 , $this->get_conf('paths.access_log') );
	}//access_log()

}

?>