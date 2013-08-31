<?php

#============================================================================
#	PxXMLDomParser
#	varsion 1.0.3
#	(C)Tomoya Koyanagi.
#	LastUpdate : 2013/09/01 01:15

class PxXMLDomParser{
	var $last_find_selector = null; //←前回のfind()に使用したセレクタ文字列を記憶。
	var $bin = null; //←XMLの本体を格納(String)

	var $pattern_html = '(?:[a-z0-9A-Z_-]+\:)?[a-zA-Z][a-z0-9A-Z_-]*';
	var $pattern_attribute = '(?:[a-z0-9A-Z_-]+\:)?[a-z0-9A-Z_-]+';

	var $safetycounter_limit = 10000000;
		#	閉じタグを探すループの回数制限。
		#	無限ループに陥ってしまわないための措置。

	var $errorlist = array();
		#	内部エラーを記憶する器。

	var $confvalues = array( // ←各種設定 PxXMLDomParser 1.0.1 追加
		//この設定値は、config()を通じて入出力してください。
		'tags_case_sensitive'=>true ,//←タグの大文字小文字を区別するか。true=区別する(default),false=区別しない;
		'atts_case_sensitive'=>true ,//←属性の大文字小文字を区別するか。true=区別する(default),false=区別しない;
	);

	#--------------------------------------
	#	コンストラクタ
	function PxXMLDomParser( $bin , $input_type = null ){
		if( !strlen( $input_type ) ){
			#	$input_type (入力の種類)を省略したら、自動的に判断する。
			if( @is_file( $bin ) || preg_match( '/^https?\:\/\//' , $bin ) ){
				$input_type = 'path';
			}else{
				$input_type = 'bin';
			}
		}
		if( $input_type == 'path' ){
			#	指定されたのがパスだったら
			$bin = file_get_contents( $bin );
		}

		$detect_encoding = $this->html_detect_encoding_by_src( $bin );
		if( strlen( $detect_encoding ) && is_callable( 'mb_internal_encoding' ) ){
			$bin = $this->convert_encoding( $bin , mb_internal_encoding() , $detect_encoding );
		}
		$this->bin = $bin;
	}

	#--------------------------------------
	#	設定の入出力
	function config( $key , $val = null ){
		if( !strlen( $key ) ){ return null; }
		$args = func_get_args();
		if( count($args) <= 1 ){
			//GETモード
			return $this->confvalues[$key];
		}else{
			//SETモード
			switch( strtolower($key) ){
				case 'tags_case_sensitive':
				case 'atts_case_sensitive':
					//boolな項目
					$this->confvalues[$key] = !empty($val);
					break;
				default:
					//なんでもありな項目
					$this->confvalues[$key] = $val;
					break;
			}
			return true;
		}
	}

	#--------------------------------------
	#	セレクタをセットする(セットするだけ)
	function select( $selector ){
		$selector = trim( $selector );
		if( !strlen( $selector ) ){ return false; }
		$this->last_find_selector = $selector;
		return true;
	}
	#--------------------------------------
	#	セレクタから要素を探す
	function find( $selector ){
		$selector = trim( $selector );
		if( !strlen( $selector ) ){ return false; }
		$this->last_find_selector = $selector;
		return $this->dom_command( 'find' );
	}
	#--------------------------------------
	#	属性をセットする
	function attr( $attname , $value ){
		if( !strlen( $this->last_find_selector ) ){ return false; }
		return $this->dom_command( 'att' , array('attname'=>trim($attname),'value'=>$value) );
	}
	#--------------------------------------
	#	スタイルをセットする
	function css( $property , $value ){
		if( !strlen( $this->last_find_selector ) ){ return false; }
		return $this->dom_command( 'css' , array('property'=>trim($property),'value'=>$value) );
	}
	#--------------------------------------
	#	CSSクラスを追加する
	function addclass( $className ){
		if( !strlen( $this->last_find_selector ) ){ return false; }
		return $this->dom_command( 'addclass' , array('className'=>trim($className)) );
	}
	#--------------------------------------
	#	CSSクラスを削除する
	function removeclass( $className ){
		if( !strlen( $this->last_find_selector ) ){ return false; }
		return $this->dom_command( 'removeclass' , array('className'=>trim($className)) );
	}
	#--------------------------------------
	#	innerHTMLをセットする
	function html( $html ){
		if( !strlen( $this->last_find_selector ) ){ return false; }
		return $this->dom_command( 'html' , array('html'=>$html) );
	}
	#--------------------------------------
	#	innerHTMLにテキストをセットする
	function text( $html ){
		if( !strlen( $this->last_find_selector ) ){ return false; }
		return $this->dom_command( 'html' , array('html'=>htmlspecialchars($html)) );
	}
	#--------------------------------------
	#	outerHTMLを置き換える
	function replace( $method ){
		if( !strlen( $this->last_find_selector ) ){ return false; }
		return $this->dom_command( 'replace' , array('replace_method'=>$method) );
	}
	#--------------------------------------
	#	現時点でのソースコード全体を取得する
	function get_src(){
		return $this->bin;
	}

