<?php
// $Id$

/**
 * @file
 * Hooks provided by the Menu Block module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the menu tree and its configuration before the block is rendered.
 *
 * @param $tree
 *   
 * @param $config
 *   An array containing the configuration of the block.
 */
function hook_menu_block_tree_alter(&$tree, &$config) {
}

/**
 * Return a list of menus to use with the menu_block module.
 *
 * @return
 *   An array containing the menus' machine names as keys with their menu titles
 *   as values.
 */
function hook_get_menus() {
  $menus = array();
  // For each menu, add the following information:
  $menus['menu_name'] = 'menu title';

  return $menus;
}

/**
 * @} End of "addtogroup hooks".
 */
