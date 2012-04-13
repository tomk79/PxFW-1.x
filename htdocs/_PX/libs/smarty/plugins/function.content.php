<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {content} function plugin
 * PxFW custom
 */

function smarty_function_content($params, $template)
{
	if( !strlen($params['name']) ){$params['name'] = '';}
	$src = $template->tpl_vars['theme']->value->pull_content($params['name']);
	return $src;
}

?>