	#--------------------------------------
	#	HTMLファイル $path の文字エンコード名を得る
	function html_detect_encoding( $path ){
		if( !is_file( $path ) ){ return false; }
		$src = file_get_contents( $path );
		return $this->html_detect_encoding_by_src( $src );
	}
	function html_detect_encoding_by_src( $bin = null ){
		if( is_null( $bin ) ){ $bin = $this->bin; }
		if( is_null( $bin ) ){ return false; }

		$RTN = null;
		if( !strlen( $RTN ) && preg_match( '/<meta(.*?)>/si' , $bin ) ){
			preg_match_all( '/<meta(.*?)>/si' , $bin , $matched );
			foreach( $matched[0] as $num=>$value ){
				$attrStr = $matched[1][$num];
				$attr = array();
				while( strlen( trim( $attrStr ) ) ){
					preg_match( '/([a-zA-Z0-9\-\_]+?)=(?:("|\'|)(.*?)\2|(.*?)(?:\s+|$))(.*)$/s' , $attrStr , $matched2 );
					$keyStr = $matched2[1];
					if( !is_null( $matched2[3] ) ){
						$valStr = $matched2[3];
					}else{
						$valStr = $matched2[4];
					}
					$attrStr = trim( $matched2[5] );
					$attr[strtolower($keyStr)] = $valStr;
					if( $attrStr == '/' ){
						$attrStr = '';
					}
				}

				if( strtolower( $attr['http-equiv'] ) == 'content-type' ){
					$content = trim( strtolower( $attr['content'] ) );
					if( preg_match( '/^[a-zA-Z0-9\-\_]+\/[a-zA-Z0-9\-\_]+\s*\;\s*charset\=(.*)$/si' , $content , $matched3 ) ){
						switch( strtolower( $matched3[1] ) ){
							case 'sjis':
							case 'shift_jis':
								return 'Shift_JIS';break;
							case 'utf-8':
							case 'utf8':
								return 'UTF-8';break;
							case 'euc-jp':
								return 'EUC-JP';break;
						}
					}
				}
			}
		}
		if( !strlen( $RTN ) && is_callable('mb_detect_encoding') ){
			$i = 0;
			while( 1 ){
				$RTN = mb_detect_encoding( $bin );
				if( $RTN !== false ){
					break;
				}
				$bin = preg_replace( '/^.*?[a-zA-Z0-9'.preg_quote('-_.,=<>{}[]()"\'^~+*:;/|','/').']+/s' , '' , $bin );
				if( $i > 10000 ){ break; }
			}
		}
		return $RTN;
	}




	#--------------------------------------
	#	HTMLファイルからコンテンツ領域のみを抜き出す
	function get_contents( $options = null ){
		if( strlen( $options['start'] ) && strlen( $options['end'] ) ){
			#	マークで見つける
			$bin = $this->get_contents_by_mark( $options['start'] , $options['end'] );
		}elseif( strlen( $options['selector'] ) ){
			#	セレクタで見つける
			$bin = $this->find( $options['selector'] );
		}

		return $bin;
	}

	#--------------------------------------
	#	コンテンツをマークで見つける
	function get_contents_by_mark( $mark_start , $mark_end ){
		$bin = $this->bin;

		$detect_encoding = $this->html_detect_encoding_by_src( $bin );
		if( $detect_encoding ){
			$bin = $this->convert_encoding( $bin , mb_internal_encoding() , $detect_encoding );
		}

		if( preg_match( '/'.preg_quote( $mark_start , '/' ).'(.*)$/s' , $bin , $matched ) ){
			$bin = $matched[1];
		}
		if( preg_match( '/^(.*)'.preg_quote( $mark_end , '/' ).'/s' , $bin , $matched ) ){
			$bin = $matched[1];
		}

		return $bin;
	}//get_contents_by_mark();
	#	/ コンテンツをマークで見つける
	#--------------------------------------



