<?php
$this->load_px_plugin_class( '/PicklesCrawler/programbase/execute.php' );

/**
 * スナップショットの実行
 * Copyright (C)Tomoya Koyanagi.
 * LastUpdate : 11:36 2011/05/20
 */
class pxplugin_PicklesCrawler_program_snapshot_execute extends pxplugin_PicklesCrawler_programbase_execute{

#	private $debug_mode = true;
		#	デバッグモード。
		#	開発/デバッグが終了したら、必ずコメントアウトすること。

	private $parse_type = null;
		#	URL解析のパターン


	/**
	 * ダウンロードされたファイルに対する処理を実行する
	 */
	public function execute( &$httpaccess , $current_url , $saved_file_path , $options = array() ){
		if( !is_file( $saved_file_path ) || !is_readable( $saved_file_path ) ){
			return	false;
		}
		$HTTP_CONTENT_TYPE = explode( '/' , strtolower( $httpaccess->get_content_type() ) );
		$this->parse_type = null;
		switch( $HTTP_CONTENT_TYPE[0] ){
			case 'text':
				switch( $HTTP_CONTENT_TYPE[1] ){
					case 'html':
					case 'xhtml':
						$this->parse_type = 'html';break;
					case 'javascript':
						$this->msg( 200 , 'OK.'); return true;//PxCrawler 0.4.0 : JavaScriptは解析したらいかん。
						//$this->parse_type = 'js';break;
					case 'css':
						$this->parse_type = 'css';break;
					case 'xml':
					case 'rdf':
						$this->parse_type = 'rss';break;
						break;
					default:
						#	知らないタイプなので、
						#	解析しないでそのままスルー
						$this->msg( 200 , 'OK.'); return true;
						break;
				}
				break;
			case 'application':
				switch( $HTTP_CONTENT_TYPE[1] ){
					case 'xml':
					case 'rdf':
						$this->parse_type = 'rss';break;
					case 'xhtml':
						$this->parse_type = 'html';break;
					case 'javascript':
						$this->msg( 200 , 'OK.'); return true;//PxCrawler 0.4.0 : JavaScriptは解析したらいかん。
						//$this->parse_type = 'js';break;
					default:
						#	知らないタイプなので、
						#	解析しないでそのままスルー
						$this->msg( 200 , 'OK.'); return true;
						break;
				}
				break;
			default:
				#	知らないタイプなので、
				#	解析しないでそのままスルー
				$this->msg( 200 , 'OK.');
				return true;
				break;
		}

		preg_match( '/^([a-z0-9]+)\:\/\/(.+?)(\/.*)$/i' , $current_url , $url_info );
		$URL_PROTOCOL = strtolower( $url_info[1] );
		$URL_DOMAIN = strtolower( $url_info[2] );
		unset( $url_info );


		#--------------------------------------
		#	リライトルールに則った仮想カレントURL
		#		= $current_virtual_url
		$current_virtual_url = $this->proj->url2localpath( $current_url , $options['post'] );
		$current_virtual_url = preg_replace( '/^'.preg_quote('/'.$URL_PROTOCOL.'/'.preg_replace( '/\:/' , '_' , $URL_DOMAIN ),'/').'/' , '' , $current_virtual_url );
		$current_virtual_url = $URL_PROTOCOL.'://'.$URL_DOMAIN.$current_virtual_url;
		#	/ リライトルールに則った仮想カレントURL
		#--------------------------------------

		$CONTENTS = $this->px->dbh()->file_get_contents( $saved_file_path );
			#	$CONTENTSは、ダウンロードしてきたバイナリです。

		if( strlen( $httpaccess->get_response( 'x-pxfw-relatedlink' ) ) ){
			# PxCrawler 0.4.3 追加の機能
			# PxFW 0.7.2 の拡張ヘッダ x-pxfw-relatedlink に対する反応。
			$tmp_relatedlink = $httpaccess->get_response( 'x-pxfw-relatedlink' );
			foreach( explode(',',$tmp_relatedlink) as $tmp_relatedlink_row ){
				trim( $tmp_relatedlink_row );
				if(!strlen($tmp_relatedlink_row)){continue;}
				$tmp_parsed_info = $this->get_replace_url_info( $current_url , $tmp_relatedlink_row , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url );
				$REPLACE_TO = $tmp_parsed_info['replace_to'];
				$ADD_URL = $tmp_parsed_info['add_url'];
				//	↓URLを追加
				$this->msg( 150 , '['.$ADD_URL.']が、ダウンロードリストに追加されます。(by X-PXFW-RELATEDLINK header)');
				$this->add_download_url( $ADD_URL , array('referer'=>$current_url) );
			}
			unset( $REPLACE_TO , $ADD_URL );
			unset( $tmp_relatedlink );
			unset( $tmp_relatedlink_row );
			unset( $tmp_parsed_info );
		}//x-pxfw-relatedlink

		switch( $this->parse_type ){
			case 'html';
				$CONTENTS = $this->execute_replace_url_html( $current_url , $CONTENTS , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url );
				break;
			case 'css';
				$CONTENTS = $this->execute_replace_url_css( $current_url , $CONTENTS , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url );
				break;
			default:
				$CONTENTS = $this->execute_replace_url_default( $current_url , $CONTENTS , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url );
				break;
		}

		#--------------------------------------
		#	内部から検出されたパスを変換した$CONTENTSを保存しなおす。
		$result = $this->px->dbh()->save_file( $saved_file_path , $CONTENTS );
		$this->px->dbh()->fclose( $saved_file_path );

		$this->msg( 200 , 'OK.');
		return	true;
	}//execute()

