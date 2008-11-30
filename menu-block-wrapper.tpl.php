<?php
// $Id$

/**
 * @file menu-block-wrapper.tpl.php
 * Default theme implementation to wrap menu blocks.
 *
 * Available variables:
 * - $content: The unordered list containing the menu.
 * - $classes: A string containing the CSS classes for the DIV tag. Includes:
 *   menu-name-NAME, parent-mlid-MLID, menu-level-LEVEL, menu-depth-DEPTH,
 *   and menu-expanded.
 * - $classes_array: An array containing each of the CSS classes.
 *
 * The following variables are provided for contextual information.
 * - $settings: An array of the block's settings. Includes menu_name,
 *   parent_mlid, level, depth, and expanded.
 *
 * @see template_preprocess_menu_block_wrapper()
 * @see theme_menu_block_wrapper()
 */
?>
<div class="<?php print $classes; ?>">
  <?php print $content; ?>
</div>
