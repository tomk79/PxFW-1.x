<?php

/**
 * $pxオブジェクトクラス
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_px{
	private $conf = array();
	private $obj_dbh  ;
	private $obj_error;
	private $obj_req  ;
	private $obj_site ;
	private $obj_theme;
	private $obj_user ;

	private $pxcommand;
	private $relatedlinks = array();
	private $path_mainconf;
	private $plugin_objects = array();

	/**
	 * PxFWのバージョン情報を取得する
	 * @return string バージョン番号を示す文字列
	 */
	public function get_version(){
		return '1.0.0b8-nb';

		//  [バージョン番号のルール]
		//    基本
		//      メジャーバージョン番号.マイナーバージョン番号.リリース番号
		//        例：1.0.0
		//        例：1.8.9
		//        例：12.19.129
		//      - 大規模な仕様の変更や追加を伴う場合にはメジャーバージョンを上げる。
		//      - 小規模な仕様の変更や追加の場合は、マイナーバージョンを上げる。
		//      - バグ修正、ドキュメント、コメント修正等の小さな変更は、リリース番号を上げる。
		//    開発中プレビュー版
		//      基本バージョンの後ろに、a(=α版)またはb(=β版)を付加し、その連番を記載する。
		//        例：1.0.0a1 ←最初のα版
		//        例：1.0.0b12 ←12回目のβ版
		//      開発中およびリリースバージョンの順序は次の通り
		//        1.0.0a1 -> 1.0.0a2 -> 1.0.0b1 ->1.0.0b2 -> 1.0.0 ->1.0.1a1 ...
		//    ナイトリービルド
		//      ビルドの手順はないので正確には "ビルド" ではないが、
		//      バージョン番号が振られていない、開発途中のリビジョンを
		//      ナイトリービルドと呼ぶ。
		//      ナイトリービルドの場合、バージョン情報は、
		//      ひとつ前のバージョン文字列の末尾に、'-nb' を付加する。
		//        例：1.0.0b12-nb (=1.0.0b12リリース後のナイトリービルド)
		//      普段の開発においてコミットする場合、
		//      必ずこの get_version() がこの仕様になっていることを確認すること。
	}

	/**
	 * $pxオブジェクトの初期化。
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
	 * 呼び出し元から明示的にキックされる。
	 * @return boolean true
	 */
	public function execute(){
		static $executed_flg = false;
		if($executed_flg){
			// 2度目は実行できないようにするロック。
			return false;
		}
		$executed_flg = true;

		$this->access_log();//アクセスログを記録

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
		if( !is_dir( $_SERVER['DOCUMENT_ROOT'].$this->get_install_path().'_caches/_themes/'.$this->theme()->get_theme_id().'/' ) ){
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
				$localpath_current_content .= 'index.html';
			}
		}
		$path_content = $this->dbh()->get_realpath( dirname($_SERVER['SCRIPT_FILENAME']).$localpath_current_content );

		//------
		//  拡張子違いのコンテンツを検索
		//  リクエストはmod_rewriteの設定上、*.html でしかこない。
		//  a.html のクエリでも、a.php があれば、a.php を採用できるようにしている。
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
			if( $class_name ){
				$obj_extension = new $class_name( $this );
				$obj_extension->execute( $path_content );
			}else{
				print $this->theme()->bind_contents( '<p>Unknow extension.</p>' );
			}
		}else{
			print $this->theme()->bind_contents( '<p>Content file is not found.</p>' );
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
	 * @return true|false
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
	 * PxFWのインストール先パスを取得する。
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
	 * ローカルリソースディレクトリのパスを得る
	 * @param $localpath_resource ローカルリソースのパス。
	 * @return string ローカルリソースのパス
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
	 * ローカルリソースディレクトリのサーバー内部パスを得る
	 * @param $localpath_resource ローカルリソースのパス。
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
	 * ローカルリソースのキャッシュディレクトリのパスを得る
	 * @param $localpath_resource ローカルリソースのパス。
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
		$rtn = $this->get_install_path().'/_caches/_contents'.$path_content;
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
	 * ローカルリソースのキャッシュディレクトリのサーバー内部パスを得る
	 * @param $localpath_resource ローカルリソースのパス。
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
	 * テーマリソースディレクトリのパスを得る
	 * @return string テーマリソースのパス
	 */
	public function path_theme_files( $localpath_theme_resource = null ){
		$localpath_theme_resource = preg_replace('/^\/+/', '', $localpath_theme_resource);

		$realpath_original = $this->realpath_theme_files().'/'.$localpath_theme_resource;
		$realpath_copyto = $_SERVER['DOCUMENT_ROOT'].$this->get_install_path().'_caches/_themes/'.$this->theme()->get_theme_id().'/'.$localpath_theme_resource;
		if( is_file($realpath_original) ){
			// 対象がファイルだったら
			if( strtolower($this->dbh()->get_extension($realpath_copyto)) == 'nopublish' ){
				// 拡張子 *.nopublish のファイルはコピーしない
			}elseif( !is_file($realpath_copyto) || $this->dbh()->is_newer_a_than_b( $realpath_original, $realpath_copyto ) ){
				// キャッシュが存在しないか、オリジナルの方が新しい場合。
				// キャッシュを作成・更新。
				$this->dbh()->mkdir_all( dirname($realpath_copyto) );
				$this->dbh()->copy( $realpath_original, $realpath_copyto );
				$this->add_relatedlink( $this->get_install_path().'_caches/_themes/'.$this->theme()->get_theme_id().'/'.$localpath_theme_resource );
			}
		}elseif( is_dir($realpath_original) ){
			// 対象がディレクトリだったら
			$this->dbh()->mkdir_all( $realpath_copyto );
			foreach( $this->dbh()->ls($realpath_original) as $tmp_basename ){
				$this->path_theme_files( $localpath_theme_resource.'/'.$tmp_basename );
			}
		}

		$rtn = $this->get_install_path().'_caches/_themes/'.$this->theme()->get_theme_id().'/'.$localpath_theme_resource;
		return $rtn;
	}//path_theme_files()

	/**
	 * テーマリソースのサーバー内部パスを得る
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
	 * 外部ソースをインクルードする(ServerSideInclude)
	 */
	public function ssi( $path_incfile ){
		//	パブリッシュツール(PxCrawlerなど)による静的パブリッシュを前提としたSSI処理機能。
		//	ブラウザで確認した場合は、インクルードを解決したソースを出力し、
		//	パブリッシュツールに対しては、ApacheのSSIタグを出力する。

		if( !strlen( $path_incfile ) ){ return false; }
		$RTN = '';
		$path_incfile = $this->dbh()->get_realpath( $path_incfile );
		if( $this->user()->is_publishtool() ){
			#	パブリッシュツールだったら、SSIタグを出力する。
			$RTN .= $this->ssi_static_tag( $path_incfile );
		}else{
			if( $this->dbh()->is_file( $_SERVER['DOCUMENT_ROOT'].$path_incfile ) && $this->dbh()->is_readable( $_SERVER['DOCUMENT_ROOT'].$path_incfile ) ){
				$RTN .= $this->dbh()->file_get_contents( $_SERVER['DOCUMENT_ROOT'].$path_incfile );
				$RTN = t::convert_encoding($RTN);
			}
		}
		return	$RTN;
	}//ssi();

	/**
	 * パブリッシュ時のSSIタグを出力する。
	 * ssi() からコールされる。
	 */
	private function ssi_static_tag( $path ){
		return '<!--#include virtual="'.htmlspecialchars( $path ).'" -->';
	}//ssi_static_tag()

	/**
	 * PXコマンドを解析する。
	 * @param string URLパラメータ PX に受け取った値
	 * @return array 先頭にPXコマンド名を含むパラメータの配列(入力値をドットで区切ったもの)
	 */
	private function parse_pxcommand( $param ){
		if( !$this->get_conf('system.allow_pxcommands') ){
			//  設定で許可されていない場合は、常に null
			return null;
		}
		if( !strlen( $param ) ){
			//  パラメータ値が付いていなければ、null
			return null;
		}
		return explode( '.' , $param );
	}

	/**
	 * PHP設定をチューニング。
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

		//  ドキュメントルートへカレントディレクトリを移動する。
		chdir( realpath( $conf->path_docroot ) );

		return true;
	}//php_setup();

	/**
	 * コンフィグ値のロード。
	 */
	private function load_conf( $path_mainconf ){
		$conf = array();
		$tmp_conf = parse_ini_file( $path_mainconf , true );
		foreach ($tmp_conf as $key1=>$row1) {
			foreach ($row1 as $key2=>$val) {
				$conf[$key1.'.'.$key2] = $val;
			}
		}
		unset( $tmp_conf , $key1 , $row1 , $key2 , $val );
		return $conf;
	}//load_conf()

	/**
	 * コンフィグ値を出力。
	 */
	public function get_conf( $key ){
		return $this->conf[$key];
	}//get_conf()

	/**
	 * コンフィグファイルのパスを取得する
	 */
	public function get_path_conf(){
		return $this->path_mainconf;
	}

	/**
	 * 全てのコンフィグ値を出力。
	 * @return すべての値が入ったコンフィグの連想配列
	 */
	public function get_conf_all(){
		return $this->conf;
	}//get_conf_all()

	/**
	 * コアライブラリのインスタンス生成。
	 * @return true
	 */
	private function create_core_instances(){
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
	 * @return array
	 */
	public function get_extensions_list(){
		$ary = $this->dbh()->ls( $this->get_conf('paths.px_dir').'_FW/extensions/' );
		$rtn = array();
		foreach( $ary as $row ){
			$ext = t::trimext($row);
			if(!strlen($ext)){continue;}
			array_push( $rtn , $ext );
		}
		return $rtn;
	}//get_extensions_list()

	/**
	 * コアオブジェクト $dbh にアクセスする。
	 * @return $dbhオブジェクト
	 */
	public function &dbh(){ return $this->obj_dbh; }

	/**
	 * コアオブジェクト $error にアクセスする。
	 * @return $errorオブジェクト
	 */
	public function &error(){ return $this->obj_error; }

	/**
	 * コアオブジェクト $req にアクセスする。
	 * @return $reqオブジェクト
	 */
	public function &req(){ return $this->obj_req; }

	/**
	 * コアオブジェクト $site にアクセスする。
	 * @return $siteオブジェクト
	 */
	public function &site(){ return $this->obj_site; }

	/**
	 * コアオブジェクト $theme にアクセスする。
	 * @return $themeオブジェクト
	 */
	public function &theme(){ return $this->obj_theme; }

	/**
	 * コアオブジェクト $user にアクセスする。
	 * @return $userオブジェクト
	 */
	public function &user(){ return $this->obj_user; }

	/**
	 * PxFWのクラスファイルをロードする。
	 * 
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
	 * @return 読み込んだクラス名(string)
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
	 * プラグインオブジェクトを取り出す
	 */
	public function get_plugin_object( $plugin_name ){
		if( !strlen($plugin_name) ){return false;}
		if( !is_object($this->plugin_objects[$plugin_name]) ){
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
	 * PxFWのテーマが定義するクラスファイルをロードする。
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
	 * 現在のアドレスへのhrefを得る
	 * @param array|string $params GETパラメータとして付加する値。連想配列(例：array('key'=>'val','key2'=>'val2'))または文字列(例:'key=val&key2=val2')で指定。
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
	}

	/**
	 * リダイレクトする
	 */
	public function redirect( $redirect_to , $options = array() ){
		while( @ob_end_clean() );

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
	 */
	public function page_notfound(){
		while( @ob_end_clean() );

		header('Status: 404 NotFound.');
		$fin = '';
		$fin .= '<!doctype html>'."\n";
		$fin .= '<html>'."\n";
		$fin .= '<head>'."\n";
		$fin .= '<meta charset="UTF-8" />'."\n";
		$fin .= '<title>404 Not found</title>'."\n";
		$fin .= '</head>'."\n";
		$fin .= '<body>'."\n";
		$fin .= '<p>'."\n";
		$fin .= 'お探しのページは見つかりませんでした。<br />'."\n";
		$fin .= '</p>'."\n";
		$fin .= '</body>'."\n";
		$fin .= '</html>'."\n";
		print $fin;
		exit();
	}//page_notfound()

	/**
	 * Forbidden画面を出力する。
	 */
	public function page_forbidden(){
		while( @ob_end_clean() );

		header('Status: 403 Forbidden.');
		$fin = '';
		$fin .= '<p>'."\n";
		$fin .= 'このページの閲覧権がありません。<br />'."\n";
		$fin .= '</p>'."\n";
		print $this->theme()->bind_contents( $fin );
		exit();
	}//page_forbidden()

	/**
	 * ログイン画面を出力する。
	 */
	public function page_login(){
		while( @ob_end_clean() );

		$fin = '';
		$fin .= '<p>'."\n";
		$fin .= '	ログインしてください。<br />'."\n";
		$fin .= '</p>'."\n";
		$fin .= '<form action="'.t::h($this->theme()->href( $this->req()->get_request_file_path() )).'" method="post">'."\n";
		$fin .= '	<p><input type="text" name="ID" value="'.t::h($this->req()->get_param('ID')).'" /><br /></p>'."\n";
		$fin .= '	<p><input type="password" name="PW" value="" /><br /></p>'."\n";
		$fin .= '	<p><input type="submit" value="送信" /></p>'."\n";
		$fin .= '</form>'."\n";
		print $this->theme()->bind_contents( $fin );
		exit();
	}//redirect()

	/**
	 * ダウンロードファイルを出力する
	 */
	public function download( $bin , $option = array() ){
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

		if( strlen( $option['content-type'] ) ){
			$contenttype = $option['content-type'];
		}else{
			$contenttype = 'x-download/download';
		}
		if( strlen( $contenttype ) ){
			if( strlen( $option['charset'] ) ){
				$contenttype .= '; charset='.$option['charset'];
			}
			@header( 'Content-type: '.$contenttype );
		}

		if( strlen( $bin ) ){
			#	ダウンロードの容量
			@header( 'Content-Length: '.strlen( $bin ) );
		}

		if( strlen( $option['filename'] ) ){
			#	ダウンロードファイル名
			@header( 'Content-Disposition: attachment; filename='.$option['filename'] );
		}

		print $bin;
		exit();
	}//download()

	/**
	 * ディスク上のファイルを標準出力する
	 */
	public function flush_file( $filepath , $option = array() ){
		#--------------------------------------
		#	$filepath => 出力するファイルのパス
		#	$option => オプションを示す連想配列
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

		if( strpos( $_SERVER['HTTP_USER_AGENT'] , 'MSIE' ) ){
			#	MSIE対策
			#	→こんな問題 http://support.microsoft.com/kb/323308/ja
			@header( 'Cache-Control: public' );
			@header( 'Pragma: public' );
		}

		if( strlen( $option['content-type'] ) ){
			$contenttype = $option['content-type'];
		}else{
			$contenttype = 'x-download/download';
		}
		if( strlen( $contenttype ) ){
			if( strlen( $option['charset'] ) ){
				$contenttype .= '; charset='.$option['charset'];
			}
			@header( 'Content-type: '.$contenttype );
		}

		#	ダウンロードの容量
		@header( 'Content-Length: '.filesize( $filepath ) );

		if( strlen( $option['filename'] ) ){
			#	ダウンロードファイル名
			@header( 'Content-Disposition: attachment; filename='.$option['filename'] );
		}

		#	ファイルを出力
		if( !@readfile( $filepath ) ){
			$this->errors->error_log( 'Disable to readfile( [ '.$filepath.' ] )' , __FILE__ , __LINE__ );
			return	false;
		}

		if( $option['delete'] ){
			#	deleteオプションが指定されていたら、
			#	ダウンロード後のファイルを削除する。
			$this->dbh()->rm( $filepath );
		}

		exit();
	}//flush_file()

	/**
	 * アクセスログを記録する。
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