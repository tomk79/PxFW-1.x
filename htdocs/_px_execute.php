<?php

#	カレントディレクトリをセット(コマンドラインから実行されたときのために)
chdir( dirname(__FILE__) );

require_once('./_PX/_FW/px.php');
$px = new px_px('./_PX/configs/mainconf.ini');
$px->execute();
exit();

?>