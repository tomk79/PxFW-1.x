<?php

/**
 * クロールコントロール
 * Copyright (C)Tomoya Koyanagi.
 */
class pxplugin_PicklesCrawler_crawlctrl{

	private $px;
	private $pcconf;
	private $cmd;

	private $project_model;
	private $program_model;

	private $target_url_list = array();	//実行待ちURLの一覧
	private $done_url_count = 0;		//実行済みURLの数

	private $crawl_starttime = 0;//クロール開始時刻
	private $crawl_endtime = 0;//クロール終了時刻

	private $output_encoding = 'UTF-8';// 出力文字セット

	/**
	 * コンストラクタ
	 */
	public function __construct( &$px, &$pcconf, $cmd ){
		$this->px = &$px;
		$this->pcconf = &$pcconf;
		$this->cmd = &$cmd;

		$this->project_model = &$this->pcconf->factory_model_project();
		$this->project_model->load_project( $this->cmd[1] );
		$this->program_model = $this->project_model->factory_program( $this->cmd[2] );

		if( strlen( $this->px->req()->get_param('crawl_max_url_number') ) ){
			$this->pcconf->set_value( 'crawl_max_url_number' , intval( $this->px->req()->get_param('crawl_max_url_number') ) );
		}

		error_reporting(4);
	}

	/**
	 * ファクトリ：HTTPアクセスオブジェクト
	 */
	private function &factory_httpaccess(){
		$className = $this->px->load_px_plugin_class( '/PicklesCrawler/resources/httpaccess.php' );
		if( !$className ){
			$this->error_log( 'HTTPアクセスオブジェクトのロードに失敗しました。' , __FILE__ , __LINE__ );
			return	$this->exit_process();
		}
		$obj = new $className();
		return	$obj;
	}


	/**
	 * ファクトリ：HTMLメタ情報抽出オブジェクト
	 */
	private function &factory_parsehtmlmetainfo(){
		$className = $this->px->load_px_plugin_class( '/PicklesCrawler/resources/parsehtmlmetainfo.php' );
		if( !$className ){
			$this->error_log( 'HTMLメタ情報抽出オブジェクトのロードに失敗しました。' , __FILE__ , __LINE__ );
			return	$this->exit_process();
		}
		$obj = new $className();
		return	$obj;
	}

	/**
	 * ファクトリ：プログラムオペレータ
	 */
	private function &factory_program_operator( $type , $kind = 'execute' ){
		$className = $this->px->load_px_plugin_class( '/PicklesCrawler/program/'.$type.'/'.$kind.'.php' );
		if( !$className ){
			$this->error_log( 'プログラムオペレータオブジェクト('.$type.'/'.$kind.')のロードに失敗しました。' , __FILE__ , __LINE__ );
			return	$this->exit_process();
		}
		if( $kind == 'execute' ){
			$obj = new $className( $this->px , $this->pcconf , $this->project_model , $this->program_model );
		}elseif( $kind == 'info' ){
			$obj = new $className();
		}else{
			$this->error_log( 'プログラムオペレータオブジェクト('.$type.'/'.$kind.')のインスタンス化に失敗しました。' , __FILE__ , __LINE__ );
			return	$this->exit_process();
		}
		return	$obj;
	}





	#########################################################################################################################################################


	/**
	 * 処理の開始
	 */
	public function start(){
		if( strlen( $this->px->req()->get_param('output_encoding') ) ){
			$this->output_encoding = $this->px->req()->get_param('output_encoding');
		}
		if( !is_null( $this->px->req()->get_param('-f') ) ){
			#	-fオプション(force)が指定されていたら、
			#	アプリケーションロックを無視する。
			$this->unlock();
		}

		while( @ob_end_clean() );//出力バッファをクリア
		@header( 'Content-type: text/plain; charset='.$this->output_encoding );

		if( !strlen( $this->cmd[1] ) ){
			$this->msg( '[ERROR!!] プロジェクトIDが指定されていません。' );
			return	$this->exit_process();
		}
		if( !strlen( $this->cmd[2] ) ){
			$this->msg( '[ERROR!!] プログラムIDが指定されていません。' );
			return	$this->exit_process();
		}

		return	$this->controll();
	}



	#########################################################################################################################################################


