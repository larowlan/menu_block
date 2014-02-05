<?php

/**
 * @file
 * Contains \Drupal\menu_block\MenuBlockViewBuilder.
 */

namespace Drupal\menu_block;

use Drupal\Component\Utility\String;
use Drupal\menu_block\Util\MenuTree;
use Symfony\Component\HttpFoundation\Request;

class MenuBlockViewBuilder {

  /**
   * The menu block builder service.
   *
   * @var \Drupal\menu_block\MenuBlockBuilderInterface
   */
  protected $builder;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The menu block repository service.
   *
   * @var \Drupal\menu_block\MenuBlockRepositoryInterface
   */
  protected $repository;

  /**
   * Constructs a MenuBlockViewBuilder object.
   *
   * @param \Drupal\menu_block\MenuBlockBuilderInterface $builder
   *   The menu block builder service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\menu_block\MenuBlockRepositoryInterface $repository
   *   The menu block repository.
   */
  public function __construct(MenuBlockBuilderInterface $builder, Request $request, MenuBlockRepositoryInterface $repository) {
    $this->builder = $builder;
    $this->request = $request;
    $this->repository = $repository;
  }

  /**
   * Retrieves the menu item to use for the tree's title.
   *
   * @param bool $render_title_as_link
   *   A boolean that says whether to render the title as a link or a simple
   *   string.
   * @return array
   *   A renderable array containing the tree's title.
   */
  public function getTitle($render_title_as_link = TRUE) {
    $menu_item = MenuTree::$title;

    // The tree's title is a menu title, a normal string.
    if (is_string($menu_item)) {
      $title = array('#markup' => String::checkPlain($menu_item));
    }
    // The tree's title is a menu item with a link.
    elseif ($render_title_as_link) {
      if (!empty($menu_item['in_active_trail'])) {
        if (!empty($menu_item['localized_options']['attributes']['class'])) {
          $menu_item['localized_options']['attributes']['class'][] = 'active-trail';
        }
        else {
          $menu_item['localized_options']['attributes']['class'][] = 'active-trail';
        }
      }
      $title = array(
        '#type' => 'link',
        '#title' => $menu_item['title'],
        '#href' => $menu_item['href'],
        '#options' => $menu_item['localized_options'],
      );
    }
    // The tree's title is a menu item.
    else {
      $title = array('#markup' => String::checkPlain($menu_item['title']));
    }
    return $title;
  }

  /**
   * Returns a renderable menu tree.
   *
   * This is a copy of menu_tree_output() with additional classes added to the
   * output.
   *
   * @param array $tree
   *   A data structure representing the tree as returned from menu_tree_data.
   * @param array $config
   *   Block config
   *
   * @return string
   *   The rendered HTML of that data structure.
   */
  public function treeOutput(&$tree, $config = array()) {
    $build = array();
    $items = array();

    // Create context if no config was provided.
    if (empty($config)) {
      // Grab any menu item to find the menu_name for this tree.
      $menu_item = current($tree);
      $config['menu_name'] = $menu_item['link']['menu_name'];
    }
    $hook_menu_name = str_replace('-', '_', $config['menu_name']);

    // Pull out just the menu links we are going to render so that we
    // get an accurate count for the first/last classes.
    foreach ($tree as $key => &$value) {
      if ($tree[$key]['link']['access'] && !$tree[$key]['link']['hidden']) {
        $items[] = $tree[$key];
      }
    }

    // @todo refactor use of $router_item to use attributes.
    $router_item = $this->request->attributes->all();
    $num_items = count($items);
    foreach ($items as $i => &$data) {
      $class = array();
      if ($i == 0) {
        $class[] = 'first';
      }
      if ($i == $num_items - 1) {
        $class[] = 'last';
      }
      // Set a class for the <li>-tag. Since $data['below'] may contain local
      // tasks, only set 'expanded' class if the link also has children within
      // the current menu.
      if ($data['link']['has_children'] && $data['below']) {
        $class[] = 'expanded';
      }
      elseif ($data['link']['has_children']) {
        $class[] = 'collapsed';
      }
      else {
        $class[] = 'leaf';
      }
      if (!empty($data['link']['leaf_has_children'])) {
        $class[] = 'has-children';
      }
      // Set a class if the link is in the active trail.
      if ($data['link']['in_active_trail']) {
        $class[] = 'active-trail';
        $data['link']['localized_options']['attributes']['class'][] = 'active-trail';
      }
      if ($data['link']['href'] == $this->request->query->get('q') || ($data['link']['href'] == '<front>' && MenuTree::frontPage())) {
        $class[] = 'active';
      }
      // Set a menu link ID class.
      $class[] = 'menu-mlid-' . $data['link']['mlid'];
      // Normally, l() compares the href of every link with $_GET['q'] and sets
      // the active class accordingly. But local tasks do not appear in menu
      // trees, so if the current path is a local task, and this link is its
      // tab root, then we have to set the class manually.
      if ($data['link']['href'] == $router_item['tab_root_href'] && $data['link']['href'] != $this->request->query->get('q')) {
        $data['link']['localized_options']['attributes']['class'][] = 'active';
      }

      // Allow menu-specific theme overrides.
      $element['#theme'] = array(
        'menu_link__menu_block__' . $hook_menu_name,
        'menu_link__menu_block',
        'menu_link__' . $hook_menu_name,
        'menu_link',
      );
      $element['#attributes']['class'] = $class;
      $element['#title'] = $data['link']['title'];
      $element['#href'] = $data['link']['href'];
      $element['#localized_options'] = !empty($data['link']['localized_options']) ? $data['link']['localized_options'] : array();
      $element['#below'] = $data['below'] ? $this->treeOutput($data['below'], $config) : $data['below'];
      $element['#original_link'] = $data['link'];
      $element['#bid'] = array('module' => 'menu_block');
      // Index using the link's unique mlid.
      $build[$data['link']['mlid']] = $element;
    }
    if ($build) {
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme_wrappers'][] = array(
        'menu_tree__menu_block__' . $hook_menu_name,
        'menu_tree__menu_block',
        'menu_tree__' . $hook_menu_name,
        'menu_tree',
      );
    }

