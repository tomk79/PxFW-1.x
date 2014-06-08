<?php
/**
 * Contents Manifesto
 * 
 * このファイルは、コンテンツの制作環境を宣言します。
 * コンテンツの形式を定義するCSSやJavaScriptファイルを読み込みます。
 * 
 * このファイルを異なるテーマ間で共有することにより、テーマを取り替えても、
 * コンテンツの表現を再現する前提を保証することができます。
 * 
 * - テーマは、このパス (`/common/contents_manifesto.nopublish.inc.php`) を、
 * headセクション内に `include()` します。(`$px->ssi()` ではなく、`include()` です)
 * - このファイルの中にPHPの記述を埋め込むことができるように読み込みます。
 * - このファイルのスコープで `$px` を利用できるようにしてください。
 * 
 * HTML上、コンテンツは `.contents` の中に置かれます。
 * 従って、このファイルで定義される内容は、`.contents` の中にのみ影響するように実装されるべきです。
 * 
 */ ?>
 <?php
	 //$pxがない(=直接アクセスされた)場合、ここで処理を抜ける。
 	if(!$px){return '';}
 ?>
<link rel="stylesheet" href="<?php print t::h($px->theme()->href('/common/css/modules.css')); ?>" type="text/css" />
<script src="<?php print t::h($px->theme()->href('/common/js/jquery-1.10.1.min.js')); ?>" type="text/javascript"></script>