	/**
	 * コントローラ
	 */
	private function controll(){

		$project_model = &$this->project_model;
		$program_model = &$this->program_model;

		$this->msg( '---------- Pickles Crawler ----------' );
		$this->msg( 'Copyright (C)Tomoya Koyanagi, All rights reserved.' );
		$this->msg( '-------------------------------------' );
		$this->msg( 'Executing Project ['.$project_model->get_project_name().'] Program ['.$program_model->get_program_name().']....' );
		$this->msg( 'Process ID ['.getmypid().']' );
		$this->msg( 'Start page URL => '.$project_model->get_url_startpage() );
		$this->msg( 'Program Type => '.$program_model->get_program_type() );
		$this->msg( 'crawl_max_url_number => '.$this->pcconf->get_value( 'crawl_max_url_number' ) );
		if( !is_int( $this->pcconf->get_value( 'crawl_max_url_number' ) ) ){
			$this->error_log( 'Config error: crawl_max_url_number is NOT a number.' , __FILE__ , __LINE__ );
			return	$this->exit_process( false );
		}

		#--------------------------------------
		#	ロック中か否かを判断
		if( !$this->lock() ){
			$error_msg = 'This program ['.$program_model->get_program_name().'] is locked.';
			$this->error_log( $error_msg , __FILE__ , __LINE__ );
			return	$this->exit_process( false );
		}

		#--------------------------------------
		#	ダウンロード先のパス内を一旦削除
		$path_dir_download_to = $this->get_path_download_to();
		if( is_dir( $path_dir_download_to ) ){
			$filelist = $this->px->dbh()->ls( $path_dir_download_to );
			if( count( $filelist ) ){
				$this->msg( '--------------------------------------' );
				$this->msg( 'Cleanning directory ['.$path_dir_download_to.']...' );
				set_time_limit(0);
				foreach( $filelist as $filename ){
					if( $filename == '..' || $filename == '.' ){ continue; }
					if( $filename == 'crawl.lock' ){ continue; } //ロックファイルは消しちゃダメ。
					if( !$this->px->dbh()->rm( $path_dir_download_to.'/'.$filename ) ){
						$this->error_log( 'Delete ['.$filename.'] FAILD.' , __FILE__ , __LINE__ );
						return	$this->exit_process();
					}else{
						$this->msg( 'Delete ['.$filename.'] Successful.' );
					}
				}
				set_time_limit(60);
			}
		}

		$this->msg( '--------------------------------------' );
		$this->crawl_starttime = time();
		$this->msg( '*** Start of Crawling --- '.$this->px->dbh()->int2datetime( $this->crawl_starttime ) );
		$this->msg( '' );

		#--------------------------------------
		#	スタートページを登録
		$startpage = $project_model->get_url_startpage();
		$this->msg( 'set ['.$startpage.'] as the Startpage.' );
		$this->add_target_url( $startpage );
		unset( $startpage );
		foreach( $this->project_model->get_urllist_startpages() as $additional_startpage ){
			set_time_limit(30);
			if( $this->add_target_url( $additional_startpage ) ){
				$this->msg( 'add ['.$additional_startpage.'] as the Startpage.' );
			}else{
				$this->msg( 'FAILD to add ['.$additional_startpage.'] as the Startpage.' );
			}
		}
		set_time_limit(30);
		unset( $additional_startpage );

		#	CSVの定義行を保存
		$this->save_executed_url_row(
			array(
				'url'=>'URL' ,
				'title'=>'タイトルタグ' ,
				'description'=>'メタタグ(description)' ,
				'keywords'=>'メタタグ(keywords)' ,
				'save_to'=>'保存先のパス' ,
				'time'=>'アクセス日時' ,
				'content-type'=>'Content-type(HTTP response header)' ,
				'charset'=>'charset(HTTP response header)' ,
				'http_status'=>'HTTP status code' ,
				'body_length'=>'ファイルサイズ' ,
				'last-modified'=>'最終更新日' ,//PxCrawler 0.3.7 追加
				'method'=>'メソッド' ,
				'http_referer'=>'Referer として送信した文字列' ,
				'response_time'=>'要求送信からダウンロード完了までに要した時間(秒)' ,
				'object_error'=>'通信エラー' ,
				'crawl_error'=>'クロールエラー' ,
			)
		);

		#######################################
		#	クロールの設定をログに残す
		$this->save_crawl_settings( $project_model , $program_model );

		#######################################
		#	HTTPリクエストオブジェクトを生成
		$httpaccess = &$this->factory_httpaccess();

		$this->start_sitemap();
			#	サイトマップを作成し始める

		while( 1 ){
			set_time_limit(0);

			#	注釈：	このwhileループは、URLの一覧($this->target_url_list)を処理する途中で、
			#			新しいURLがリストに追加される可能性があるため、
			#			これがゼロ件になるまで処理を継続する必要があるために、用意されたものです。

			$counter = $this->get_count_target_url();
			if( !$counter ){
				$this->msg( 'All URL are done!!' );
				break;
			}

			if( $this->is_request_cancel() ){
				//キャンセル要求を検知したらば、中断して抜ける。
				$cancel_message = 'This operation has been canceled.';
				$program_model->crawl_error( $cancel_message );
				$this->msg( $cancel_message );
				break;
			}

			foreach( $this->target_url_list as $url=>$url_property ){
				if( $this->is_request_cancel() ){
					//キャンセル要求を検知したらば、中断して抜ける。
					$cancel_message = 'This operation has been canceled.';
					$program_model->crawl_error( $cancel_message );
					$this->msg( $cancel_message );
					break 2;
				}

				$this->msg( '-----' );
				$this->msg( 'Downloading ['.$url.']...' );
				$this->touch_lockfile();//ロックファイルを更新

				preg_match( '/^([a-z0-9]+)\:\/\/(.+?)(\/.*)$/i' , $url , $url_info );
				$URL_PROTOCOL = strtolower( $url_info[1] );
				$URL_DOMAIN = strtolower( $url_info[2] );

				#	ダウンロード先のパスを得る
				$path_dir_download_to = $this->get_path_download_to();
				if( $path_dir_download_to === false ){
					$this->error_log( 'ダウンロード先のディレクトリが不正です。' , __FILE__ , __LINE__ );
					$this->target_url_done( $url );
					return	$this->exit_process();
				}

				$path_save_to = $project_model->url2localpath( $url , $url_property['post'] );
				$this->msg( 'save to ['.$path_save_to.']' );

				$this->progress_report( 'url' , $url );
				$this->progress_report( 'saveto' , $path_save_to );

				$fullpath_save_to = $path_dir_download_to.$path_save_to;
				$fullpath_save_to = str_replace( '\\' , '/' , $fullpath_save_to );
				$fullpath_savetmpfile_to = $path_dir_download_to.'/tmp_downloadcontent.tmp';

				clearstatcache();

				#--------------------------------------
				#	HTTPアクセス実行
				$program_model->clear_crawl_error();//前ファイルのクロールエラーを消去
				$httpaccess->clear_request_header();
				$httpaccess->set_user_agent( $program_model->get_program_useragent() );
				$httpaccess->set_http_referer( $url_property['referer'] );
				$httpaccess->set_auth_type( $this->project_model->get_authentication_type() );
				$httpaccess->set_auth_user( $this->project_model->get_basic_authentication_id() );
				$httpaccess->set_auth_pw( $this->project_model->get_basic_authentication_pw() );
				$httpaccess->set_max_redirect_number( 10 );
				$httpaccess->set_auto_redirect_flg( true );
				if( !strlen( $url_property['method'] ) ){
					#	methodのデフォルトはGETとする。
					$url_property['method'] = 'GET';
				}
				$httpaccess->set_method( $url_property['method'] );

				#	★「常に送信するパラメータ」をマージする ( PicklesCrawler 0.3.0 追加 )
				#		「常に送信するパラメータ」は、
				#		「リクエストに含める」がOFFに設定されたパラメータよりも優先する。
				#		よってここでは、「常に送信するパラメータ」を無条件に送信してしまってよい。
				$tmp_url = $url;
				if( strtoupper( $url_property['method'] ) == 'POST' ){
					#	POST
					$url_property['post'] = $program_model->merge_param( $url_property['post'] );
				}else{
					#	GET
					$tmp_url = $program_model->merge_param( $tmp_url );
				}

				if( strtoupper( $url_property['method'] ) == 'POST' && strlen( $url_property['post'] ) ){
					#	method="post" で、postデータがあれば、放り込む。
					$httpaccess->set_post_data( $url_property['post'] );
				}
				$httpaccess->set_url( $tmp_url );
				$httpaccess->save_http_contents( $fullpath_savetmpfile_to );

				$this->px->dbh()->fclose( $fullpath_savetmpfile_to );//ファイルを閉じておかないと、programに追記されちゃう。
				#	/ HTTPアクセス実行
				#--------------------------------------

				clearstatcache();

				#--------------------------------------
				#	実際のあるべき場所へファイルを移動
				$is_savefile = true;
				if( !intval( $httpaccess->get_status_cd() ) || ( intval( $httpaccess->get_status_cd() ) >= 400 && intval( $httpaccess->get_status_cd() ) < 600 ) ){
					#	HTTPステータスコードが 400番台から500番台の間だった場合、
					#	またはHTTPステータスコードを取得できなかった場合、
					#	save404_flg を参照し、true じゃなかったら保存しない。
					if( !$project_model->get_save404_flg() ){
						$is_savefile = false;
					}
				}
				if( $is_savefile ){
					clearstatcache();
					if( is_file( $fullpath_save_to ) ){
						if( !is_writable( $fullpath_save_to ) ){
							$this->error_log( 'ダウンロード先にファイルが存在し、書き込み権限がありません。' , __FILE__ , __LINE__ );
						}
					}elseif( is_dir( $fullpath_save_to ) ){
						$this->error_log( 'ダウンロード先がディレクトリです。' , __FILE__ , __LINE__ );
					}elseif( is_dir( dirname( $fullpath_save_to ) ) ){
						if( !is_writable( dirname( $fullpath_save_to ) ) ){
							$this->error_log( 'ダウンロード先にファイルは存在せず、親ディレクトリに書き込み権限がありません。' , __FILE__ , __LINE__ );
						}
					}else{
						if( !$this->px->dbh()->mkdir_all( dirname( $fullpath_save_to ) ) || !is_dir( dirname( $fullpath_save_to ) ) ){
							$this->error_log( 'ダウンロード先ディレクトリの作成に失敗しました。' , __FILE__ , __LINE__ );
						}
					}

					clearstatcache();
					if( !@rename( $fullpath_savetmpfile_to , $fullpath_save_to ) ){
						$this->error_log( 'ダウンロードしたファイルを正しいパスに配置できませんでした。' , __FILE__ , __LINE__ );
						$program_model->crawl_error( 'FAILD to rename file to; ['.$fullpath_save_to.']' , $url , $fullpath_save_to );
					}

					clearstatcache();
					$fullpath_save_to = realpath( $fullpath_save_to );
					if( $fullpath_save_to === false ){
						$this->error_log( '保存先の realpath() を取得できませんでした。' , __FILE__ , __LINE__ );
					}
				}
				clearstatcache();
				if( is_file( $fullpath_savetmpfile_to ) ){
					@unlink( $fullpath_savetmpfile_to );
				}
				#	/ 実際のあるべき場所へファイルを移動
				#--------------------------------------

				$html_meta_info = array();
				switch( strtolower( $httpaccess->get_content_type() ) ){
					case 'text/html':
					case 'text/xhtml':
					case 'application/xml+xhtml':
						#	サイトマップに情報を追記
						$this->add_sitemap_url( $url );

						#	HTMLのメタ情報を抽出する
						$obj_parsehtmlmetainfo = &$this->factory_parsehtmlmetainfo();
						$obj_parsehtmlmetainfo->execute( $fullpath_save_to );
						$html_meta_info = $obj_parsehtmlmetainfo->get_metadata();
						unset( $obj_parsehtmlmetainfo );
						break;

					default:
						break;
				}

				#--------------------------------------
				#	画面にメッセージを出力
				$this->msg( 'Content-type='.$httpaccess->get_content_type() );
				if( !is_null( $httpaccess->get_content_charset() ) ){
					$this->msg( 'Charset='.$httpaccess->get_content_charset() );
				}
				$this->msg( 'HTTP Method='.$httpaccess->get_method() );
				$this->msg( 'HTTP Status='.$httpaccess->get_status_cd().' - '.$httpaccess->get_status_msg() );
				if( intval( $httpaccess->get_status_cd() ) >= 400 && intval( $httpaccess->get_status_cd() ) < 600 ){
					#	HTTPステータスコードが 400番台から500番台の間だった場合、
					#	クロールエラーログを残す。
					$program_model->crawl_error( 'HTTP Status alert; ['.$httpaccess->get_status_cd().']' , $url , $fullpath_save_to );
				}
				if( !is_null( $httpaccess->get_content_length() ) ){
					$this->msg( 'Body Length='.$httpaccess->get_content_length() );
				}
				if( !is_null( $httpaccess->get_transfer_encoding() ) ){
					$this->msg( 'Transfer Encoding='.$httpaccess->get_transfer_encoding() );
				}
				if( !is_null( $httpaccess->http_response_connection ) ){
					$this->msg( 'Connection='.$httpaccess->http_response_connection );
				}

				$this->msg( 'Response Time='.$httpaccess->get_response_time() );
				#	/ 画面にメッセージを出力
				#--------------------------------------

				#--------------------------------------
				#	完了のメモを残す
				$tmp_crawlerror = '';
				$tmp_crawlerror_list = $program_model->get_crawl_error();
				foreach( $tmp_crawlerror_list as $tmp_crawlerror_line ){
					$tmp_crawlerror .= $tmp_crawlerror_line['errormsg']."\n";
				}
				$this->target_url_done( $url );
				$last_modified = $httpaccess->get_last_modified_timestamp();//リモートコンテンツの更新日時
				if( !is_null( $last_modified ) ){
					$last_modified = date( 'Y-m-d H:i:s' , $last_modified );
				}
				$this->save_executed_url_row(
					array(
						'url'=>$url ,
						'title'=>$html_meta_info['title'] ,
						'description'=>$html_meta_info['description'] ,
						'keywords'=>$html_meta_info['keywords'] ,
						'save_to'=>$path_save_to ,
						'time'=>date('Y/m/d H:i:s') ,
						'content-type'=>$httpaccess->get_content_type() ,
						'charset'=>$httpaccess->get_content_charset() ,
						'http_status'=>$httpaccess->get_status_cd() ,
						'body_length'=>$httpaccess->get_content_length() ,
						'last-modified'=>$last_modified ,
						'method'=>$httpaccess->get_method() ,
						'http_referer'=>$url_property['referer'] ,
						'response_time'=>$httpaccess->get_response_time() ,
						'object_error'=>$httpaccess->get_socket_open_error() ,
						'crawl_error'=>$tmp_crawlerror ,
					)
				);
				unset( $tmp_crawlerror );
				unset( $tmp_crawlerror_list );
				unset( $tmp_crawlerror_line );
				#	/ 完了のメモを残す
				#--------------------------------------

				clearstatcache();
				if( !is_file( $fullpath_save_to ) ){
					#	この時点でダウンロードファイルがあるべきパスに保存されていなければ、
					#	これ以降の処理は不要。次へ進む。
					$this->msg( '処理済件数 '.intval( $this->get_count_done_url() ).' 件.' );
					$this->msg( '残件数 '.count( $this->target_url_list ).' 件.' );
					$this->progress_report( 'progress' , intval( $this->get_count_done_url() ).'/'.count( $this->target_url_list ) );

					$this->msg( '' );
					continue;
				}

				if( preg_match( '/\/$/' , $url ) ){
					#	スラッシュで終わってたら、ファイル名を追加
					if( strlen( $project_model->get_default_filename() ) ){
						$url .= $project_model->get_default_filename();
					}else{
						$url .= 'index.html';
					}
				}

				#--------------------------------------
				#	オペレータをロードして実行
				$operator = &$this->factory_program_operator( $program_model->get_program_type() );
				if( !$operator->execute( $httpaccess , $url , realpath( $fullpath_save_to ) , $url_property ) ){
					$this->error_log( 'FAILD to Executing in operator object.' , __FILE__ , __LINE__ );
					return	$this->exit_process();
				}

				#--------------------------------------
				#	文字コード・改行コード変換
				#	PicklesCrawler 0.3.0 追加
				$this->execute_charset( $path_save_to );

				#--------------------------------------
				#	一括置換処理
				#	PicklesCrawler 0.3.0 追加
				$this->execute_preg_replace( $path_save_to , $url );

				#--------------------------------------
				#	実行結果を取得
				$result = $operator->get_result();
				if( !is_array( $result ) ){
					$this->error_log( '[FATAL ERROR] Operator\'s result is not a Array.' , __FILE__ , __LINE__ );
					return	$this->exit_process();
				}

				foreach( $result as $result_line ){
					$status_cd = intval( $result_line['status'] );
						#↑	※注意：このステータスコードは、HTTPステータスコードではありません。オペレータのステータスです。

					if( $status_cd >= 500 ){
						#	500番台以上
						$this->error_log( '['.$status_cd.'] '.$result_line['usermessage'] , __FILE__ , __LINE__ );
						return	$this->exit_process();
					}elseif( $status_cd >= 400 ){
						#	400番台
						if( $status_cd == 450 ){
							$this->error_log( '['.$status_cd.'] '.$result_line['usermessage'] , __FILE__ , __LINE__ );
							return	$this->exit_process();
						}else{
							$this->msg( '['.$status_cd.'] '.$result_line['usermessage'] );
						}
					}elseif( $status_cd >= 300 ){
						#	300番台
						$this->msg( '['.$status_cd.'] '.$result_line['usermessage'] );
					}elseif( $status_cd >= 200 ){
						#	200番台
						$this->msg( '['.$status_cd.'] '.$result_line['usermessage'] );
					}elseif( $status_cd >= 100 ){
						#	100番台
						if( $status_cd == 100 ){
							#	URL(parameter) を、実行待ちリストに追加
							#	追加してもよいURLか否かは、add_target_url()が勝手に判断する。
							if( $this->add_target_url( $result_line['parameter'] , $result_line['option'] ) ){
								$this->msg( '['.$status_cd.'] Add Param: ['.$result_line['parameter'].'] '.$result_line['usermessage'] );
							}

						}else{
							$this->msg( '['.$status_cd.'] '.$result_line['usermessage'] );
						}
					}else{
						#	100番未満
						$this->msg( '['.$status_cd.'] '.$result_line['usermessage'] );
					}
				}

				$this->msg( '処理済件数 '.intval( $this->get_count_done_url() ).' 件.' );
				$this->msg( '残件数 '.count( $this->target_url_list ).' 件.' );
				$this->progress_report( 'progress' , intval( $this->get_count_done_url() ).'/'.count( $this->target_url_list ) );

				if( $this->get_count_done_url() >= $this->pcconf->get_value( 'crawl_max_url_number' ) ){
					#	処理可能な最大URL数を超えたらおしまい。
					$message_string = 'URL count is OVER '.$this->pcconf->get_value( 'crawl_max_url_number' ).'.';
					$program_model->crawl_error( $message_string );
					$this->msg( $message_string );
					$this->progress_report( 'message' , $message_string );
					break 2;
				}
				$this->msg( '' );
				continue;

			}

		}

		$this->close_sitemap();
			#	サイトマップを閉じる

		unset( $httpaccess );
		#	/ HTTPリクエストオブジェクトを破壊
		#######################################


		#######################################
		#	複製先指定の処理
		$path_copyto = $this->project_model->get_path_copyto();
		if( strlen( $this->program_model->get_path_copyto() ) ){
			//	プログラムに指定があれば上書き
			$path_copyto = $this->program_model->get_path_copyto();
		}
		$copyto_apply_deletedfile_flg = $this->program_model->get_copyto_apply_deletedfile_flg();
		if( strlen( $path_copyto ) ){
			//	1:03 2009/08/27 追加の分岐
			//	有効なコピー先が指定されていたら、コピーする。
			$this->msg( '------' );
			$this->msg( 'コンテンツの複製を開始します。' );
			clearstatcache();
			if( !is_dir( $path_copyto ) ){
				$this->error_log( 'コンテンツの複製先が存在しません。' , __FILE__ , __LINE__ );
			}else{
				preg_match( '/^(https?)\:\/\/([a-zA-Z0-9\-\_\.\:]+)/si' , $this->project_model->get_url_startpage() , $matched );
				$matched[2] = preg_replace( '/\:/' , '_' , $matched[2] );
				$path_copyfrom = realpath( $this->get_path_download_to().'/'.$matched[1].'/'.$matched[2] );
				$this->msg( '複製元パス：'.$path_copyfrom );
				$this->msg( '複製先パス：'.$path_copyto );
				if( strlen( $path_copyfrom ) && is_dir( $path_copyfrom ) ){
					set_time_limit(0);
					if( $this->px->dbh()->copyall( $path_copyfrom , $path_copyto ) ){
						$this->msg( 'コンテンツの複製を完了しました。' );
						if( $copyto_apply_deletedfile_flg ){
							$this->msg( '------' );
							$this->msg( '削除されたファイル/ディレクトリを反映します。' );
							set_time_limit(0);
							if( $this->px->dbh()->compare_and_cleanup( $path_copyto , $path_copyfrom ) ){
								$this->msg( '削除されたファイル/ディレクトリを反映しました。' );
							}else{
								$this->error_log( '削除されたファイル/ディレクトリ反映に失敗しました。' , __FILE__ , __LINE__ );
							}
							set_time_limit(30);
						}
					}else{
						$this->error_log( 'コンテンツの複製に失敗しました。' , __FILE__ , __LINE__ );
					}
					set_time_limit(30);
				}else{
					$this->error_log( '複製元を正しく判断できません。コンテンツのクロールに失敗した可能性があります。' , __FILE__ , __LINE__ );
				}

			}
			$this->msg( '------' );
		}
		#	/ 複製先指定の処理
		#######################################

		return	$this->exit_process();
	}

