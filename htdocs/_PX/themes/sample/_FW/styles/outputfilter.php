<?php

/**
 * コンテンツの仕上げ処理を施す。
 */
class pxtheme_styles_outputfilter{

	private $px;

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
	}//__construct()

	/**
	 * コンテンツソースを完成させる。
	 * @param string $src = 完全なHTMLソース
	 */
	public function execute( $src, $extension ){
		/*
			$src には、テーマの処理が完了したあとの
			完成されたHTMLソースが渡されます。
			(ただし、文字コード変換処理の前の状態です)
			このメソッドに、変換処理を実装し、
			変換後のソースを返してください。
			すべてのextensionについて、このメソッドを通ります。
			対象となるextensionの種類は、
			$extensionで受け取ることができます。
		*/
		return $src;
	}//execute()

}

?>