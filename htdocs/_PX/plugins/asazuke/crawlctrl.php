<?php

/**
 * クロールコントロール
 * Copyright (C)Tomoya Koyanagi.
 * Last Update: 12:53 2011/08/28
 */
class pxplugin_asazuke_crawlctrl{

	private $px;
	private $pcconf;
	private $cmd;

	private $project_model;
	private $program_model;

	private $target_path_list = array();	//実行待ちURLの一覧
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
		$this->project_model->load_project();
		$this->program_model = $this->project_model->factory_program();

		if( strlen( $this->px->req()->get_param('crawl_max_url_number') ) ){
			$this->pcconf->set_value( 'crawl_max_url_number' , intval( $this->px->req()->get_param('crawl_max_url_number') ) );
		}

		error_reporting(4);
	}

	/**
	 * ファクトリ：オペレータ：コンテンツ
	 */
	private function &factory_contents_operator(){
		$className = $this->px->load_px_plugin_class( '/asazuke/operator/contents.php' );
		if( !$className ){
			$this->error_log( 'コンテンツオペレータのロードに失敗しました。' , __FILE__ , __LINE__ );
			return	$this->exit_process();
		}
		$obj = new $className( $this->px, $this->project_model );
		return	$obj;
	}


	/**
	 * ファクトリ：オペレータ：サイトマップ
	 */
	private function &factory_sitemap_operator(){
		$className = $this->px->load_px_plugin_class( '/asazuke/operator/sitemap.php' );
		if( !$className ){
			$this->error_log( 'サイトマップオペレータのロードに失敗しました。' , __FILE__ , __LINE__ );
			return	$this->exit_process();
		}
		$path_sitemap_csv = realpath( $this->get_path_download_to() ).'/sitemaps/sitemap.csv';
		$obj = new $className( $this->px, $this->project_model, $path_sitemap_csv );
		return	$obj;
	}


	/**
	 * ファクトリ：HTTPアクセスオブジェクト
	 */
	private function &factory_httpaccess(){
		$className = $this->px->load_px_plugin_class( '/asazuke/resources/httpaccess.php' );
		if( !$className ){
			$this->error_log( 'HTTPアクセスオブジェクトのロードに失敗しました。' , __FILE__ , __LINE__ );
			return	$this->exit_process();
		}
		$obj = new $className();
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

		return	$this->controll();
	}



	#########################################################################################################################################################


	/**
	 * コントローラ
	 */
	private function controll(){

		$project_model = &$this->project_model;
		$program_model = &$this->program_model;

		$this->msg( '---------- asazuke ----------' );
		$this->msg( 'Copyright (C)Tomoya Koyanagi, All rights reserved.' );
		$this->msg( '-------------------------------------' );
		$this->msg( 'Process ID ['.getmypid().']' );
		$this->msg( 'Document root path => '.$project_model->get_path_docroot() );
		$this->msg( 'Start page path => '.$project_model->get_path_startpage() );
		$this->msg( 'Accept HTML file max size => '.$project_model->get_accept_html_file_max_size() );
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
		$startpage = $project_model->get_path_startpage();
		$this->msg( 'set ['.$startpage.'] as the Startpage.' );

		$this->add_target_path( $startpage );
		unset( $startpage );

		// 対象のファイルをスキャンして、スクレイピング対象に追加
		$this->scan_starting_files($project_model);
		set_time_limit(30);


		#	CSVの定義行を保存
		$this->save_executed_url_row(
			array(
				'url'=>'Path' ,
				'errors'=>'スクレイプエラー' ,
				'original_filesize'=>'オリジナルファイルサイズ' ,
				'extension'=>'拡張子' ,
				'title'=>'タイトル' ,
				'title:replace_pattern'=>'タイトル置換パターン名' ,
				'main_contents:pattern'=>'メインコンテンツ抽出パターン名' ,
				'sub_contents:pattern'=>'サブコンテンツ抽出パターン名' ,
				'replace_strings'=>'文字列置換パターン名' ,
				'dom_convert'=>'DOM置換パターン名' ,
				'time'=>'日時' ,
			)
		);

		#######################################
		#	クロールの設定をログに残す
		$this->save_crawl_settings();


		#	サイトマップを作成し始める
		$this->start_sitemap();

		while( 1 ){
			set_time_limit(0);

			#	注釈：	このwhileループは、URLの一覧($this->target_path_list)を処理する途中で、
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

			foreach( $this->target_path_list as $url=>$url_property ){
				if( $this->is_request_cancel() ){
					//キャンセル要求を検知したらば、中断して抜ける。
					$cancel_message = 'This operation has been canceled.';
					$program_model->crawl_error( $cancel_message );
					$this->msg( $cancel_message );
					break 2;
				}

				$this->msg( '-----' );
				$this->msg( 'Executing ['.$url.']...' );
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

				// $path_save_to = $project_model->url2localpath( $url , $url_property['post'] );
				$path_save_to = '/htdocs'.$url;
				$this->msg( 'save to ['.$path_save_to.']' );

				$this->progress_report( 'url' , $url );
				$this->progress_report( 'saveto' , $path_save_to );

				$fullpath_save_to = $path_dir_download_to.$path_save_to;
				$fullpath_save_to = str_replace( '\\' , '/' , $fullpath_save_to );
				$fullpath_savetmpfile_to = $path_dir_download_to.'/tmp_downloadcontent.tmp';

				$fullpath_from = $this->px->dbh()->get_realpath($project_model->get_path_docroot().$url);

				clearstatcache();


				// オリジナルを、一時ファイルにコピー
				$original_filesize = filesize($fullpath_from);
				$this->msg( 'original file size : '.$original_filesize.' byte(s)' );
				if( !$this->px->dbh()->copy( $fullpath_from, $fullpath_savetmpfile_to ) ){
					$this->error_log( 'クロール対象のファイル ['.$url.'] を一時ファイルに保存できませんでした。' , __FILE__ , __LINE__ );
					$program_model->crawl_error( 'FAILD to copy file to; ['.$fullpath_save_to.']' , $url , $fullpath_save_to );
				}

				clearstatcache();

				// オペレータを準備
				$obj_contents_operator = $this->factory_contents_operator();
				$obj_sitemap_operator = $this->factory_sitemap_operator();

				// ----------
				// スクレイピングしてサイトマップを追記
				$obj_sitemap_operator->scrape($url, $fullpath_savetmpfile_to);
				// ----------

				#--------------------------------------
				#	実際のあるべき場所へファイルを移動
				#		(=>コンテンツのスクレイピングを実施)
				$is_savefile = true;
				if( !is_file( $fullpath_savetmpfile_to ) ){
					$is_savefile = false;
				}
				if( $is_savefile ){
					clearstatcache();
					if( is_file( $fullpath_save_to ) ){
						if( !is_writable( $fullpath_save_to ) ){
							$this->error_log( 'コンテンツ設置先にファイルが存在し、書き込み権限がありません。' , __FILE__ , __LINE__ );
						}
					}elseif( is_dir( $fullpath_save_to ) ){
						$this->error_log( 'コンテンツ設置先がディレクトリです。' , __FILE__ , __LINE__ );
					}elseif( is_dir( dirname( $fullpath_save_to ) ) ){
						if( !is_writable( dirname( $fullpath_save_to ) ) ){
							$this->error_log( 'コンテンツ設置先にファイルは存在せず、親ディレクトリに書き込み権限がありません。' , __FILE__ , __LINE__ );
						}
					}else{
						if( !$this->px->dbh()->mkdir_all( dirname( $fullpath_save_to ) ) || !is_dir( dirname( $fullpath_save_to ) ) ){
							$this->error_log( 'コンテンツ設置先ディレクトリの作成に失敗しました。' , __FILE__ , __LINE__ );
						}
					}

					clearstatcache();

					if( !$obj_contents_operator->scrape( $url, $fullpath_savetmpfile_to, $fullpath_save_to ) ){
						$this->error_log( 'コンテンツのスクレイピングに失敗しました。' , __FILE__ , __LINE__ );
						$program_model->crawl_error( 'FAILD to scraping file; ['.$fullpath_save_to.']' , $url , $fullpath_save_to );
					}
					if( !unlink($fullpath_savetmpfile_to) ){
						$this->error_log( '一時ファイルの削除に失敗しました。' , __FILE__ , __LINE__ );
						$program_model->crawl_error( 'FAILD to delete temporary file to; ['.$fullpath_savetmpfile_to.']' , $url , $fullpath_savetmpfile_to );
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

				// 結果報告を受け取る
				$result_cont = $obj_contents_operator->get_result();
				$result_sitemap = $obj_sitemap_operator->get_result();
				// var_dump($result_cont);
				// var_dump($result_sitemap);

				#--------------------------------------
				#	画面にメッセージを出力
				$this->msg( 'title: ['.$result_sitemap['title'].']' );
				$this->msg( 'title:replace_pattern: ['.$result_sitemap['title:replace_pattern'].']' );
				$this->msg( 'main contents pattern: ['.$result_cont['main_contents:pattern'].']' );
				if( count($result_cont['sub_contents:pattern']) ){
					$this->msg( 'sub contents pattern: ['.implode(', ', $result_cont['sub_contents:pattern']).']' );
				}
				if( count($result_cont['replace_strings']) ){
					$this->msg( 'replace strings: ['.implode(', ', $result_cont['replace_strings']).']' );
				}
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
				$this->save_executed_url_row(
					array(
						'url'=>$url ,
						'errors'=>$result_cont['errors'] ,
						'original_filesize'=>$original_filesize ,
						'extension'=>$this->px->dbh()->get_extension($url) ,
						'title'=>$result_sitemap['title'] ,
						'title:replace_pattern'=>$result_sitemap['title:replace_pattern'] ,
						'main_contents:pattern'=>$result_cont['main_contents:pattern'] ,
						'sub_contents:pattern'=>implode(', ', $result_cont['sub_contents:pattern']) ,
						'replace_strings'=>implode(', ', $result_cont['replace_strings']) ,
						'dom_convert'=>implode(', ', $result_cont['dom_convert']) ,
						'time'=>date('Y/m/d H:i:s') ,
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
					$this->msg( '残件数 '.count( $this->target_path_list ).' 件.' );
					$this->progress_report( 'progress' , intval( $this->get_count_done_url() ).'/'.count( $this->target_path_list ) );

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


				$this->msg( '処理済件数 '.intval( $this->get_count_done_url() ).' 件.' );
				$this->msg( '残件数 '.count( $this->target_path_list ).' 件.' );
				$this->progress_report( 'progress' , intval( $this->get_count_done_url() ).'/'.count( $this->target_path_list ) );

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

		return	$this->exit_process();
	}

	/**
	 * 進捗報告
	 */
	protected function progress_report( $key , $cont ){
		#	このメソッドは、
		#	必要に応じて拡張して利用してください。
	}


	/**
	 * 対象ページをスキャンしてスタートページに登録する
	 */
	private function scan_starting_files( $project_model, $path = null ){
		if(!strlen($path)){
			$path = '';
		}
		$path_base = $project_model->get_path_docroot();
		if( !strlen($path_base) ){ return false; }
		if( !is_dir($path_base.$path) ){
			return false;
		}

		// スキャン開始
		$ls = $this->px->dbh()->ls( $path_base.$path );
		foreach( $ls as $base_name ){
			set_time_limit(30);
			if( is_dir( $path_base.$path.$base_name ) ){
				// 再帰処理
				$this->scan_starting_files($project_model, $path.$base_name.'/');
			}elseif( is_file( $path_base.$path.$base_name ) ){
				$ext = $this->px->dbh()->get_extension( $path_base.$path.$base_name );
				$target_path = '/'.$path.$base_name;
				switch( strtolower($ext) ){
					case 'html':
						$target_path = preg_replace( '/\/index\.html$/s', '/', $target_path );
						if( $this->add_target_path( $target_path ) ){
							$this->msg( 'set ['.$target_path.'] as the Startpage.' );
						}else{
							$this->msg( 'FAILD to add ['.$target_path.'] as the Startpage.' );
						}
						break;
					default:
						if( $this->add_target_path( $target_path ) ){
							$this->msg( 'set ['.$target_path.'] as the Startpage.' );
						}else{
							$this->msg( 'FAILD to add ['.$target_path.'] as the Startpage.' );
						}
						break;
				}
			}
		}
		set_time_limit(30);
		return true;
	}//scan_starting_files()



	#########################################################################################################################################################
	#	その他

	/**
	 * pathを処理待ちリストに追加
	 */
	private function add_target_path( $path , $option = array() ){
		$path = preg_replace( '/\/$/', '/index.html' , $path );

		#--------------------------------------
		#	要求を評価

		if( !preg_match( '/^\//' , $path ) ){ return false; }
			// 定形外のURLは省く
#		if( !preg_match( '/\.html$/' , $path ) ){ return false; }
			// ここで扱うのは、*.html のみ
		if( is_array( $this->target_path_list[$path] ) ){ return false; }
			// すでに予約済みだったら省く

		$path_dir_download_to = $this->get_path_download_to();
		if( is_dir( $path_dir_download_to.$path ) ){ return false; }
			// 既に保存済みだったら省く
		if( is_file( $path_dir_download_to.$path ) ){ return false; }
			// 既に保存済みだったら省く


		#--------------------------------------
		#	問題がなければ追加。
		$this->target_path_list[$path] = array();
		$this->target_path_list[$path]['path'] = $path;

		return	true;
	}

	/**
	 * 現在処理待ちのURL数を取得
	 */
	public function get_count_target_url(){
		return	count( $this->target_path_list );
	}
	/**
	 * URLが処理済であることを宣言
	 */
	private function target_url_done( $url ){
		unset( $this->target_path_list[$url] );
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

		error_log( $csv_line , 3 , $path_dir_download_to.'/__LOGS__/execute_list.csv' );
		$this->px->dbh()->chmod( $path_dir_download_to.'/__LOGS__/execute_list.csv' );

		return	true;
	}//save_executed_url_row();

	/**
	 * クロールの設定をログに残す
	 */
	private function save_crawl_settings(){
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
		$FIN .= 'Start page URL: '.$this->project_model->get_path_startpage()."\n";
		$FIN .= 'Document root URL: '.$this->project_model->get_path_docroot()."\n";



		error_log( $FIN , 3 , $path_dir_download_to.'/__LOGS__/settings.txt' );
		$this->px->dbh()->chmod( $path_dir_download_to.'/__LOGS__/settings.txt' );

		return	true;
	}//save_crawl_settings();

	/**
	 * サイトマップCSVを保存する系: 先頭
	 */
	private function start_sitemap(){
		$path_dir_download_to = realpath( $this->get_path_download_to() );
		if( !is_dir( $path_dir_download_to ) ){ return false; }
		if( !is_dir( $path_dir_download_to.'/sitemaps' ) ){
			if( !$this->px->dbh()->mkdir( $path_dir_download_to.'/sitemaps' ) ){
				return	false;
			}
		}

		$sitemap_definition = $this->px->site()->get_sitemap_definition();
		$sitemap_key_list = array();
		foreach( $sitemap_definition as $row ){
			array_push( $sitemap_key_list , '* '.$row['key'] );

		}
		$LINE = '';
		$LINE .= $this->px->dbh()->mk_csv(array($sitemap_key_list), array('charset'=>'UTF-8'));

		error_log( $LINE , 3 , $path_dir_download_to.'/sitemaps/sitemap.csv' );
		$this->px->dbh()->chmod( $path_dir_download_to.'/sitemaps/sitemap.csv' );

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