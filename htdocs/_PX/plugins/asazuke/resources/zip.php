<?php

/**
 * zip形式のファイルの結合・展開
 * Copyright (C)Tomoya Koyanagi.
 * Last Update : 0:51 2008/12/08
 */
class pxplugin_asazuke_resources_zip{

	private $px;


	/**
	 * コンストラクタ
	 */
	public function __construct( &$px ){
		$this->px = &$px;
	}

	/**
	 * ZIPメソッドを利用可能か否か確認する
	 */
	public function enable_zip(){
		if( !class_exists( 'ZipArchive' ) ){ return false; }
		return	true;
	}

	/**
	 * ファイルまたはディレクトリをZIP圧縮する
	 */
	public function zip( $path_target , $path_zipto ){
		if( !class_exists( 'ZipArchive' ) ){ return false; }

		$zip = new ZipArchive;
		$res = $zip->open( $path_zipto , ZipArchive::CREATE );
		if( $res !== true ){
			return	false;//失敗
		}

		if( is_dir( $path_target ) ){
			$this->zip_add_directory( $zip , $path_target );
		}elseif( is_file( $path_target ) ){
			$zip->addFile( $path_target , basename( $path_target ) );
		}

		$zip->close();
		return true;
	}

	/**
	 * 再帰的にZIPエントリを追加する
	 */
	private function zip_add_directory( &$zip , $path_base , $path_local = null ){
		if( !is_dir( $path_base.$path_local ) ){ return false; }

		$dirlist = $this->px->dbh()->ls( $path_base.$path_local );
		foreach( $dirlist as $basename ){
			if( $basename == '.' || $basename == '..' ){ continue; }
			if( is_dir( $path_base.$path_local.'/'.$basename ) ){
				#	再帰的に登録
				$this->zip_add_directory( $zip , $path_base , $path_local.'/'.$basename );
			}elseif( is_file( $path_base.$path_local.'/'.$basename ) ){
				#	ファイルは直接追加
				$zippath = preg_replace( '/^\/+/' , '' , $path_local.'/'.$basename );
				$zip->addFile( $path_base.$path_local.'/'.$basename , $zippath );
			}
		}
		return	true;
	}

	/**
	 * ZIPファイルを展開する
	 */
	public function unzip( $path_target , $path_unzipto ){
		if( !class_exists( 'ZipArchive' ) ){ return false; }

		$zip = new ZipArchive;
		$res = $zip->open( $path_target );
		if( $res === true ){
			$zip->extractTo( $path_unzipto );
			$zip->close();
		}else{
			return false;
		}

		return true;
	}
}

?>