	#--------------------------------------
	#	DOMコマンドを実行する
	function dom_command( $command_name = 'find' , $option = array() ){
		if( !strlen( $this->last_find_selector ) ){ return false; }
		$selector = $this->last_find_selector;

		$bin = $this->bin;

		#	セレクタを整理する。
		$selectorInfoList = $this->parse_cssselector( $selector );

		$RTN = array();//返却値：ヒットしたinnerHTMLを上から順に格納する
		$pedigree = array();//上位階層エレメント情報を世代順に格納する

		$pattern_html = $this->get_pattern_html( null );//←全部のエレメントが調査対象

		$str_prev = '';
		$str_next = $bin;
		while( strlen( $str_next ) ){

			$is_hit = 0;
			$str_nextMemo = '';
			while( 1 ){
				#	PxFW 0.6.6 : ヒットしなかった場合にリトライするようにした。
				#	PxFW 0.6.7 : リトライのロジックを見直した。
				#	(http://www.pxt.jp/ja/diary/article/218/index.html この問題への対応)
				$tmp_start = strpos( $str_next , '<' );
				if( !is_int( $tmp_start ) || $tmp_start < 0 ){
					#	[<]記号 が見つからなかったら、
					#	本当にヒットしなかったものとみなせる。
					$str_next    = $str_nextMemo.$str_next;
					break;
				}
				$str_nextMemo .= substr( $str_next , 0 , $tmp_start );
				$str_next = substr( $str_next , $tmp_start );
				$is_hit = preg_match( $pattern_html , $str_next , $results );
				if( $is_hit ){
					#	ヒットしたらここでbreak;
					$results[1] = $str_nextMemo.$results[1];
					$str_next    = $str_nextMemo.$str_next;
					break;
				}
				//今回先頭にあるはずの[<]記号を $str_nextMemo に移してリトライ
				$str_nextMemo .= substr( $str_next , 0 , 1 );
				$str_next = substr( $str_next , 1 );
			}
			unset( $str_nextMemo );
			unset( $tmp_start );

			if( !$is_hit ){
				$this->bin = $str_prev.$str_next;
				return $RTN;
			}
			if( !is_null( $results[0] ) ){
				$MEMO = array();
				$preg_number = 0;
				$preg_number ++;
				$MEMO['str_prev']			= $results[$preg_number++];
				$MEMO['start_tag']			= $results[$preg_number++];
				$MEMO['commentout']			= $results[$preg_number++];
				$MEMO['cdata']				= $results[$preg_number++];
				$MEMO['php_script']			= $results[$preg_number++];
				$MEMO['close_tag_mark']		= $results[$preg_number++];
				$MEMO['tag']				= $results[$preg_number++];
				$MEMO['tagOriginal']		= $MEMO['tag'];
				$MEMO['attribute_str']		= $results[$preg_number++];
				$MEMO['att_quot']			= $results[$preg_number++];
				$MEMO['self_closed_flg']	= $results[$preg_number++];
				$MEMO['str_next']			= $results[$preg_number++];
				unset( $preg_number );

				$MEMO['attribute'] = $this->html_attribute_parse( $MEMO['attribute_str'] );

				$str_prev .= $MEMO['str_prev'];
				$str_next = $MEMO['str_next'];


				if( strlen( $MEMO['commentout'] ) || strlen( $MEMO['php_script'] ) ){
					$str_prev .= $MEMO['start_tag'];
					continue;
				}

				//閉じタグ探しとく
				$searched_closetag = array();
				if( !strlen( $MEMO['close_tag_mark'] ) && !strlen( $MEMO['self_closed_flg'] ) ){
					$searched_closetag = $this->search_closetag( $MEMO['tagOriginal'] , $MEMO['str_next'] );
					if( $searched_closetag['content_str'] === false && strlen( $searched_closetag['error'] ) ){
						#	閉じタグがなかったら(パースエラー)
						#	自己完結タグなことにしちゃう。
						$searched_closetag['content_str'] = null;
//						$MEMO['start_tag'] = preg_replace( '/\/*>$/si' , ' />' , $MEMO['start_tag'] );
						$MEMO['self_closed_flg'] = '/';
					}
				}

				if( strlen( $MEMO['self_closed_flg'] ) ){
					//自己完結タグを見つけた場合
					array_push( $pedigree , array( 'tag'=>$MEMO['tag'] , 'attribute'=>$MEMO['attribute'] ) );
				}else{
					if( !strlen( $MEMO['close_tag_mark'] ) ){
						//開始タグを見つけた場合
						array_push( $pedigree , array( 'tag'=>$MEMO['tag'] , 'attribute'=>$MEMO['attribute'] ) );
					}else{
						//閉じタグを見つけた場合
						array_pop( $pedigree );
						$str_prev .= $MEMO['start_tag'];
						continue;
					}
				}

				//カレントの要素が収集対象か否か評価
				$is_hit = $this->is_element_hit( $pedigree , $selectorInfoList );

				if( strlen( $MEMO['self_closed_flg'] ) ){
					#	自己完結タグだったら
					array_pop( $pedigree );
				}

				if( $is_hit ){
					if( $command_name == 'html' && strlen( $option['html'] ) ){
						$MEMO['self_closed_flg'] = null;
					}

					#--------------------------------------
					#	収集対象だったら
					if( $command_name == 'html' || $command_name == 'replace' || $command_name == 'addclass' || $command_name == 'removeclass' || $command_name == 'att' || $command_name == 'css' ){
						if( $command_name == 'addclass' || $command_name == 'removeclass' ){
							#--------------------------------------
							#	クラスの追加・削除
							$classList = array();
							if( strlen( trim( $MEMO['attribute']['class'] ) ) ){
								$classList = preg_split( '/\s+/si' , trim( $MEMO['attribute']['class'] ) );
							}
							$className_ishit = false;
							foreach( $classList as $classNum=>$classLine ){
								if( $option['className'] == $classLine ){
									if( $command_name == 'removeclass' ){
										unset( $classList[$classNum] );//削除する場合
									}
									$className_ishit = true;
									break;
								}
							}
							if( $command_name == 'addclass' && !$className_ishit ){
								array_push( $classList , $option['className'] );
							}
							if( count( $classList ) ){
								$MEMO['attribute']['class'] = implode( ' ' , $classList );
							}else{
								unset( $MEMO['attribute']['class'] );
							}
							unset( $classList );
							#	/ クラスの追加・削除
							#--------------------------------------
						}elseif( $command_name == 'att' ){
							#--------------------------------------
							#	属性のセット
							if( strlen( $option['attname'] ) ){
								if( !is_null( $option['value'] ) ){
									$MEMO['attribute'][$option['attname']] = $option['value'];
								}else{
									unset( $MEMO['attribute'][$option['attname']] );
								}
							}
							#	/ 属性のセット
							#--------------------------------------
						}elseif( $command_name == 'css' ){
							#--------------------------------------
							#	スタイルのセット
							$styleList = array();
							if( strlen( trim( $MEMO['attribute']['style'] ) ) ){
								$tmp_styleList = preg_split( '/\s*\;\s*/si' , trim( $MEMO['attribute']['style'] ) );
								foreach( $tmp_styleList as $tmp_styleNum=>$tmp_styleLine ){
									if( strlen( trim($tmp_styleLine) ) ){
										$tmp_styleLine = explode(':',trim($tmp_styleLine));
										$styleList[trim(strtolower($tmp_styleLine[0]))] = trim($tmp_styleLine[1]);
									}
								}
								unset( $tmp_styleList );
							}
							$styleName_ishit = false;
							foreach( $styleList as $styleName=>$styleLine ){
								if( trim( strtolower( $option['property'] ) ) == trim( strtolower( $styleName ) ) ){
									if( is_null( $option['value'] ) ){
										unset( $styleList[$styleName] );//削除する場合
									}
									$styleName_ishit = true;
									break;
								}
							}
							if( !is_null( $option['value'] ) ){
								$styleList[trim( strtolower( $option['property'] ) )] = trim( $option['value'] );
							}
							if( count( $styleList ) ){
								$tmp_styleList = array();
								foreach( $styleList as $styleName=>$styleLine ){
									array_push( $tmp_styleList , implode(':',array($styleName,$styleLine)) );
								}
								$MEMO['attribute']['style'] = implode( '; ' , $tmp_styleList ).';';
								unset( $tmp_styleList );
							}else{
								unset( $MEMO['attribute']['style'] );
							}
							unset( $styleList );
							#	/ スタイルのセット
							#--------------------------------------
						}
						$starttag = '<'.$MEMO['tagOriginal'];
						if( is_array($MEMO['attribute']) ){
							foreach( $MEMO['attribute'] as $attName=>$attVal ){
								$starttag .= ' '.htmlspecialchars($attName);
								if( !is_null( $attVal ) ){
									$starttag .= '="'.htmlspecialchars( $attVal ).'"';
								}
							}
						}
						if( strlen( $MEMO['self_closed_flg'] ) ){
							$starttag .= ' '.$MEMO['self_closed_flg'];
						}
						$starttag .= '>';
						$MEMO['start_tag'] = $starttag;
						unset($starttag);
					}
					$tmpRTN = array();
					$tmpRTN['tagName'] = $MEMO['tagOriginal'];
					$tmpRTN['innerHTML'] = $searched_closetag['content_str'];
					if( strlen( $searched_closetag['content_str'] ) || !strlen( $MEMO['self_closed_flg'] ) ){
						$tmpRTN['outerHTML'] = $MEMO['start_tag'].$searched_closetag['content_str'].'</'.$MEMO['tagOriginal'].'>';
					}else{
						$tmpRTN['outerHTML'] = $MEMO['start_tag'];
					}
					$tmpRTN['attributes'] = $MEMO['attribute'];

					if( $command_name == 'html' ){
						#	HTMLの置き換え要求への対応
						if( count( $searched_closetag ) ){
							$searched_closetag['content_str'] = $option['html'];
							$MEMO['start_tag'] .= $option['html'].'</'.$MEMO['tagOriginal'].'>';
							$str_next = $searched_closetag['str_next'];
						}elseif( strlen( $option['html'] ) ){
							$MEMO['start_tag'] .= $option['html'].'</'.$MEMO['tagOriginal'].'>';
						}
						$tmpRTN['innerHTML'] = $option['html'];
						$tmpRTN['outerHTML'] = $MEMO['start_tag'];
					}elseif( $command_name == 'replace' ){
						#	HTMLの書き換え要求への対応
						if( is_array( $option['replace_method'] ) ){
							if( is_object( $option['replace_method'][0] ) ){
								$tmpRTN['outerHTML'] = $option['replace_method'][0]->$option['replace_method'][1]( $tmpRTN , count($RTN) );
							}elseif( is_string( $option['replace_method'][0] ) && class_exists( $option['replace_method'][0] ) ){
								$tmpRTN['outerHTML'] = eval( 'return '.$option['replace_method'][0].'::'.$option['replace_method'][1].'( $tmpRTN , count($RTN) );' );
							}
						}else{
							$tmpRTN['outerHTML'] = $option['replace_method']( $tmpRTN , count($RTN) );
						}
						$MEMO['start_tag'] = $tmpRTN['outerHTML'];
						if( count( $searched_closetag ) ){
							$str_next = $searched_closetag['str_next'];
						}
					}

					array_push( $RTN , $tmpRTN );
					unset( $tmpRTN );
				}
				$str_prev .= $MEMO['start_tag'];
				unset( $searched_closetag );

			}
			unset( $MEMO );
		}

		$this->bin = $str_prev.$str_next;

		return $RTN;
	}//dom_command();
	#	/ コンテンツをセレクタで見つける
	#--------------------------------------

