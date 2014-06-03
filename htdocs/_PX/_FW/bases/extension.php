<?php
/**
 * class px_bases_extension
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
/**
 * extensions の基底クラス
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_bases_extension{

	/**
	 * $pxオブジェクト
	 */
	protected $px;

	/**
	 * コンストラクタ
	 * 
	 * @param object $px $pxオブジェクト
	 */
	public function __construct( $px ){
		$this->px = $px;
	}//__construct()

	/**
	 * コンテンツを実行し、結果出力されるソースを返す。
	 *
	 * @param string $path_content コンテンツパス
	 * @return string 出力ソース
	 */
	public function execute( $path_content ){
		/*ここをオーバーライドしてください。*/
		print $this->px->theme()->bind_contents( '<p>ここをオーバーライドしてください。</p>' );
		return true;
	}

}

?>