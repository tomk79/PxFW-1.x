<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 11:32 2010/01/27

#------------------------------------------------------------------------------------------------------------------
#	HTMLのメタ情報を抽出
class pxplugin_PicklesCrawler_resources_parsehtmlmetainfo{

	var $metadata = array(
		'title'=>null ,
		'description'=>null ,
		'keywords'=>null ,
	);
		#	抽出したデータの記憶。
		#	解析の結果抽出された値は、ここに記憶される。
		#	記憶される際の文字コードは、mb_internal_encoding() で取得された文字コード。

	var $html_charset = null;
		#	抽出対象のHTMLの文字コード(事前に分かっている場合)

	#--------------------------------------
	#	HTMLの文字コードをセットする
	function set_html_charset( $charset ){
		#	事前に分かっている場合のみ指定。
		#	分からない場合(null)は、自動(つまり、detect_orderの順)になる。
		$this->html_charset = $charset;
		return	true;
	}

	#--------------------------------------
	#	抽出したメタデータを初期化する
	function clear_metadata(){
		$this->metadata = array(
			'title'=>null ,
			'description'=>null ,
			'keywords'=>null ,
		);
		return	true;
	}

	#--------------------------------------
	#	抽出したメタデータを取り出す
	function get_metadata( $key = null ){
		if( !count( func_get_args() ) ){
			#	キーが指定されていなければ、全部返す。
			return	$this->metadata;
		}
		if( !array_key_exists( $key , $this->metadata ) ){
			return	false;
		}
		return	$this->metadata[$key];
	}

	#--------------------------------------
	#	抽出したメタデータを記憶する
	function set_metadata( $key , $val ){
		if( strlen( $this->html_charset ) ){
			$val = mb_convert_encoding( $val , mb_internal_encoding() , $this->html_charset.','.implode( ',' , mb_detect_order() ) );
		}elseif( mb_detect_encoding( $val ) ){
			$val = mb_convert_encoding( $val , mb_internal_encoding() , implode( ',' , mb_detect_order() ) );
		}
		$this->metadata[$key] = $val;
		return	true;
	}

	#--------------------------------------
	#	全てのデータを抽出したか調べる
	function is_set_all(){
		if( !is_array( $this->metadata ) ){ $this->metadata = array(); }
		foreach( $this->metadata as $value ){
			if( is_null( $value ) ){
				return	false;
			}
		}
		return	true;
	}

	#--------------------------------------
	#	抽出を開始する
	function execute( $path_htmlfile ){
		$this->clear_metadata();//初期化

		if( !strlen( $path_htmlfile ) || is_dir( $path_htmlfile ) || !is_file( $path_htmlfile ) || !is_readable( $path_htmlfile ) ){
			#	ディレクトリだったらおしまい。
			#	ファイルがなければおしまい。
			#	読み込めなければおしまい。
			return	false;
		}

		$res = fopen( $path_htmlfile , 'r' );
		if( $res === false ){
			return	false;
		}

		while( !feof( $res ) ){
			$line = fgets( $res , 4096 );


			#--------------------------------------
			#	タイトルタグを抽出
			if( is_null( $this->metadata['title'] ) ){
				#	UTODO : 簡単実装しています。タイトルは実際には1行に収まるとは限らない。
				if( preg_match( '/<title.*?>(.*?)<\/title>/si' , $line , $result ) ){
					$this->set_metadata( 'title' , $result[1] );
				}
			}
			#	/ タイトルタグを抽出
			#--------------------------------------

			#--------------------------------------
			#	メタタグを抽出
			if( is_null( $this->metadata['description'] ) || is_null( $this->metadata['keywords'] ) ){
				#	UTODO : 簡単実装しています。1行に収まっているとは限らない
				preg_match_all( '/<meta\s(.*?)>/si' , $line , $result );
				if( is_array( $result[1] ) ){
					foreach( $result[1] as $meta_att ){
						foreach( array( 'description','keywords' ) as $meta_name ){
							if( is_null( $this->metadata[$meta_name] ) ){
								if( preg_match( '/name=\"'.preg_quote($meta_name,'/').'"/si' , $meta_att ) ){
									preg_match( '/content\="(.*?)"/si' , $meta_att , $meta_att_result );
									$this->set_metadata( $meta_name , $meta_att_result[1] );
								}
							}
						}
					}
				}
			}
			#	/ メタタグを抽出
			#--------------------------------------


			if( $this->is_set_all() ){
				break;
			}
		}

		fclose( $res );
		return	true;
	}

}

?>