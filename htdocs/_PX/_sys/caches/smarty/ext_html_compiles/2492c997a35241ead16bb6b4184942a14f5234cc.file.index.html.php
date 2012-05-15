<?php /* Smarty version Smarty-3.1.8, created on 2012-05-15 12:47:20
         compiled from "\Users\k-watanabe\Desktop\private\Dropbox\project\px\PxFW-1.x\htdocs\test\index.html" */ ?>
<?php /*%%SmartyHeaderCode:226704fb2343864dee0-07596492%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '2492c997a35241ead16bb6b4184942a14f5234cc' => 
    array (
      0 => '\\Users\\k-watanabe\\Desktop\\private\\Dropbox\\project\\px\\PxFW-1.x\\htdocs\\test\\index.html',
      1 => 1336110606,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '226704fb2343864dee0-07596492',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'px' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_4fb2343869f983_99407953',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_4fb2343869f983_99407953')) {function content_4fb2343869f983_99407953($_smarty_tpl) {?>

<p>test/index.html のHTMLソースコードです。</p>
<ul>
	<li><a href="../">トップへ戻る</a></li>
	<li><a href="./abc.html">./abc.html</a></li>
	<li><a href="./nopage.html">./nopage.html</a></li>
	<li><a href="<?php echo $_smarty_tpl->tpl_vars['px']->value->get_install_path();?>
test/dynamic/a/b.html">/test/dynamic/a/b.html</a></li>
	<li><a href="./user_test.html">./user_test.html</a></li>
</ul>

<?php }} ?>