	/**
	 * 進捗報告
	 */
	protected function progress_report( $key , $cont ){
		#	このメソッドは、
		#	必要に応じて拡張して利用してください。
	}



	#########################################################################################################################################################


	/**
	 * 文字コード・改行コード変換
	 */
	private function execute_charset( $path_save_to ){
		#	PicklesCrawler 0.3.0 追加
		#	このメソッドは、指定されたファイルを開いて、
		#	変換して、そして勝手に保存して閉じます。

		$path_targetfile = realpath( $this->get_path_download_to().$path_save_to );

		$project_model = &$this->project_model;
		$charset = $project_model->get_charset_charset();
		$crlf = $project_model->get_charset_crlf();
		$ext = $project_model->get_charset_ext();

		if( !strlen( $charset ) && !strlen( $crlf ) ){
			#	文字コードも改行コードも指定なしなら、変換処理はない。
			return	true;
		}

		#--------------------------------------
		#	拡張子判定
		if( !strlen( $ext ) ){
			return true;
		}
		$extList = explode(';',$ext);
		$pathinfo = pathinfo( $path_targetfile );
		$is_hit = false;
		foreach( $extList as $extLine ){
			$extLine = trim( $extLine );
			if( !strlen( $extLine ) ){ continue; }
			if( strtolower( $extLine ) == strtolower( $pathinfo['extension'] ) ){
				$is_hit = true;
				break;
			}
		}
		if( !$is_hit ){
			#	ヒットしない拡張子なら、ここでお終い。
			return	true;
		}
		#	/ 拡張子判定
		#--------------------------------------

		clearstatcache();
		$SRC = $this->px->dbh()->file_get_contents( $path_targetfile );

		#--------------------------------------
		#	文字コードを変換
		if( strlen( $charset ) ){
			$charset_to = $charset;
			switch( strtolower( $charset ) ){
				case 'shift_jis':
				case 'sjis':
					$charset_to = 'SJIS-win';
					break;
				case 'euc-jp':
					$charset_to = 'eucJP-win';
					break;
			}
			$SRC = t::convert_encoding( $SRC , $charset_to );
			switch( strtolower( $pathinfo['extension'] ) ){
				case 'html':
				case 'htm':
				case 'shtml':
				case 'shtm':
					$SRC = preg_replace( '/^(<'.'\?xml .*?encoding\=")[A-Za-z0-9\_\-]+(".*?\?'.'>)/i' , '\1'.htmlspecialchars( $charset ).'\2' , $SRC );
					$SRC = preg_replace( '/(content\="\s*(?:[a-zA-Z0-9\-\_]+)\/(?:[a-zA-Z0-9\-\_\+]+)\s*\;\s*charset\=)[A-Za-z0-9\_\-]+(\s*")/is' , '\1'.htmlspecialchars( $charset ).'\2' , $SRC );
						//↑PxCrawler 0.3.2 修正。text/html; と charset= の間に空白文字が入る場合を想定した。
					$SRC = preg_replace( '/(charset\="\s*)[A-Za-z0-9\_\-]+(\s*")/is' , '\1'.htmlspecialchars( $charset ).'\2' , $SRC );
						//↑PxCrawler 0.4.1 追加。HTML5の簡易書式に対応。
					break;
				case 'css':
					$SRC = preg_replace( '/(\@charset[ \t]+")[A-Za-z0-9\_\-]+(")/i' , '\1'.htmlspecialchars( $charset ).'\2' , $SRC );
					break;
			}
		}
		#	/ 文字コードを変換
		#--------------------------------------

		#--------------------------------------
		#	改行コードを変換
		if( strlen( $crlf ) ){
			$src_crlfto = null;
			switch( strtolower( $crlf ) ){
				case 'crlf'://Windows
					$src_crlfto = "\r\n";
					break;
				case 'cr'://Macintosh
					$src_crlfto = "\r";
					break;
				case 'lf'://UNIX/Linux
					$src_crlfto = "\n";
					break;
				default:
					break;
			}
			if( !is_null( $src_crlfto ) ){
				$SRC = preg_replace( '/\r\n|\r|\n/' , $src_crlfto , $SRC );
			}
		}
		#	/ 改行コードを変換
		#--------------------------------------

		$result = $this->px->dbh()->save_file( $path_targetfile , $SRC );
		$this->px->dbh()->fclose( $path_targetfile );
		clearstatcache();
		if( !$result ){
			return	false;
		}
		return	true;
	}


