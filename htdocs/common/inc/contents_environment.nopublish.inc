<?php
/**
 * Contents Envoronment
 * 
 * このファイルは、コンテンツの環境を定義するCSSやJavaScriptファイルを読み込みます。
 * 
 * テーマは、このパス (`/common/inc/contents_environment.nopublish.inc`) を、
 * headセクション内に `include()` します。(`$px->ssi()` ではなく、`include()` です)
 * 
 * このファイルを異なるテーマ間で共有することにより、
 * テーマを取り替えても、コンテンツの表現を支える前提を保証することができます。
 * 
 * コンテンツは、 `.contents` の中に置かれます。
 * 従って、このファイルで定義される内容は、
 * `.contents` の中にのみ影響するように実装されるべきです。
 * 
 */ ?>
		<link rel="stylesheet" href="<?php print t::h($px->theme()->href('/common/css/setup.css')); ?>" type="text/css" />
		<link rel="stylesheet" href="<?php print t::h($px->theme()->href('/common/css/modules.css')); ?>" type="text/css" />
		<script src="<?php print t::h($px->theme()->href('/common/js/jquery-1.10.1.min.js')); ?>" type="text/javascript"></script>
