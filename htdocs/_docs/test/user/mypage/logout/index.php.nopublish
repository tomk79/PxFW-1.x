<?php

$result = $px->user()->logout();
if( $result ){
	print '<p>正常にログアウトしました。</p>';
}else{
	print '<p class="error">ログアウトに失敗しました。</p>';
}

print '<p class="center">[<a href="'.t::h( $px->theme()->href('') ).'">もどる</a>]</p>';

?>