	#########################################################################################################################################################


	/**
	 * 一括置換処理
	 */
	private function execute_preg_replace( $path_save_to , $url ){
		#	PicklesCrawler 0.3.0 追加
		#	このメソッドは、指定されたファイルを開いて、変換して、そして勝手に保存して閉じます。

		$path_targetfile = realpath( $this->get_path_download_to().$path_save_to );
		$parsed_url = parse_url( trim( $url ) );

		$project_model = &$this->project_model;
		$preg_replace_rules = $project_model->get_preg_replace_rules();
		if( !is_array( $preg_replace_rules ) ){
			$preg_replace_rules = array();
		}
		if( !count( $preg_replace_rules ) ){
			#	設定されていなければお終い。
			return true;
		}

		$pathinfo = pathinfo( $path_targetfile );

		$path_dir_download_to = realpath( $this->get_path_download_to() );
		$localpath_targetfile = preg_replace( '/^'.preg_quote( $path_dir_download_to , '/' ).'/' , '' , realpath( $path_targetfile ) );
		if( $path_dir_download_to.$localpath_targetfile != $path_targetfile ){
			#	何か計算が間違っているはず。
			return false;
		}
		$localpath_targetfile = preg_replace( '/\\\\|\//' , '/' , $localpath_targetfile );//ディレクトリの区切り文字をスラッシュに変換
		$path_dir_download_to = $this->px->dbh()->get_realpath( $path_dir_download_to );

		clearstatcache();
		$SRC = $this->px->dbh()->file_get_contents( $path_targetfile );

		#--------------------------------------
		#	置換ルールを一つずつ処理
		foreach( $preg_replace_rules as $rule ){
			#--------------------
			#	対象ファイルか否か判定
			if( !strlen( $rule['ext'] ) ){
				continue;
			}
			$extList = explode( ';' , $rule['ext'] );
			$is_hit = false;
			foreach( $extList as $extLine ){
				$extLine = trim( $extLine );
				if( !strlen( $extLine ) ){ continue; }
				if( strtolower( $extLine ) == strtolower( $pathinfo['extension'] ) ){
					$is_hit = true;
					break;
				}
			}
			if( !$is_hit ){
				#	ヒットしない拡張子なら、ここでお終い。
				continue;
			}

			#	対象パスを検証
			$is_hit = false;
			if( $rule['path'] == '/' ){
				$rule['path'] = '';
			}
			$rule_path = '/'.$parsed_url['scheme'].'/'.$parsed_url['host'];
			if( strlen( $parsed_url['port'] ) ){
				$rule_path .= '_'.$parsed_url['port'];
			}
			$rule_path .= $rule['path'];
			if( $rule_path == $localpath_targetfile ){
				#	ファイル単体で指名だったら無条件にtrue。
				$is_hit = true;
			}elseif( is_dir( $path_dir_download_to.$rule_path ) ){
				#	パス指定がディレクトリだったら
				if( !preg_match( '/^'.preg_quote( $rule_path.'/' , '/' ).'(.*)$/' , $localpath_targetfile , $tmp_preg_matched ) ){
					#	パス指定に含まれるファイルじゃなかったらここでお終い。
					continue;
				}
				if( $rule['dirflg'] ){
					#	ディレクトリ以下再帰的に有効な指定ならこの時点でOK。
					$is_hit = true;
				}elseif( !preg_match( '/\\\\|\//' , $tmp_preg_matched[1] ) ){
					#	ディレクトリ直下のみ有効な指定なら、
					#	$tmp_preg_matched[1] にディレクトリ区切り文字(スラッシュ)が含まれていない場合のみOK。
					$is_hit = true;
				}
			}
			if( !$is_hit ){
				#	ヒットしない拡張子なら、ここでお終い。
				continue;
			}
			unset( $tmp_preg_matched );

			#	/ 対象ファイルか否か判定
			#--------------------

			#	↓置換実行！
			$SRC = @preg_replace( $rule['pregpattern'] , $rule['replaceto'] , $SRC );
		}

		$result = $this->px->dbh()->save_file( $path_targetfile , $SRC );
		$this->px->dbh()->fclose( $path_targetfile );
		clearstatcache();
		if( !$result ){
			return	false;
		}
		return	true;
	}