	/**
	 * ダウンロードされたHTMLファイルに対する処理を実行する
	 */
	private function execute_replace_url_html( $current_url , $CONTENTS , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url ){
		#--------------------------------------
		#	タグの属性を変換
		$RTN = '';
		while( 1 ){
			#--------------------------------------
			#	開始タグを一つずつ検出してまわす
			if( !preg_match( '/^(.*?)(<([a-z][a-z0-9]*)(\s.*?)?'.'>|(<\!\-\-.*?\-\->))(.*)$/si' , $CONTENTS , $matches ) ){//PicklesCrawler0.1.13 : HTMLコメントを抽出できるようにした。
				#	タグが検出できないところまできたら終わり。
				$RTN .= $CONTENTS;
				break;
			}
			$RTN                       .= $matches[1];//タグ検出の前の文字列
			$TARGET_TAG_SRC             = $matches[2];//タグそのもの
			$TagName                    = trim( $matches[3] );//タグ名
			$TagAttributes              = $matches[4];//タグの属性
			$TagAttributes_parsed_array = $this->html_attribute_parse( $TagAttributes );
			$CommentTag                 = $matches[5];//検出されたコメントタグ
			$CONTENTS                   = $matches[6];//タグ検出の後ろの文字列
			unset( $matches );
			if( !strlen( $CONTENTS ) ){
				$RTN .= $TARGET_TAG_SRC;
				break;
			}
			if( strlen( $CommentTag ) ){
				#	検出したタグがコメントだったら//PicklesCrawler0.1.13
				$RTN .= $CommentTag;
				continue;
			}

			#	タグ名と、その場合に対象とする属性名
			$target_att_names = array();
			array_push( $target_att_names , 'on[a-zA-Z]+' );
			array_push( $target_att_names , 'style' );
			array_push( $target_att_names , 'background' );//PicklesCrawler 0.3.0 追加
			switch( strtolower( $TagName ) ){
				case 'a':
				case 'area'://PicklesCrawler 0.3.0 追加
				case 'link':
					array_push( $target_att_names , 'href' );
					break;
				case 'img':
				case 'iframe':
				case 'frame':
				case 'frameset':
				case 'script':
				case 'embed':
				case 'input':
					array_push( $target_att_names , 'src' );
					break;
				case 'param':
					if( strtolower( $TagAttributes_parsed_array['name'] ) == 'movie' ){
						array_push( $target_att_names , 'value' );
					}
					break;
				case 'object':
					array_push( $target_att_names , 'data' );//PxCrawler 0.3.7 追加
					break;
				case 'form':
					array_push( $target_att_names , 'action' );
					break;
			}

			$srcPrev = '';
			$srcNext = $TagAttributes;
			while(1){
				if( !preg_match( '/^(.*?)(([a-zA-Z0-9\_\-]+(?:\:[a-zA-Z0-9\_\-]+)?)\s*=\s*("|\')(.*?)\4)(.*)$/si' , $srcNext , $matches ) ){
					$TagAttributes = $srcPrev.$srcNext;
					unset( $srcPrev );
					unset( $srcNext );
					break;
				}
				$i = 1;
				$srcPrev       .= $matches[$i++];
				$attSrcBefore   = $matches[$i++];
				$hit_attname    = $matches[$i++];
				$DELIMITTER     = $matches[$i++];
				$attValueBefore = $matches[$i++];
				$srcNext        = $matches[$i++];

				$attValueBefore = $this->html2text( $attValueBefore );//←PxCrawler 0.3.8 デコードせずに取り扱っていた不具合を修正。

				$hit_attname_lower = strtolower( $hit_attname );

				$is_target_attname = false;
				foreach( $target_att_names as $attname ){
					#	解析対象の属性かどうか確認
					if( preg_match( '/^'.$attname.'$/si' , $hit_attname_lower ) ){
						$is_target_attname = true;
						break;
					}
				}
				if( !$is_target_attname ){
					#--------------------------------------
					#	解析対象の属性じゃない場合
					$srcPrev .= $attSrcBefore;
					continue;

				}elseif( $hit_attname_lower == 'style' ){
					#	style属性だったら、CSSの変換を通す。
					$srcPrev .= $hit_attname.'='.$DELIMITTER.$this->execute_replace_url_css( $current_url , $attValueBefore , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url ).$DELIMITTER;
					continue;

				}elseif(
					( $hit_attname_lower == 'href' && !preg_match( '/^(?:javascript:|mailto:|tel:|#)/is' , $attValueBefore ) )
					|| $hit_attname_lower == 'src'
					|| $hit_attname_lower == 'background'//PicklesCrawler 0.3.0 追加
					|| ( strtolower( $TagName ) == 'form' && $hit_attname_lower == 'action' && !preg_match( '/^(?:javascript:|mailto:|tel:|#)/is' , $attValueBefore ) )
					|| ( strtolower( $TagName ) == 'param' && $hit_attname_lower == 'value' )
					|| ( strtolower( $TagName ) == 'object' && $hit_attname_lower == 'data' ) //PxCrawler 0.3.7 追加
				){
					#	ズバリそのままURLを示す属性だったら

					$option = array();
					$option['referer'] = $current_url;
					$option['method'] = 'GET';
					$FORM_DATA = null;//初期化
					if( strtolower( $TagName ) == 'form' ){
						#--------------------------------------
						#	フォームだったら
						if( strlen( $TagAttributes_parsed_array['method'] ) ){
							#	メソッド値を反映
							$option['method'] = strtoupper( $TagAttributes_parsed_array['method'] );
						}

						#	フォーム内側のパラメータを追加
						$FORM_INNERHTML_SRC = $CONTENTS;
						$FORM_INNERHTML_SRC = preg_replace( '/<\!\-\-.*?\-\->/si' , '' , $FORM_INNERHTML_SRC );//HTMLコメントを削除
						$FORM_INNERHTML_SRC = preg_replace( '/^(.*?)<\/form>.*$/si' , '\1' , $FORM_INNERHTML_SRC );//form閉じタグまでの間を抽出
						$FORM_DATA = $this->get_form_element_values( $FORM_INNERHTML_SRC );
						unset( $FORM_INNERHTML_SRC );
						if( strtoupper( $option['method'] ) == 'POST' ){
							$option['post'] = $FORM_DATA;
						}
						$option['type'] = 'form';

						#	/ フォームだったら
						#--------------------------------------
					}

					$parsed_info = $this->get_replace_url_info( $current_url , $attValueBefore , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url , $option['method'] , $FORM_DATA );
					if( $parsed_info === false ){
						$srcPrev .= $attSrcBefore;
						continue;
					}
					unset( $FORM_DATA );
					$REPLACE_TO = $parsed_info['replace_to'];
					$ADD_URL = $parsed_info['add_url'];
					$this->msg( 150 , '['.$REPLACE_TO.']に変換されます。');
					if( $this->proj->get_path_conv_method() != 'none' ){
						#	パス変換方法設定を考慮して変換
						$srcPrev .= $hit_attname.'='.$DELIMITTER.$REPLACE_TO.$DELIMITTER;
							#	$CONTENTS内で検出したパス($TARGET_PATH_ORIGINAL)を、
							#	保存後のローカルディレクトリ上の相対パス($DELIMITTER.$REPLACE_TO.$DELIMITTER)に変換している。
					}else{
						$srcPrev .= $attSrcBefore;
					}

					//	↓URLを追加
					$this->msg( 150 , '['.$ADD_URL.']が、ダウンロードリストに追加されます。');
					$this->add_download_url( $ADD_URL , $option );

					continue;
				}else{
					#	その他の属性だったら、普通の変換。
					$srcPrev .= $hit_attname.'='.$DELIMITTER.$this->execute_replace_url_default( $current_url , $attValueBefore , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url ).$DELIMITTER;
					continue;
				}

			}
			$RTN .= '<'.$TagName;
			if( strlen( $TagAttributes ) ){
				$RTN .= $TagAttributes;
			}
			$RTN .= '>';

		}
		#	/ タグの属性を変換
		#--------------------------------------

		$CONTENTS = $RTN;
		unset($RTN);

		#--------------------------------------
		#	<style>タグと<script>タグの中身も変換
		$tag_list = array();
		if( $this->proj->get_parse_jsinhtml_flg() ){//PxCrawler 0.4.3 追加 : オプションになった。
			array_push( $tag_list , 'script' );
		}
		array_push( $tag_list , 'style' );
		foreach( $tag_list as $tagnameLine ){
			$RTN = '';
			while( 1 ){
				if( !preg_match( '/^(.*?<'.$tagnameLine.'.*?>)(.*?)(<\/'.$tagnameLine.'>.*)$/si' , $CONTENTS , $matches ) ){
					$RTN .= $CONTENTS;
					break;
				}
				$RTN .= $matches[1];
				$TARGET_TAG_SRC = $matches[2];
				$CONTENTS = $matches[3];
				unset( $matches );
				if( !strlen( $CONTENTS ) ){
					$RTN .= $TARGET_TAG_SRC;
					break;
				}

				if( $tagnameLine == 'style' ){
					#	CSSの解析
					$TARGET_TAG_SRC = $this->execute_replace_url_css( $current_url , $TARGET_TAG_SRC , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url );
				}else{
					#	その他一般的な解析
					$TARGET_TAG_SRC = $this->execute_replace_url_default( $current_url , $TARGET_TAG_SRC , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url );
				}
				$RTN .= $TARGET_TAG_SRC;

			}

			$CONTENTS = $RTN;
			unset($RTN);

		}
		#	/ <style>タグと<script>タグの中身も変換
		#--------------------------------------

		return	$CONTENTS;
	}//execute_replace_url_html()

