// $Id$

$(document).ready( function() {
  $('#edit-parent')
    .html(Drupal.settings.menu_block.parent_options[Drupal.settings.menu_block.menus_default])
    .val(Drupal.settings.menu_block.parent_default)
    .before(Drupal.settings.menu_block.menus);
  $('#edit-parent-menu').change( function() {
    $('#edit-parent')
      .html(Drupal.settings.menu_block.parent_options[$('#edit-parent-menu').val()])
      .val(Drupal.settings.menu_block.parent_default);
  } );
} );
