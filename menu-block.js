// $Id$

(function ($) {

Drupal.behaviors.menu_block = {
  attach: function (context, settings) {
    // This behavior attaches by ID, so is only valid once on a page.
    if ($('#menu-block-settings.menu-block-processed').size()) {
      return;
    }
    $('#menu-block-settings', context).addClass('menu-block-processed');

    // Process the form if its on a block config page (not in a Panel overlay.)
    if ($('.menu-block-configure-form', context).size()) {
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

    // Toggle display of "follow parent" if "follow" has been checked.
    $('.menu-block-follow', context).change( function() {
      if ($('.menu-block-follow:checked').length) {
        $('.menu-block-follow-parent').slideDown('fast');
      }
      else {
        $('.menu-block-follow-parent').slideUp('fast');
      }
    } );
    if (!$('.menu-block-follow:checked', context).length) {
      $('.menu-block-follow-parent', context).css('display', 'none');
    }
  }
};

})(jQuery);
