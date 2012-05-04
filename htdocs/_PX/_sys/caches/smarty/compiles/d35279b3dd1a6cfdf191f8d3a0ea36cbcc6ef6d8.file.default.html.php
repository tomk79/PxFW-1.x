<?php /* Smarty version Smarty-3.1.8, created on 2012-04-21 10:56:38
         compiled from "/Users/ahomegane/Dropbox/project/px/PxFW-1.x/htdocs/_PX/themes/default/default.html" */ ?>
<?php /*%%SmartyHeaderCode:5640793804f929266caa4f7-71221778%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd35279b3dd1a6cfdf191f8d3a0ea36cbcc6ef6d8' => 
    array (
      0 => '/Users/ahomegane/Dropbox/project/px/PxFW-1.x/htdocs/_PX/themes/default/default.html',
      1 => 1335002650,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '5640793804f929266caa4f7-71221778',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'page_info' => 0,
    'px' => 0,
    'req' => 0,
    'site' => 0,
    'theme' => 0,
    'local_bros_page_info' => 0,
    'local_page_id' => 0,
    'local_page_info' => 0,
    'local_children' => 0,
    'local_child_page_id' => 0,
    'local_child_page_info' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_4f929266eaa322_72212332',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_4f929266eaa322_72212332')) {function content_4f929266eaa322_72212332($_smarty_tpl) {?><?php if (!is_callable('smarty_function_content')) include '/Users/ahomegane/Dropbox/project/px/PxFW-1.x/htdocs/_PX/libs/smarty/plugins/function.content.php';
?><!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<title><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['page_info']->value['title'], ENT_QUOTES, 'UTF-8', true);?>
 | <?php echo $_smarty_tpl->tpl_vars['px']->value->get_conf('project.name');?>
</title>
<meta name="keywords" content="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['page_info']->value['keywords'], ENT_QUOTES, 'UTF-8', true);?>
" />
<meta name="description" content="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['page_info']->value['description'], ENT_QUOTES, 'UTF-8', true);?>
" />
<?php echo $_smarty_tpl->tpl_vars['px']->value->ssi(implode('/',array($_smarty_tpl->tpl_vars['px']->value->get_install_path(),'/common/inc/common_setup.inc')));?>

<link rel="stylesheet" href="<?php echo $_smarty_tpl->tpl_vars['px']->value->get_install_path();?>
common/css/common.css" type="text/css" />
<?php echo smarty_function_content(array('name'=>"head"),$_smarty_tpl);?>

</head>
<body>
<div class="outline" id="pagetop">
<div class="header">
	<div class="logo"><a href="<?php echo $_smarty_tpl->tpl_vars['px']->value->get_install_path();?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['px']->value->get_conf('project.name'), ENT_QUOTES, 'UTF-8', true);?>
</a></div>
</div><!-- /.header -->
<div class="middle">
<div class="column1">
<h1><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['page_info']->value['title_h1'], ENT_QUOTES, 'UTF-8', true);?>
</h1>
<div id="content" class="contents">

<?php echo smarty_function_content(array(),$_smarty_tpl);?>


</div><!-- /#content -->
</div><!-- /.column1 -->
<div class="column2">
<div class="localnavi">
<?php if ($_smarty_tpl->tpl_vars['site']->value->get_page_info($_smarty_tpl->tpl_vars['req']->value->get_request_file_path(),'id')){?>
<div><?php echo $_smarty_tpl->tpl_vars['theme']->value->mk_link($_smarty_tpl->tpl_vars['site']->value->get_parent($_smarty_tpl->tpl_vars['req']->value->get_request_file_path()));?>
</div>
<?php }?>
<?php $_smarty_tpl->tpl_vars['local_bros_page_info'] = new Smarty_variable($_smarty_tpl->tpl_vars['site']->value->get_bros(), null, 0);?>
<?php if (count($_smarty_tpl->tpl_vars['local_bros_page_info']->value)){?>
<ul>
<?php  $_smarty_tpl->tpl_vars['local_page_id'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['local_page_id']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['local_bros_page_info']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['local_page_id']->key => $_smarty_tpl->tpl_vars['local_page_id']->value){
$_smarty_tpl->tpl_vars['local_page_id']->_loop = true;
?>
<?php $_smarty_tpl->tpl_vars['local_page_info'] = new Smarty_variable($_smarty_tpl->tpl_vars['site']->value->get_page_info($_smarty_tpl->tpl_vars['local_page_id']->value), null, 0);?>
<li><?php echo $_smarty_tpl->tpl_vars['theme']->value->mk_link($_smarty_tpl->tpl_vars['local_page_info']->value['path']);?>

<?php if ($_smarty_tpl->tpl_vars['local_page_info']->value['id']==$_smarty_tpl->tpl_vars['page_info']->value['id']){?>
<?php $_smarty_tpl->tpl_vars['local_children'] = new Smarty_variable($_smarty_tpl->tpl_vars['site']->value->get_children(), null, 0);?>
<?php if (count($_smarty_tpl->tpl_vars['local_children']->value)){?>
<ul>
<?php  $_smarty_tpl->tpl_vars['local_child_page_id'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['local_child_page_id']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['local_children']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['local_child_page_id']->key => $_smarty_tpl->tpl_vars['local_child_page_id']->value){
$_smarty_tpl->tpl_vars['local_child_page_id']->_loop = true;
?>
<?php $_smarty_tpl->tpl_vars['local_child_page_info'] = new Smarty_variable($_smarty_tpl->tpl_vars['site']->value->get_page_info($_smarty_tpl->tpl_vars['local_child_page_id']->value), null, 0);?>
<li><?php echo $_smarty_tpl->tpl_vars['theme']->value->mk_link($_smarty_tpl->tpl_vars['local_child_page_info']->value['path']);?>
</li>
<?php } ?>
</ul>
<?php }?>
<?php }?>
</li>
<?php } ?>
</ul>
<?php }?>
</div><!-- /.localnavi -->
</div><!-- /.column2 -->
</div><!-- /.middle -->
<div class="footer">
	<div class="right small">[<a href="#pagetop">ページの先頭へ戻る</a>]</div>
	<div class="breadcrumb"><?php echo $_smarty_tpl->tpl_vars['theme']->value->mk_breadcrumb();?>
</div>
	<div class="center">[<?php echo $_smarty_tpl->tpl_vars['theme']->value->mk_link('/');?>
]</div>
</div><!-- /.footer -->
</div><!-- /.outline -->
</body>
</html><?php }} ?>