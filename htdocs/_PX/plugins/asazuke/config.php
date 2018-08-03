<?php

/**
 * Pickles Crawler 機能設定
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class pxplugin_asazuke_config{

	#--------------------------------------
	#	コアオブジェクト
	private $px;

	#--------------------------------------
	#	設定項目
	private $path_home_dir = null;
		#	PicklesCrawlerのホームディレクトリ設定

	private $localpath_proj_dir = '/proj';		#	プロジェクトディレクトリ
	private $localpath_log_dir = '/logs';		#	ログディレクトリ
	private $localpath_proc_dir = '/proc';		#	プロセス記憶ディレクトリ

	private $conf_crawl_max_url_number = 10000000;
		#	1回のクロールで処理できる最大URL数。
		#	URLなので、画像などのリソースファイルも含まれる。
		#		6:44 2009/08/27 : 100000 から 10000000 に変更

	private $conf_dl_datetime_in_filename = true;
		#	クロール結果を管理画面からダウンロードするときに、
		#	ファイル名にクロール日時を含めるか否か。

	private $conf_download_list_csv_charset = 'SJIS-win';
		#	ダウンロードリストCSVの文字コード。
		#	null を指定すると、mb_internal_encoding() になる。

	private $path_commands = array(
		'php'=>'php' ,
		'tar'=>'tar' ,
	);

	#	/ 設定項目
	#--------------------------------------

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
	}

	/**
	 * 設定値を取得
	 */
	public function get_value( $key ){
		if( !preg_match( '/^[a-zA-Z][a-zA-Z0-9_]*$/' , $key ) ){ return false; }
		$RTN = @eval( 'return $this->conf_'.strtolower( $key ).';' );
		return	$RTN;
	}

	/**
	 * 値を設定
	 */
	public function set_value( $key , $val ){
		if( !preg_match( '/^[a-zA-Z][a-zA-Z0-9_]*$/' , $key ) ){ return false; }
		@eval( '$this->conf_'.strtolower( $key ).' = '.text::data2text( $val ).';' );
		return	true;
	}


	/**
	 * ホームディレクトリの設定
	 */
	public function set_home_dir( $path ){
		if( !strlen( $path ) ){ return false; }
		$path = $this->px->dbh()->get_realpath( $path );
		if( !$this->px->dbh()->is_writable( $path ) ){
			return	false;
		}

		$this->path_home_dir = $path;
		return	true;
	}
	/**
	 * ホームディレクトリの取得
	 */
	public function get_home_dir(){
		return	$this->path_home_dir;
	}

	/**
	 * プロジェクトディレクトリの取得
	 */
	public function get_proj_dir(){
		if( !is_dir( $this->get_home_dir().$this->localpath_proj_dir ) ){
			if( !$this->px->dbh()->mkdir_all( $this->get_home_dir().$this->localpath_proj_dir ) ){
				return	false;
			}
		}
		return	$this->get_home_dir().$this->localpath_proj_dir;
	}

	/**
	 * プログラムディレクトリの取得
	 */
	public function get_program_home_dir(){
		// if( !strlen( $project_id ) ){ return false; }
		$proj_dir = $this->get_proj_dir();
		if( !is_dir( $proj_dir ) ){
			return	false;
		}
		if( !is_dir( $proj_dir.'/prg' ) ){
			if( !$this->px->dbh()->mkdir( $proj_dir.'/prg' ) ){
				return	false;
			}
		}
		// if( strlen( $program_id ) ){
		// 	return	$proj_dir.'/prg/';
		// }
		return	$proj_dir.'/prg';
	}

	/**
	 * ログディレクトリの取得
	 */
	public function get_log_dir(){
		if( !is_dir( $this->get_home_dir().$this->localpath_log_dir ) ){
			if( !$this->px->dbh()->mkdir( $this->get_home_dir().$this->localpath_log_dir ) ){
				return	false;
			}
		}
		return	$this->get_home_dir().$this->localpath_log_dir;
	}

	/**
	 * プロセス記憶ディレクトリの取得
	 */
	public function get_proc_dir(){
		if( !is_dir( $this->get_home_dir().$this->localpath_proc_dir ) ){
			if( !$this->px->dbh()->mkdir( $this->get_home_dir().$this->localpath_proc_dir ) ){
				return	false;
			}
		}
		return	$this->get_home_dir().$this->localpath_proc_dir;
	}


	/**
	 * コマンドのパスを取得する
	 */
	public function get_path_command($cmd){
		if( !strlen( $this->path_commands[$cmd] ) ){
			return false;
		}
		return trim($this->path_commands[$cmd]);
	}

	/**
	 * ファクトリ：プロジェクトモデル
	 */
	public function &factory_model_project(){
		$className = $this->px->load_px_plugin_class( '/asazuke/model/project.php' );
		if( !$className ){
			return	false;
		}
		$obj = new $className( $this->px , $this );
		return	$obj;
	}



	/**
	 * ファクトリ：管理画面インスタンスを取得
	 */
	public function &factory_admin($cmd){
		$className = $this->px->load_px_plugin_class( '/asazuke/admin.php' );
		if( !$className ){
			$this->px->error()->error_log( 'asazukeプラグイン「管理画面」の読み込みに失敗しました。' , __FILE__ , __LINE__ );
			return	false;
		}
		$obj = new $className( $this->px, $this, $cmd );
		return	$obj;
	}


	/**
	 * ファクトリ：クローラインスタンスを取得
	 */
	public function &factory_crawlctrl($cmd){
		$className = $this->px->load_px_plugin_class( '/asazuke/crawlctrl.php' );
		if( !$className ){
			$this->px->error()->error_log( 'asazukeプラグイン「クロールコントローラ」の読み込みに失敗しました。' , __FILE__ , __LINE__ );
			return	false;
		}
		$obj = new $className( $this->px, $this, $cmd );
		return	$obj;
	}

}

?>