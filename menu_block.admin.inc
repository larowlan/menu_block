<?php
/**
 * @file
 * Provides infrequently used functions and hooks for menu_block.
 */

use Drupal\Core\Render\Element;

/**
 * Return the title of the block.
 *
 * @param $config
 *   array The configuration of the menu block.
 * @return
 *   string The title of the block.
 */
function _menu_block_format_title($config) {
  // If an administrative title is specified, use it.
  if (!empty($config['admin_title'])) {
    return check_plain($config['admin_title']);
  }
  $menus = \Drupal::service('menu_block.repository')->getMenus();
  $menus[MENU_TREE__CURRENT_PAGE_MENU] = t('Current menu');
  if (empty($config['menu_name']) || empty($menus[$config['menu_name']])) {
    $title = t('Unconfigured menu block');
  }
  else {
    // Show the configured levels in the block info
    $replacements = array(
      '@menu_name' => $menus[$config['menu_name']],
      '@level1' => $config['level'],
      '@level2' => $config['level'] + $config['depth'] - 1,
    );
    if ($config['parent_mlid']) {
      $parent_item = menu_link_load($config['parent_mlid']);
      $replacements['@menu_name'] = $parent_item['title'];
    }
    if ($config['follow']) {
      $title = t('@menu_name (active menu item)', $replacements);
    }
    elseif ($config['depth'] == 1) {
      $title = t('@menu_name (level @level1)', $replacements);
    }
    elseif ($config['depth']) {
      if ($config['expanded']) {
        $title = t('@menu_name (expanded levels @level1-@level2)', $replacements);
      }
      else {
        $title = t('@menu_name (levels @level1-@level2)', $replacements);
      }
    }
    else {
      if ($config['expanded']) {
        $title = t('@menu_name (expanded levels @level1+)', $replacements);
      }
      else {
        $title = t('@menu_name (levels @level1+)', $replacements);
      }
    }
  }
  return $title;
}