	/**
	 * フォームソースを解析して、送信する値を抽出
	 */
	private function get_form_element_values( $CONTENTS ){
		$RTN_MEMO = array();
		while( 1 ){
			if( !preg_match( '/^(.*?)(<([a-z][a-z0-9]*)(\s.*?)?'.'>)(.*)$/si' , $CONTENTS , $matches ) ){
				#	タグが検出できないところまできたら終わり。
				break;
			}
			$TARGET_TAG_SRC = $matches[2];//タグそのもの
			$TagName = trim( $matches[3] );//タグ名
			$TagAttributes = trim( $matches[4] );//タグの属性
			$TagAttributes_parsed_array = $this->html_attribute_parse( $TagAttributes );
			$CONTENTS = $matches[5];//タグ検出の後ろの文字列
			unset( $matches );

			$form_elm_name = null;
			if( strlen( $TagAttributes_parsed_array['name'] ) ){
				$form_elm_name = $TagAttributes_parsed_array['name'];
			}

			if( !strlen( $form_elm_name ) ){
				continue;
			}
			if( !$this->proj->is_param_allowed( $form_elm_name ) ){
				#	送信していいパラメータか否か調べる
				#	PicklesCrawler 0.1.7 追加
				continue;
			}

			switch( strtolower( $TagName ) ){
				#--------------------------------------
				#	input
				case 'input':
					$input_type = 'text';
					if( strlen( $TagAttributes_parsed_array['type'] ) ){
						#	タイプ
						$input_type = strtolower( $TagAttributes_parsed_array['type'] );
					}
					unset( $matches );
					$input_value = null;
					if( !is_null( $TagAttributes_parsed_array['value'] ) ){
						#	値
						$input_value = $TagAttributes_parsed_array['value'];
					}
					unset( $matches );
					switch( $input_type ){
						case 'text':
						case 'hidden':
						case 'submit':
						case 'image':
						case 'password':
							$RTN_MEMO[$form_elm_name] = $input_value;
							break;

						case 'radio':
						case 'checkbox':
							if( !is_null( $TagAttributes_parsed_array['checked'] ) ){
								#	デフォルトで選択されていれば追加
								$RTN_MEMO[$form_elm_name] = $input_value;
							}
							break;

						case 'button':
							break;

					}
					break;

				#--------------------------------------
				#	textarea
				case 'textarea':
					$input_value = preg_replace( '/^(.*?)<\/textarea>.*$/si' , '\1' , $CONTENTS );//textarea閉じタグまでの間を抽出
					$RTN_MEMO[$form_elm_name] = $input_value;
					break;

				#--------------------------------------
				#	select
				case 'select':
					$OPTIONS = preg_replace( '/^(.*?)<\/select>.*$/si' , '\1' , $CONTENTS );//select閉じタグまでの間を抽出
					$first_value = null;
					while( 1 ){
						if( !preg_match( '/^.*?<option(\s.*?)?'.'>(.*?)<\/option>(.*)$/si' , $OPTIONS , $matches ) ){
							#	タグが検出できないところまできたら終わり。
							break;
						}
						$option_att = $this->html_attribute_parse( $matches[1] );
						$option_innerHTML = $matches[2];
						$OPTIONS = $matches[3];
						unset( $matches );

						$input_value = $option_innerHTML;
						if( strlen( $option_att['value'] ) ){
							#	値
							$input_value = $option_att['value'];
						}
						unset( $matches );
						if( is_null( $first_value ) ){
							$first_value = $input_value;
						}

						if( !is_null( $option_att['selected'] ) ){
							#	デフォルトで選択されていれば追加
							$RTN_MEMO[$form_elm_name] = $input_value;
							break 2;
						}

					}
					if( is_null( $RTN_MEMO[$form_elm_name] ) ){
						$RTN_MEMO[$form_elm_name] = $first_value;//selectedな項目がなければ、最初の項目を入れる
					}
					break;

			}

			continue;
		}

		$RTN = array();
		foreach( $RTN_MEMO as $key=>$val ){
			array_push( $RTN , urlencode($key).'='.urlencode($val) );
		}
		return	implode( '&' , $RTN );
	}//get_form_element_values()



