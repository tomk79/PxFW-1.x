<?php
/**
 * Smarty plugin to send_content blocks
 *
 * @package Smarty
 * @subpackage PluginsBlock
 */

/**
 * Smarty {send_content}{/send_content} block plugin
 * PxFW custom
 */
function smarty_block_send_content($params, $content, $template, &$repeat)
{
	$template->tpl_vars['theme']->value->send_content($content,$params['name']);
	return '';
}

?>