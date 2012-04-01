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

	/**
	 * $pxオブジェクトの初期化。
	 */
	public function __construct( $path_mainconf ){

		//  PHP設定のチューニング
		$this->php_setup();

		//  コンフィグ値のロード
		$this->conf = $this->load_config( $path_mainconf );

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
		$tmp_px_class_name = $this->load_pxclass( 'pxcommands/'.$this->pxcommand[0].'.php' );
		if( $tmp_px_class_name ){
			$obj_pxcommands = new $tmp_px_class_name( $this );
		}
		unset( $tmp_px_class_name );

		@header('Content-type: text/html; charset=UTF-8');//←デフォルトのContent-type。$theme->bind_contents() 内で必要があれば上書き可能。

		$path_content = dirname($_SERVER['SCRIPT_FILENAME']).$this->site()->get_page_info( $this->req()->get_request_file_path() , 'content' );
		if( !is_file($path_content) ){
			$path_content = dirname($_SERVER['SCRIPT_FILENAME']).$this->req()->get_request_file_path();
		}

		//------
		//  拡張子違いのコンテンツを検索
		//  リクエストはmod_rewriteの設定上、*.html でしかこない。
		//  a.html のクエリでも、a.php があれば、a.php を採用できるようにしている。
		$list_extensions = $this->get_extensions_list();
		foreach( $list_extensions as $row_extension ){
			if( is_file($path_content.'.'.$row_extension) ){
				$path_content = $path_content.'.'.$row_extension;
				break;
			}
		}
		//  / 拡張子違いのコンテンツを検索
		//------

		if( is_file( $path_content ) ){
			$extension = strtolower( $this->dbh()->get_extension( $path_content ) );
			$class_name = $this->load_pxclass( 'extensions/'.$extension.'.php' );
			if( $class_name ){
				$obj_extension = new $class_name( &$this );
				$obj_extension->execute( $path_content );
			}else{
				print $this->theme()->bind_contents( '<p>Unknow extension.</p>' );
			}
		}else{
			print $this->theme()->bind_contents( '<p>Content file is not found.</p>' );
		}
		return true;
	}//execute()

	/**
	 * PxFWのインストール先パスを取得する。
	 * @return string ドキュメントルートからのパス(スラッシュ閉じ)
	 */
	public function get_install_path(){
		$rtn = dirname( $_SERVER['SCRIPT_NAME'] );
		$rtn = str_replace('\\','/',$rtn);
		$rtn .= ($rtn!='/'?'/':'');
		return $rtn;
	}

	/**
	 * Smartyオブジェクトを生成する。
	 */
	public function factory_smarty(){
		$path_px_dir = $this->get_conf('paths.px_dir');
		@require_once($path_px_dir.'libs/smarty/Smarty.class.php');
		$smarty = new Smarty;
		//$smarty->force_compile = true;
		$smarty->debugging = false;
		$smarty->caching = true;
		$smarty->cache_lifetime = (60*60*24);//キャッシュの有効期限：24h

		$smarty->config_dir   = $path_px_dir.'configs/';
		$smarty->template_dir = $path_px_dir.'_sys/caches/smarty/templates/';
		$smarty->compile_dir  = $path_px_dir.'_sys/caches/smarty/compiles/';
		$smarty->cache_dir    = $path_px_dir.'_sys/caches/smarty/caches/';

		$smarty->assign('px',&$this);
		$smarty->assign('site',$this->site());
		$smarty->assign('theme',$this->theme());
		$smarty->assign('req',$this->req());
		$smarty->assign('dbh',$this->dbh());
		$smarty->assign('error',$this->error());
		$smarty->assign('user',$this->user());

		return $smarty;
	}

	/**
	 * PXコマンドを解析する。
	 * @param string URLパラメータ PX に受け取った値
	 * @return array 先頭にPXコマンド名を含むパラメータの配列(入力値をドットで区切ったもの)
	 */
	private function parse_pxcommand( $param ){
		if( !strlen( $param ) ){ return null; }
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

		return	true;
	}//php_setup();

	/**
	 * コンフィグ値のロード。
	 */
	private function load_config( $path_mainconf ){
		$conf = array();
		$tmp_conf = parse_ini_file( $path_mainconf , true );
		foreach ($tmp_conf as $key1=>$row1) {
			foreach ($row1 as $key2=>$val) {
				$conf[$key1.'.'.$key2] = $val;
			}
		}
		unset( $tmp_conf , $key1 , $row1 , $key2 , $val );
		return $conf;
	}//load_config()

	/**
	 * コンフィグ値を出力。
	 */
	public function get_conf( $key ){
		return $this->conf[$key];
	}//get_conf()
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
		$this->obj_error = new px_cores_error( &$this );
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/dbh.php' );
		$this->obj_dbh = new px_cores_dbh( &$this );
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/req.php' );
		$this->obj_req = new px_cores_req( &$this );
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/site.php' );
		$this->obj_site = new px_cores_site( &$this );
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/user.php' );
		$this->obj_user = new px_cores_user( &$this );
		require_once( $this->get_conf('paths.px_dir').'_FW/cores/theme.php' );
		$this->obj_theme = new px_cores_theme( &$this );

		return true;
	}//create_core_instances()

	/**
	 * extensionsの一覧を取得する。
	 * @return array
	 */
	private function get_extensions_list(){
		return array( 'html','php','wiki','txt','direct','download' );
	}

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
	 * Pxのクラスファイルをロードする。
	 * 
	 */
	public function load_pxclass($path){
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
		if( !@include_once( $this->get_conf('paths.px_dir').'_FW/'.$path ) ){
			return false;
		}
		if( !class_exists( $class_name ) ){
			return false;
		}
		return $class_name;
	}//load_pxclass()

}

?>