	/**
	 * ダウンロードされたCSSファイルに対する処理を実行する
	 */
	private function execute_replace_url_css( $current_url , $CONTENTS , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url ){

		$preg_ptn_url = '^(.*?)(?:(\/\*.*?\*\/)|(url\()\s*((?:"|\')?)(.*?)\4\s*(\)))(.*)$';
		$preg_ptn_import = '^(.*?)(?:(\/\*.*?\*\/)|(\@import(?:\t| )+)((?:"|\'))(.*?)\4(.*?(?:\r\n|\r|\n|\;)))(.*)$';

		foreach( array( '/'.$preg_ptn_url.'/is' , '/'.$preg_ptn_import.'/is' ) as $preg_ptn ){
			$TMP_CONTENTS = '';
			while( 1 ){
				if( !preg_match( $preg_ptn , $CONTENTS , $result ) ){
					$TMP_CONTENTS .= $CONTENTS;
					unset( $result );
					break;
				}

				if( strlen( $result[2] ) ){
					#	url() の場合
					$TMP_CONTENTS .=		$result[1];
					$CommentOut =			$result[2];
					$PREFIX =				$result[3];
					$DELIMITTER =			$result[4];
					$TARGET_PATH_ORIGINAL =	$result[5];
					$SUFIX =				$result[6];
					$CONTENTS =				$result[7];
				}else{
					#	@import の場合
					$TMP_CONTENTS .=		$result[1];
					$CommentOut =			$result[2];
					$PREFIX =				$result[3];
					$DELIMITTER =			$result[4];
					$TARGET_PATH_ORIGINAL =	$result[5];
					$SUFIX =				$result[6];
					$CONTENTS =				$result[7];
				}

				if( strlen( $CommentOut ) ){
					#	コメントだった場合の処理//PicklesCrawler0.1.13 追加
					$TMP_CONTENTS .= $CommentOut;
					continue;
				}

				if( preg_match( '/\r\n|\r|\n| |\t/s' , $TARGET_PATH_ORIGINAL ) ){ continue; }

				$this->msg( 150 , 'クオート('.$DELIMITTER.')内のパス['.$TARGET_PATH_ORIGINAL.']が含まれています。');

				#--------------------------------------
				#	URLをパースして、変換指示を得る
				$parsed_info = $this->get_replace_url_info( $current_url , $TARGET_PATH_ORIGINAL , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url );
				if( $parsed_info === false ){ continue; }
				$REPLACE_TO = $parsed_info['replace_to'];
				$ADD_URL = $parsed_info['add_url'];
				#	/ URLをパースして、変換指示を得る
				#--------------------------------------

				$this->msg( 150 , '['.$REPLACE_TO.']に変換されます。');

				if( $this->proj->get_path_conv_method() != 'none' ){
					#	パス変換方法設定を考慮して変換
					$TMP_CONTENTS .= $PREFIX.$DELIMITTER.$REPLACE_TO.$DELIMITTER.$SUFIX;
						#	$CONTENTS内で検出したパス($TARGET_PATH_ORIGINAL)を、
						#	保存後のローカルディレクトリ上の相対パス($DELIMITTER.$REPLACE_TO.$DELIMITTER)に変換している。
				}else{
					$TMP_CONTENTS .= $PREFIX.$DELIMITTER.$TARGET_PATH_ORIGINAL.$DELIMITTER.$SUFIX;
				}

				$this->msg( 150 , '['.$ADD_URL.']が、ダウンロードリストに追加されます。');
				$option = array();
				$option['referer'] = $current_url;
				$this->add_download_url( $ADD_URL , $option );

				continue;
			}
			$CONTENTS = $TMP_CONTENTS;
			unset( $TMP_CONTENTS );
			continue;

		}

		return $CONTENTS;
	}//execute_replace_url_css()


