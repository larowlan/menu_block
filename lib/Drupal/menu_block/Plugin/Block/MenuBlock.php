<?php

/**
 * @file
 * Contains \Drupal\menu_block\Plugin\Block\MenuBlock.
 */

namespace Drupal\menu_block\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_block\MenuBlockRepositoryInterface;

/**
 * Defines a menu block block type.
 *
 * @Block(
 *  id = "menu_block",
 *  admin_label = @Translation("Menu block"),
 *  category = @Translation("Menus"),
 * )
 */
class MenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {
    $config = $this->configuration;
    // Get the config from the form state.
    if (!empty($form_state['values'])) {
      $config = $form_state['values'];
      if (!empty($config['parent'])) {
        list($config['menu_name'], $config['parent_mlid']) = explode(':', $config['parent']);
      }
    }

    // Build the standard form.
    // @todo Move this to hook_library_info with dependencies on jquery.
    $form['#attached']['js'][] = drupal_get_path('module', 'menu_block') . '/menu-block.js';
    $form['#attached']['css'][] = drupal_get_path('module', 'menu_block') . '/menu-block.admin.css';
    $form['#attached']['library'][] = array('system', 'ui.button');
    // $form['#attached']['library'][] = array('menu_block', 'menu_block.admin');

    $form['menu-block-wrapper-start'] = array(
      '#markup' => '<div id="menu-block-settings">',
      '#weight' => -30,
    );
    $form['display_options'] = array(
      '#type' => 'radios',
      '#title' => t('Display'),
      '#default_value' => 'basic',
      '#options' => array(
        'basic' => t('Basic options'),
        'advanced' => t('Advanced options'),
      ),
      '#attributes' => array('class' => array('clearfix')),
      '#weight' => -29,
    );
    $form['title_link'] = array(
      '#type' => 'checkbox',
      '#title' => t('Block title as link'),
      '#default_value' => $config['title_link'],
      '#description' => t('Make the default block title a link to that menu item. An overridden block title will not be a link.'),
      '#states' => array(
        'visible' => array(
          ':input[name=title]' => array('value' => ''),
        ),
      ),
    );
    // We need a different state if the form is in a Panel overlay.
    if (isset($form['override_title'])) {
      $form['title_link']['#states'] = array(
        'visible' => array(
          ':input[name=override_title]' => array('checked' => FALSE),
        ),
      );
    }
    $form['admin_title'] = array(
      '#type' => 'textfield',
      '#default_value' => $config['admin_title'],
      '#title' => t('Administrative title'),
      '#description' => t('This title will be used administratively to identify this block. If blank, the regular title will be used.'),
    );
    // @todo inject the MenuBlock repository.
    $menus = \Drupal::service('menu_block.repository')->getMenus();
    $form['menu_name'] = array(
      '#type' => 'select',
      '#title' => t('Menu'),
      '#default_value' => $config['menu_name'],
      '#options' => $menus,
      '#description' => t('The preferred menus used by <em>&lt;the menu selected by the page&gt;</em> can be customized on the <a href="!url">Menu block settings page</a>.', array('!url' => url('admin/config/user-interface/menu-block'))),
      '#attributes' => array('class' => array('menu-block-menu-name')),
    );
    $form['level'] = array(
      '#type' => 'select',
      '#title' => t('Starting level'),
      '#default_value' => $config['level'],
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
      ),
      '#description' => t('Blocks that start with the 1st level will always be visible. Blocks that start with the 2nd level or deeper will only be visible when the trail to the active menu item passes though the block’s starting level.'),
    );
    // The value of "follow" in the database/config array is either FALSE or the
    // value of the "follow_parent" form element.
    if ($follow = $config['follow']) {
      $follow_parent = $follow;
      $follow = 1;
    }
    else {
      $follow_parent = 'active';
    }
    $form['follow'] = array(
      '#type' => 'checkbox',
      '#title' => t('Make the starting level follow the active menu item.'),
      '#default_value' => $follow,
      '#description' => t('If the active menu item is deeper than the level specified above, the starting level will follow the active menu item. Otherwise, the starting level of the tree will remain fixed.'),
      '#element_validate' => array('\Drupal\menu_block\Plugin\Block\MenuBlock::followValidate'),
    );
    $form['follow_parent'] = array(
      '#type' => 'select',
      '#title' => t('Starting level will be'),
      '#default_value' => $follow_parent,
      '#options' => array(
        'active' => t('Active menu item'),
        'child' => t('Children of active menu item'),
      ),
      '#description' => t('When following the active menu item, specify if the starting level should be the active menu item or its children.'),
      '#states' => array(
        'visible' => array(
          ':input[name=follow]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['depth'] = array(
      '#type' => 'select',
      '#title' => t('Maximum depth'),
      '#default_value' => $config['depth'],
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
        '0'  => t('Unlimited'),
      ),
      '#description' => t('From the starting level, specify the maximum depth of the menu tree.'),
    );
    $form['expanded'] = array(
      '#type' => 'checkbox',
      '#title' => t('<strong>Expand all children</strong> of this tree.'),
      '#default_value' => $config['expanded'],
    );
    $form['sort'] = array(
      '#type' => 'checkbox',
      '#title' => t('<strong>Sort</strong> menu tree by the active menu item’s trail.'),
      '#default_value' => $config['sort'],
      '#description' => t('Sort each item in the active trail to the top of its level. When used on a deep or wide menu tree, the active menu item’s children will be easier to see when the page is reloaded.'),
    );
    $form['parent'] = array(
      '#type' => 'select',
      '#title' => t('Fixed parent item'),
      '#default_value' => $config['menu_name'] . ':' . $config['parent_mlid'],
      '#options' => menu_parent_options($menus),
      '#description' => t('Alter the “starting level” and “maximum depth” options to be relative to the fixed parent item. The tree of links will only contain children of the selected menu item.'),
      '#attributes' => array('class' => array('menu-block-parent-mlid')),
      '#element_validate' => array('\Drupal\menu_block\Plugin\Block\MenuBlock::parentValidate'),
    );
    // @todo move the constant to the menu block repository.
    $form['parent']['#options'][MenuBlockRepositoryInterface::CURRENT_PAGE_MENU . ':0'] = '<' . t('the menu selected by the page') . '>';
    $form['menu-block-wrapper-close'] = array('#markup' => '</div>');

    // Set visibility of advanced options.
    foreach (array('title_link', 'follow', 'follow_parent', 'expanded', 'sort', 'parent') as $key) {
      $form[$key]['#states']['visible'][':input[name=display_options]'] = array('value' => 'advanced');
    }
    if ($config['title_link'] || $follow || $config['expanded'] || $config['sort'] || $config['parent_mlid']) {
      $form['display_options']['#default_value'] = 'advanced';
    }

    return $form;
  }

  public function access(AccountInterface $account) {
    return $account->hasPermission('administer blocks') &&
      $account->hasPermission('administer menu');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;
    $data = menu_tree_build($config);
    // Add contextual links for this block.
    if (!empty($data['content'])) {
      // @todo use the repo service.
      if (in_array($config['menu_name'], array_keys(menu_get_menus()))) {
        // @todo move these to plugins.
        $data['content']['#contextual_links']['menu_block'] = array('admin/structure/menu/manage', array($config['menu_name']));
      }
      elseif (strpos($config['menu_name'], 'book-toc-') === 0) {
        // @todo move these to plugins.
        $node = str_replace('book-toc-', '', $config['menu_name']);
        $data['content']['#contextual_links']['menu_block'] = array('admin/content/book', array($node));
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'menu_name'   => 'main-menu',
      'parent_mlid' => 0,
      'parent'      => '',
      'title_link'  => 0,
      'admin_title' => '',
      'level'       => 1,
      'follow'      => 0,
      'depth'       => 0,
      'expanded'    => 0,
      'sort'        => 0,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    $this->setConfiguration($form_state['values']);
  }


  /**
   * Validates the parent element of the block configuration form.
   * @todo fix warnings or move to validate method.
   */
  public static function parentValidate($element, &$form_state) {
    // Determine the fixed parent item's menu and mlid.
    list($menu_name, $parent_mlid) = explode(':', $form_state['values']['parent']);
    $form_state['values']['parent_mlid'] = (int) $parent_mlid;

    if ($parent_mlid) {
      // If mlid is set, its menu overrides the menu_name option.
      $form_state['values']['menu_name'] = $menu_name;
    }
  }

  /**
   * Validates the follow element of the block configuration form.
   * @todo fix warnings or move to validate method.
   */
  public static function followValidate($element, &$form_state) {
    // The value of "follow" stored in the database/config array is either FALSE
    // or the value of the "follow_parent" form element.
    if ($form_state['values']['follow'] && !empty($form_state['values']['follow_parent'])) {
      $form_state['values']['follow'] = $form_state['values']['follow_parent'];
    }
  }

}
