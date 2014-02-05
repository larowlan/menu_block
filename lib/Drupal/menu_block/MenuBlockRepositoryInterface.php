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
   * Loads a menu link entity.
   *
   * @param int $mlid
   *   The menu link ID.
   *
   * @return \Drupal\menu_link\Entity\MenuLink|null
   *   A menu link entity, or NULL if there is no entity with the given ID.
   */
  public function loadLink($mlid);

}
