<?php

/**
 * テキストを扱うクラス(static)
 * 
 * インスタンス化せず、スタティックに使用してください。
 * 
 * @author Tomoya Koyanagi
 */
class t{

	#------------------------------------------------------------------------------------------------------------------
	#	★文字変換系

	/**
	 * HTML特殊文字を変換する
	 * htmlspecialchars() のエイリアス
	 */
	public function h($text){
		return htmlspecialchars($text);
	}

	/**
	 * 受け取ったテキストをHTML形式に変換する
	 * htmlspecialchars() を通した上で、改行コードを<br/>に変換する。
	 */
	public function text2html( $text = '' ){
		$text = htmlspecialchars( $text );
		$text = preg_replace('/\r\n|\r|\n/','<br />',$text);
		return	$text;
	}

	/**
	 * HTMLソースをテキストに変換する。
	 */
	public function html2text( $TEXT = '' ){
		$TEXT = preg_replace( '/<br(?: ?\/)?'.'>/is' , "\n" , $TEXT );
		$TEXT = strip_tags( $TEXT );
		$TEXT = preg_replace( '/&lt;/' , '<' , $TEXT );
		$TEXT = preg_replace( '/&gt;/' , '>' , $TEXT );
		$TEXT = preg_replace( '/&quot;/' , '"' , $TEXT );
		$TEXT = preg_replace( '/&amp;/' , '&' , $TEXT );
		return	$TEXT;
	}

	/**
	 * 特殊な HTML エンティティを文字に戻す
	 */
	public function htmlspecialchars_decode( $TEXT = '' ){
		#	PxFW 0.6.6 追加
		#	htmlspecialchars_decode()というのもあるが、
		#	PHP5.1.0以降なので使わない。
		$TEXT = preg_replace( '/&lt;/' , '<' , $TEXT );
		$TEXT = preg_replace( '/&gt;/' , '>' , $TEXT );
		$TEXT = preg_replace( '/&quot;/' , '"' , $TEXT );
		$TEXT = preg_replace( '/&amp;/' , '&' , $TEXT );
		return	$TEXT;
	}

	/**
	 * シングルクオートで囲えるようにエスケープ処理する。
	 */
	public function escape_singlequote( $TEXT = '' ){
		$TEXT = preg_replace( '/\\\\/' , '\\\\\\\\' , $TEXT);
		$TEXT = preg_replace( '/\'/' , '\\\'' , $TEXT);
		return	$TEXT;
	}

	/**
	 * ダブルクオートで囲えるようにエスケープ処理する。
	 */
	public function escape_doublequote( $TEXT = '' ){
		$TEXT = preg_replace( '/\\\\/' , '\\\\\\\\' , $TEXT);
		$TEXT = preg_replace( '/"/' , '\\"' , $TEXT);
		return	$TEXT;
	}

	/**
	 * ファイル名やパス名から、拡張子を削除する
	 */
	public function trimext( $filename ){
		#	trim extension
		$path_parts = pathinfo($filename);
		$extension = $path_parts['extension'];
		if( !strlen( $extension ) ){
			#	拡張子が取れない場合は、無変換で返す。
			return	$filename;
		}
		$RTN = substr( $filename , 0 , strlen($filename) - strlen( '.'.$extension ) );
		return	$RTN;
	}



	#------------------------------------------------------------------------------------------------------------------
	#	★変数変換系

