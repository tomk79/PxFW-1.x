<?php

#	カレントディレクトリをセット(コマンドラインから実行されたときのために)
chdir( dirname(__FILE__) );

require_once('./_PX/_FW/px.php'); //ahomemo: index.htmlが叩かれたら_px_execute.phpが実行される(.htaccessのmod rewriteで設定)
$px = new px_px('./_PX/configs/mainconf.ini');
$px->execute();
exit();

?>