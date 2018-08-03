<?php

/**
 * 各プログラムの基底クラス
 * Copyright (C)Tomoya Koyanagi.
 * Last Update: 14:26 2011/04/17
 */
class pxplugin_PicklesCrawler_programbase_execute{

	protected $px;

	protected $pcconf;
	protected $proj;
	protected $program;

	protected $result = array();	//結果メッセージを記憶する配列

	protected $debug_mode = false;
		#	開発中のデバッグメッセージを出力するか。

	/**
	 * コンストラクタ
	 */
	public function __construct( &$px , &$pcconf , &$proj , &$program ){
		$this->px = &$px;
		$this->pcconf = &$pcconf;
		$this->proj = &$proj;
		$this->program = &$program;

		$this->additional_constructor();
	}
	/**
	 * コンストラクタの追加処理
	 */
	protected function additional_constructor(){
		#	必要に応じて拡張してください。
	}





	/**
	 * 処理の開始
	 */
	public function execute( &$httpaccess , $current_url , $saved_file_path , $options = array() ){
		/** -------------------------------------- **
			&$httpaccess
				HTTPアクセスオブジェクト
			$current_url
				ダウンロードした現在のURL。
			$saved_file_path
				ダウンロードして保存された先のファイルのフルパス
			$options
				current_url にアクセスした時のオプション(PicklesCrawler 0.1.7 追加)
		/** -------------------------------------- **/
		return	true;
	}

	/**
	 * ロードするURLを新たに追加する処理
	 */
	public function add_download_url( $add_url , $option = array() ){
		#	$option['referer']//リファラ値;
		#	$option['method'];//送信メソッド(GET/POST)
		#	$option['post'];//POSTメソッドで送る値($option['method']がPOSTじゃない場合は無視される)
		#	$option['type'];//<a>とか<form>とか<img>とか<style>とか<script>とかの区別をしたい。

		$this->msg( 100 , 'ダウンロードリストに追加。' , 'add_download_url' , $add_url , $option );
		return	true;
	}


	/**
	 * エラーをログに残す
	 */
	public function error_log( $msg = '' ){
		return	$this->px->error()->error_log( $msg );
	}

	/**
	 * 実行結果を出力する
	 */
	public function msg( $status_cd , $msg = '' , $program = '' , $parameter = '' , $option = array() ){
		/** --------------------------------------
		【　ステータスコード　】
		※PicklesDYプラグイン の API の <progress /> と同じ。
		※$option は、拡張項目です。
		-------------------------------------- **/

		if( $status_cd == 150 && !$this->debug_mode ){
			#	150番はデバッグメッセージ
			#	debug_mode が無効なら出力しない
			return	true;
		}

		$result = array(
			'status'=>intval( $status_cd ),
			'division'=>'crawler',
			'program'=>$program,
			'parameter'=>$parameter,
			'usermessage'=>$msg,
			'option'=>$option,//拡張項目
		);

		array_push( $this->result , $result );
		return	true;
	}

	/**
	 * 実行結果を得る
	 */
	public function get_result(){
		return	$this->result;
	}



	#========================================================================================================================================================
	#	★URL変換系メソッド★
	#* ------------------------------------------------------------------------------------------------------------------ *
	#	これらのメソッドは、ダウンロードしたHTMLから取得したURLの変換を行います。
	#	1つのURLは、処理の間にいくつかの状態を経ます。
	#
	#		ST1■ダウンロードしたソースに書かれている生の状態
	#			絶対パス/相対パス共にありえます。
	#
	#		ST2■HTTPから始まる絶対パスの状態
	#			必ず絶対パスです。
	#			引き継がないパラメータの設定などを考慮して決められるため、
	#			必ずしもST1と同じURLを指すとは限りません。
	#			この状態のURLが、次のダウンロードページとして追加リクエストされます。
	#			ページ内アンカー(#以降)は除去されます。
	#
	#		ST3■ダウンロード後のHTML上で書き換えられる最終的な相対パス
	#			基本的に相対パス/対象がサイト外の場合は絶対パス。
	#			ページ内アンカー(#以降)は保持されます。
	#
	#* ------------------------------------------------------------------------------------------------------------------ *

