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
    for ($i=2; $i<5; $i++) {
      $form['menu_block_enabled_blocks']['#options']["$mid-$i"]
        = t('@menu (levels @level+)', array( '@menu' => $title, '@level' => $i));
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
      $title = $menus[$mid];
      $blocks[$bid]['info'] = t('@menu (levels @level+)', array('@menu' => $title, '@level' => $level));
    }
  }
  return $blocks;
}
