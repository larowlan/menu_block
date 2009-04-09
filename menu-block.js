// $Id$

$(document).ready( function() {
  // Toggle display of "title link" if "block title" has a value.
  $('#edit-title').change( function() {
    if ($('#edit-title').val()) {
      $('#edit-title-link-wrapper').slideUp('fast');
    }
    else {
      $('#edit-title-link-wrapper').slideDown('fast');
    }
  } );
  if ($('#edit-title').val()) {
    $('#edit-title-link-wrapper').css('display', 'none');
  }
  // Split the un-wieldly "parent item" pull-down into two hierarchal pull-downs.
  $('#edit-parent')
    .html(Drupal.settings.menu_block.parent_options[Drupal.settings.menu_block.menus_default])
    .val(Drupal.settings.menu_block.parent_default)
    .before(Drupal.settings.menu_block.menus);
  $('#edit-parent-menu').change( function() {
    $('#edit-parent')
      .html(Drupal.settings.menu_block.parent_options[$('#edit-parent-menu').val()])
      .val(Drupal.settings.menu_block.parent_default);
  } );
  // Toggle display of "follow parent" if "follow" has been checked.
  $('#edit-follow').change( function() {
    if ($('#edit-follow:checked').length) {
      $('#edit-follow-parent-wrapper').slideDown('fast');
    }
    else {
      $('#edit-follow-parent-wrapper').slideUp('fast');
    }
  } );
  if (!$('#edit-follow:checked').length) {
    $('#edit-follow-parent-wrapper').css('display', 'none');
  }
} );