	/**
	 * URLをパースして、変換指示を返す
	 * $method,$post_data : 19:52 2008/04/16 追加
	 */
	public function get_replace_url_info( $current_url , $TARGET_PATH_ORIGINAL , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url , $method = 'GET' , $FORM_DATA = null ){
		if( !strlen( $method ) ){
			$method = 'GET';
		}
		$method = strtoupper( $method );

		$TARGET_PATH_ORIGINAL = trim( $TARGET_PATH_ORIGINAL );//PxCrawler 0.4.0 : trim() するようにした。
		$TARGET_PATH = $TARGET_PATH_ORIGINAL;
		if( preg_match( '/^(?:[a-zA-Z]+:)?\/\/.*$/is' , $TARGET_PATH ) && !preg_match( '/^'.preg_quote($URL_PROTOCOL,'/').':\/\/'.preg_quote($URL_DOMAIN,'/').'\//is' , $TARGET_PATH_ORIGINAL ) ){
			#	ドメインが別物だったら、除外
			#	PxCrawler 0.4.0 : 「//」 で始まるリンクに対応
			return	false;
		}
		if( preg_match( '/^data:[a-zA-Z0-9\_\-]+\/[a-zA-Z0-9\_\-]+.*$/is' , $TARGET_PATH ) && !preg_match( '/^'.preg_quote($URL_PROTOCOL,'/').':\/\/'.preg_quote($URL_DOMAIN,'/').'\//is' , $TARGET_PATH_ORIGINAL ) ){
			#	「data:」で始まるリンク(HTML埋め込み画像など)に対応
			#	PxCrawler 0.4.0 : 追加
			return	false;
		}

		$anchor = null;
		#	アンカーを考慮
		if( strpos( $TARGET_PATH , '#' ) ){
			#	ターゲットパスから一旦アンカーを削除して、
			#	でも忘れちゃいけないから $anchor にメモる。
			list( $TARGET_PATH , $anchor ) = explode( '#' , $TARGET_PATH , 2 );
		}

		$params = null;
		if( strpos( $TARGET_PATH , '?' ) ){
			#	パラメータがあったら覚えておく
			#	PicklesCrawler 0.1.4 追記
			list( $TARGET_PATH , $params ) = explode( '?' , $TARGET_PATH , 2 );
		}
		if( strlen( $params ) ){
			#	PicklesCrawler 0.3.6 追記
			#	送信してはいけないパラメータが含まれていた場合、
			#	この時点でそぎ落としてしまう。
			$param_list = explode( '&' , $params );
			$alive_param_memo = array();
			foreach( $param_list as $param_line ){
				list( $key , $val ) = explode( '=' , $param_line );
				if( !$this->proj->is_param_allowed( urldecode( $key ) ) ){
					continue;
				}
				array_push( $alive_param_memo , $param_line );
			}
			$params = implode( '&' , $alive_param_memo );
			#	/ PicklesCrawler 0.3.6 追記
		}

		$full_params = $params; //←URLとFORM_DATAをあわせた全パラメータ(20:03 2008/04/16 追加)
		if( strlen( $FORM_DATA ) ){
			$full_params = implode( '&' , array( $full_params , $FORM_DATA ) );
		}

		$TARGET_PATH = $this->optimize_url( $current_url , $TARGET_PATH );//←ここで $TARGET_PATH はrealpathになってるハズ。
		$TARGET_URL = $URL_PROTOCOL.'://'.$URL_DOMAIN.$TARGET_PATH;//←これは、参照対象のURL。

		$REPLACE_TO = '';
#		if( $this->proj->is_outofsite( $TARGET_URL ) ){
#			#	参照対象がサイト外設定されていたら、
#			#	URLに変換し、ダウンロード対象には含まない。
#			$REPLACE_TO = $TARGET_URL;
##			$REPLACE_TO .= '#'.$anchor;//アンカーを復活させる//PxCrawler 0.3.6 削除 (あとで一括して処理してるから)
#		}else{
			$localpath_save_to = $this->proj->url2localpath( implode( '?' , array( $TARGET_URL , $full_params ) ) , $FORM_DATA );
				#	URLを内部パス(保存先パス)に変換する。
				#	$localpath_save_to には、'/http/www.xxx.jp/aaa/index.html' のような文字列が入ります。
				#	$localpath_save_to は、rewrite_rulesが適用されています。
			$URL_DOMAIN = preg_replace( '/\:/' , '_' , $URL_DOMAIN );
			$REPLACE_TO_ABSOLUTE = preg_replace( '/^'.preg_quote( '/'.$URL_PROTOCOL.'/'.$URL_DOMAIN , '/' ).'/' , '' , $localpath_save_to );
			$REPLACE_TO = $this->convert2relativepath( $current_virtual_url , $REPLACE_TO_ABSOLUTE );
#			$REPLACE_TO .= '#'.$anchor;//アンカーを復活させる//PxCrawler 0.3.6 削除 (あとで一括して処理してるから)
//			unset( $REPLACE_TO_ABSOLUTE );
#		}

		if( $method == 'GET' ){
			$ADD_URL = $this->convert2url( $current_url , $TARGET_PATH , $full_params );
		}else{
			$ADD_URL = $this->convert2url( $current_url , $TARGET_PATH , $params );
		}

		#--------------------------------------
		#	回答を作成
		$RTN = array();
		$RTN['replace_to'] = null;
		$path_conv_method = $this->proj->get_path_conv_method();
		if( $this->proj->is_outofsite( $TARGET_URL ) && $this->proj->get_outofsite2url_flg() ){
			$RTN['replace_to'] = $TARGET_URL;
		}else{
			switch( $path_conv_method ){
				#	パス変換方法設定を考慮して変換パスを指示
				case 'absolute':
					if( strlen( $localpath_save_to ) ){
						#	23:47 2009/03/02 Pickles Crawler 0.2.0
						#	参照先が絶対パスにならない問題を修正。
						$RTN['replace_to'] = $REPLACE_TO_ABSOLUTE;
					}else{
						$RTN['replace_to'] = $TARGET_PATH;
					}
					break;
				case 'url':
					$RTN['replace_to'] = $TARGET_URL;
					break;
				case 'none':
					$RTN['replace_to'] = $TARGET_PATH_ORIGINAL;
					break;
				case 'relative':
				default:
					$RTN['replace_to'] = $REPLACE_TO;
					break;
			}
		}
		if( $path_conv_method != 'none' ){
			//「変換しない」設定の場合、この時点で$RTN['replace_to']は完成されているので添加物を加えない。
			if( !preg_match( '/\/$/si' , $RTN['replace_to'] ) ){
				//PxCrawler 0.4.3 追加：省略するファイル名の削除処理
				foreach( $this->proj->get_omit_filename() as $omit_filename ){
					if( basename( $RTN['replace_to'] ) == $omit_filename ){
						$RTN['replace_to'] = preg_replace( '/'.preg_quote($omit_filename,'/').'$/si' , '' , $RTN['replace_to'] );
						if( !strlen( $RTN['replace_to'] ) ){
							$RTN['replace_to'] = './';
						}
						break;
					}
				}
			}
			if( strlen( $params ) ){ $RTN['replace_to'] .= '?'.$params; };//パラメータを復活させる//PxCrawler 0.3.6 追加
			if( strlen( $anchor ) ){ $RTN['replace_to'] .= '#'.$anchor; };//アンカーを復活させる//PxCrawler 0.3.6 追加
		}
		$RTN['add_url'] = $ADD_URL;
		if( strlen( $FORM_DATA ) ){
			if( $method == 'POST' ){
				$RTN['post_data'] = $FORM_DATA;
			}
		}
		return	$RTN;

	}//get_replace_url_info()


