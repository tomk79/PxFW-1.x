<?php

/**
 * インポート・エクスポート
 * Copyright (C)Tomoya Koyanagi.
 * Last Update : 0:52 2010/08/25
 */
class pxplugin_asazuke_resources_io{

	private $px;
	private $pcconf;

	/**
	 * コンストラクタ
	 */
	public function __construct( &$px, &$pcconf ){
		$this->px = &$px;
		$this->pcconf = &$pcconf;
	}

	/**
	 * エクスポートファイルを作成する
	 */
	public function mk_export_file( $ziptype , $options = array() ){

		#	エクスポートを実行
		if( !$this->local_export( $options ) ){
			return false;
		}

		$path_export_dir = $this->pcconf->get_home_dir().'/_export/';

		$download_content_path = $path_export_dir.'tmp/';
		$download_zipto_path = $path_export_dir.'PxCrawer_export_'.date('Ymd_His');
		if( !is_dir( $download_content_path ) ){
			return false;//←圧縮対象が存在しません。
		}

		if( strtolower($ziptype) == 'tgz' && strlen( $this->pcconf->get_path_command('tar') ) ){
			#	tarコマンドが使えたら(UNIXのみ)
			$className = $this->px->load_px_plugin_class( '/asazuke/resources/tgz.php' );
			if( !$className ){
				$this->px->error()->error_log( 'tgzライブラリのロードに失敗しました。' , __FILE__ , __LINE__ );
				return false;
			}
			$obj_tgz = new $className( $this->px, $this->pcconf->get_path_command('tar') );

			if( !$obj_tgz->zip( $download_content_path , $download_zipto_path.'.tgz' ) ){
				return false;//圧縮に失敗しました。
			}

			if( !is_file( $download_zipto_path.'.tgz' ) ){
				return false;//圧縮されたアーカイブファイルは現在は存在しません。
			}

			$download_zipto_path = $download_zipto_path.'.tgz';

		}elseif( strtolower($ziptype) == 'zip' && class_exists( 'ZipArchive' ) ){
			#	ZIP関数が有効だったら
			$className = $this->px->load_px_plugin_class( '/asazuke/resources/zip.php' );
			if( !$className ){
				$this->px->error()->error_log( 'zipライブラリのロードに失敗しました。' , __FILE__ , __LINE__ );
				return false;
			}
			$obj_zip = new $className( $this->px );

			if( !$obj_zip->zip( $download_content_path , $download_zipto_path.'.zip' ) ){
				return false;//圧縮に失敗しました。
			}

			if( !is_file( $download_zipto_path.'.zip' ) ){
				return false;//圧縮されたアーカイブファイルは現在は存在しません。
			}

			$download_zipto_path = $download_zipto_path.'.zip';

		}

		if( is_file( $download_zipto_path ) ){
			return $download_zipto_path;
		}

		return false;
	}// mk_export_file()

	/**
	 * エクスポートデータを作成
	 */
	private function local_export( $options = array() ){
		$path_export_dir = $this->pcconf->get_home_dir().'/_export/';

		$this->px->dbh()->rm( $path_export_dir );
		$this->px->dbh()->mkdir_all( $path_export_dir );
		$this->px->dbh()->mkdir_all( $path_export_dir.'tmp/' );

		$projList = $this->px->dbh()->ls( $this->pcconf->get_home_dir().'/proj/' );
		foreach( $projList as $project_id ){
			if( @count( $options['project'] ) && !$options['project'][$project_id] ){
				continue;
			}
			$this->px->dbh()->mkdir_all( $path_export_dir.'tmp/'.$project_id.'/' );
			$this->local_export_project(
				$this->pcconf->get_home_dir().'/proj/'.$project_id.'/' ,
				$path_export_dir.'tmp/'.$project_id.'/'
			);
		}

		return true;
	}//local_export()

	/**
	 * プロジェクトをエクスポートフォルダにコピーする
	 */
	private function local_export_project( $from , $to ){
		$projFileList = $this->px->dbh()->ls( $from );
		foreach( $projFileList as $project_filename ){
			$tmp_path = $from.$project_filename;
			if( is_dir( $tmp_path ) ){
				$this->px->dbh()->mkdir_all( $to.$project_filename.'/' );
				if( $project_filename == 'prg' ){
					$projPrgList = $this->px->dbh()->ls( $from.$project_filename.'/' );
					foreach( $projPrgList as $program_id ){
						$this->px->dbh()->mkdir_all( $to.$project_filename.'/'.$program_id.'/' );
						$result = $this->local_export_program(
							$from.$project_filename.'/'.$program_id.'/' ,
							$to.$project_filename.'/'.$program_id.'/'
						);
					}
				}
			}elseif( is_file( $tmp_path ) ){
				$this->px->dbh()->copy(
					$tmp_path ,
					$to.$project_filename
				);
			}
		}
		return true;
	}// local_export_project()

	/**
	 * プログラムをエクスポートフォルダにコピーする
	 */
	private function local_export_program( $from , $to ){
		if( !is_dir( $from ) ){ return false; }
		$from = $this->px->dbh()->get_realpath( $from ).'/';
		if( !is_dir( $to ) ){ return false; }
		$to = $this->px->dbh()->get_realpath( $to ).'/';

		$prgFileList = $this->px->dbh()->ls( $from );
		foreach( $prgFileList as $prgFile ){
			if( is_dir( $from.$prgFile ) ){
				$this->px->dbh()->mkdir($to.$prgFile);
			}elseif( is_file( $from.$prgFile ) ){
				$this->px->dbh()->copy(
					$from.$prgFile ,
					$to.$prgFile
				);
			}
		}

		return true;
	}// local_export_program()

}

?>