	#########################################################################################################################################################
	#	その他

	/**
	 * URLを処理待ちリストに追加
	 */
	private function add_target_url( $url , $option = array() ){
		#	アンカーを考慮
		if( strpos( $url , '#' ) ){
			list($url,$anchor) = explode('#',$url,2);
		}

		$url = $this->project_model->optimize_url( $url );

		#--------------------------------------
		#	要求を評価

		if( !preg_match( '/^https?\:\/\//' , $url ) ){ return false; }
			// 定形外のURLは省く
		if( is_array( $this->target_url_list[$url] ) ){ return false; }
			// すでに予約済みだったら省く

		$path_saveto = $this->project_model->url2localpath( $url , $option['post'] );
		$path_dir_download_to = $this->get_path_download_to();
		if( is_dir( $path_dir_download_to.$path_saveto ) ){ return false; }
			// 既に保存済みだったら省く
		if( is_file( $path_dir_download_to.$path_saveto ) ){ return false; }
			// 既に保存済みだったら省く

		if( !$this->project_model->get_send_form_flg() ){
			#	フォームを送信しない設定だったら
			#	PicklesCrawler 0.1.7 追加
			if( strlen( $option['type'] ) && strtolower( $option['type'] ) == 'form' ){
				#	フォームのデータなら追加しない
				return	false;
			}
		}

		#	対象外URLリストを評価
		if( $this->project_model->is_outofsite( $url ) ){
			return	false;
		}

		#	対象範囲とするURLリストを評価
		if( !$this->program_model->is_scope( $url ) ){
			#	対象範囲外だったらやめる
			return	false;
		}

		#	ダウンロードしないURLリストを評価
		if( $this->program_model->is_nodownload( $url ) ){
			#	ダウンロードしない指定があったらやめる
			return	false;
		}

		#--------------------------------------
		#	問題がなければ追加。
		$this->target_url_list[$url] = array();
		$this->target_url_list[$url]['url'] = $url;
		if( strlen( $option['referer'] ) ){
			$this->target_url_list[$url]['referer'] = $option['referer'];
		}

		if( !strlen( $option['method'] ) ){
			$option['method'] = 'GET';
		}
		#	13:44 2008/04/16 追加
		$this->target_url_list[$url]['method'] = $option['method'];

		if( strlen( $option['post'] ) ){
			#	13:44 2008/04/16 追加
			#	post が指定された場合にも、メソッド POST とは限らない。
			$this->target_url_list[$url]['post'] = $option['post'];
		}
		if( strlen( $option['type'] ) ){
			#	13:44 2008/04/16 追加
			#	<a>とか<form>とか<img>とか<style>とか<script>とかの区別をしたい。
			$this->target_url_list[$url]['type'] = $option['type'];
		}

		return	true;
	}

