<?php
/**
 * @file
 * Provides infrequently used functions and hooks for menu_block.
 */

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

/**
 * Theme a drag-to-reorder table of menu selection checkboxes.
 */
function theme_menu_block_menu_order($variables) {
  $element = $variables['element'];
  drupal_add_tabledrag('menu-block-menus', 'order', 'sibling', 'menu-weight');

  $variables = array(
    'header' => array(
      t('Menu'),
      t('Available'),
      t('Weight'),
    ),
    'rows' => array(),
    'attributes' => array('id' => 'menu-block-menus'),
  );

  // Generate table of draggable menu names.
  foreach (element_children($element) as $menu_name) {
    $element[$menu_name]['weight']['#attributes']['class'] = array('menu-weight');
    $variables['rows'][] = array(
      'data' => array(
        drupal_render($element[$menu_name]['title']),
        drupal_render($element[$menu_name]['available']),
        drupal_render($element[$menu_name]['weight']),
      ),
      'class' => array('draggable'),
    );
  }

  return theme('table', $variables);
}