    return $build;
  }

  /**
   * Build a menu tree based on the provided configuration.
   *
   * @param $config
   *   array An array of configuration options that specifies how to build the
   *   menu tree and its title.
   *   - delta: (string) The menu_block's block delta.
   *   - menu_name: (string) The machine name of the requested menu. Can also be
   *     set to MENU_TREE__CURRENT_PAGE_MENU to use the menu selected by the page.
   *   - parent_mlid: (int) The mlid of the item that should root the tree. Use 0
   *     to use the menu's root.
   *   - title_link: (boolean) Specifies if the title should be rendered as a link
   *     or a simple string.
   *   - admin_title: (string) An optional title to uniquely identify the block on
   *     the administer blocks page.
   *   - level: (int) The starting level of the tree.
   *   - follow: (string) Specifies if the starting level should follow the
   *     active menu item. Should be set to 0, 'active' or 'child'.
   *   - depth: (int) The maximum depth the tree should contain, relative to the
   *     starting level.
   *   - expanded: (boolean) Specifies if the entire tree be expanded or not.
   *   - sort: (boolean) Specifies if the tree should be sorted with the active
   *     trail at the top of the tree.
   * @return array
   *   An associative array containing several pieces of data.
   *   - content: The tree as a renderable array.
   *   - subject: The title rendered as HTML.
   *   - subject_array: The title as a renderable array.
   */
  public function build(&$config) {
    // Retrieve the active menu item from the database.
    if ($config['menu_name'] == MenuBlockRepositoryInterface::CURRENT_PAGE_MENU) {
      $config['menu_name'] = $this->builder->getCurrentPageMenu();
      $config['parent_mlid'] = 0;

      // If no menu link was found, don't display the block.
      if (empty($config['menu_name'])) {
        return array(
          'subject_array' => array(),
          'content' => array(),
        );
      }
    }

    // Get the default block name.
    $menu_names = $this->repository->getMenus();
    MenuTree::$title = $menu_names[$config['menu_name']];

    // Get the raw menu tree data.
    $tree = $this->builder->blockData($config);
    $title = $this->getTitle($config['title_link'], $config);

    // Create a renderable tree.
    $data = array();
    $data['subject'] = $title;
    $data['content']['#content'] = $this->treeOutput($tree, $config);
    if (!empty($data['content']['#content'])) {
      $data['content']['#theme'] = array(
        'menu_block_wrapper__' . str_replace('-', '_', $config['menu_name']),
        'menu_block_wrapper'
      );
      $data['content']['#config'] = $config;
    }
    else {
      $data['content'] = '';
    }

    return $data;
  }
}
