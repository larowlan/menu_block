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
    case 'admin/build/block':
      $output =
        '<p>' . t('Use the <a href="@url">add menu block page</a> to create a customizable menu block. You will then be able to configure your menu block before adding it.', array('@url' => url('admin/build/block/add-menu-block'))) . '</p>'
      . theme('more_help_link', url('admin/help/menu_block'));
      
      break;
    case 'admin/help#menu_block':
      $output =
        '<h3>' . t('Adding menu blocks') . '</h3>'
      . '<p>' . t('To add new menu blocks, use the "Add menu block" tab (or button) on the <a href="@url">administer blocks page</a>. You will then be able to configure your menu block before adding it.', array('@url' => url('admin/build/block'))) . '</p>'
      . '<h3>' . t('Configuring menu blocks') . '</h3>'
      . '<p>' . t('When adding or configuring a menu block, several configuration options are available:') . '</p>'
      . '<dl>'
      . '<dt><strong>' . t('Block title') . '</strong></dt>'
      . '<dd>' . t('For menu trees that start with the 1st level, the default block title will be the menu name. For menu trees that start with the 2nd level or deeper, the default block title will be the title for the parent menu item of the specified level.')
      . '<p>' . t('For example, if the active menu trail for the Navigation menu is: Administer › Site building › Menus › Primary links, then a menu block configured to start with the 1st level of the Navigation will display a block title of “Navigation”. And a menu block configured to start with the 3rd level of the Navigation menu will display a block title of “Site building”.') . '</p></dd>'
      . '<dt><strong>' . t('Menu') . '</strong></dt>'
      . '<dd>' . t('The tree of links will be taken from this menu.') . '</dd>'
      . '<dt><strong>' . t('Starting level of menu tree') . '</strong></dt>'
      . '<dd>' . t('Blocks that start with the 1st level will always be visible. Blocks that start with the 2nd level or deeper will only be visible when the trail to the active menu item is in the block’s tree.') . '</dd>'
      . '<dt><strong>' . t('Maximum depth of menu tree') . '</strong></dt>'
      . '<dd>' . t('From the starting level, specify the maximum depth of the tree. Blocks with a maximum dpeth of 1 will just be a single un-nested list of links with none of those links’ children displayed.') . '</dd>'
      . '<dt><strong>' . t('Expand all children') . '</strong></dt>'
      . '<dd>' . t('All sub-menus of this menu will be expanded.') . '</dd>'
      . '</dl>'
      . '<h3>' . t('Styling menu blocks') . '</h3>'
      . '<p>' . t('Themers should look at the myriad of classes added to the %div, %li and %a elements.', array('%div' => '<div>', '%li' => '<li>', '%a' => '<a>')) . '</p>'
      . '<dl>'
      . '<dt>' . theme('placeholder', '<div>') . '</dt>'
      . '<dd>' . t('The %div wrapped around the menu tree has a class for each of the configurable options of the block: %class.', array('%div' => '<div>', '%class' => 'menu-name-NAME menu-level-LEVEL menu-depth-DEPTH menu-expanded')) . '</dd>'
      . '<dt>' . theme('placeholder', '<li>') . '</dt>'
      . '<dd>' . t('The %li elements of the menu tree can have an extended list of classes (compared to standard menu trees): %class.', array('%li' => '<li>', '%class' => 'first last menu-mlid-MLID active active-trail')) . '</dd>'
      . '<dt>' . theme('placeholder', '<a>') . '</dt>'
      . '<dd>' . t('The %a elements of the menu tree can have: %class.', array('%a' => '<a>', '%class' => 'active active-trail')) . '</dd>'
      . '</dl>'
      . '<h3>' . t('Menu block API') . '</h3>'
      . '<p>' . t('Developers can use the API of this module to create their own menu trees outside the confines of blocks. All of the publicly available API functions are documented in the menu_block.module file.') . '</p>';
      break;
  }
  return $output;
}

/**
 * This page is simply to make Menu block visible on admin pages.
 *
 * In Drupal 6, the admin/by-module page won't display Menu block's help link
 * because it doesn't have any non-tab menu links. So to allow Menu block to
 * have some "visibility" in the admin pages, we add a fake-ish page to
 * admin/settings/menu_block that redirects to the "Add menu block" tab.
 */
function _menu_block_by_module_hack() {
  drupal_goto('admin/build/block/add-menu-block', NULL, NULL, 301);
}
