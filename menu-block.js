// $Id$

(function ($) {

Drupal.behaviors.menu_block = {
  attach: function (context, settings) {
    // This behavior attaches by ID, so is only valid once on a page.
    if ($('#menu-block-settings.menu-block-processed').size()) {
      return;
    }
    $('#menu-block-settings', context).addClass('menu-block-processed');

    // Split the un-wieldly "parent item" pull-down into two hierarchal pull-downs.
    $('.menu-block-parent', context)
      .html(Drupal.settings.menu_block.parent_options[Drupal.settings.menu_block.menus_default])
      .val(Drupal.settings.menu_block.parent_default)
      .before(Drupal.settings.menu_block.menus);
    $('.menu-block-parent-menu', context).change( function() {
      $('.menu-block-parent')
        .html(Drupal.settings.menu_block.parent_options[$('.menu-block-parent-menu').val()])
        .val(Drupal.settings.menu_block.parent_default);
    } );
  }
};

})(jQuery);
