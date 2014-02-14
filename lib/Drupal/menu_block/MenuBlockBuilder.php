<?php

/**
 * @file
 * Contains \Drupal\menu_block\MenuBlockBuilder.
 */

namespace Drupal\menu_block;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\menu_block\Util\MenuTree;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a service for building menu block output.
 */
class MenuBlockBuilder implements MenuBlockBuilderInterface {

  /**
   * Title of the menu block.
   *
   * @var string
   */
  protected $title;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * Menu block configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The menu block repository.
   *
   * @var \Drupal\menu_block\MenuBlockRepositoryInterface
   */
  protected $repository;

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
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\menu_block\MenuBlockRepositoryInterface $repository
   *   The menu block repository.
   */
  public function __construct(Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory, Request $request, MenuBlockRepositoryInterface $repository) {
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->cache = $cache;
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('menu_block.settings');
    $this->request = $request;
    $this->repository = $repository;
  }

  /**
   * {@inheritdoc}
   */
  public function blockData(&$config) {
    // Determine the max depth based on level and depth setting.
    $max_depth = ($config['depth'] == 0) ? NULL : min($config['level'] + $config['depth'] - 1, MENU_MAX_DEPTH);

    if ($config['expanded'] || $config['parent_mlid']) {
      // Get the full, un-pruned tree.
      if ($config['parent_mlid']) {
        $tree = $this->menuTreeAllData($config['menu_name']);
      }
      else {
        $tree = $this->menuTreeAllData($config['menu_name'], NULL, $max_depth);
      }
      // And add the active trail data back to the full tree.
      $this->addActivePath($tree);
    }
    else {
      // Get the tree pruned for just the active trail.
      $tree = $this->menuTreePageData($config['menu_name'], $max_depth);
    }

    // Allow alteration of the tree and config before we begin operations on it.
    $this->moduleHandler->alter('menu_block_tree', $tree, $config);

    // Prune the tree along the active trail to the specified level.
    if ($config['level'] > 1 || $config['parent_mlid']) {
      if ($config['parent_mlid']) {
        $parent_item = $this->repository->loadLink($config['parent_mlid']);
        MenuTree::pruneTree($tree, $config['level'], $parent_item);
      }
      else {
        MenuTree::pruneTree($tree, $config['level']);
      }
    }

    // Prune the tree to the active menu item.
    if ($config['follow']) {
      MenuTree::pruneActiveTree($tree, $config['follow']);
    }

    // If the menu-item-based tree is not "expanded", trim the tree to the active path.
    if ($config['parent_mlid'] && !$config['expanded']) {
      $this->trimActivePath($tree);
    }

    // Trim the branches that extend beyond the specified depth.
    if ($config['depth'] > 0) {
      MenuTree::depthTrim($tree, $config['depth']);
    }

    // Sort the active path to the top of the tree.
    if ($config['sort']) {
      $this->sortActivePath($tree);
    }

    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentPageMenu() {
    // Retrieve the list of available menus.
    $menu_order = $this->config->get('menu_block_menu_order');

    // Check for regular expressions as menu keys.
    $patterns = array();
    foreach (array_keys($menu_order) as $pattern) {
      if ($pattern[0] == '/') {
        $patterns[$pattern] = NULL;
      }
    }

    // Extract the "current" path from the request, or from the active menu
    // trail if applicable.
    $link_path = $this->request->query->has('q') ? $this->request->query->get('q') : '<front>';
    $trail = $this->menuGetActiveTrail();
    $last_item = end($trail);
    if (!empty($last_item['link_path'])) {
      $link_path = $last_item['link_path'];
    }

    // Retrieve all the menus containing a link to the current page.
    // @todo use the repository and don't inject the database. (add to interface).
    $result = $this->database->query("SELECT menu_name FROM {menu_links} WHERE link_path = :link_path", array(':link_path' => $link_path));
    foreach ($result as $item) {
      // Check if the menu is in the list of available menus.
      if (isset($menu_order[$item->menu_name])) {
        // Mark the menu.
        $menu_order[$item->menu_name] = MenuBlockRepositoryInterface::CURRENT_PAGE_MENU;
      }
      else {
        // Check if the menu matches one of the available patterns.
        foreach (array_keys($patterns) as $pattern) {
          if (preg_match($pattern, $item->menu_name)) {
            // Mark the menu.
            $menu_order[$pattern] = MenuBlockRepositoryInterface::CURRENT_PAGE_MENU;
            // Store the actual menu name.
            $patterns[$pattern] = $item->menu_name;
          }
        }
      }
    }
    // Find the first marked menu.
    $menu_name = array_search(MenuBlockRepositoryInterface::CURRENT_PAGE_MENU, $menu_order);
    // If a pattern was matched, use the actual menu name instead of the pattern.
    if (!empty($patterns[$menu_name])) {
      $menu_name = $patterns[$menu_name];
    }

    return $menu_name;
  }

  /**
   * {@inheritdoc}
   */
  public function addActivePath(&$tree) {
    // Grab any menu item to find the menu_name for this tree.
    $menu_item = current($tree);
    $tree_with_trail = $this->menuTreePageData($menu_item['link']['menu_name']);

    // To traverse the original tree down the active trail, we use a pointer.
    $subtree_pointer =& $tree;

    // Find each key in the active trail.
    while ($tree_with_trail) {
      foreach ($tree_with_trail AS $key => &$value) {
        if ($tree_with_trail[$key]['link']['in_active_trail'] && isset($subtree_pointer[$key])) {
          // Set the active trail info in the original tree.
          $subtree_pointer[$key]['link']['in_active_trail'] = TRUE;
          // Continue in the subtree, if it exists.
          $tree_with_trail =& $tree_with_trail[$key]['below'];
          $subtree_pointer =& $subtree_pointer[$key]['below'];
          break;
        }
        else {
          unset($tree_with_trail[$key]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trimActivePath(&$tree) {
    foreach ($tree AS $key => &$value) {
      if (($tree[$key]['link']['in_active_trail'] || $tree[$key]['link']['expanded']) && $tree[$key]['below']) {
        // Continue in the subtree, if it exists.
        $this->trimActivePath($tree[$key]['below']);
      }
      else {
        // Trim anything not expanded or along the active trail.
        $tree[$key]['below'] = FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sortActivePath(&$tree) {
    // To traverse the original tree down the active trail, we use a pointer.
    $current_level =& $tree;

    // Traverse the tree along the active trail.
    do {
      $next_level = $sort = $first_key = FALSE;
      foreach ($current_level AS $key => &$value) {
        // Save the first key for later use.
        if (!$first_key) {
          $first_key = $key;
        }
        if ($current_level[$key]['link']['in_active_trail'] && $current_level[$key]['below']) {
          // Don't re-sort if its already sorted.
          if ($key != $first_key) {
            // Create a new key that will come before the first key.
            list($first_key, ) = explode(' ', $first_key);
            $first_key--;
            list(, $new_key) = explode(' ', $key, 2);
            $new_key = "$first_key $new_key";
            // Move the item to the new key.
            $current_level[$new_key] = $current_level[$key];
            unset($current_level[$key]);
            $key = $new_key;
            $sort = TRUE; // Flag sorting.
          }
          $next_level = $key; // Flag subtree.
          break;
        }
      }
      // Sort this level.
      if ($sort) {
        ksort($current_level);
      }
      // Continue in the subtree, if it exists.
      if ($next_level) {
        $current_level =& $current_level[$next_level]['below'];
      }
    } while ($next_level);
  }

  /**
   * Wraps menu_tree_all_data().
   */
  protected function menuTreeAllData($menu_name, $link = NULL, $max_depth = NULL) {
    return menu_tree_all_data($menu_name, $link, $max_depth);
  }

  /**
   * Wraps menu_tree_page_data().
   */
  protected function menuTreePageData($menu_name, $max_depth = NULL, $only_active_trail = FALSE) {
    return menu_tree_page_data($menu_name, $max_depth, $only_active_trail);
  }

  /**
   * Wraps menu_get_active_trail().
   */
  protected function menuGetActiveTrail() {
    return menu_get_active_trail();
  }

  /**
   * {@inheritdoc}
   */
  public function resetTitle() {
    unset($this->title);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title;
  }

}
