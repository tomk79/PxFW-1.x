<?php
/**
 * class px_bases_dao
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
/**
 * DAOの基底クラス
 * 
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_bases_dao{

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
}
?>