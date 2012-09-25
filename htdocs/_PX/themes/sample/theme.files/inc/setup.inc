
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
}
body .theme_outline{
	text-align:left;
	max-width:800px;
	width:auto;
	margin:20px auto;
	background-color:#fff;
	padding:20px;
}
body .theme_outline .theme_header{
}
body .theme_outline .theme_header .logo{
}
body .theme_outline .theme_header .logo a{
	font-size:150%;
	font-weight:bold;
}

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

body .theme_outline .theme_footer{
	clear:both;
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

</style>
<script src="<?php print t::h($px->get_install_path()); ?>common/js/jquery-1.7.2.min.js" type="text/javascript"></script>
<!-- / インクルードエリア -->

