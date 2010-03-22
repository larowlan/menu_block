// $Id$

(function ($) {

Drupal.behaviors.menu_block = {
  attach: function (context, settings) {
    // This behavior attaches by ID, so is only valid once on a page.
    if ($('#menu-block-settings.menu-block-processed').size()) {
      return;
    }
    $('#menu-block-settings', context).addClass('menu-block-processed');

    // Process the form if its in a Panel overlay.
    if ($('#override-title-checkbox', context).size()) {
      // Toggle display of "title link" if "override title" is checked.
      $('#override-title-checkbox', context).change( function() {
        if ($('#override-title-checkbox:checked').length) {
          $('#edit-title-link-wrapper').slideUp('fast');
        }
        else {
          $('#edit-title-link-wrapper').slideDown('fast');
        }
      } );
      if ($('#override-title-checkbox:checked').length) {
        $('#edit-title-link-wrapper').css('display', 'none');
      }
    }
    // Process the form if its on a block config page.
    else {
      // Toggle display of "title link" if "block title" has a value.
      $('#edit-title', context).change( function() {
        if ($('#edit-title').val()) {
          $('#edit-title-link-wrapper').slideUp('fast');
        }
        else {
          $('#edit-title-link-wrapper').slideDown('fast');
        }
      } );
      if ($('#edit-title', context).val()) {
        $('#edit-title-link-wrapper').css('display', 'none');
      }

      // Split the un-wieldly "parent item" pull-down into two hierarchal pull-downs.
      $('#edit-parent', context)
        .html(Drupal.settings.menu_block.parent_options[Drupal.settings.menu_block.menus_default])
        .val(Drupal.settings.menu_block.parent_default)
        .before(Drupal.settings.menu_block.menus);
      $('#edit-parent-menu', context).change( function() {
        $('#edit-parent')
          .html(Drupal.settings.menu_block.parent_options[$('#edit-parent-menu').val()])
          .val(Drupal.settings.menu_block.parent_default);
      } );
    }

    // Toggle display of "follow parent" if "follow" has been checked.
    $('#edit-follow', context).change( function() {
      if ($('#edit-follow:checked').length) {
        $('#edit-follow-parent-wrapper').slideDown('fast');
      }
      else {
        $('#edit-follow-parent-wrapper').slideUp('fast');
      }
    } );
    if (!$('#edit-follow:checked', context).length) {
      $('#edit-follow-parent-wrapper', context).css('display', 'none');
    }
  }
};

})(jQuery);