	#--------------------------------------
	#	セレクタにヒットする要素か否か調べる
	function is_element_hit( $pedigree , $selectorInfoList ){
		$kouhoList = array();
		array_push( $kouhoList , $pedigree );

		#--------------------------------------
		#	セレクタを順に解釈
		foreach( $selectorInfoList as $selectorNum=>$selectorInfo ){
			$tmp_kouhoList = array();

			#--------------------------------------
			#	残っている候補を順に評価
			foreach( $kouhoList as $kouho ){
				$tmp_kouho = array();
				for( $i = 0; $i < count( $kouho ); $i ++ ){
					$is_hit = true;

					#	タグ名を評価
					if( strlen( $selectorInfo['tagName'] ) && $selectorInfo['tagName'] != '*' ){
						if( !$this->config( 'tags_case_sensitive' ) ){
							//大文字小文字の区別をしない : PxXMLDomParser 1.0.1
							if( strtolower($selectorInfo['tagName']) != strtolower($kouho[$i]['tag']) ){
								$is_hit = false;//ヒットしない
							}
						}else{
							if( $selectorInfo['tagName'] != $kouho[$i]['tag'] ){
								$is_hit = false;//ヒットしない
							}
						}
					}
					#	ID名を評価
					if( strlen( $selectorInfo['id'] ) ){
						if( !$this->config( 'atts_case_sensitive' ) ){
							//大文字小文字の区別をしない : PxXMLDomParser 1.0.1
							if( strtolower($selectorInfo['id']) != strtolower($kouho[$i]['attribute']['id']) ){
								$is_hit = false;//ヒットしない
							}
						}else{
							if( $selectorInfo['id'] != $kouho[$i]['attribute']['id'] ){
								$is_hit = false;//ヒットしない
							}
						}
					}
					#	class名を評価
					if( is_array( $selectorInfo['class'] ) && count( $selectorInfo['class'] ) ){
						$classname_is_hit = false;
						foreach( $selectorInfo['class'] as $selector_class ){
							foreach( preg_split( '/\s+/' , $kouho[$i]['attribute']['class'] ) as $tag_class ){
								if( !strlen( $tag_class ) ){ continue; }
								if( !$this->config( 'atts_case_sensitive' ) ){
									//大文字小文字の区別をしない : PxXMLDomParser 1.0.1
									if( strtolower($selector_class) == strtolower($tag_class) ){
										$classname_is_hit = true;
										break 2;
									}
								}else{
									if( $selector_class == $tag_class ){
										$classname_is_hit = true;
										break 2;
									}
								}
							}
						}
						if( !$classname_is_hit ){
							$is_hit = false;//ヒットしない
						}
					}
					#	属性値を評価
					if( is_array( $selectorInfo['attributes'] ) && count( $selectorInfo['attributes'] ) ){
						$att_is_hit = false;
						foreach( $selectorInfo['attributes'] as $selector_att_key=>$selector_att_val ){
							if( !$this->config( 'atts_case_sensitive' ) ){
								//大文字小文字の区別をしない : PxXMLDomParser 1.0.1
								if( strtolower($kouho[$i]['attribute'][$selector_att_key]) == strtolower($selector_att_val) ){
									$att_is_hit = true;
									break;
								}
							}else{
								if( $kouho[$i]['attribute'][$selector_att_key] == $selector_att_val ){
									$att_is_hit = true;
									break;
								}
							}
						}
						if( !$att_is_hit ){
							$is_hit = false;//ヒットしない
						}
					}

					if( $selectorInfo['child_flg'] && !$is_hit ){
						#	次のセレクタが直子じゃないと該当しない指示の場合
						$is_hit = false;//ヒットしない
						break;//かつ、この候補はこれで寿命が尽きる。
					}

					#	候補として残す。
					if( $is_hit ){
						$tmp_tmp_kouho = array();
						for( $tmp_start_i = $i+1; $tmp_start_i < count( $kouho ); $tmp_start_i ++ ){
							array_push( $tmp_tmp_kouho , $kouho[$tmp_start_i] );
						}
						array_push( $tmp_kouhoList , $tmp_tmp_kouho );
					}
				}
			}
			#	/ 残っている候補を順に評価
			#--------------------------------------
			$kouhoList = $tmp_kouhoList;
			unset( $tmp_kouhoList );
			if( !count( $kouhoList ) ){
				break;//候補がなくなったら終わり。
			}
		}
		#	/ セレクタを順に解釈
		#--------------------------------------

		if( !count( $kouhoList ) ){
			#	残ってなかったら false
			return false;
		}
		foreach( $kouhoList as $kouho ){
			if( count( $kouho ) === 0 ){
				#	要素の階層が1層だけ残っている場合のみ、
				#	その要素自身が対象となる。
				return true;
			}
		}
		return false;
	}
	#	/ セレクタにヒットする要素か否か調べる
	#--------------------------------------