	/**
	 * リンク先URLの最適化
	 */
	public function optimize_url( $current_url , $TARGET_PATH ){
		#	絶対パスと相対パスとを吸収し、
		#	スラッシュから始まる絶対パスに変換する。

		if( preg_match( '/^[a-zA-Z]+:\/\/.*?(\/.*)$/is' , $TARGET_PATH , $preg_result ) ){
			#	リンク先がURLになっていたら。
			return	$preg_result[1];
		}

		$urlinfo = parse_url( trim( $current_url ) );
		$DOMAIN = $urlinfo['host'];
		if( strlen( $urlinfo['port'] ) ){
			$DOMAIN .= ':'.$urlinfo['port'];
		}

		$TARGET_PATH = $this->convert2realpath( $current_url , $TARGET_PATH );
		$TARGET_PATH = $this->proj->optimize_url( $urlinfo['scheme'].'://'.$DOMAIN.''.$TARGET_PATH );

		$urlinfo2 = parse_url( trim( $TARGET_PATH ) );

		$RTN = $urlinfo2['path'];
		return	$RTN;
	}//optimize_url()


	/**
	 * 現在地点(URL)から、$pathへの相対パスを求める
	 */
	public function convert2relativepath( $url , $path ){

		#	アンカーを考慮
		if( strpos( $url , '#' ) ){
			list($url,$dmy) = explode('#',$url,2);
			unset($dmy);
		}
		if( strpos( $path , '#' ) ){
			list($path,$anchor) = explode('#',$path,2);
		}

		if( !preg_match( '/^([a-zA-Z]+)\:\/\/(.+?(?:\:[0-9]+)?)(\/.*)/i' , $url , $result ) ){
			#	URLの形式が不正ならスキップ
			return	false;
		}
		$PROTOCOL = strtolower( $result[1] );
		$DOMAIN = strtolower( $result[2] );
		$INNER_PATH = $result[3];

		$path = $this->convert2realpath( $url , $path );
			#	↑リンク先が相対パスかも知れないから、
			#	　一旦絶対パス(スラッシュから始まる)に変換している。

		//	Windows形式→UNIX形式
		$DIR_INNER_PATH = preg_replace( '/\\\\/' , '/' , dirname( $INNER_PATH ) );
		$DIR_path = preg_replace( '/\\\\/' , '/' , dirname( $path ) );

		$debug_loop_limit = 5000;
		$debug_loop_count = 0;
		while( strlen( $DIR_INNER_PATH ) && strlen( $DIR_path ) ){
			$debug_loop_count ++;
			if( $debug_loop_count > $debug_loop_limit ){
				#	無限ループ回避のための安全措置
				$errormsg = '無限ループの危険性を察知しました。';
				$this->error_log( $errormsg );
				$this->msg( 500 , $errormsg );
				break;
			}

			$tmp_inner_lay = explode( '/' , $DIR_INNER_PATH );
			array_shift( $tmp_inner_lay );
			$tmp_inner_path = '/'.array_shift( $tmp_inner_lay );

			$tmp_path_lay = explode( '/' , $DIR_path );
			array_shift( $tmp_path_lay );
			$tmp_path = '/'.array_shift( $tmp_path_lay );

			if( $tmp_inner_path != $tmp_path ){
				break;
			}
			unset( $tmp_inner_path , $tmp_path );
			$DIR_INNER_PATH = '';
			if( count( $tmp_inner_lay ) ){
				$DIR_INNER_PATH = '/'.implode( '/' , $tmp_inner_lay );
			}

			$DIR_path = '';
			if( count( $tmp_path_lay ) ){
				$DIR_path = '/'.implode( '/' , $tmp_path_lay );
			}

			unset( $tmp_inner_lay , $tmp_path_lay );

		}

		$path_layers = explode( '/' , $DIR_INNER_PATH );
		$tmp_path_layers_memo = array();
		foreach( $path_layers as $layer ){
			if( !strlen( $layer ) ){ continue; }
			array_push( $tmp_path_layers_memo , '..' );
		}

		$DIR_path = preg_replace( '/^\/+/' , '' , $DIR_path );
		$lay_str = '';
		if( count( $tmp_path_layers_memo ) ){
			$lay_str = implode( '/' , $tmp_path_layers_memo ).'/';
		}

		$RTN = '';
		$RTN .= $lay_str;
		if( strlen( $DIR_path ) ){
			$RTN .= $DIR_path.'/';
		}
		$RTN .= basename( $path );
		if( strlen($anchor) ){
			$RTN .= '#'.$anchor;
		}
		return	$RTN;
	}//convert2relativepath()

