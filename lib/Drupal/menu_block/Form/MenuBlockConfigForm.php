<?php

/**
 * @file
 * Contains \Drupal\menu_block\Form\MenuBlockConfigForm.
 */

namespace Drupal\menu_block\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MenuBlockConfigForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_block_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {

    $config = $this->configFactory()->get('menu_block.settings');

    // Option to suppress core's blocks of menus.
    $form['menu_block_suppress_core'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Suppress Drupal’s standard menu blocks'),
      '#default_value' => $config->get('menu_block_suppress_core'),
      '#description' => $this->t('On the blocks admin page, hide Drupal’s standard blocks of menus.'),
      '#access' => $this->moduleHandler->moduleExists('block'),
    );

    // Retrieve core's menus.
    $menus = menu_ui_get_menus();
    // Retrieve all the menu names provided by hook_menu_block_get_sort_menus().
    $menus = array_merge($menus, $this->moduleHandler->invokeAll('menu_block_get_sort_menus'));
    asort($menus);

    // Load stored configuration.
    $menu_order = $this->config('menu_block.settings')->get('menu_block_menu_order');

    // Remove any menus no longer in the list of all menus.
    foreach (array_keys($menu_order) as $menu) {
      if (!isset($menus[$menu])) {
        unset($menu_order[$menu]);
      }
    }

    // Merge the saved configuration with any un-configured menus.
    $all_menus = $menu_order + $menus;

    $form['heading'] = array(
      '#markup' => '<p>' . $this->t('If a block is configured to use <em>"the menu selected by the page"</em>, the block will be generated from the first "available" menu that contains a link to the page.') . '</p>',
    );


    // Orderable list of menu selections.
    $form['menu_order'] = array(
      '#type' => 'table',
      '#header' => array(
        t('Menu'),
        t('Available'),
        t('Weight'),
      ),
      '#attributes' => array('id' => 'menu-block-menus'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ),
      ),
    );

    foreach (array_keys($all_menus) as $menu_name) {
      $form['menu_order'][$menu_name] = array(
        'title' => array(
          '#markup' => String::checkPlain($menus[$menu_name]),
        ),
        'available' => array(
          '#type' => 'checkbox',
          '#attributes' => array('title' => $this->t('Select from the @menu_name menu', array('@menu_name' => $menus[$menu_name]))),
          '#default_value' => isset($menu_order[$menu_name]),
        ),
        'weight' => array(
          '#type' => 'textfield',
          '#title' => t('Weight for @title', array('@title' => $menu_name)),
          '#title_display' => 'invisible',
          '#default_value' => isset($menu_order[$menu_name]) ? $menu_order[$menu_name] : 0,
          '#attributes' => array('class' => array('menu-weight')),
        ),
        '#attributes' => array('class' => array('draggable')),
      );
    }

    $form['footer_note'] = array(
      '#markup' => '<p>' . $this->t('The above list will <em>not</em> affect menu blocks that are configured to use a specific menu.') . '</p>',
    );


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->configFactory()->get('menu_block.settings');
    $menu_order = array();
    foreach ($form_state['values']['menu_order'] as $menu_name => $row) {
      if ($row['available']) {
        // Add available menu and its weight to list.
        $menu_order[$menu_name] = (int) $row['weight'];
      }
    }
    // Sort the keys by the weight stored in the value.
    asort($menu_order);
    foreach ($menu_order as $menu_name => $weight) {
      // Now that the array is sorted, the weight is redundant data.
      $menu_order[$menu_name] = '';
    }
    $config->set('menu_block_menu_order', $menu_order)
      ->set('menu_block_suppress_core', $form_state['values']['menu_block_suppress_core'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
