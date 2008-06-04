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
    $form['menu_block_enabled_blocks']['#options']["$mid-2"] = t('@menu (2nd level)', array('@menu' => $title));
    $form['menu_block_enabled_blocks']['#options']["$mid-3"] = t('@menu (3rd level)', array('@menu' => $title));
    $form['menu_block_enabled_blocks']['#options']["$mid-4"] = t('@menu (4th level)', array('@menu' => $title));
  }
  return system_settings_form($form);
}

/**
 * Returns the 'list' $op info for hook_block().
 */
function menu_block_block_list() {
  $menus = menu_get_root_menus();
  foreach (variable_get('menu_block_enabled_blocks', array()) AS $bid) {
    list($mid, $level) = explode('-', $bid);
    $title = $menus[$mid];
    if ($level == '2') {
      $blocks[$bid]['info'] = t('@menu (2nd level)', array('@menu' => $title));
    }
    elseif ($level == '3') {
      $blocks[$bid]['info'] = t('@menu (3rd level)', array('@menu' => $title));
    }
    elseif ($level == '4') {
      $blocks[$bid]['info'] = t('@menu (4th level)', array('@menu' => $title));
    }
  }
  return $blocks;
}