	#--------------------------------------
	#	セレクタを整理する。
	function parse_cssselector( $selector ){
		$tmp_selectorList = preg_split( '/\s+/' , $selector );
		$selectorList = array();
		$tmp_memo = '';
		foreach( $tmp_selectorList as $line ){
			$line = trim( $line );
			$tmp_memo .= $line;
			if( $tmp_memo == '>' ){
				continue;
			}
			array_push( $selectorList , $tmp_memo );
			$tmp_memo = '';
		}
		unset( $tmp_selectorList );
		unset( $tmp_memo );
		unset( $line );

		$RTN = array();

		#--------------------------------------
		#	セレクタを順番に検索する
		for( $i = 0; $i < count( $selectorList ); $i ++ ){
			$selectorLine = $selectorList[$i];

			#--------------------------------------
			#	セレクタを解析する
			$selectorInfo = array();
			$selectorInfo['child_flg'] = false;
			if( preg_match( '/^>(.*)$/si' , $selectorLine , $matched ) ){
				$selectorInfo['child_flg'] = true;
				$selectorLine = $matched[1];
			}

			preg_match( '/^((?:[a-zA-Z0-9\-\_]+\:)?[a-zA-Z0-9\-\_]+)?((?:(?:#|\\.)[a-zA-Z0-9\-\_]+)*)?((?:\[[0-9a-zA-Z\-\_]+\=[0-9a-zA-Z\-\_]*\])*)$/s' , $selectorLine , $matched );
			$selectorInfo['tagName'] = $matched[1];
			$selectorInfo['id'] = null;
			$selectorInfo['class'] = array();
			$selectorInfo['attributes'] = array();
			$str_options = trim( $matched[2] );
			$str_attributes = trim( $matched[3] );

			while( strlen( $str_options ) ){
				if( !preg_match( '/^(#|\\.)([a-zA-Z0-9\-\_]+)(.*)$/s' , $str_options , $matched ) ){
					break;
				}
				$type = $matched[1];
				$name = $matched[2];
				$str_options = trim( $matched[3] );
				if( $type == '#' ){
					$selectorInfo['id'] = $name;
				}elseif( $type == '.' ){
					array_push( $selectorInfo['class'] , $name );
				}
			}

			while( strlen( $str_attributes ) ){
				if( !preg_match( '/^\[(.*?)=(.*?)\](.*)$/s' , $str_attributes , $matched ) ){
					break;
				}
				$key = $matched[1];
				$val = $matched[2];
				$str_attributes = trim( $matched[3] );
				$selectorInfo['attributes'][trim($key)] = trim($val);
			}

			unset( $type );
			unset( $name );
			unset( $matched );
			#	/ セレクタを解析する
			#--------------------------------------

			array_push( $RTN , $selectorInfo );
		}
		#	/ セレクタを順番に検索する
		#--------------------------------------

		return $RTN;
	}//parse_cssselector()
	#	/ セレクタを整理する。
	#--------------------------------------



