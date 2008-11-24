<?php
// $Id$

/**
 * @file
 * Provides infrequently used functions for menu_block.
 */

/**
 * Menu callback: display the menu block addition form.
 */
function menu_block_add_block_form(&$form_state) {
  include_once './' . drupal_get_path('module', 'block') . '/block.admin.inc';
  return block_admin_configure($form_state, 'menu_block', NULL);
}

/**
 * Save the new menu block.
 */
function menu_block_add_block_form_submit($form, &$form_state) {
  // Determine the delta of the new block.
  $block_ids = variable_get('menu_block_ids', array());
  $delta = empty($block_ids) ? 1 : max($block_ids) + 1;

  // Save the new array of blocks IDs.
  $block_ids[] = $delta;
  variable_set('menu_block_ids', $block_ids);

  // Save the block configuration.
  _menu_block_block_save($delta, $form_state['values']);

  // Run the normal new block submission (borrowed from block_add_block_form_submit).
  foreach (list_themes() as $key => $theme) {
    if ($theme->status) {
      db_query("INSERT INTO {blocks} (visibility, pages, custom, title, module, theme, status, weight, delta, cache) VALUES(%d, '%s', %d, '%s', '%s', '%s', %d, %d, %d, %d)", $form_state['values']['visibility'], trim($form_state['values']['pages']), $form_state['values']['custom'], $form_state['values']['title'], $form_state['values']['module'], $theme->name, 0, 0, $delta, BLOCK_NO_CACHE);
    }
  }

  foreach (array_filter($form_state['values']['roles']) as $rid) {
    db_query("INSERT INTO {blocks_roles} (rid, module, delta) VALUES (%d, '%s', '%s')", $rid, $form_state['values']['module'], $delta);
  }

  drupal_set_message(t('The block has been created.'));
  cache_clear_all();

  $form_state['redirect'] = 'admin/build/block';
  return;
}

/**
 * Alters the block admin form to add delete links next to menu blocks.
 */
function _menu_block_form_block_admin_display_form_alter(&$form, $form_state) {
  foreach (variable_get('menu_block_ids', array()) AS $delta) {
    $form['menu_block_' . $delta]['delete'] = array('#value' => l(t('delete'), 'admin/build/block/delete-menu-block/'. $delta));
  }
}

/**
 * Menu callback: confirm deletion of menu blocks.
 */
function menu_block_delete(&$form_state, $delta = 0) {
  $title = _menu_block_format_title($delta);
  $form['block_title'] = array('#type' => 'hidden', '#value' => $title);
  $form['delta'] = array('#type' => 'hidden', '#value' => $delta);

  return confirm_form($form, t('Are you sure you want to delete the "%name" block?', array('%name' => $title)), 'admin/build/block', NULL, t('Delete'), t('Cancel'));
}

/**
 * Deletion of menu blocks.
 */
function menu_block_delete_submit($form, &$form_state) {
  // Remove the menu block configuration variables.
  $delta = $form_state['values']['delta'];
  $block_ids = variable_get('menu_block_ids', array());
  unset($block_ids[array_search($delta, $block_ids)]);
  sort($block_ids);
  variable_set('menu_block_ids', $block_ids);
  variable_del("menu_block_{$delta}_menu_name");
  variable_del("menu_block_{$delta}_level");
  variable_del("menu_block_{$delta}_depth");
  variable_del("menu_block_{$delta}_expanded");

  db_query("DELETE FROM {blocks} WHERE module = 'menu_block' AND delta = %d", $delta);
  db_query("DELETE FROM {blocks_roles} WHERE module = 'menu_block' AND delta = %d", $delta);
  drupal_set_message(t('The "%name" block has been removed.', array('%name' => $form_state['values']['block_title'])));
  cache_clear_all();
  $form_state['redirect'] = 'admin/build/block';
  return;
}

/**
 * Returns the 'list' $op info for hook_block().
 */