	/**
	 * ダウンロードされたその他ファイルに対する処理を実行する
	 */
	private function execute_replace_url_default( $current_url , $CONTENTS , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url ){

		$pregpattern = '/^(.*?)("|\')((?:(?:\.\.?\/|\.?\/|\/|'.preg_quote( $URL_PROTOCOL , '/' ).':\/\/'.preg_quote( $URL_DOMAIN , '/' ).'\/).*?)|[a-zA-Z0-9\-\_\.\/\@]+\.(?:gif|jpg|jpe|jpeg|png|bmp|swf|css|js|txt))\2(.*)$/si';
		$RTN = '';
		while( 1 ){
			if( !preg_match( $pregpattern , $CONTENTS , $matched ) ){
				$RTN .= $CONTENTS;
				break;
			}
			$i = 1;
			$RTN                 .= $matched[$i++];
			$DELIMITTER           = $matched[$i++];
			$TARGET_PATH_ORIGINAL = $matched[$i++];
			$CONTENTS             = $matched[$i++];

			if( preg_match( '/\r\n|\r|\n| |\t/s' , $TARGET_PATH_ORIGINAL ) ){
				#	改行を含んでいた場合、パスを示していない可能性が高いため、スキップ。
				$RTN .= $DELIMITTER.$TARGET_PATH_ORIGINAL.$DELIMITTER;
				continue;
			}

			$this->msg( 150 , 'クオート('.$DELIMITTER.')内のパス['.$TARGET_PATH_ORIGINAL.']が含まれています。');

			#--------------------------------------
			#	URLをパースして、変換指示を得る
			$parsed_info = $this->get_replace_url_info( $current_url , $TARGET_PATH_ORIGINAL , $URL_PROTOCOL , $URL_DOMAIN , $current_virtual_url );
			if( $parsed_info === false ){ continue; }
			$REPLACE_TO = $parsed_info['replace_to'];
			$ADD_URL = $parsed_info['add_url'];
			#	/ URLをパースして、変換指示を得る
			#--------------------------------------

			$this->msg( 150 , '['.$REPLACE_TO.']に変換されます。');

			if( $this->proj->get_path_conv_method() != 'none' ){
				#	パス変換方法設定を考慮して変換
				$RTN .= $DELIMITTER.$REPLACE_TO.$DELIMITTER;
			}else{
				$RTN .= $DELIMITTER.$TARGET_PATH_ORIGINAL.$DELIMITTER;
			}

			$this->msg( 150 , '['.$ADD_URL.']が、ダウンロードリストに追加されます。');
			$option = array();
			$option['referer'] = $current_url;
			$this->add_download_url( $ADD_URL , $option );

			continue;;
		}
		return $RTN;

	}//execute_replace_url_default()

}

?>