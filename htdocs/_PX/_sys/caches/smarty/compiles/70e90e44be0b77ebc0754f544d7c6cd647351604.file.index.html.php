<?php /* Smarty version Smarty-3.1.8, created on 2012-04-21 10:56:38
         compiled from "/Users/ahomegane/Dropbox/project/px/PxFW-1.x/htdocs/index.html" */ ?>
<?php /*%%SmartyHeaderCode:17768796814f929266c17054-17822753%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '70e90e44be0b77ebc0754f544d7c6cd647351604' => 
    array (
      0 => '/Users/ahomegane/Dropbox/project/px/PxFW-1.x/htdocs/index.html',
      1 => 1335002650,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '17768796814f929266c17054-17822753',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_4f929266c9cce8_89849114',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_4f929266c9cce8_89849114')) {function content_4f929266c9cce8_89849114($_smarty_tpl) {?><?php if (!is_callable('smarty_block_send_content')) include '/Users/ahomegane/Dropbox/project/px/PxFW-1.x/htdocs/_PX/libs/smarty/plugins/block.send_content.php';
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