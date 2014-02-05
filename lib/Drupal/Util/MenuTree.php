<?php

/**
 * @file
 * Contains \Drupal\menu_block\Util\MenuTree.
 */

namespace Drupal\menu_block\Util;

/**
 * Provides utilities for working with menu trees.
 */
class MenuTree {

  /**
   * Title extracted from most recent manipulation.
   *
   * @var string
   */
  public static $title;

  /**
   * Prune a tree so that it begins at the specified level.
   *
   * This function will follow the active menu trail to the specified level.
   *
   * @param array $tree
   *   The menu tree to prune.
   * @param int $level
   *   The level of the original tree that will start the pruned tree.
   * @param array|bool $parent_item
   *   The menu item that should be used as the root of the tree.
   */
  public static function pruneTree(&$tree, $level, $parent_item = FALSE) {
    if (!empty($parent_item)) {
      // Prune the tree along the path to the menu item.
      for ($i = 1; $i <= MENU_MAX_DEPTH && $parent_item["p$i"] != '0'; $i++) {
        $plid = $parent_item["p$i"];
        $found_active_trail = FALSE;
        // Examine each element at this level for the ancestor.
        foreach (array_keys($tree) AS $key) {
          if ($tree[$key]['link']['mlid'] == $plid) {
            static::$title = $tree[$key]['link'];
            // Prune the tree to the children of this ancestor.
            $tree = $tree[$key]['below'] ? $tree[$key]['below'] : array();
            $found_active_trail = TRUE;
            break;
          }
        }
        // If we don't find the ancestor, bail out.
        if (!$found_active_trail) {
          $tree = array();
          break;
        }
      }
    }

    $is_front_page = static::frontPage();
    // Trim the upper levels down to the one desired.
    for ($i = 1; $i < $level; $i++) {
      $found_active_trail = FALSE;
      // Examine each element at this level for the active trail.
      foreach (array_keys($tree) AS $key) {
        // Also include the children of the front page.
        if ($tree[$key]['link']['in_active_trail'] ||
          ($tree[$key]['link']['link_path'] == '<front>' && $is_front_page)) {
          // Get the title for the pruned tree.
          static::$title = $tree[$key]['link'];
          // Prune the tree to the children of the item in the active trail.
          $tree = $tree[$key]['below'] ? $tree[$key]['below'] : array();
          $found_active_trail = TRUE;
          break;
        }
      }
      // If we don't find the active trail, the active item isn't in the tree we
      // want.
      if (!$found_active_trail) {
        $tree = array();
        break;
      }
    }
  }

  /**
   * Prune a tree so that it begins at the active menu item.
   *
   * @param array $tree
   *   The menu tree to prune.
   * @param string $level
   *   The level which the tree will be pruned to: 'active' or 'child'.
   */
  public static function pruneActiveTree(&$tree, $level) {
    do {
      $found_active_trail = FALSE;
      // Examine each element at this level for the active trail.
      foreach (array_keys($tree) AS $key) {
        if ($tree[$key]['link']['in_active_trail']) {
          $found_active_trail = TRUE;
          // If the active trail item has children, examine them.
          if ($tree[$key]['below']) {
            // If we are pruning to the active menu item's level, check if this
            // is the active menu item by checking its children.
            if ($level == 'active') {
              foreach (array_keys($tree[$key]['below']) AS $child_key) {
                if ($tree[$key]['below'][$child_key]['link']['in_active_trail']) {
                  // Get the title for the pruned tree.
                  static::$title = $tree[$key]['link'];
                  $tree = $tree[$key]['below'];
                  // Continue in the pruned tree.
                  break 2;
                }
              }
              // If we've found the active item, we're done.
              break 2;
            }
            // Set the title for the pruned tree.
            static::$title = $tree[$key]['link'];
            // If we are pruning to the children of the active menu item, just
            // prune the tree to the children of the item in the active trail.
            $tree = $tree[$key]['below'];
            // Continue in the pruned tree.
            break;
          }
          // If the active menu item has no children, we're done.
          else {
            if ($level == 'child') {
              $tree = array();
            }
            break 2;
          }
        }
      }
    } while ($found_active_trail);
  }

  /**
   * Prune a tree so it does not extend beyond the specified depth limit.
   *
   * @param array $tree
   *   The menu tree to prune.
   * @param int $depth_limit
   *   The maximum depth of the returned tree; must be a positive integer.
   */
  public static function depthTrim(&$tree, $depth_limit) {
    // Prevent invalid input from returning a trimmed tree.
    if ($depth_limit < 1) {
      return;
    }

    // Examine each element at this level to find any possible children.
    foreach (array_keys($tree) AS $key) {
      if ($tree[$key]['below']) {
        if ($depth_limit > 1) {
          static::depthTrim($tree[$key]['below'], $depth_limit-1);
        }
        else {
          // Remove the children items.
          $tree[$key]['below'] = FALSE;
        }
      }
      if ($depth_limit == 1 && $tree[$key]['link']['has_children']) {
        // Turn off the menu styling that shows there were children.
        $tree[$key]['link']['has_children'] = FALSE;
        $tree[$key]['link']['leaf_has_children'] = TRUE;
      }
    }
  }


  /**
   * Wraps drupal_is_front_page();
   */
  public static function frontPage() {
    return drupal_is_front_page();
  }

}
