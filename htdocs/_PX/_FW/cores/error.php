<?php
class px_cores_error{
	private $px;

	public function __construct( &$px ){
		$this->px = &$px;
	}

	//--------------------------------------
	//  エラーログに書き出し
	public function error_log( $message = null , $file = null , $line = null , $option = array() ){
		//UTODO: 未開発
		return	true;
	}

}
?>