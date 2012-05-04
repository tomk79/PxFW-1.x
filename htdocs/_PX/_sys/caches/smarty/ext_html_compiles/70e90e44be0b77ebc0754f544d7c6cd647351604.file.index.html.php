<?php /* Smarty version Smarty-3.1.8, created on 2012-05-04 06:29:08
         compiled from "/Users/ahomegane/Dropbox/project/px/PxFW-1.x/htdocs/index.html" */ ?>
<?php /*%%SmartyHeaderCode:3500575664fa377343a2ed6-24049252%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '70e90e44be0b77ebc0754f544d7c6cd647351604' => 
    array (
      0 => '/Users/ahomegane/Dropbox/project/px/PxFW-1.x/htdocs/index.html',
      1 => 1336110588,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '3500575664fa377343a2ed6-24049252',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_4fa377344a1b02_32955396',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_4fa377344a1b02_32955396')) {function content_4fa377344a1b02_32955396($_smarty_tpl) {?><?php if (!is_callable('smarty_block_send_content')) include '/Users/ahomegane/Dropbox/project/px/PxFW-1.x/htdocs/_PX/libs/smarty/plugins/block.send_content.php';
?>
<h2>Welcome to Pickles Framework 1.X</h2>
<p>これはトップページのHTMLソースです。</p>
<ul>
	<li><a href="test/">./test/</a></li>
	<li><a href="test/abc.html">./test/abc.html</a></li>
</ul>



<?php $_smarty_tpl->smarty->_tag_stack[] = array('send_content', array('name'=>"head")); $_block_repeat=true; echo smarty_block_send_content(array('name'=>"head"), null, $_smarty_tpl, $_block_repeat);while ($_block_repeat) { ob_start();?>

<!-- このソースは、テーマのキャビネット "head" に送られます。 -->
<?php $_block_content = ob_get_clean(); $_block_repeat=false; echo smarty_block_send_content(array('name'=>"head"), $_block_content, $_smarty_tpl, $_block_repeat);  } array_pop($_smarty_tpl->smarty->_tag_stack);?>



<?php }} ?>