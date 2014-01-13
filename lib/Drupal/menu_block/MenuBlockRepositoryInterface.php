<?php

/**
 * @file
 * Contains \Drupal\menu_block\MenuBlockRepository.
 */

namespace Drupal\menu_block;

/**
 * Provides an interface for the menu block repository service.
 */
interface MenuBlockRepositoryInterface {

  /**
   * A constant for indicating 'the active menu on the page'
   */
  const CURRENT_PAGE_MENU = '_active';

  /**
   * Returns an array of menus for use as menu blocks.
   *
   * @return \Drupal\system\Entity\Menu[]
   *   Array of menus.
   */
  public function getMenus();

  /**
   * Gets the data structure representing a menu tree for the given configuration.
   *
   * @param array $config
   *   An array of configuration options that specifies how to build the
   *   menu tree and its title.
   *
   * @return array
   *   An array of menu links.
   *
   * @see \Drupal\menu_block\MenuBlockBuilder::build().
   */
  public function blockData(&$config);

  /**
   * Returns the current page's menu.
   *
   * @return string
   *   Current page's menu name.
   */
  public function getCurrentPageMenu();

  /**
   * Adds the active trail indicators into the tree.
   *
   * The data returned by menu_tree_page_data() has link['in_active_trail'] set
   * to TRUE for each menu item in the active trail. The data returned from
   * $this->menuTreeAllData() does not contain the active trail indicators. This is
   * a helper function that adds it back in.
   *
   * @param array $tree
   *   The menu tree.
   */
  public function addActivePath(&$tree);

  /**
   * Trims everything but the active trail in the tree.
   *
   * @param array $tree
   *   The menu tree to trim.
   */
  public function trimActivePath(&$tree);

  /**
   * Sorts the active trail to the top of the tree.
   *
   * @param array $tree
   *   The menu tree to sort.
   */
  public function sortActivePath(&$tree);

}