	/**
	 * 現在処理待ちのURL数を取得
	 */
	public function get_count_target_url(){
		return	count( $this->target_url_list );
	}
	/**
	 * URLが処理済であることを宣言
	 */
	private function target_url_done( $url ){
		unset( $this->target_url_list[$url] );
		$this->done_url_count ++;
		return	true;
	}
	/**
	 * 処理済URL数を取得
	 */
	public function get_count_done_url(){
		return	intval( $this->done_url_count );
	}

	/**
	 * ダウンロード先のディレクトリパスを得る
	 */
	private function get_path_download_to(){
		$path = $this->pcconf->get_program_home_dir( $this->cmd[1] , $this->cmd[2] );
		if( !is_dir( $path ) ){ return false; }

		$RTN = realpath( $path ).'/dl';
		if( !is_dir( $RTN ) ){
			if( !$this->px->dbh()->mkdir( $RTN ) ){
				return	false;
			}
		}
		return	$RTN;
	}

	/**
	 * ダウンロードしたURLの一覧に履歴を残す
	 */
	private function save_executed_url_row( $array_csv_line = array() ){
		$path_dir_download_to = realpath( $this->get_path_download_to() );
		if( !is_dir( $path_dir_download_to ) ){ return false; }
		if( !is_dir( $path_dir_download_to.'/__LOGS__' ) ){
			if( !$this->px->dbh()->mkdir( $path_dir_download_to.'/__LOGS__' ) ){
				return	false;
			}
		}

		$csv_charset = mb_internal_encoding();
		if( strlen( $this->pcconf->get_value( 'download_list_csv_charset' ) ) ){
			$csv_charset = $this->pcconf->get_value( 'download_list_csv_charset' );
		}

		#--------------------------------------
		#	行の文字コードを調整
		foreach( $array_csv_line as $lineKey=>$lineVal ){
			if( mb_detect_encoding( $lineVal ) ){
				$array_csv_line[$lineKey] = mb_convert_encoding( $lineVal , mb_internal_encoding() , mb_detect_encoding( $lineVal ) );
			}
		}
		#	/ 行の文字コードを調整
		#--------------------------------------

		$csv_line = $this->px->dbh()->mk_csv( array( $array_csv_line ) , array('charset'=>$csv_charset) );

		error_log( $csv_line , 3 , $path_dir_download_to.'/__LOGS__/download_list.csv' );
		$this->px->dbh()->chmod( $path_dir_download_to.'/__LOGS__/download_list.csv' );

		return	true;
	}//save_executed_url_row();

