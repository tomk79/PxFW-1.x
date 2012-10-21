
<!-- インクルードエリア -->
<meta name="keywords" content="<?php print t::h($page_info['keywords']); ?>" />
<meta name="description" content="<?php print t::h($page_info['description']); ?>" />

<link rel="stylesheet" href="<?php print t::h($px->get_install_path()); ?>common/css/setup.css" type="text/css" />
<link rel="stylesheet" href="<?php print t::h($px->get_install_path()); ?>common/css/modules.css" type="text/css" />
<style type="text/css">

body{
	text-align:center;
	padding:0px 0px 0px 0px;
	margin:0px 0px 0px 0px;
	background-color:#dddddd;
	font-size:small;
	font-family:"メイリオ", sans-serif;
}
body .theme_outline{
	text-align:left;
	max-width:850px;
	width:auto;
	margin:20px auto;
	background-color:#fff;
	padding:20px;
	border-radius:10px;
}

/**
 * ヘッダー
 */
body .theme_outline .theme_header{
	padding:10px;
	border-bottom:1px solid #dddddd;
	margin-bottom:5px;
}
body .theme_outline .theme_header .logo{
}
body .theme_outline .theme_header .logo a{
	font-size:150%;
	font-weight:bold;
}

/**
 * 中段
 */
body .theme_outline .theme_middle{
	clear:both;
}
body .theme_outline .theme_middle .theme_column1{
	width:74%;
	float:left;
}

body .theme_outline .theme_middle .theme_column2{
	width:24%;
	float:right;
}

/**
 * ローカルナビ
 */
body .theme_outline .theme_middle .theme_localnavi{
	padding:10px;
	background-color:#eeeeee;
	border-radius:10px;
}
body .theme_outline .theme_middle .theme_localnavi ul{
	margin:5px 0;
	padding:0;
}
body .theme_outline .theme_middle .theme_localnavi ul li{
	list-style-type:none;
	margin:5px 0;
	padding:0 0 0 10px;
}

/**
 * フッター
 */
body .theme_outline .theme_footer{
	padding:10px;
	clear:both;
	border-top:1px solid #dddddd;
	margin-top:5px;
}

/**
 * パンくず
 */
.breadcrumb{
}
.breadcrumb ul,
.breadcrumb ul li{
	display: inline;
	list-style-type: none;
	margin:0px;
	padding:0px;
	font-size: 85%;
}

/**
 * コンテンツモジュール
 */
.contents h2{
	border-left:8px solid #999999;
	padding-left:15px;
}

code{
	color:#0066dd;
}

.code code{
	color:#333333;
}

dl{
	margin-left:20px;
}
dl dt{
	font-weight:bold;
}
</style>

<style type="text/css" media="all and (max-width:390px)">
body{
	font-size:medium;
}
body .theme_outline .theme_middle .theme_column1,
body .theme_outline .theme_middle .theme_column2{
	float:none;
	clear:both;
	width:auto;
}
body .theme_outline{
	margin-left:5px;
	margin-right:5px;
}
</style>
<script src="<?php print t::h($px->get_install_path()); ?>common/js/jquery-1.7.2.min.js" type="text/javascript"></script>
<!-- / インクルードエリア -->
