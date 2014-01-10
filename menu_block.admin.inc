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
 * Menu callback: admin settings form.
 *
 * @return
 *   The settings form used by Menu block.
 */
function menu_block_admin_settings_form($form, &$form_state) {
  // Option to suppress core's blocks of menus.
  $form['menu_block_suppress_core'] = array(
    '#type' => 'checkbox',
    '#title' => t('Suppress Drupal’s standard menu blocks'),
    '#default_value' => variable_get('menu_block_suppress_core', 0),
    '#description' => t('On the blocks admin page, hide Drupal’s standard blocks of menus.'),
    '#access' => module_exists('block'),
  );

  // Retrieve core's menus.
  $menus = menu_get_menus();
  // Retrieve all the menu names provided by hook_menu_block_get_sort_menus().
  $menus = array_merge($menus, module_invoke_all('menu_block_get_sort_menus'));
  asort($menus);

  // Load stored configuration.
  $menu_order = variable_get('menu_block_menu_order', array('main-menu' => '', 'user-menu' => ''));
  // Remove any menus no longer in the list of all menus.
  foreach (array_keys($menu_order) as $menu) {
    if (!isset($menus[$menu])) {
      unset($menu_order[$menu]);
    }
  }

  // Merge the saved configuration with any un-configured menus.
  $all_menus = $menu_order + $menus;

  $form['heading'] = array(
    '#markup' => '<p>' . t('If a block is configured to use <em>"the menu selected by the page"</em>, the block will be generated from the first "available" menu that contains a link to the page.') . '</p>',
  );

  // Orderable list of menu selections.
  $form['menu_order'] = array(
    '#tree' => TRUE,
    '#theme' => 'menu_block_menu_order',
  );

  $order = 0;
  $total_menus = count($all_menus);
  foreach (array_keys($all_menus) as $menu_name) {
    $form['menu_order'][$menu_name] = array(
      'title' => array(
        '#markup' => check_plain($menus[$menu_name]),
      ),
      'available' => array(
        '#type' => 'checkbox',
        '#attributes' => array('title' => t('Select from the @menu_name menu', array('@menu_name' => $menus[$menu_name]))),
        '#default_value' => isset($menu_order[$menu_name]),
      ),
      'weight' => array(
        '#type' => 'weight',
        '#default_value' => $order - $total_menus,
        '#delta' => $total_menus,
        '#id' => 'edit-menu-block-menus-' . $menu_name,
      ),
    );
    $order++;
  }

  $form['footer_note'] = array(
    '#markup' => '<p>' . t('The above list will <em>not</em> affect menu blocks that are configured to use a specific menu.') . '</p>',
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Form submission handler.
 */
function menu_block_admin_settings_form_submit($form, &$form_state) {
  $menu_order = array();
  foreach ($form_state['values']['menu_order'] AS $menu_name => $row) {
    if ($row['available']) {
      // Add available menu and its weight to list.
      $menu_order[$menu_name] = (int) $row['weight'];
    }
  }
  // Sort the keys by the weight stored in the value.
  asort($menu_order);
  foreach ($menu_order AS $menu_name => $weight) {
    // Now that the array is sorted, the weight is redundant data.
    $menu_order[$menu_name] = '';
  }
  variable_set('menu_block_menu_order', $menu_order);
  if ($form_state['values']['menu_block_suppress_core']) {
    variable_set('menu_block_suppress_core', 1);
  }
  else {
    variable_del('menu_block_suppress_core');
  }
  drupal_set_message(t('The configuration options have been saved.'));
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
