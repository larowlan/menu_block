<?php
// $Id$

/**
 * @file
 * Provides infrequently used functions and hooks for menu_block_export.
 */

/**
 * Implements hook_menu().
 */
function _menu_block_export_menu() {
  $items['admin/config/user-interface/menu-block/export'] = array(
    'title' => 'Menu block export',
    'description' => 'Export menu blocks.',
    'access arguments' => array('administer blocks'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('menu_block_export_form'),
    'type' => MENU_LOCAL_TASK,
    'file' => 'menu_block_export.admin.inc',
  );
  return $items;
}

/**
 * Menu callback; export form.
 *
 * @return
 *   The export form used by Menu block.
 */
function menu_block_export_form($form, &$form_state) {
  return $form;
}