	#--------------------------------------
	#	HTMLタグを検出するPREGパターンを生成して返す。
	#	(base_resources_htmlparser からの移植->改造)
	function get_pattern_html( $tagName = null ){
		#	タグの種類
		$tag = $this->pattern_html;
		if( strlen( $tagName ) ){
			$tag = $tagName;
		}
		$att = $this->pattern_attribute;

		$rnsp = '(?:\r\n|\r|\n| |\t)';

		#	属性のパターン
		#	属性の中にタグがあってはならない
		$atteribute = ''.$rnsp.'*(?:'.$rnsp.'*(?:'.$att.')(?:'.$rnsp.'*\='.$rnsp.'*(?:(?:[^"\' ]+)|([\'"]?).*?\9))?'.$rnsp.'*)*'.$rnsp.'*';

		#	コメントタグのパターン
		$commentout = '(?:<\!--((?:(?!-->).)*)(?:-->)?)';
		$cdata      = '(?:<\!\[CDATA\[(.*?)\]\]>)';
		$php_script = '(?:<\?(?:php)?((?:(?!\?'.'>).)*)(?:\?'.'>)?)';

		$pregstring = '/(.*?)('.$commentout.'|'.$cdata.'|'.$php_script.'|(?:<(\/?)('.$tag.')(?:'.$rnsp.'+('.$atteribute.'))?(\\/?)>))(.*)/s';

		return	$pregstring;
	}//get_pattern_html();
	#	/ HTMLタグを検出するPREGパターンを生成して返す。
	#--------------------------------------

	#--------------------------------------
	#	タグの属性情報を検出するPREGパターンを生成して返す。
	#	(base_resources_htmlparser からの移植)
	function get_pattern_attribute(){
		#	属性の種類
		$rnsp = '(?:\r\n|\r|\n| |\t)';
		$prop = $this->pattern_attribute;
		$typeA = '([\'"]?)(.*?)\3';	#	ダブルクオートあり
		$typeB = '[^"\' ]+';		#	ダブルクオートなし

		#	属性指定の式
		$prop_exists = '/'.$rnsp.'*('.$prop.')(?:\=(?:('.$typeB.')|'.$typeA.'))?'.$rnsp.'*/s';

		return	$prop_exists;
	}//get_pattern_attribute();
	#	/ タグの属性情報を検出するPREGパターンを生成して返す。
	#--------------------------------------