	/**
	 * 受け取ったテキストを、指定の文字コードに変換する
	 */
	public function convert_encoding( $TEXT = null , $encode = null , $encodefrom = null ){
		if( !is_callable( 'mb_internal_encoding' ) ){ return $TEXT; }
		if( !strlen( $encodefrom ) ){ $encodefrom = mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII'; }
		if( !strlen( $encode ) ){ $encode = mb_internal_encoding(); }

		if( is_array( $TEXT ) ){
			$RTN = array();
			if( !count( $TEXT ) ){ return	$TEXT; }
			$TEXT_KEYS = array_keys( $TEXT );
			foreach( $TEXT_KEYS as $Line ){
				$KEY = mb_convert_encoding( $Line , $encode , $encodefrom );
				if( is_array( $TEXT[$Line] ) ){
					$RTN[$KEY] = t::convert_encoding( $TEXT[$Line] , $encode , $encodefrom );
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

	/**
	 * stripslashes()処理を実行する
	 */
	public function stripslashes( $TEXT ){
		#	この関数は、配列を受け取ると再帰的に文字列を変換して返します。
		if( is_array( $TEXT ) ){
			#	配列なら
			foreach( $TEXT as $key=>$val ){
				$TEXT[$key] = t::stripslashes( $val );
			}
		}elseif( is_string( $TEXT ) ){
			#	文字列なら
			$TEXT = stripslashes( $TEXT );
		}
		return	$TEXT;
	}

	/**
	 * 半角に変換する
	 */
	public function hankaku( $TEXT ){
		return	@mb_convert_kana( $TEXT , 'a' , @mb_internal_encoding() );
	}

	/**
	 * 変数を受け取り、PHPのシンタックスに変換する
	 */
	public function data2text( $value = null , $option = array() ){
		#======================================
		#	[ $option ]
		#		delete_arrayelm_if_null
		#			配列の要素が null だった場合に削除。
		#		array_break
		#			配列に適当なところで改行を入れる。
		#======================================


		if( is_array( $value ) ){
			#	配列
			$RTN .= 'array(';
			if( $option['array_break'] ){ $RTN .= "\n"; }
			$keylist = array_keys( $value );
			foreach( $keylist as $Line ){
				if( $option['delete_arrayelm_if_null'] && is_null( $value[$Line] ) ){
					#	配列のnull要素を削除するオプションが有効だった場合
					continue;
				}
				$RTN .= ''.t::data2text( $Line ).'=>'.t::data2text( $value[$Line] , $option ).',';
				if( $option['array_break'] ){ $RTN .= "\n"; }
			}
			$RTN = preg_replace( '/,(?:\r\n|\r|\n)?$/' , '' , $RTN );
			$RTN .= ')';
			if( $option['array_break'] ){ $RTN .= "\n"; }
			return	$RTN;
		}

		if( is_int( $value ) ){
			#	数値
			return	$value;
		}

		if( is_float( $value ) ){
			#	浮動小数点
			return	$value;
		}

		if( is_string( $value ) ){
			#	文字列型
			$RTN = '\''.t::escape_singlequote( $value ).'\'';
			$RTN = preg_replace( '/\r\n|\r|\n/' , '\'."\\n".\'' , $RTN );
			$RTN = preg_replace( '/'.preg_quote('<'.'?','/').'/' , '<\'.\'?' , $RTN );
			$RTN = preg_replace( '/'.preg_quote('?'.'>','/').'/' , '?\'.\'>' , $RTN );
			$RTN = preg_replace( '/'.preg_quote('/'.'*','/').'/' , '/\'.\'*' , $RTN );
			$RTN = preg_replace( '/'.preg_quote('*'.'/','/').'/' , '*\'.\'/' , $RTN );
			$RTN = preg_replace( '/<(scr)(ipt)/i' , '<$1\'.\'$2' , $RTN );
			$RTN = preg_replace( '/\/(scr)(ipt)>/i' , '/$1\'.\'$2>' , $RTN );
			$RTN = preg_replace( '/<(sty)(le)/i' , '<$1\'.\'$2' , $RTN );
			$RTN = preg_replace( '/\/(sty)(le)>/i' , '/$1\'.\'$2>' , $RTN );
			$RTN = preg_replace( '/<\!\-\-/i' , '<\'.\'!\'.\'--' , $RTN );
			$RTN = preg_replace( '/\-\->/i' , '--\'.\'>' , $RTN );
			return	$RTN;
		}

		if( is_null( $value ) ){
			#	ヌル
			return	'null';
		}

		if( is_object( $value ) ){
			#	オブジェクト型
			return	'\''.t::escape_singlequote( gettype( $value ) ).'\'';
		}

		if( is_resource( $value ) ){
			#	リソース型
			return	'\''.t::escape_singlequote( gettype( $value ) ).'\'';
		}

		if( is_bool( $value ) ){
			#	ブール型
			if( $value ){
				return	'true';
			}else{
				return	'false';
			}
		}

		return	'\'unknown\'';

	}

	/**
	 * 変数をPHPのソースコードに変換する
	 * includeしたらそのままの値を返す形になるよう変換する。
	 */
	public function data2phpsrc( $value = null , $option = array() ){
		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	/'.'* '.@mb_internal_encoding().' *'.'/'."\n";
		$RTN .= '	return '.t::data2text( $value , $option ).';'."\n";
		$RTN .= '?'.'>';
		return	$RTN;
	}

	/**
	 * 変数をJavaScriptのシンタックスに変換する
	 */
	public function data2jssrc( $value = null , $option = array() ){
		#======================================
		#	[ $option ]
		#		delete_arrayelm_if_null
		#			配列の要素が null だった場合に削除。
		#		array_break
		#			配列に適当なところで改行を入れる。
		#======================================

		if( is_array( $value ) ){
			#	配列
			$is_hash = false;
			$i = 0;
			foreach( $value as $key=>$val ){
				#	ArrayかHashか見極める
				if( !is_int( $key ) ){
					$is_hash = true;
					break;
				}
				if( $key != $i ){
					#	順番通りに並んでなかったらHash とする。
					$is_hash = true;
					break;
				}
				$i ++;
			}

			if( $is_hash ){
				$RTN .= '{';
			}else{
				$RTN .= '[';
			}
			if( $option['array_break'] ){ $RTN .= "\n"; }
			foreach( $value as $key=>$val ){
				if( $option['delete_arrayelm_if_null'] && is_null( $value[$key] ) ){
					#	配列のnull要素を削除するオプションが有効だった場合
					continue;
				}
				if( $is_hash ){
					$RTN .= ''.t::data2jssrc( $key.'' , $option ).':';
				}
				$RTN .= t::data2jssrc( $value[$key] , $option );
				$RTN .= ', ';
				if( $option['array_break'] ){ $RTN .= "\n"; }
			}
			$RTN = preg_replace( '/,(?:\s+)?(?:\r\n|\r|\n)?$/' , '' , $RTN );
			if( $is_hash ){
				$RTN .= '}';
			}else{
				$RTN .= ']';
			}
			if( $option['array_break'] ){ $RTN .= "\n"; }
			return	$RTN;
		}

		if( is_object( $value ) ){
			#	オブジェクト型
			$RTN = '';
			$RTN .= '{';
			$proparray = get_object_vars( $value );
			$methodarray = get_class_methods( get_class( $value ) );
			foreach( $proparray as $key=>$val ){
				$RTN .= ''.t::data2jssrc( $key , $option ).':';

				$RTN .= t::data2jssrc( $val , $option );
				$RTN .= ', ';
			}
			$RTN = preg_replace( '/,(?:\s+)?(?:\r\n|\r|\n)?$/' , '' , $RTN );
			$RTN .= '}';
			return	$RTN;
		}

		if( is_int( $value ) ){
			#	数値
			return	$value;
		}

		if( is_float( $value ) ){
			#	浮動小数点
			return	$value;
		}

		if( is_string( $value ) ){
			#	文字列型
			$RTN = '"'.t::escape_doublequote( $value ).'"';
			$RTN = preg_replace( '/\r\n|\r|\n/' , '"+"\n"+"' , $RTN );
			$RTN = preg_replace( '/'.preg_quote('<'.'?','/').'/' , '<"+"?' , $RTN );
			$RTN = preg_replace( '/'.preg_quote('?'.'>','/').'/' , '?"+">' , $RTN );
			$RTN = preg_replace( '/'.preg_quote('/'.'*','/').'/' , '/"+"*' , $RTN );
			$RTN = preg_replace( '/'.preg_quote('*'.'/','/').'/' , '*"+"/' , $RTN );
			$RTN = preg_replace( '/<(scr)(ipt)/i' , '<$1"+"$2' , $RTN );
			$RTN = preg_replace( '/\/(scr)(ipt)>/i' , '/$1"+"$2>' , $RTN );
			$RTN = preg_replace( '/<(sty)(le)/i' , '<$1"+"$2' , $RTN );
			$RTN = preg_replace( '/\/(sty)(le)>/i' , '/$1"+"$2>' , $RTN );
			$RTN = preg_replace( '/<\!\-\-/i' , '<"+"!"+"--' , $RTN );
			$RTN = preg_replace( '/\-\->/i' , '--"+">' , $RTN );
			return	$RTN;
		}

		if( is_null( $value ) ){
			#	ヌル
			return	'null';
		}

		if( is_resource( $value ) ){
			#	リソース型
			return	'undefined';
		}

		if( is_bool( $value ) ){
			#	ブール型
			if( $value ){
				return	'true';
			}else{
				return	'false';
			}
		}

		return	'undefined';

	}

	/**
	 * 変数をXMLのシンタックスに変換する
	 */
	public function data2xml( $value = null , $option = array() ){
		#======================================
		#	[ $option ]
		#		delete_arrayelm_if_null
		#			配列の要素が null だった場合に削除。
		#		array_break
		#			配列に適当なところで改行を入れる。
		#======================================

		if( is_array( $value ) ){
			#	配列
			$is_hash = false;
			$i = 0;
			foreach( $value as $key=>$val ){
				#	ArrayかHashか見極める
				if( !is_int( $key ) ){
					$is_hash = true;
					break;
				}
				if( $key != $i ){
					#	順番通りに並んでなかったらHash とする。
					$is_hash = true;
					break;
				}
				$i ++;
			}

			if( $is_hash ){
				$RTN .= '<object>';
			}else{
				$RTN .= '<array>';
			}
			if( $option['array_break'] ){ $RTN .= "\n"; }
			foreach( $value as $key=>$val ){
				if( $option['delete_arrayelm_if_null'] && is_null( $value[$key] ) ){
					#	配列のnull要素を削除するオプションが有効だった場合
					continue;
				}
				$RTN .= '<element';
				if( $is_hash ){
					$RTN .= ' name="'.htmlspecialchars( $key ).'"';
				}
				$RTN .= '>';
				$RTN .= t::data2xml( $value[$key] , $option );
				$RTN .= '</element>';
				if( $option['array_break'] ){ $RTN .= "\n"; }
			}
			if( $is_hash ){
				$RTN .= '</object>';
			}else{
				$RTN .= '</array>';
			}
			if( $option['array_break'] ){ $RTN .= "\n"; }
			return	$RTN;
		}

		if( is_object( $value ) ){
			#	オブジェクト型
			$RTN = '';
			$RTN .= '<object>';
			$proparray = get_object_vars( $value );
			$methodarray = get_class_methods( get_class( $value ) );
			foreach( $proparray as $key=>$val ){
				$RTN .= '<element name="'.htmlspecialchars( $key ).'">';

				$RTN .= t::data2xml( $val , $option );
				$RTN .= '</element>';
			}
			$RTN .= '</object>';
			return	$RTN;
		}

		if( is_int( $value ) ){
			#	数値
			$RTN = '<value type="int">'.t::h( $value ).'</value>';
			return	$RTN;
		}

		if( is_float( $value ) ){
			#	浮動小数点
			$RTN = '<value type="float">'.t::h( $value ).'</value>';
			return	$RTN;
		}

		if( is_string( $value ) ){
			#	文字列型
			$RTN = '<value type="string">'.t::h( $value ).'</value>';
			return	$RTN;
		}

		if( is_null( $value ) ){
			#	ヌル
			return	'<value type="null"></value>';
		}

		if( is_resource( $value ) ){
			#	リソース型
			return	'<value type="undefined"></value>';
		}

		if( is_bool( $value ) ){
			#	ブール型
			if( $value ){
				return	'<value type="bool">true</value>';
			}else{
				return	'<value type="bool">false</value>';
			}
		}

		return	'<value type="undefined"></value>';

	}


	/**
	 * realpath()のラッパ
	 */
	public function realpath( $path ){
		#	PicklesFramework 0.2.2 追加
		#	realpath()の動作を、
		#	WindowsでもUNIX系と同じスラッシュ区切りのパスで得る。
		$path = @realpath($path);
		if( !is_string( $path ) ){
			#	string型じゃなかったら（つまり、falseだったら）
			return	$path;
		}
		if( strpos( $path , '/' ) !== 0 ){
			#	Windowsだったら。
			$path = preg_replace( '/^[A-Z]:/' , '' , $path );
			$path = preg_replace( '/\\\\/' , '/' , $path );
		}
		return	$path;
	}



	#------------------------------------------------------------------------------------------------------------------
	#	★文字列生成系

	/**
	 * ランダムな文字列を生成する
	 */
	public function randomtext( $width = 8 , $option = array() ){
		#	$width = 文字列のバイト数。

		if( !is_int($width) ){
			$width = 8;
		}
		if( $width < 1 ){
			$width = 8;
		}

		for( $i=0; $i < $width; $i++ ){
			$RTN .= rand(0,9);
		}
		return	$RTN;
	}



	#------------------------------------------------------------------------------------------------------------------
	#	★色コード系

	/**
	 * 16進数の色コードからRGBの10進数を得る
	 */
	public function color_hex2rgb( $txt_hex ){
		if( is_int( $txt_hex ) ){
			$txt_hex = dechex( $txt_hex );
			$txt_hex = '#'.str_pad( $txt_hex , 6 , '0' , STR_PAD_LEFT );
		}
		$txt_hex = preg_replace( '/^#/' , '' , $txt_hex );
		if( strlen( $txt_hex ) == 3 ){
			#	長さが3バイトだったら
			if( !preg_match( '/^([0-9a-f])([0-9a-f])([0-9a-f])$/si' , $txt_hex , $matched ) ){
				return	false;
			}
			$matched[1] = $matched[1].$matched[1];
			$matched[2] = $matched[2].$matched[2];
			$matched[3] = $matched[3].$matched[3];
		}elseif( strlen( $txt_hex ) == 6 ){
			#	長さが6バイトだったら
			if( !preg_match( '/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/si' , $txt_hex , $matched ) ){
				return	false;
			}
		}else{
			return	false;
		}
		$RTN = array(
			"r"=>eval( 'return 0x'.$matched[1].';' ) ,
			"g"=>eval( 'return 0x'.$matched[2].';' ) ,
			"b"=>eval( 'return 0x'.$matched[3].';' ) ,
		);
		return	$RTN;
	}

	/**
	 * RGBの10進数の色コードから16進数を得る
	 */
	public function color_rgb2hex( $int_r , $int_g , $int_b ){
		$hex_r = dechex( $int_r );
		$hex_g = dechex( $int_g );
		$hex_b = dechex( $int_b );
		if( strlen( $hex_r ) > 2 || strlen( $hex_g ) > 2 || strlen( $hex_b ) > 2 ){
			return	false;
		}
		$RTN = '#';
		$RTN .= str_pad( $hex_r , 2 , '0' , STR_PAD_LEFT );
		$RTN .= str_pad( $hex_g , 2 , '0' , STR_PAD_LEFT );
		$RTN .= str_pad( $hex_b , 2 , '0' , STR_PAD_LEFT );
		return	$RTN;
	}

	/**
	 * 色相を調べる
	 */
	public function color_get_hue( $txt_hex , $int_round = 0 ){
		$int_round = intval( $int_round );
		if( $int_round < 0 ){ return false; }

		$rgb = t::color_hex2rgb( $txt_hex );
		if( $rgb === false ){ return false; }

		foreach( $rgb as $key=>$val ){
			$rgb[$key] = $val/255;
		}

		$hue = 0;
		if( $rgb['r'] == $rgb['g'] && $rgb['g'] == $rgb['b'] ){//PxFW 0.6.2
			return	0;
		}
		if( $rgb['r'] >= $rgb['g'] && $rgb['g'] >= $rgb['b'] ){
			#	R>G>B
			$hue = 60 * ( ($rgb['g']-$rgb['b'])/($rgb['r']-$rgb['b']) );

		}elseif( $rgb['g'] >= $rgb['r'] && $rgb['r'] >= $rgb['b'] ){
			#	G>R>B
			$hue = 60 * ( 2-( ($rgb['r']-$rgb['b'])/($rgb['g']-$rgb['b']) ) );

		}elseif( $rgb['g'] >= $rgb['b'] && $rgb['b'] >= $rgb['r'] ){
			#	G>B>R
			$hue = 60 * ( 2+( ($rgb['b']-$rgb['r'])/($rgb['g']-$rgb['r']) ) );

		}elseif( $rgb['b'] >= $rgb['g'] && $rgb['g'] >= $rgb['r'] ){
			#	B>G>R
			$hue = 60 * ( 4-( ($rgb['g']-$rgb['r'])/($rgb['b']-$rgb['r']) ) );

		}elseif( $rgb['b'] >= $rgb['r'] && $rgb['r'] >= $rgb['g'] ){
			#	B>R>G
			$hue = 60 * ( 4+( ($rgb['r']-$rgb['g'])/($rgb['b']-$rgb['g']) ) );

		}elseif( $rgb['r'] >= $rgb['b'] && $rgb['b'] >= $rgb['g'] ){
			#	R>B>G
			$hue = 60 * ( 6-( ($rgb['b']-$rgb['g'])/($rgb['r']-$rgb['g']) ) );

		}else{
			return	0;
		}

		if( $int_round ){
			$hue = round( $hue , $int_round );
		}else{
			$hue = intval( $hue );
		}
		return $hue;
	}

	/**
	 * 彩度を調べる
	 */
	public function color_get_saturation( $txt_hex , $int_round = 0 ){
		$int_round = intval( $int_round );
		if( $int_round < 0 ){ return false; }

		$rgb = t::color_hex2rgb( $txt_hex );
		if( $rgb === false ){ return false; }

		sort( $rgb );
		$minval = $rgb[0];
		$maxval = $rgb[2];

		if( $minval == 0 && $maxval == 0 ){
			#	真っ黒だったら
			return	0;
		}

		$saturation = ( 100-( $minval/$maxval * 100 ) );

		if( $int_round ){
			$saturation = round( $saturation , $int_round );
		}else{
			$saturation = intval( $saturation );
		}
		return $saturation;
	}

	/**
	 * 明度を調べる
	 */
	public function color_get_brightness( $txt_hex , $int_round = 0 ){
		$int_round = intval( $int_round );
		if( $int_round < 0 ){ return false; }

		$rgb = t::color_hex2rgb( $txt_hex );
		if( $rgb === false ){ return false; }

		sort( $rgb );
		$maxval = $rgb[2];

		$brightness = ( $maxval * 100/255 );

		if( $int_round ){
			$brightness = round( $brightness , $int_round );
		}else{
			$brightness = intval( $brightness );
		}
		return $brightness;
	}

	/**
	 * 16進数のRGBコードからHSB値を得る
	 */
	public function color_hex2hsb( $txt_hex , $int_round = 0 ){
		$int_round = intval( $int_round );
		if( $int_round < 0 ){ return false; }

		$hsb = array(
			'h'=>t::color_get_hue( $txt_hex , $int_round ) ,
			's'=>t::color_get_saturation( $txt_hex , $int_round ) ,
			'b'=>t::color_get_brightness( $txt_hex , $int_round ) ,
		);
		return	$hsb;
	}

	/**
	 * RGB値からHSB値を得る
	 */
	public function color_rgb2hsb( $int_r , $int_g , $int_b , $int_round = 0 ){
		$int_round = intval( $int_round );
		if( $int_round < 0 ){ return false; }

		$txt_hex = t::color_rgb2hex( $int_r , $int_g , $int_b );
		$hsb = array(
			'h'=>t::color_get_hue( $txt_hex , $int_round ) ,
			's'=>t::color_get_saturation( $txt_hex , $int_round ) ,
			'b'=>t::color_get_brightness( $txt_hex , $int_round ) ,
		);
		return	$hsb;
	}

	/**
	 * HSB値からRGB値を得る
	 */
	public function color_hsb2rgb( $int_hue , $int_saturation , $int_brightness , $int_round = 0 ){
		$int_round = intval( $int_round );
		if( $int_round < 0 ){ return false; }

		$int_hue = round( $int_hue%360 , 3 );
		$int_saturation = round( $int_saturation , 3 );
		$int_brightness = round( $int_brightness , 3 );

		$maxval = round( $int_brightness * ( 255/100 ) , 3 );
		$minval = round( $maxval - ( $maxval * $int_saturation/100 ) , 3 );

		$keyname = array( 'r' , 'g' , 'b' );
		if(      $int_hue >=   0 && $int_hue <  60 ){
			$keyname = array( 'r' , 'g' , 'b' );
			$midval = $minval + ( ($maxval - $minval) * ( ($int_hue -  0)/60 ) );
		}elseif( $int_hue >=  60 && $int_hue < 120 ){
			$keyname = array( 'g' , 'r' , 'b' );
			$midval = $maxval - ( ($maxval - $minval) * ( ($int_hue - 60)/60 ) );
		}elseif( $int_hue >= 120 && $int_hue < 180 ){
			$keyname = array( 'g' , 'b' , 'r' );
			$midval = $minval + ( ($maxval - $minval) * ( ($int_hue -120)/60 ) );
		}elseif( $int_hue >= 180 && $int_hue < 240 ){
			$keyname = array( 'b' , 'g' , 'r' );
			$midval = $maxval - ( ($maxval - $minval) * ( ($int_hue -180)/60 ) );
		}elseif( $int_hue >= 240 && $int_hue < 300 ){
			$keyname = array( 'b' , 'r' , 'g' );
			$midval = $minval + ( ($maxval - $minval) * ( ($int_hue -240)/60 ) );
		}elseif( $int_hue >= 300 && $int_hue < 360 ){
			$keyname = array( 'r' , 'b' , 'g' );
			$midval = $maxval - ( ($maxval - $minval) * ( ($int_hue -300)/60 ) );
		}

		$tmp_rgb = array();
		if( $int_round ){
			$tmp_rgb = array(
				$keyname[0]=>round( $maxval , $int_round ) ,
				$keyname[1]=>round( $midval , $int_round ) ,
				$keyname[2]=>round( $minval , $int_round ) ,
			);
		}else{
			$tmp_rgb = array(
				$keyname[0]=>intval( $maxval ) ,
				$keyname[1]=>intval( $midval ) ,
				$keyname[2]=>intval( $minval ) ,
			);
		}
		$rgb = array( 'r'=>$tmp_rgb['r'] , 'g'=>$tmp_rgb['g'] , 'b'=>$tmp_rgb['b'] );
		return	$rgb;
	}
	/**
	 * HSB値から16進数のRGBコードを得る
	 */
	public function color_hsb2hex( $int_hue , $int_saturation , $int_brightness , $int_round = 0 ){
		$rgb = t::color_hsb2rgb( $int_hue , $int_saturation , $int_brightness , $int_round );
		$hex = t::color_rgb2hex( $rgb['r'] , $rgb['g'] , $rgb['b'] );
		return	$hex;
	}


	#------------------------------------------------------------------------------------------------------------------
	#	★バリデータ系

	/**
	 * 機種依存文字( Model Dependence Character )が含まれているかどうかを判定する。
	 */
	public function mdc_exists( $TEXT , $charset = null ){
		#	機種依存文字判定->暫定実装 Pickles Framework 0.2.8 1:46 2008/03/22
		#	$TEXT は、内部エンコーディングされた文字列であることが前提。

		if( !is_string( $charset ) || !strlen( $charset ) ){ $charset = strtolower( mb_internal_encoding() ); }

		$TEXT = str_replace( base64_decode('772e') , '' , $TEXT );//←にょろ
		$TEXT = str_replace( base64_decode('44Cc') , '' , $TEXT );//←ぎざにょろ
			//	PxFW 0.6.2 ：全角日本語の "にょろ" が "ぎざにょろ" に変換されるために、機種依存文字として判断されてしまう問題を修正。

		$TEXT = mb_convert_encoding( $TEXT , $charset , mb_internal_encoding().','.implode( ',' , mb_detect_order() ) );
		$TEXT_UTF8 = mb_convert_encoding( $TEXT , 'UTF-8' , $charset );
		$TEXT_SJIS = mb_convert_encoding( $TEXT_UTF8 , 'SJIS' , 'UTF-8' );
		$TEXT_FINALENCODING = mb_convert_encoding( $TEXT_SJIS , $charset , 'SJIS' );

		if( $TEXT !== $TEXT_FINALENCODING ){
			return	true;
		}

		return	false;
	}

	/**
	 * Eメールアドレスとして正しい形式であるか判定
	 */
	public function is_email( $TEXT ){
		//注：このロジックは概ね拾えるが、完全ではないかも知れない。
		if( !preg_match( '/^[\-\_\.a-zA-Z0-9]+\@[a-zA-Z0-9\-\_][\-\_\.a-zA-Z0-9]*[a-zA-Z0-9]+\.[a-zA-Z]{2,}$/i' , $TEXT ) ){
			return	false;
		}
		return	true;
	}

	/**
	 * URLとして正しい形式であるか判定
	 */
	public function is_url( $TEXT ){
		//注：このロジックは概ね拾えるが、完全ではないかも知れない。
		if( !preg_match( '/^(?:http|https)\:\/\/[a-z0-9\-\_][\-\_\.a-z0-9]*(?:\:[0-9]+)?\/.*$/i' , $TEXT ) ){
			return	false;
		}
		return	true;
	}

}

?>