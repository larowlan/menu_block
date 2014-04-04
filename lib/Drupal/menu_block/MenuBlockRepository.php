<?php

/**
 * @file
 * Contains \Drupal\menu_block\MenuBlockRepository.
 */

namespace Drupal\menu_block;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a repository service for loading menu blocks.
 */
class MenuBlockRepository implements MenuBlockRepositoryInterface {

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The menu link storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $linkStorage;

  /**
   * The menu storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $menuStorage;

  /**
   * The cache.menu bin
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Array of menus
   *
   * @var array
   */
  protected $menus;

  /**
   * Constructs the menu block repository service.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The current database connection.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache bin for menus.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->linkStorage = $entity_manager->getStorage('menu_link');
    $this->menuStorage = $entity_manager->getStorage('menu');
    $this->cache = $cache;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenus() {
    if (!$this->menus) {
      if ($cached = $this->cache->get('menu_block_menus')) {
        $this->menus = $cached->data;
      }
      else {
        // Retrieve core's menus.
        $this->menus = $this->coreMenus();
        // Retrieve all the menu names provided by hook_menu_block_get_menus().
        $this->menus = array_merge($this->menus, $this->moduleHandler->invokeAll('menu_block_get_menus'));
        // Add an option to use the menu for the active menu item.
        $this->menus[MenuBlockRepositoryInterface::CURRENT_PAGE_MENU] = '<' . t('the menu selected by the page') . '>';
        asort($this->menus);
        $this->cache->set('menu_block_menus', $this->menus);
      }
    }
    return $this->menus;
  }

  /**
   * Fetch core menus.
   */
  protected function coreMenus() {
    if ($custom_menus = $this->menuStorage->loadMultiple()) {
      foreach ($custom_menus as $menu_name => $menu) {
        $custom_menus[$menu_name] = $menu->label();
      }
      asort($custom_menus);
    }
    return $custom_menus ?: array();
  }

  /**
   * {@inheritdoc}
   */
  public function loadLink($mlid) {
    $this->linkStorage->load($mlid);
  }

}
