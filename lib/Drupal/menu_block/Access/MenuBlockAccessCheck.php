<?php

/**
 * @file
 * Contains Drupal\menu_block\Access\MenuBlockAccessCheck.
 */

namespace Drupal\menu_block\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determine whether the user has permission to use menu_block module.
 */
class MenuBlockAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    return $account->hasPermission('administer blocks') && $account->hasPermission('administer menu') ? static::ALLOW : static::DENY;
  }

}