	/**
	 * クロールの設定をログに残す
	 */
	private function save_crawl_settings( &$project_model , &$program_model ){
		// PicklesCrawler 0.4.2 追加
		$path_dir_download_to = realpath( $this->get_path_download_to() );
		if( !is_dir( $path_dir_download_to ) ){ return false; }
		if( !is_dir( $path_dir_download_to.'/__LOGS__' ) ){
			if( !$this->px->dbh()->mkdir( $path_dir_download_to.'/__LOGS__' ) ){
				return	false;
			}
		}

		$FIN = '';
		$FIN .= '[Project Info]'."\n";
		$FIN .= 'Project ID: '.$project_model->get_project_id()."\n";
		$FIN .= 'Project Name: '.$project_model->get_project_name()."\n";
		$FIN .= 'Start page URL: '.$project_model->get_url_startpage()."\n";
		$FIN .= 'Document root URL: '.$project_model->get_url_docroot()."\n";
		$FIN .= 'Default filename: '.$project_model->get_default_filename()."\n";
		$FIN .= 'Omit filename(s): '.implode( ', ' , $project_model->get_omit_filename() )."\n";
		$FIN .= 'Path convert method: '.$project_model->get_path_conv_method()."\n";
		$FIN .= 'outofsite2url flag: '.($project_model->get_outofsite2url_flg()?'true':'false')."\n";
		$FIN .= 'send unknown params flag: '.($project_model->get_send_unknown_params_flg()?'true':'false')."\n";
		$FIN .= 'send form flag: '.($project_model->get_send_form_flg()?'true':'false')."\n";
		$FIN .= 'parse inline JavaScript flag: '.($project_model->get_parse_jsinhtml_flg()?'true':'false')."\n";
		$FIN .= 'save notfound page flag: '.($project_model->get_save404_flg()?'true':'false')."\n";
		$FIN .= 'path copyto: '.$project_model->get_path_copyto()."\n";
		$FIN .= '(conv)charset: '.$project_model->get_charset_charset()."\n";
		$FIN .= '(conv)crlf: '.$project_model->get_charset_crlf()."\n";
		$FIN .= '(conv)ext: '.$project_model->get_charset_ext()."\n";
		$FIN .= 'Auth type: '.$project_model->get_authentication_type()."\n";
		$FIN .= 'Auth user: '.$project_model->get_basic_authentication_id()."\n";
		$FIN .= 'Auth Password: ********'."\n";
		$FIN .= '------'."\n";
		$FIN .= '[param define]'."\n";
		if( count($project_model->get_param_define_list()) ){
			foreach( $project_model->get_param_define_list() as $paramname ){
				$FIN .= $paramname.': '.($project_model->is_param_allowed($paramname)?'true':'false')."\n";
			}
		}else{
			$FIN .= '(no entry)'."\n";
		}
		$FIN .= '------'."\n";
		$FIN .= '[rewriterules]'."\n";
		if( count($project_model->get_localfilename_rewriterules()) ){
			foreach( $project_model->get_localfilename_rewriterules() as $key=>$rule ){
				$FIN .= '**** '.$key.' ****'."\n";
				$FIN .= 'priority =>      '.$rule['priority']."\n";
				$FIN .= 'before =>        '.$rule['before']."\n";
				$FIN .= 'requiredparam => '.$rule['requiredparam']."\n";
				$FIN .= 'after =>         '.$rule['after']."\n";
			}
		}else{
			$FIN .= '(no entry)'."\n";
		}
		$FIN .= '------'."\n";
		$FIN .= '[preg_replace rules]'."\n";
		if( count($project_model->get_preg_replace_rules()) ){
			foreach( $project_model->get_preg_replace_rules() as $key=>$rule ){
				$FIN .= '**** '.$key.' ****'."\n";
				$FIN .= 'priority =>    '.$rule['priority']."\n";
				$FIN .= 'pregpattern => '.$rule['pregpattern']."\n";
				$FIN .= 'replaceto =>   '.$rule['replaceto']."\n";
				$FIN .= 'path =>        '.$rule['path']."\n";
				$FIN .= 'dirflg =>      '.$rule['dirflg']."\n";
				$FIN .= 'ext =>         '.$rule['ext']."\n";
			}
		}else{
			$FIN .= '(no entry)'."\n";
		}
		$FIN .= '------'."\n";
		$FIN .= '[URLs as out of site]'."\n";
		if( count($project_model->get_urllist_outofsite()) ){
			foreach( $project_model->get_urllist_outofsite() as $outofsite ){
				$FIN .= $outofsite."\n";
			}
		}else{
			$FIN .= '(no entry)'."\n";
		}
		$FIN .= '------'."\n";
		$FIN .= '[additional start pages]'."\n";
		if( count($project_model->get_urllist_startpages()) ){
			foreach( $project_model->get_urllist_startpages() as $additional_startpage ){
				$FIN .= $additional_startpage."\n";
			}
		}else{
			$FIN .= '(no entry)'."\n";
		}
		$FIN .= ''."\n";
		$FIN .= '--------------------------------------'."\n";
		$FIN .= '[Program Info]'."\n";
		$FIN .= 'Program ID: '.$program_model->get_program_id()."\n";
		$FIN .= 'Program Name: '.$program_model->get_program_name()."\n";
		$FIN .= 'Program Type: '.$program_model->get_program_type()."\n";
		$FIN .= 'Params: '.$program_model->get_program_param()."\n";
		$FIN .= 'HTTP_USER_AGENT: '.$program_model->get_program_useragent()."\n";
		$FIN .= 'path copyto: '.$program_model->get_path_copyto()."\n";
		$FIN .= 'path copyto (apply deleted file flag): '.($program_model->get_copyto_apply_deletedfile_flg()?'true':'false')."\n";
		$FIN .= '------'."\n";
		$FIN .= '[URLs scope]'."\n";
		if( count($program_model->get_urllist_scope()) ){
			foreach( $program_model->get_urllist_scope() as $row ){
				$FIN .= $row."\n";
			}
		}else{
			$FIN .= '(no entry)'."\n";
		}
		$FIN .= '------'."\n";
		$FIN .= '[URLs out of scope]'."\n";
		if( count($program_model->get_urllist_nodownload()) ){
			foreach( $program_model->get_urllist_nodownload() as $row ){
				$FIN .= $row."\n";
			}
		}else{
			$FIN .= '(no entry)'."\n";
		}
		$FIN .= ''."\n";
		$FIN .= '--------------------------------------'."\n";
		$FIN .= '[Other Info]'."\n";
		$FIN .= 'Process ID: '.getmypid()."\n";
		$FIN .= 'crawl_max_url_number: '.$this->pcconf->get_value( 'crawl_max_url_number' )."\n";
		$FIN .= ''."\n";

		error_log( $FIN , 3 , $path_dir_download_to.'/__LOGS__/settings.txt' );
		$this->px->dbh()->chmod( $path_dir_download_to.'/__LOGS__/settings.txt' );

		return	true;
	}//save_crawl_settings();

