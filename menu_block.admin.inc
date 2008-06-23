<?php
// $Id$

/**
 * Display the settings form for Menu block.
 *
 * @return
 *   The form array.
 */
function _menu_block_settings() {
  $form = array();
  $form['menu_block_enabled_blocks'] = array(
    '#type'          => 'checkboxes',
    '#title'         => t('Enable the following blocks of menu trees'),
    '#default_value' => variable_get('menu_block_enabled_blocks', array()),
    '#options'       => array(),
    '#description'   => t('Only enabled blocks will appear on the <a href="!url">administer blocks page</a>.', array('!url' => url('admin/build/block'))),
  );
  $menus = menu_get_root_menus();
  foreach ($menus AS $mid => $title) {
    for ($i=1; $i<5; $i++) {
      $form['menu_block_enabled_blocks']['#options']["$mid-$i"] = _menu_block_format_title($title, $mid, $i);
    }
  }
  return system_settings_form($form);
}

/**
 * Returns the 'list' $op info for hook_block().
 */
function menu_block_block_list() {
  $menus = menu_get_root_menus();
  foreach (variable_get('menu_block_enabled_blocks', array()) AS $bid => $enabled) {
    if ($enabled) {
      list($mid, $level) = explode('-', $bid);
      $blocks[$bid]['info'] = _menu_block_format_title($menus[$mid], $mid, $level);
    }
  }
  return $blocks;
}

/**
 * Return the title of the block.
 *
 * @param $menu_title
 *   int The title of the menu
 * @param $mid
 *   int The menu id for the block
 * @param $level
 *   int The starting level of the block
 * @return
 *   string The title of the block
 */
function _menu_block_format_title($menu_title, $mid, $level) {
  $depth = variable_get("menu_block_depth_{$mid}_{$level}", 0);
  // Show the configured levels in the block info
  $replacements = array('@menu' => $menu_title, '@level1' => $level, '@level2' => $level + $depth - 1);
  if ($depth == 1) {
    $title = t('@menu (level @level1)', $replacements);
  }
  elseif ($depth) {
    $title = t('@menu (levels @level1-@level2)', $replacements);
  }
  else {
    $title = t('@menu (levels @level1+)', $replacements);
  }
  return $title;
}

/**
 * Returns the 'configure' $op info for hook_block().
 */
function menu_block_block_configure($delta = null) {
  list($menu, $level) = explode('-', $delta);
  $form['depth'] = array(
    '#type' => 'select',
    '#title' => t('Maximum depth of menu tree'),
    '#default_value' => variable_get("menu_block_depth_{$menu}_{$level}", 0),
    '#options' => array(
      '1'  => '1',
      '2'  => '2',
      '3'  => '3',
      '4'  => '4',
      '5'  => '5',
      '6'  => '6',
      '7'  => '7',
      '8'  => '8',
      '9'  => '9',
      '10' => '10',
      '0'  => 'Unlimited',
    ),
    '#description' => t('Trees with a maximum depth of 1 will be styled as a menu list, i.e. theme_menu_block_list() instead of theme_menu_block_tree().'),
  );
  $form['expanded'] = array(
    '#type' => 'checkbox',
    '#title' => t('Expand all childen'),
    '#default_value' => variable_get("menu_block_expanded_{$menu}_{$level}", 0),
    '#description' => t('All sub-menus of this menu will be expanded.'),
  );
  return $form;
}

/**
 * Returns the 'save' $op info for hook_block().
 */
function menu_block_block_save($delta = null, $edit = null) {
  list($menu, $level) = explode('-', $delta);
  variable_set("menu_block_depth_{$menu}_{$level}", $edit['depth']);
  variable_set("menu_block_expanded_{$menu}_{$level}", $edit['expanded']);
}