	/**
	 * 現在地点(URL)から、相対的に解釈された$pathへの絶対的URLを求める
	 */
	public function convert2url( $url , $path , $params = null ){
		#	$params は、PicklesCrawler 0.1.4 で追加されました。

		if( !preg_match( '/^([a-zA-Z]+)\:\/\/(.+?(?:\:[0-9]+)?)(\/.*)/i' , $url , $result ) ){
			return	false;
		}

		$urlinfo = parse_url( trim( $url ) );
		$PROTOCOL = strtolower( $urlinfo['scheme'] );
		$DOMAIN = strtolower( $urlinfo['host'] );
		if( strlen( $urlinfo['port'] ) ){
			$DOMAIN .= ':'.strtolower( $urlinfo['port'] );
		}
		$INNER_PATH = $urlinfo['path'];

		$path = $this->convert2realpath( $url , $path );

		if( !preg_match( '/^\//' , $path ) ){
			return	false;
		}

		$RTN = $PROTOCOL.'://'.$DOMAIN.$path;
		if( strlen( $params ) ){
			#	PicklesCrawler 0.1.4 追記
			$param_list = explode( '&' , $params );
			$alive_param_memo = array();
			foreach( $param_list as $param_line ){
				list( $key , $val ) = explode( '=' , $param_line );
				if( !$this->proj->is_param_allowed( urldecode( $key ) ) ){
					continue;
				}
				array_push( $alive_param_memo , $param_line );
			}
			$RTN .= '?'.implode( '&' , $alive_param_memo );
		}
		return	$RTN;
	}//convert2url()

