<?php
// $Id$

/**
 * @file
 * Provides infrequently used pages for menu_block.
 */

/**
 * Implements hook_help().
 */
function _menu_block_help($path, $arg) {
  $output = '';
  switch ($path) {
    case 'admin/help#menu_block':
      $output =
        '<h3>' . t('Adding menu blocks') . '</h3>'
      . '<p>' . t('To add new menu blocks, use the "Add menu block" button on the <a href="@url">administer blocks page</a>. You will then be able to configure your menu block before adding it.', array('@url' => url('admin/build/block'))) . '</p>'
      . '<h3>' . t('Configuring menu blocks') . '</h3>'
      . '<p>' . t('When adding or configuring a menu block, several configuration options are available:') . '</p>'
      . '<dl>'
      . '<dt>' . t('Menu') . '</dt>'
      . '<dd>' . t('The tree (or list) of links will be taken from this menu.') . '</dd>'
      . '<dt>' . t('Starting level of menu tree') . '</dt>'
      . '<dd>' . t('Blocks that start with the 1st level will always be visible. Blocks that start with the 2nd level or deeper will only be visible when the trail to the active menu item is in the block’s tree.') . '</dd>'
      . '<dt>' . t('Maximum depth of menu tree') . '</dt>'
      . '<dd>' . t('Trees with a maximum depth of 1 will be styled as a menu list, i.e. theme_menu_block_list() instead of theme_menu_block_tree().') . '</dd>'
      . '<dt>' . t('Expand all children') . '</dt>'
      . '<dd>' . t('All sub-menus of this menu will be expanded.') . '</dd>'
      . '</dl>'
      . '<h3>' . t('Styling menu blocks') . '</h3>'
      . '<p>' . t('Themers should look at the myriad of classes added to the %ul and %li elements.', array('%ul' => 'ul', '%li' => 'li')) . '</p>'
      . '<h3>' . t('Menu block API') . '</h3>'
      . '<p>' . t('Developers can use the API of this module to create their own menu trees or lists outside the confines of blocks. All of the publicly available API functions are documented in the menu_block.module file.') . '</p>'
      ;
      break;
    case 'admin/build/block/add-menu-block':
      $output = t('<a href="@url">More detailed help</a> is available.', array('@url' => url('admin/help/menu_block')));
      break;
  }
  return $output;
}
