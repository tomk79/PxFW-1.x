<?php

/**
 * tgz形式のファイルの結合・展開
 * Copyright (C)Tomoya Koyanagi.
 * Last Update : 0:19 2008/07/04
 */
class pxplugin_asazuke_resources_tgz{

	private $conf;
	private $path_command_tar;


	/**
	 * コンストラクタ
	 */
	public function __construct( &$px , $path_command_tar ){
		$this->px = &$px;
		$this->path_command_tar = $path_command_tar;
	}

	/**
	 * ZIPメソッドを利用可能か否か確認する
	 */
	public function enable_zip(){
		if( !strlen( $this->path_command_tar ) ){ return false; }
		if( !is_callable( 'exec' ) ){ return false; }
		return	true;
	}

	/**
	 * ファイルまたはディレクトリをZIP圧縮する
	 */
	public function zip( $path_target , $path_zipto ){
		#	$path_target => 圧縮する元ファイル/ディレクトリ
		#	$path_zipto => 作成したzipファイルの保存先パス
		$path_target = $this->px->dbh()->get_realpath($path_target);
		$path_zipto = $this->px->dbh()->get_realpath($path_zipto);

		if( !$this->enable_zip() ){ return false; }

		if( !is_dir( $path_target ) && !is_file( $path_target ) ){
			#	ファイルでもディレクトリでもなければ、ダメ。
			$this->px->error()->error_log( 'ZIP対象['.$path_target.']は、ファイルでもディレクトリでもありません。' );
			return	false;
		}

		#	現在のディレクトリを記憶
		$MEMORY_CDIR = realpath('.');

		$cdto = $path_target;
		if( is_file( $path_target ) ){
			$cdto = dirname( $path_target );
		}

		if( !@chdir( $cdto ) ){
			return	false;
		}

		#--------------------------------------
		#	tarコマンドを実行する
		$command = escapeshellcmd( $this->path_command_tar ).' cvfz '.escapeshellarg( $path_zipto ).' ';
		if( is_dir( $path_target ) ){
			$command .= ' '.'./*';
		}else{
			$command .= ' '.escapeshellarg( './'.basename( $path_target ) );
		}
		$result = @exec( $command );
		#	/ tarコマンドを実行する
		#--------------------------------------

		#	元のディレクトリに戻す
		@chdir( $MEMORY_CDIR );

		if( $result === false ){
			return	false;
		}

		return true;

	}

	/**
	 * ZIPファイルを展開する
	 */
	public function unzip( $path_target , $path_unzipto ){
		#	$path_target => 圧縮する元ファイル/ディレクトリ
		#	$path_unzipto => 作成したzipファイルの保存先パス
		$path_target = $this->px->dbh()->get_realpath($path_target);
		$path_unzipto = $this->px->dbh()->get_realpath($path_unzipto);

		if( !$this->enable_zip() ){ return false; }

		if( !is_file( $path_target ) ){
			#	ファイルじゃなければ、ダメ。
			$this->px->error()->error_log( 'UNZIP対象['.$path_target.']は、ファイルでありません。' );
			return	false;
		}

		if( is_file( $path_unzipto ) ){
			#	展開先がファイルだったらダメ。
			$this->px->error()->error_log( 'UNZIP先['.$path_unzipto.']は、ファイルです。' );
			return	false;
		}

		if( !is_dir( $path_unzipto ) ){
			#	展開先ディレクトリがなかったらダメ。
			$this->px->error()->error_log( 'UNZIP先ディレクトリ['.$path_unzipto.']は、存在しません。' );
			return	false;
		}

		if( !$this->dbh->is_writable( $path_unzipto ) ){
			#	展開先ディレクトリが書き込めなかったらダメ。
			$this->px->error()->error_log( 'UNZIP先ディレクトリ['.$path_unzipto.']は、書き込めません。' );
			return	false;
		}

		#	現在のディレクトリを記憶
		$MEMORY_CDIR = realpath('.');
	
		if( !@chdir( $path_unzipto ) ){
			return	false;
		}

		#--------------------------------------
		#	tarコマンドを実行する
		$command = 'tar zxvf '.escapeshellarg( $path_target ).'';
		$result = @exec( $command );
		#	/ tarコマンドを実行する
		#--------------------------------------

		#	元のディレクトリに戻す
		@chdir( $MEMORY_CDIR );

		if( $result === false ){
			return	false;
		}

		return true;

	}

}

?>