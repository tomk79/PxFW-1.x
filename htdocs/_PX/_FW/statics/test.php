<?php
/**
 * class test (static class)
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
/**
 * 開発中のテスト用の機能群
 * 
 * インスタンス化せず、スタティックに使用してください。
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class test{

	/**
	 * 変数の内容を展開し、文字列として返す。
	 * 
	 * @param mixed $value プレビューする値
	 * @return string 整形されたHTMLソース
	 */
	public static function preview( $value ){

		if( is_array( $value ) ){
			#	配列
			$RTN = '';
			$RTN .= '<span style="color:#ff0000;">'.gettype( $value ).'</span>( '.count( $value ).' )';
			if( count( $value ) ){
				$RTN .= '<ul style="margin-left:20px;text-align:left;">';
				$keylist = array_keys( $value );
				foreach( $keylist as $Line ){
					$RTN .= '<li>';
					if( is_int( $Line ) || is_float( $Line ) ){
						$RTN .= '['.htmlspecialchars( $Line ).']';
					}else{
						$RTN .= '[<span style="color:#ff3333;">&quot;'.htmlspecialchars( $Line ).'&quot;</span>]';
					}
					$RTN .= ' =&gt; ';
					if( is_object( $value[$Line] ) ){
						$RTN .= '<span style="color:#009900;">&lt;Object&gt;</span>';
						$tmp_class_name = get_class( $value[$Line] );
						if( $tmp_class_name ){
							$RTN .= '( '.htmlspecialchars( $tmp_class_name );
							while( get_parent_class( $tmp_class_name ) ){
								$RTN .= ' <span style="color:#0000ff;">extends</span> '.htmlspecialchars( get_parent_class( $tmp_class_name ) ).'';
								$tmp_class_name = get_parent_class( $tmp_class_name );
							}
							$RTN .= ' )';
						}
						unset( $tmp_class_name );
					}else{
						$RTN .= test::preview( $value[$Line] );
					}
					$RTN .= '</li>';
				}
				$RTN .= '</ul>';
			}
			return	$RTN;
		}

		if( is_object( $value ) ){
			#	オブジェクト型
			$RTN = '';
			$RTN .= '<span style="color:#ff0000;">'.gettype( $value ).'</span>';
			if( class_exists( get_class( $value ) ) ){
				$RTN .= '( '.htmlspecialchars( get_class( $value ) ).'';
				$tmp_class_name = get_class( $value );
				while( get_parent_class( $tmp_class_name ) ){
					$RTN .= ' <span style="color:#0000ff;">extends</span> '.htmlspecialchars( get_parent_class( $tmp_class_name ) ).'';
					$tmp_class_name = get_parent_class( $tmp_class_name );
				}
				unset( $tmp_class_name );
				$RTN .= ' )';
			}
			$proparray = get_object_vars( $value );
			$methodarray = get_class_methods( get_class( $value ) );

			if( count( $proparray ) || count( $methodarray ) ){
				$RTN .= '<ul style="margin-left:20px;">';
				if( count( $proparray ) ){
					$keylist = array_keys( $proparray );
					foreach( $keylist as $Line ){
						if( is_object( $proparray[$Line] ) ){
							$RTN .= '<li>$'.$Line.' =&gt; <span style="color:#009900;">&lt;Object&gt;</span>';
							$tmp_class_name = get_class( $proparray[$Line] );
							if( $tmp_class_name ){
								$RTN .= '( '.htmlspecialchars( $tmp_class_name );
								while( get_parent_class( $tmp_class_name ) ){
									$RTN .= ' <span style="color:#0000ff;">extends</span> '.htmlspecialchars( get_parent_class( $tmp_class_name ) ).'';
									$tmp_class_name = get_parent_class( $tmp_class_name );
								}
								$RTN .= ' )';
							}
							unset( $tmp_class_name );
							$RTN .= '</li>';
						}else{
							$RTN .= '<li>$'.$Line.' =&gt; '.test::preview( $proparray[$Line] ).'</li>';
						}
					}
				}

				if( count( $methodarray ) ){
					$keylist = array_keys( $methodarray );
					$classname = '';
					if( class_exists( get_class( $value ) ) ){
						$classname = get_class( $value ).'::';
					}
					foreach( $methodarray as $Line ){
						$RTN .= '<li>'.htmlspecialchars( $classname ).'<span style="color:#0000ff">'.htmlspecialchars( $Line ).'()</span>'.'</li>';
					}
				}
				$RTN .= '</ul>';
			}
			return	$RTN;
		}

		if( is_int( $value ) ){
			#	数値
			return	'<span style="color:#0000ff;">'.gettype( $value ).'</span>( <span style="color:#000000;">'.htmlspecialchars( $value ).'</span> )';
		}

		if( is_double( $value ) ){
			#	浮動小数点
			return	'<span style="color:#0000ff;">'.gettype( $value ).'</span>( <span style="color:#000000;">'.htmlspecialchars( $value ).'</span> )';
		}

		if( is_string( $value ) ){
			#	文字列型
			return	'<span style="color:#0000ff;">'.gettype( $value ).'</span>( <span style="color:#ff3333; white-space:pre;">&quot;'.preg_replace( '/\r\n|\r|\n/' , '<br />' , htmlspecialchars( $value ) ).'&quot;</span> )';
		}

		if( is_resource( $value) ){
			#	リソース型
			return	'<span style="color:#0000ff;">'.gettype( $value ).'</span>';
		}

		if( is_null( $value) ){
			#	ヌル
			return	'<span style="color:#0000ff;">'.gettype( $value ).'</span>';
		}

		if( is_bool( $value ) ){
			#	ブール型
			$RTN = '';
			$RTN .= '<span style="color:#0000ff;">'.gettype( $value ).'</span>( <span style="color:#0000ff;">';
			if( $value ){
				$RTN .= 'true';
			}else{
				$RTN .= 'false';
			}
			return	$RTN.'</span> )';
		}

		return	'<span style="color:#0000ff;">'.gettype( $value ).'</span>';
	}

	/**
	 * 変数に関する情報を見やすいHTMLに整形してダンプする。
	 * 
	 * 引数に与えられた値を、着色した見やすいHTMLに整形して(`t::preview()` を利用)、標準出力します。
	 * PHPの `var_dump()` の出力を、より見やすくしたようなものです。
	 * 
	 * この関数は、複数の引数を受け付けます。
	 * 
	 * @return null 常に `null` を返します。
	 */
	public static function var_dump(){
		$vars = func_get_args();
		foreach( $vars as $value ){
			print	test::preview( $value )."\n";
		}
		return null;
	}

	/**
	 * オブジェクトの先祖(継承元)を一覧化する。
	 * 
	 * @param object $obj オブジェクト
	 * @return string 一覧を表示するHTMLソース
	 */
	public static function get_history_of_object( $obj ){
		if( is_string( $obj ) ){
			#	文字列を受け取ったら、クラス名として処理。
			return test::get_history_of_class( $obj );
		}
		if( !is_object( $obj ) ){
			#	文字列ではないし、オブジェクトでもない場合
			return	'NOT a object';
		}
		$class_name = get_class( $obj );
		$RTN = '';
		$RTN .= test::get_history_of_class( $class_name );
		$RTN .= '=&gt; Instance<br />';
		return	$RTN;
	}

	/**
	 * クラス名から、その先祖(継承元)を一覧化する。
	 * 
	 * @param string|object $class_name クラス名。オブジェクトを受け取った場合は、`test::get_history_of_object()` にバイパスします。
	 * @return string 一覧を表示するHTMLソース
	 */
	public static function get_history_of_class( $class_name ){
		if( is_object( $class_name ) ){
			#	オブジェクトを受け取ったら、オブジェクトとして処理。
			return	test::get_history_of_object( $class_name );
		}
		if( !is_string( $class_name ) ){
			#	文字列ではないし、オブジェクトでもない場合
			return	'NOT a string';
		}

		$RTN = '';
		$RTN .= '<strong>** '.htmlspecialchars( $class_name ).'</strong><br />';
		$parent = get_parent_class( $class_name );
		if( !strlen( $parent ) ){
			$RTN = 'START OF GENESIS;<br />'.$RTN;
		}else{
			$RTN = test::get_history_of_class( $parent ).$RTN;
		}
		return	$RTN;
	}

	/**
	 * ダミー文字列を生成する。
	 *
	 * @param int $int_strcount ダミー文字列の文字数(バイト数ではありません)
	 * @return string 生成されたダミー文字列を表すHTMLソース
	 */
	public static function mk_dummy_text( $int_strcount = 50 ){
		#	$int_strcount は、バイトじゃなくて文字数。

		$int_strcount = intval( $int_strcount );

		$divided = intval( $int_strcount / 10 );
		$remainder = intval( $int_strcount % 10 );//余

		$RTN = '';

		for( $i = 1; $divided >= $i; $i ++ ){
			#	注意：記号じゃなくて、漢字のクチとタを使用。
			#		　記号のシカクを使用すると、途中の自然改行ができないブラウザがあるため。
			$RTN .= '口口口口田';
			$RTN .= '口口口口';
			$RTN .= str_pad( intval(($i*10)%100) , 2 , '0' , STR_PAD_LEFT );
		}

		#	余の処理
		for( $i = 1; $remainder >= $i; $i++ ){
			if( intval($i%5) ){
				$RTN .= '口';
				continue;
			}
			$RTN .= '田';
			continue;
		}

		return	'<span style="word-break:break-all;">'.$RTN.'</span>';
	}

}

?>