	/**
	 * 相対パスを絶対パスに書き換える
	 */
	public function convert2realpath( $url , $relativepath ){
		if( preg_match( '/^\//' , $relativepath ) ){
			#	スラッシュから始まっていたら、
			#	変換の必要はない。
			return	$relativepath;
		}

		if( !preg_match( '/^([a-zA-Z]+)\:\/\/(.+?(?:\:[0-9]+)?)(\/.*)/i' , $url ) ){
			return	false;
		}

		$urlinfo = parse_url( trim( $url ) );
		$PROTOCOL = strtolower( $urlinfo['scheme'] );
		$DOMAIN = strtolower( $urlinfo['host'] );
		if( strlen( $urlinfo['port'] ) ){
			$DOMAIN .= ':'.strtolower( $urlinfo['port'] );
		}
		$INNER_PATH = $urlinfo['path'];

		$RTN = dirname( $INNER_PATH );
		if( $RTN == '/' || $RTN == '\\' ){
			$RTN = '';
		}

		$path_layers = explode( '/' , $relativepath );
		foreach( $path_layers as $layer_name ){

#			if( !strlen( $layer_name ) ){ continue; }
#				↑PxCrawler 0.3.5 : 相対パスでファイル名を省略した場合に、階層がズレるバグの原因だったため削除。

			if( $layer_name == '..' ){
				$RTN = dirname( $RTN );
				if( $RTN == '/' ){
					$RTN = '';
				}
			}elseif( $layer_name == '.' ){
				$RTN = $RTN;
			}else{
				$RTN .= '/'.$layer_name;
			}

		}

		return	$RTN;
	}//convert2realpath()



	#========================================================================================================================================================
	#	★base_resources_htmlparser からの移植

	/**
	 * HTML属性の解析
	 */
	public function html_attribute_parse( $strings ){
		preg_match_all( $this->get_pattern_attribute() , $strings , $results );
		for( $i = 0; !is_null($results[0][$i]); $i++ ){
			if( !strlen($results[3][$i]) ){
				$results[4][$i] = null;
			}
			if( $results[2][$i] ){
				$RTN[strtolower( $results[1][$i] )] = $this->html2text($results[2][$i]);
			}else{
				$RTN[strtolower( $results[1][$i] )] = $this->html2text($results[4][$i]);
			}
		}
		return	$RTN;
	}//html_attribute_parse()

	/**
	 * タグの属性情報を検出するPREGパターンを生成して返す。
	 */
	public function get_pattern_attribute(){
		#	属性の種類
		$rnsp = '(?:\r\n|\r|\n| |\t)';
		$prop = '[a-z0-9A-Z_-]+';
		$typeA = '([\'"]?)(.*?)\3';	#	ダブルクオートあり
		$typeB = '[^"\' ]+';					#	ダブルクオートなし

		#	属性指定の式
		$prop_exists = '/'.$rnsp.'*('.$prop.')(?:\=(?:('.$typeB.')|'.$typeA.'))?'.$rnsp.'*/is';

		return	$prop_exists;
	}//get_pattern_attribute()

	/**
	 * 受け取ったHTMLをテキスト形式に変換する
	 * (クラス base_static_text からのコピー)
	 */
	public function html2text(){
		//	htmlspecialchars_decode()というのもあるが、
		//	PHP5以降からなので、とりあえず使ってない。
		list($TEXT) = func_get_args();
		$TEXT = preg_replace( '/<br(?: \/)?>/' , "\n" , $TEXT );
		$TEXT = preg_replace( '/&lt;/' , '<' , $TEXT );
		$TEXT = preg_replace( '/&gt;/' , '>' , $TEXT );
		$TEXT = preg_replace( '/&quot;/' , '"' , $TEXT );
		$TEXT = preg_replace( '/&amp;/' , '&' , $TEXT );
		return	$TEXT;
	}

}

?>