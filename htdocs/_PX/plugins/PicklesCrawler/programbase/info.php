<?php

/**
 * 各プログラム情報クラスの基底クラス
 * Copyright (C)Tomoya Koyanagi.
 * Last Update : 19:12 2007/06/23
 */
class pxplugin_PicklesCrawler_programbase_info{

	protected $program_type_name = 'No Titled';// プログラムタイプ名称

	/**
	 * プログラムタイプの名称を取得する
	 */
	public function get_program_type_name(){
		return	$this->program_type_name;
	}

}

?>