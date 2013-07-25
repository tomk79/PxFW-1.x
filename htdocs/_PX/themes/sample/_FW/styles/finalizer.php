<?php

/**
 * コンテンツの仕上げ処理を施す。
 */
class pxtheme_styles_finalizer{

	private $px;

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
	}//__construct()

	/**
	 * コンテンツソースを完成させる。
	 * @param string $src = コンテンツのHTMLソース
	 */
	public function finalize_contents( $src ){

		/**/
		//このコードは、最終的なコンテンツのソースを
		//DOM解析により置換するサンプルコードです。
		//DOM置換の処理を実装する場合、このブロックコメントを解除して、
		//適宜変換ロジックを実装してください。

		//DOMパーサーのインスタンスを作成
		require_once( $this->px->get_conf('paths.px_dir').'libs/PxXMLDomParser/PxXMLDomParser.php' );
		$obj = new PxXMLDomParser( $src , 'bin' );

		//h2タグ(例)を選択
		$obj->select( 'h2' );

		//選択したタグ(例:h2)を置き換えるためのコールバックメソッドを実行
		$obj->replace( array( $this , 'replace_dom_h2_sample' ) );

		//変換後のソースを取得し、$srcを置き換える。
		$src = $obj->get_src();
		/**/

		return $src;
	}//finalize_contents()

	/**
	 * callback: h2タグを置き換える。(サンプルコード)
	 * このメソッドはサンプルコードです。
	 * finalize_contents() 内からコールバックメソッドとして登録されるものです。
	 */
	public function replace_dom_h2_sample( $dom , $num ){

		//属性値を復元
		$attr = '';
		if( is_array($dom['attributes']) ){
			foreach( $dom['attributes'] as $key=>$val ){
				$attr .= ' '.$key.'="'.$val.'"';
			}
		}

		// 入れ子の span を追加するサンプル
		$rtn = '<h2'.$attr.'><span>'.$dom['innerHTML'].'</span></h2>';

		// クラス名によって処理を変更するサンプル
		switch( $dom['attributes']['class'] ){
			case 'xxx':
				$rtn = '<h2'.$attr.'><span class="xxx-innerspan">'.$dom['innerHTML'].'</span></h2>';
				break;
			default:
				$rtn = '<h2'.$attr.'><span>'.$dom['innerHTML'].'</span></h2>';
				break;
		}

		return $rtn;
	}//replace_dom_h2_sample()

}

?>