	#--------------------------------------
	#	閉じタグを検索する
	#	(base_resources_htmlparser からの移植)
	function search_closetag( $tagname , $strings ){
		#	タグの深さ
		$att = $this->pattern_attribute;
		$depth = 0;
		$strings_original = $strings;

		$rnsp = '(?:\r\n|\r|\n| |\t)';

		#	属性のパターン
		#	属性の中にタグがあってはならない
		$atteribute = ''.$rnsp.'*?(?:'.$rnsp.'*(?:'.$att.')(?:'.$rnsp.'*'.preg_quote('=','/').''.$rnsp.'*?(?:(?:[^"\' ]+?)|([\'"]?).*?\7))?'.$rnsp.'*)*';

		$case_sensitive_option = '';
		if( !$this->config( 'tags_case_sensitive' ) ){
			$case_sensitive_option = 'i';//←大文字小文字の区別をしない : PxXMLDomParser 1.0.1
		}

		$pregstring = '/^(.*?)(?:('.preg_quote('<'.'?','/').'(?:php)?)|('.preg_quote('<![CDATA[','/').')|('.preg_quote('<!--','/').')|(<(\/?)?(?:'.preg_quote($tagname,'/').')(?:'.$rnsp.'+?'.$atteribute.')?'.'>))(.*)$/'.$case_sensitive_option.'s';

		$safetycounter = 0;
		$RTN = array(
			'content_str'=>'' ,
			'str_next'=>'' ,
		);
		while( true ){
			$safetycounter ++;
			if( is_int( $this->safetycounter_limit ) && $safetycounter > intval( $this->safetycounter_limit ) ){
				#	安全装置作動
				#	$this->safetycounter_limitに設定した数以上先の
				#	閉じタグは探せません。
				$msg = '[SafetyBreak!] on HTML Parser of LINE ['.__LINE__.']. COUNTER = ['.$safetycounter.'] TAGNAME = ['.$tagname.']';
				$this->error( $msg , __FILE__ , __LINE__ );
				return array( 'content_str'=>$msg , 'str_next'=>'' );
			}

			$i = 0;

			$is_hit = 0;
			$stringsMemo = '';
			while( 1 ){
				#	PxFW 0.6.6 : ヒットしなかった場合にリトライするようにした。
				#	PxFW 0.6.7 : リトライのロジックを見直した。
				#	(http://www.pxt.jp/ja/diary/article/218/index.html この問題への対応)
				$tmp_start = strpos( $strings , '<' );
				if( !is_int( $tmp_start ) || $tmp_start < 0 ){
					#	[<]記号 が見つからなかったら、
					#	本当にヒットしなかったものとみなせる。
					$strings    = $stringsMemo.$strings;
					break;
				}
				$stringsMemo .= substr( $strings , 0 , $tmp_start );
				$strings = substr( $strings , $tmp_start );
				$is_hit = preg_match( $pregstring , $strings , $results );
				if( $is_hit ){
					#	ヒットしたらここでbreak;
					$results[1] = $stringsMemo.$results[1];
					$strings    = $stringsMemo.$strings;
					break;
				}
				//今回先頭にあるはずの[<]記号を $stringsMemo に移してリトライ
				$stringsMemo .= substr( $strings , 0 , 1 );
				$strings = substr( $strings , 1 );
			}
			unset( $stringsMemo );
			unset( $tmp_start );

			if( $is_hit ){
				#	何かしらの結果があった場合
				$preg_i = 0;
				$MEMO = array();
				$preg_i ++;
				$MEMO['str_prev']			= $results[$preg_i++];
				$MEMO['start_php']			= $results[$preg_i++];
				$MEMO['start_cdata']		= $results[$preg_i++];
				$MEMO['start_htmlcomment']	= $results[$preg_i++];
				$MEMO['mytag']				= $results[$preg_i++];
				$MEMO['closed_flg']			= $results[$preg_i++];
				$MEMO['attribute_str']		= $results[$preg_i++];
				$MEMO['str_next']			= $results[$preg_i++];

				#--------------------------------------
				#	戻り値を作成
				if( strlen( $MEMO['start_php'] ) ){
					#	PHPスクリプト内の閉じタグは検出してはいけない
					preg_match( '/^(.*?)(?:'.preg_quote('?'.'>','/').')(.*)$/si' , $MEMO['str_next'] , $php_preg_matched );
					$RTN['content_str'] .= $MEMO['str_prev'];
					$RTN['content_str'] .= $MEMO['start_php'];
					$RTN['content_str'] .= $php_preg_matched[1];
					$RTN['content_str'] .= '?'.'>';
					$strings = $php_preg_matched[2];
					continue;

				}elseif( strlen( $MEMO['start_cdata'] ) ){
					#	CDATAセクション内の閉じタグは検出してはいけない
					preg_match( '/^(.*?)(?:'.preg_quote(']]'.'>','/').')(.*)$/si' , $MEMO['str_next'] , $php_preg_matched );
					$RTN['content_str'] .= $MEMO['str_prev'];
					$RTN['content_str'] .= $MEMO['start_cdata'];
					$RTN['content_str'] .= $php_preg_matched[1];
					$RTN['content_str'] .= ']]'.'>';
					$strings = $php_preg_matched[2];
					continue;

				}elseif( strlen( $MEMO['start_htmlcomment'] ) ){
					#	コメントタグ内の閉じタグは検出してはいけない
					preg_match( '/^(.*?)(?:'.preg_quote('--'.'>','/').')(.*)$/si' , $MEMO['str_next'] , $php_preg_matched );
					$RTN['content_str'] .= $MEMO['str_prev'];
					$RTN['content_str'] .= $MEMO['start_htmlcomment'];
					$RTN['content_str'] .= $php_preg_matched[1];
					$RTN['content_str'] .= '--'.'>';
					$strings = $php_preg_matched[2];
					continue;

				}elseif( strlen( $MEMO['closed_flg'] ) && $depth <= 0 ){
					$RTN['content_str'] .= $MEMO['str_prev'];
					#	深さ0階層で、閉じタグを発見した場合
					$RTN['str_next'] .= $MEMO['str_next'];

					return $RTN;
					break;

				}elseif( strlen( $MEMO['closed_flg'] ) && $depth > 0 ){
					$RTN['content_str'] .= $MEMO['str_prev'].$MEMO['mytag'];
					#	深さ1階層以上で、閉じタグを発見した場合
					$depth --;
					$strings = $MEMO['str_next'];
					continue;

				}elseif( !strlen( $MEMO['closed_flg'] ) ){
					$RTN['content_str'] .= $MEMO['str_prev'].$MEMO['mytag'];
					#	入れ子の開始タグを発見してしまった場合
					$depth ++;
					$strings = $MEMO['str_next'];
					continue;

				}else{
					break;

				}

			}
			break;
		}

		#	解析が最後まで行ってしまった場合
		#	つまり、閉じタグがなかった場合
		#	または、ファイルが大きすぎてpregの限界を超えた場合。
		$RTN = array( 'content_str'=>false , 'str_next'=>$strings_original );
		$RTN['error'] = 'Parse error: 閉じタグが見つかりません。('.$tagname.')';
		$this->error( $RTN['error'] , __FILE__ , __LINE__ );

		return $RTN;

	}//search_closetag();
	#	/ 閉じタグを検索する
	#--------------------------------------