function _menu_block_block_list() {
  $blocks = array();
  foreach (variable_get('menu_block_ids', array()) AS $delta) {
    $blocks[$delta]['info'] = _menu_block_format_title($delta);
    // Menu blocks can't be cached because each menu item can have
    // a custom access callback. menu.inc manages its own caching.
    $blocks[$delta]['cache'] = BLOCK_NO_CACHE;
  }
  return $blocks;
}

/**
 * Return the title of the block.
 *
 * @param $delta
 *   int The delta of the menu block
 * @return
 *   string The title of the block
 */
function _menu_block_format_title($delta) {
  $menu_name = variable_get("menu_block_{$delta}_menu_name", NULL);
  if (is_null($menu_name)) {
    $title = t('Unconfigured menu block');
  }
  else {
    $level = variable_get("menu_block_{$delta}_level", 1);
    $depth = variable_get("menu_block_{$delta}_depth", 0);
    $expanded = variable_get("menu_block_{$delta}_expanded", 0);
    $menus = menu_get_menus(TRUE);
    // Show the configured levels in the block info
    $replacements = array('@menu_name' => $menus[$menu_name], '@level1' => $level, '@level2' => $level + $depth - 1);
    if ($depth == 1) {
      $title = t('@menu_name (level @level1)', $replacements);
    }
    elseif ($depth) {
      if ($expanded) {
        $title = t('@menu_name (expanded levels @level1-@level2)', $replacements);
      }
      else {
        $title = t('@menu_name (levels @level1-@level2)', $replacements);
      }
    }
    else {
      if ($expanded) {
        $title = t('@menu_name (expanded levels @level1+)', $replacements);
      }
      else {
        $title = t('@menu_name (levels @level1+)', $replacements);
      }
    }
  }
  return $title;
}

/**
 * Returns the 'configure' $op info for hook_block().
 */
function _menu_block_block_configure($delta) {
  $form['menu_name'] = array(
    '#type' => 'select',
    '#title' => t('Menu'),
    '#default_value' => variable_get("menu_block_{$delta}_menu_name", 'navigation'),
    '#options' => menu_get_menus(TRUE),
    '#description' => t('The tree (or list) of links will be taken from this menu.'),
  );
  $form['level'] = array(
    '#type' => 'select',
    '#title' => t('Starting level of menu tree'),
    '#default_value' => variable_get("menu_block_{$delta}_level", 1),
    '#options' => array(
      '1'  => t('1st level (primary)'),
      '2'  => t('2nd level (secondary)'),
      '3'  => t('3rd level (tertiary)'),
      '4'  => t('4th level'),
      '5'  => t('5th level'),
      '6'  => t('6th level'),
      '7'  => t('7th level'),
      '8'  => t('8th level'),
      '9'  => t('9th level'),
      '10' => t('10th level'),
    ),
    '#description' => t('Blocks that start with the 1st level will always be visible. Blocks that start with the 2nd level or deeper will only be visible when the trail to the active menu item is in the block’s tree.'),
  );
  $form['depth'] = array(
    '#type' => 'select',
    '#title' => t('Maximum depth of menu tree'),
    '#default_value' => variable_get("menu_block_{$delta}_depth", 0),
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
    '#description' => t('From the starting level, specify the maximum depth of the tree.'),
  );
  $form['expanded'] = array(
    '#type' => 'checkbox',
    '#title' => t('Expand all children'),
    '#default_value' => variable_get("menu_block_{$delta}_expanded", 0),
    '#description' => t('All sub-menus of this menu will be expanded.'),
  );
  return $form;
}

/**
 * Returns the 'save' $op info for hook_block().
 */
function _menu_block_block_save($delta, $edit) {
  variable_set("menu_block_{$delta}_menu_name", $edit['menu_name']);
  variable_set("menu_block_{$delta}_level", $edit['level']);
  variable_set("menu_block_{$delta}_depth", $edit['depth']);
  variable_set("menu_block_{$delta}_expanded", $edit['expanded']);
}
