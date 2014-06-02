<?php
/**
 * class t (static class)
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
/**
 * テキストを扱うクラス(static class)
 * 
 * インスタンス化せず、スタティックに使用してください。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class t{

	#------------------------------------------------------------------------------------------------------------------
	#	★文字変換系

	/**
	 * HTML特殊文字を変換する。
	 * 
	 * htmlspecialchars() のエイリアス
	 *
	 * @param string $text テキスト
	 * @return string HTML特殊文字をエスケープしたHTMLソース
	 */
	public static function h($text){
		return htmlspecialchars($text);
	}

	/**
	 * 受け取ったテキストをHTML形式に変換する。
	 * 
	 * htmlspecialchars() を通した上で、改行コードを<br/>に変換する。
	 *
	 * @param string $text テキスト
	 * @return string HTML特殊文字をエスケープしたHTMLソース
	 */
	public static function text2html($text){
		$text = htmlspecialchars( $text );
		$text = preg_replace('/\r\n|\r|\n/','<br />',$text);
		return	$text;
	}

	/**
	 * HTMLソースをテキストに変換する。
	 *
	 * @param string $text HTMLソース
	 * @return string プレーンテキスト
	 */
	public static function html2text($text){
		$text = preg_replace( '/<br(?: ?\/)?'.'>/is' , "\n" , $text );
		$text = strip_tags( $text );
		$text = preg_replace( '/&lt;/' , '<' , $text );
		$text = preg_replace( '/&gt;/' , '>' , $text );
		$text = preg_replace( '/&quot;/' , '"' , $text );
		$text = preg_replace( '/&amp;/' , '&' , $text );
		return	$text;
	}

	/**
	 * 特殊な HTML エンティティを文字に戻す
	 *
	 * @param string $text HTMLソース
	 * @return string プレーンテキスト
	 */
	public static function htmlspecialchars_decode($text){
		//	PxFW 0.6.6 追加
		//	htmlspecialchars_decode()というのもあるが、
		//	PHP5.1.0以降なので使わない。
		$text = preg_replace( '/&lt;/' , '<' , $text );
		$text = preg_replace( '/&gt;/' , '>' , $text );
		$text = preg_replace( '/&quot;/' , '"' , $text );
		$text = preg_replace( '/&amp;/' , '&' , $text );
		return	$text;
	}

	/**
	 * シングルクオートで囲えるようにエスケープ処理する。
	 *
	 * @param string $text テキスト
	 * @return string エスケープされたテキスト
	 */
	public static function escape_singlequote($text){
		$text = preg_replace( '/\\\\/' , '\\\\\\\\' , $text);
		$text = preg_replace( '/\'/' , '\\\'' , $text);
		return	$text;
	}

	/**
	 * ダブルクオートで囲えるようにエスケープ処理する。
	 *
	 * @param string $text テキスト
	 * @return string エスケープされたテキスト
	 */
	public static function escape_doublequote( $text ){
		$text = preg_replace( '/\\\\/' , '\\\\\\\\' , $text);
		$text = preg_replace( '/"/' , '\\"' , $text);
		return	$text;
	}

	/**
	 * ファイル名やパス名から、拡張子を削除する。
	 *
	 * @param string $filename パス
	 * @return string 拡張子を削除したパス
	 */
	public static function trimext( $filename ){
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
	 * 受け取ったテキストを、指定の文字セットに変換する。
	 * 
	 * @param mixed $text テキスト
	 * @param string $encode 変換後の文字セット。省略時、`mb_internal_encoding()` から取得
	 * @param string $encodefrom 変換前の文字セット。省略時、自動検出
	 * @return string 文字セット変換後のテキスト
	 */
	public static function convert_encoding( $text, $encode = null, $encodefrom = null ){
		if( !is_callable( 'mb_internal_encoding' ) ){ return $text; }
		if( !strlen( $encodefrom ) ){ $encodefrom = mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII'; }
		if( !strlen( $encode ) ){ $encode = mb_internal_encoding(); }

		if( is_array( $text ) ){
			$RTN = array();
			if( !count( $text ) ){ return $text; }
			$TEXT_KEYS = array_keys( $text );
			foreach( $TEXT_KEYS as $Line ){
				$KEY = mb_convert_encoding( $Line , $encode , $encodefrom );
				if( is_array( $text[$Line] ) ){
					$RTN[$KEY] = t::convert_encoding( $text[$Line] , $encode , $encodefrom );
				}else{
					$RTN[$KEY] = @mb_convert_encoding( $text[$Line] , $encode , $encodefrom );
				}
			}
		}else{
			if( !strlen( $text ) ){ return $text; }
			$RTN = @mb_convert_encoding( $text , $encode , $encodefrom );
		}
		return $RTN;
	}

	/**
	 * クォートされた文字列のクォート部分を取り除く。
	 *
	 * この関数は、PHPの `stripslashes()` のラッパーです。
	 * 配列を受け取ると再帰的に文字列を変換して返します。
	 * 
	 * @param mixed $text テキスト
	 * @return string クォートが元に戻されたテキスト
	 */
	public static function stripslashes( $text ){
		if( is_array( $text ) ){
			#	配列なら
			foreach( $text as $key=>$val ){
				$text[$key] = t::stripslashes( $val );
			}
		}elseif( is_string( $text ) ){
			#	文字列なら
			$text = stripslashes( $text );
		}
		return	$text;
	}

	/**
	 * 半角に変換する。
	 * 
	 * @param mixed $text テキスト
	 * @return string 半角に変換されたテキスト
	 */
	public static function hankaku( $text ){
		return	@mb_convert_kana( $text , 'a' , @mb_internal_encoding() );
	}

	/**
	 * 変数を受け取り、PHPのシンタックスに変換する。
	 * 
	 * @param mixed $value 値
	 * @param array $options オプション
	 * <dl>
	 *   <dt>delete_arrayelm_if_null</dt>
	 *     <dd>配列の要素が `null` だった場合に削除。</dd>
	 *   <dt>array_break</dt>
	 *     <dd>配列に適当なところで改行を入れる。</dd>
	 * </dl>
	 * @return string PHPシンタックスに変換された値
	 */
	public static function data2text( $value = null , $options = array() ){

		$RTN = '';
		if( is_array( $value ) ){
			#	配列
			$RTN .= 'array(';
			if( @$options['array_break'] ){ $RTN .= "\n"; }
			$keylist = array_keys( $value );
			foreach( $keylist as $Line ){
				if( @$options['delete_arrayelm_if_null'] && is_null( @$value[$Line] ) ){
					#	配列のnull要素を削除するオプションが有効だった場合
					continue;
				}
				$RTN .= ''.t::data2text( $Line ).'=>'.t::data2text( $value[$Line] , $options ).',';
				if( @$options['array_break'] ){ $RTN .= "\n"; }
			}
			$RTN = preg_replace( '/,(?:\r\n|\r|\n)?$/' , '' , $RTN );
			$RTN .= ')';
			if( @$options['array_break'] ){ $RTN .= "\n"; }
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

	}//data2text()

	/**
	 * 変数をPHPのソースコードに変換する。
	 * 
	 * `include()` に対してそのままの値を返す形になるよう変換する。
	 *
	 * @param mixed $value 値
	 * @param array $options オプション (`t::data2text()`にバイパスされます。`t::data2text()`の項目を参照してください)
	 * @return string `include()` に対して値 `$value` を返すPHPコード
	 */
	public static function data2phpsrc( $value = null , $options = array() ){
		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	/'.'* '.@mb_internal_encoding().' *'.'/'."\n";
		$RTN .= '	return '.t::data2text( $value , $options ).';'."\n";
		$RTN .= '?'.'>';
		return	$RTN;
	}

	/**
	 * 変数をJavaScriptのシンタックスに変換する。
	 * 
	 * @param mixed $value 値
	 * @param array $options オプション
	 * <dl>
	 *   <dt>delete_arrayelm_if_null</dt>
	 *     <dd>配列の要素が `null` だった場合に削除。</dd>
	 *   <dt>array_break</dt>
	 *     <dd>配列に適当なところで改行を入れる。</dd>
	 * </dl>
	 * @return string JavaScriptシンタックスに変換された値
	 */
	public static function data2jssrc( $value = null , $options = array() ){

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
			if( $options['array_break'] ){ $RTN .= "\n"; }
			foreach( $value as $key=>$val ){
				if( $options['delete_arrayelm_if_null'] && is_null( $value[$key] ) ){
					#	配列のnull要素を削除するオプションが有効だった場合
					continue;
				}
				if( $is_hash ){
					$RTN .= ''.t::data2jssrc( $key.'' , $options ).':';
				}
				$RTN .= t::data2jssrc( $value[$key] , $options );
				$RTN .= ', ';
				if( $options['array_break'] ){ $RTN .= "\n"; }
			}
			$RTN = preg_replace( '/,(?:\s+)?(?:\r\n|\r|\n)?$/' , '' , $RTN );
			if( $is_hash ){
				$RTN .= '}';
			}else{
				$RTN .= ']';
			}
			if( $options['array_break'] ){ $RTN .= "\n"; }
			return	$RTN;
		}

		if( is_object( $value ) ){
			#	オブジェクト型
			$RTN = '';
			$RTN .= '{';
			$proparray = get_object_vars( $value );
			$methodarray = get_class_methods( get_class( $value ) );
			foreach( $proparray as $key=>$val ){
				$RTN .= ''.t::data2jssrc( $key , $options ).':';

				$RTN .= t::data2jssrc( $val , $options );
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
	 * 変数をXMLのシンタックスに変換する。
	 * 
	 * @param mixed $value 値
	 * @param array $options オプション
	 * <dl>
	 *   <dt>delete_arrayelm_if_null</dt>
	 *     <dd>配列の要素が `null` だった場合に削除。</dd>
	 *   <dt>array_break</dt>
	 *     <dd>配列に適当なところで改行を入れる。</dd>
	 * </dl>
	 * @return string XMLシンタックスに変換された値
	 */
	public static function data2xml( $value = null , $options = array() ){

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
			if( $options['array_break'] ){ $RTN .= "\n"; }
			foreach( $value as $key=>$val ){
				if( $options['delete_arrayelm_if_null'] && is_null( $value[$key] ) ){
					#	配列のnull要素を削除するオプションが有効だった場合
					continue;
				}
				$RTN .= '<element';
				if( $is_hash ){
					$RTN .= ' name="'.htmlspecialchars( $key ).'"';
				}
				$RTN .= '>';
				$RTN .= t::data2xml( $value[$key] , $options );
				$RTN .= '</element>';
				if( $options['array_break'] ){ $RTN .= "\n"; }
			}
			if( $is_hash ){
				$RTN .= '</object>';
			}else{
				$RTN .= '</array>';
			}
			if( $options['array_break'] ){ $RTN .= "\n"; }
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

				$RTN .= t::data2xml( $val , $options );
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
	 * 絶対パスを取得する。
	 *
	 * この関数は、PHPの `realpath()` のラッパーですが、
	 * Windows環境でも、UNIX同様、スラッシュ区切りのパスを返す点が異なります。
	 *
	 * @param string $path パス
	 * @return string 絶対パス
	 */
	public static function realpath( $path ){
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
	 * ランダムな文字列を生成する。
	 *
	 * @param int $width 文字列のサイズ
	 * @return string 生成されたランダムな文字列
	 */
	public static function randomtext( $width = 8 ){
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
	}//randomtext()



	#------------------------------------------------------------------------------------------------------------------
	#	★色コード系

	/**
	 * 16進数の色コードからRGBの10進数を得る。
	 *
	 * @param int|string $txt_hex 16進数色コード
	 * @return array 10進数のRGB色コードを格納した連想配列
	 */
	public static function color_hex2rgb( $txt_hex ){
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
	 * RGBの10進数の色コードから16進数を得る。
	 *
	 * @param int $int_r 10進数の色コード(Red)
	 * @param int $int_g 10進数の色コード(Green)
	 * @param int $int_b 10進数の色コード(Blue)
	 * @return string 16進数の色コード
	 */
	public static function color_rgb2hex( $int_r , $int_g , $int_b ){
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
	 * 色相を調べる。
	 * 
	 * @param int|string $txt_hex 16進数の色コード
	 * @param int $int_round 小数点以下を丸める桁数
	 * @return int 色相値
	 */
	public static function color_get_hue( $txt_hex , $int_round = 0 ){
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
	 * 彩度を調べる。
	 * 
	 * @param int|string $txt_hex 16進数の色コード
	 * @param int $int_round 小数点以下を丸める桁数
	 * @return int 彩度値
	 */
	public static function color_get_saturation( $txt_hex , $int_round = 0 ){
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
	 * 明度を調べる。
	 * 
	 * @param int|string $txt_hex 16進数の色コード
	 * @param int $int_round 小数点以下を丸める桁数
	 * @return int 明度値
	 */
	public static function color_get_brightness( $txt_hex , $int_round = 0 ){
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
	 * 16進数のRGBコードからHSB値を得る。
	 * 
	 * @param int|string $txt_hex 16進数の色コード
	 * @param int $int_round 小数点以下を丸める桁数
	 * @return array 色相値、彩度値、明度値を含む連想配列
	 */
	public static function color_hex2hsb( $txt_hex , $int_round = 0 ){
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
	 * RGB値からHSB値を得る。
	 * 
	 * @param int $int_r 10進数の色コード(Red)
	 * @param int $int_g 10進数の色コード(Green)
	 * @param int $int_b 10進数の色コード(Blue)
	 * @param int $int_round 小数点以下を丸める桁数
	 * @return array 色相値、彩度値、明度値を含む連想配列
	 */
	public static function color_rgb2hsb( $int_r , $int_g , $int_b , $int_round = 0 ){
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
	 * HSB値からRGB値を得る。
	 * 
	 * @param int $int_hue 10進数の色相値
	 * @param int $int_saturation 10進数の彩度値
	 * @param int $int_brightness 10進数の明度値
	 * @param int $int_round 小数点以下を丸める桁数
	 * @return array 10進数のRGB色コードを格納した連想配列
	 */
	public static function color_hsb2rgb( $int_hue , $int_saturation , $int_brightness , $int_round = 0 ){
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
	 * HSB値から16進数のRGBコードを得る。
	 * 
	 * @param int $int_hue 10進数の色相値
	 * @param int $int_saturation 10進数の彩度値
	 * @param int $int_brightness 10進数の明度値
	 * @param int $int_round 小数点以下を丸める桁数
	 * @return string 16進数の色コード
	 */
	public static function color_hsb2hex( $int_hue , $int_saturation , $int_brightness , $int_round = 0 ){
		$rgb = t::color_hsb2rgb( $int_hue , $int_saturation , $int_brightness , $int_round );
		$hex = t::color_rgb2hex( $rgb['r'] , $rgb['g'] , $rgb['b'] );
		return	$hex;
	}


	#------------------------------------------------------------------------------------------------------------------
	#	★バリデータ系

	/**
	 * 機種依存文字( Model Dependence Character )が含まれているかどうかを判定する。
	 * 
	 * @param string $TEXT 文字列
	 * @param string $charset 調べる文字セット
	 * @return bool 機種依存文字が含まれる場合に `true`、含まれない場合に `false` を返します。
	 */
	public static function mdc_exists( $TEXT , $charset = null ){
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
	 * メールアドレスとして正しい形式であるか判定する。
	 * 
	 * 注：メールアドレスの仕様はとても複雑です。
	 * このロジックは概ね一般的に利用されるメールアドレスには適用できますが、
	 * 完全ではないかも知れません。
	 * 
	 * @param string $text メールアドレス
	 * @return bool 正しいメールアドレスの形式なら `true`、それ以外なら `false` を返します。
	 */
	public static function is_email( $text ){
		if( !preg_match( '/^[\-\_\.a-zA-Z0-9]+\@[a-zA-Z0-9\-\_][\-\_\.a-zA-Z0-9]*[a-zA-Z0-9]+\.[a-zA-Z]{2,}$/i' , $text ) ){
			return	false;
		}
		return	true;
	}

	/**
	 * URLとして正しい形式であるか判定する。
	 * 
	 * 注：このロジックは概ね適用できますが、完全ではないかも知れません。
	 * 
	 * @param string $text URL
	 * @return bool 正しいURLの形式なら `true`、それ以外なら `false` を返します。
	 */
	public static function is_url( $text ){
		if( !preg_match( '/^(?:http|https)\:\/\/[a-z0-9\-\_][\-\_\.a-z0-9]*(?:\:[0-9]+)?\/.*$/i' , $text ) ){
			return	false;
		}
		return	true;
	}

}

?>