	#----------------------------------------------------------------------------
	#	HTML属性の解析
	function html_attribute_parse( $strings ){
		preg_match_all( $this->get_pattern_attribute() , $strings , $results );
		for( $i = 0; !is_null($results[0][$i]); $i++ ){
			if( !strlen($results[3][$i]) ){
				$results[4][$i] = null;
			}
			if( $results[2][$i] ){
				$RTN[strtolower( $results[1][$i] )] = $results[2][$i];
			}else{
				$RTN[strtolower( $results[1][$i] )] = $results[4][$i];
			}
		}
		return	$RTN;
	}//html_attribute_parse();
	#	/ HTML属性の解析
	#----------------------------------------------------------------------------

	#----------------------------------------------------------------------------
	#	内部エラーハンドラ

	#	クラス内にエラーを保持する
	#	(base_resources_htmlparser からの移植)
	function error( $errormessage , $FILE = null , $LINE = null ){
		$ERROR = array();
		$ERROR['msg'] = $errormessage;
		$ERROR['file'] = $FILE;
		$ERROR['line'] = $LINE;
		array_push( $this->errorlist , $ERROR );
		return	true;
	}

	#	保持したエラーを取得する
	#	(base_resources_htmlparser からの移植)
	function get_errorlist(){
		return	$this->errorlist;
	}

	#	エラーが発生したか否か調べる
	#	(base_resources_htmlparser からの移植)
	function is_error(){
		if( count( $this->errorlist ) ){
			return	true;
		}
		return	false;
	}

	#----------------------------------------------------------------------------
	#	受け取ったテキストを、指定の文字コードに変換する
	#	PxFW base_static_text からの移植
	function convert_encoding( $TEXT = null , $encode = null , $encodefrom = null ){
		if( !is_callable( 'mb_internal_encoding' ) ){ return $TEXT; }
		if( !strlen( $encodefrom ) ){ $encodefrom = mb_internal_encoding().',UTF-8,SJIS,EUC-JP,JIS'; }
		if( !strlen( $encode ) ){ $encode = mb_internal_encoding(); }

		if( is_array( $TEXT ) ){
			$RTN = array();
			if( !count( $TEXT ) ){ return	$TEXT; }
			$TEXT_KEYS = array_keys( $TEXT );
			foreach( $TEXT_KEYS as $Line ){
				$KEY = mb_convert_encoding( $Line , $encode , $encodefrom );
				if( is_array( $TEXT[$Line] ) ){
					$RTN[$KEY] = $this->convert_encoding( $TEXT[$Line] , $encode , $encodefrom );
				}else{
					$RTN[$KEY] = @mb_convert_encoding( $TEXT[$Line] , $encode , $encodefrom );
				}
			}
		}else{
			if( !strlen( $TEXT ) ){ return	$TEXT; }
			$RTN = @mb_convert_encoding( $TEXT , $encode , $encodefrom );
		}
		return	$RTN;
	}

}

?>