	/**
	 * サイトマップXMLを保存する系: 先頭
	 */
	private function start_sitemap(){
		$path_dir_download_to = realpath( $this->get_path_download_to() );
		if( !is_dir( $path_dir_download_to ) ){ return false; }
		if( !is_dir( $path_dir_download_to.'/__LOGS__' ) ){
			if( !$this->px->dbh()->mkdir( $path_dir_download_to.'/__LOGS__' ) ){
				return	false;
			}
		}

		$LINE = '';
		$LINE .= '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'."\n";
		$LINE .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

		error_log( $LINE , 3 , $path_dir_download_to.'/__LOGS__/sitemap.xml' );
		$this->px->dbh()->chmod( $path_dir_download_to.'/__LOGS__/sitemap.xml' );

		return	true;
	}
	/**
	 * サイトマップXMLを保存する系: URLを追加
	 */
	private function add_sitemap_url( $url ){
		$path_dir_download_to = realpath( $this->get_path_download_to() );
		if( !is_dir( $path_dir_download_to ) ){ return false; }
		if( !is_dir( $path_dir_download_to.'/__LOGS__' ) ){
			if( !$this->px->dbh()->mkdir( $path_dir_download_to.'/__LOGS__' ) ){
				return	false;
			}
		}

		$LINE = '';
		$LINE .= '	<url>'."\n";
		$LINE .= '		<loc>'.htmlspecialchars( $url ).'</loc>'."\n";
		$LINE .= '		<lastmod>'.htmlspecialchars( date( 'Y-m-d' ) ).'</lastmod>'."\n";
#		$LINE .= '		<changefreq></changefreq>'."\n";
#		$LINE .= '		<priority></priority>'."\n";
		$LINE .= '	</url>'."\n";

		error_log( $LINE , 3 , $path_dir_download_to.'/__LOGS__/sitemap.xml' );

		return	true;
	}
	/**
	 * サイトマップXMLを保存する系: 閉じる
	 */
	private function close_sitemap(){
		$path_dir_download_to = realpath( $this->get_path_download_to() );
		if( !is_dir( $path_dir_download_to ) ){ return false; }
		if( !is_dir( $path_dir_download_to.'/__LOGS__' ) ){
			if( !$this->px->dbh()->mkdir( $path_dir_download_to.'/__LOGS__' ) ){
				return	false;
			}
		}

		$LINE = '';
		$LINE .= '</urlset>';

		error_log( $LINE , 3 , $path_dir_download_to.'/__LOGS__/sitemap.xml' );
		$this->px->dbh()->chmod( $path_dir_download_to.'/__LOGS__/sitemap.xml' );

		return	true;
	}



	/**
	 * 開始と終了の時刻を保存する
	 */
	private function save_start_and_end_datetime( $start_time , $end_time ){
		$path_dir_download_to = realpath( $this->get_path_download_to() );
		$CONTENT = '';
		$CONTENT .= $this->px->dbh()->int2datetime( $start_time );
		$CONTENT .= ' --- ';
		$CONTENT .= $this->px->dbh()->int2datetime( $end_time );
		$result = $this->px->dbh()->save_file( $path_dir_download_to.'/__LOGS__/datetime.txt' , $CONTENT );
		$this->px->dbh()->fclose( $path_dir_download_to.'/__LOGS__/datetime.txt' );
		return	$result;
	}

	/**
	 * エラーログを残す
	 */
	private function error_log( $msg , $file = null , $line = null ){
		$this->px->error()->error_log( $msg , $file , $line );
		$this->msg( '[--ERROR!!--] '.$msg );
		return	true;
	}
	/**
	 * メッセージを出力する
	 */
	private function msg( $msg ){
		$msg = t::convert_encoding( $msg , $this->output_encoding , mb_internal_encoding() );
		if( $this->px->req()->is_cmd() ){
			print	$msg."\n";
		}else{
			print	$msg."\n";
		}
		flush();
		return	true;
	}

	/**
	 * プロセスを終了する
	 */
	private function exit_process( $is_unlock = true ){
		if( $is_unlock ){
			if( !$this->unlock() ){
				$this->error_log( 'FAILD to unlock!' , __FILE__ , __LINE__ );
			}
		}
		$this->crawl_endtime = time();
		$this->msg( '*** Exit --- '.$this->px->dbh()->int2datetime( $this->crawl_endtime ) );
		$this->save_start_and_end_datetime( $this->crawl_starttime , $this->crawl_endtime );//←開始、終了時刻の記録
		exit;
		//return	$this->px->theme()->print_and_exit( '' );
	}


	###################################################################################################################

	/**
	 * キャンセルリクエスト
	 */
	public function request_cancel(){
		$path = realpath( $this->get_path_download_to() ).'/__LOGS__/cancel.request';
		if( !is_dir( dirname( $path ) ) ){
			return	false;
		}
		if( is_file( $path ) && !is_writable( $path ) ){
			return	false;
		}elseif( !is_writable( dirname( $path ) ) ){
			return	false;
		}
		$this->px->dbh()->save_file( $path , 'Cancel request: '.date('Y-m-d H:i:s')."\n" );
		$this->px->dbh()->fclose( $path );
		return	true;
	}
	private function is_request_cancel(){
		$path = realpath( $this->get_path_download_to() ).'/__LOGS__/cancel.request';
		if( is_file( $path ) ){
			return	true;
		}
		return	false;
	}
	public function delete_request_cancel(){
		$path = realpath( $this->get_path_download_to() ).'/__LOGS__/cancel.request';
		if( !is_file( $path ) ){
			return	true;
		}elseif( !is_writable( $path ) ){
			return	false;
		}
		return	$this->px->dbh()->rm( $path );
	}


	###################################################################################################################
	#	アプリケーションロック

	/**
	 * アプリケーションをロックする
	 */
	private function lock(){
		$lockfilepath = $this->get_path_lockfile();

		if( !@is_dir( dirname( $lockfilepath ) ) ){
			$this->px->dbh()->mkdir_all( dirname( $lockfilepath ) );
		}

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		if( @is_file( $lockfilepath ) ){
			#	ロックファイルが存在したら、
			#	ファイルの更新日時を調べる。
			if( @filemtime( $lockfilepath ) > time() - (60*60) ){
				#	最終更新日時が 60分前 よりも未来ならば、
				#	このロックファイルは有効とみなす。
				return	false;
			}
		}

		$result = $this->px->dbh()->save_file( $lockfilepath , 'This lockfile created at: '.date( 'Y-m-d H:i:s' , time() ).'; Process ID: ['.getmypid().'];'."\n" );
		$this->px->dbh()->fclose( $lockfilepath );
		return	$result;
	}

	/**
	 * ロックファイルの更新日時を更新する。
	 */
	private function touch_lockfile(){
		$lockfilepath = $this->get_path_lockfile();

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		touch( $lockfilepath );
		return	true;
	}

	/**
	 * アプリケーションロックを解除する
	 */
	private function unlock(){
		$lockfilepath = $this->get_path_lockfile();

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		return	$this->px->dbh()->rm( $lockfilepath );
	}

	/**
	 * ロックファイルのパスを返す
	 */
	private function get_path_lockfile(){
		return	realpath( $this->get_path_download_to() ).'/crawl.lock';
	}

}

?>