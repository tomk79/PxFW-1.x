<?php /* Smarty version Smarty-3.1.8, created on 2012-05-16 09:09:54
         compiled from "\Users\k-watanabe\Desktop\private\Dropbox\project\px\PxFW-1.x\htdocs\index.html" */ ?>
<?php /*%%SmartyHeaderCode:25144fb352c26fda59-06109344%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'b7c9dd9f776d5c8bd58a907af8ea940527172609' => 
    array (
      0 => '\\Users\\k-watanabe\\Desktop\\private\\Dropbox\\project\\px\\PxFW-1.x\\htdocs\\index.html',
      1 => 1337078930,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '25144fb352c26fda59-06109344',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_4fb352c296ad04_20124867',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_4fb352c296ad04_20124867')) {function content_4fb352c296ad04_20124867($_smarty_tpl) {?><?php if (!is_callable('smarty_block_send_content')) include 'C:\\Users\\k-watanabe\\Desktop\\private\\Dropbox\\project\\px\\PxFW-1.x\\htdocs\\_PX\\libs\\smarty\\plugins\\block.send_content.php';
?>
<h2>Welcome to Pickles Framework 1.X</h2>
<p>これはトップページのHTMLソースです。</p>
<ul>
	<li><a href="test/">./test/</a></li>
	<li><a href="test/abc.html">./test/abc.html</a></li>
</ul>

<ul>
	<li><a href="test/commandlist.html">ピクルスのコマンドリストはこちら</a></li>
</ul>



<?php $_smarty_tpl->smarty->_tag_stack[] = array('send_content', array('name'=>"head")); $_block_repeat=true; echo smarty_block_send_content(array('name'=>"head"), null, $_smarty_tpl, $_block_repeat);while ($_block_repeat) { ob_start();?>

<!-- このソースは、テーマのキャビネット "head" に送られます。 -->
<?php $_block_content = ob_get_clean(); $_block_repeat=false; echo smarty_block_send_content(array('name'=>"head"), $_block_content, $_smarty_tpl, $_block_repeat);  } array_pop($_smarty_tpl->smarty->_tag_stack);?>



<?php }} ?>