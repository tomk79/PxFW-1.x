<?php
class px_cores_error{
	private $px;

	public function __construct( &$px ){
		$this->px = &$px;
	}

	//--------------------------------------
	//  エラーログに書き出し
	public function error_log( $error_message = null , $error_file = null , $error_line = null ){
		if( !strlen( $this->px->get_conf('paths.error_log') ) ){ return false; }
		return error_log(
			date('Y-m-d H:i:s')
			.'	'.$error_message
			.'	'.$error_file
			.'	'.$error_line
			."\r\n" , 3 , $this->px->get_conf('paths.error_log') );
	}

}
?>