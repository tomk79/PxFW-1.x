<?php

/**
 * extensionの基底クラス
 */
class px_bases_extension{
	protected $px;

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
	}//__construct()

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		/*ここをオーバーライドしてください。*/
		print $this->px->theme()->bind_contents( '<p>ここをオーバーライドしてください。</p>' );
		return true;
	}

}
?>