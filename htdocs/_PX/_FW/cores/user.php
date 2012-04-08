<?php
class px_cores_user{
	private $px;

	public function __construct( &$px ){
		$this->px = &$px;
	}

	/**
	 * パブリッシュツールか否か調べる
	 * @return true|false
	 */
	public function is_publishtool(){
		$val = strpos( $_SERVER['HTTP_USER_AGENT'] , 'PicklesCrawler' );
		if( $val !== false && $val >= 0 ){
			return true;
		}
		return false;
	}//is_publishtool()

}
?>