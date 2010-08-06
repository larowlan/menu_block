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
      .before(Drupal.settings.menu_block.menus)
      .wrap(Drupal.settings.menu_block.parent_wrapper)
      .before(Drupal.settings.menu_block.parent_label);
    $('.menu-block-parent-menu', context).change( function() {
      $('.menu-block-parent')
        .html(Drupal.settings.menu_block.parent_options[$('.menu-block-parent-menu').val()])
        .val(Drupal.settings.menu_block.parent_default);
    } );

    // Show the "display options" if javascript is on.
    $('.form-item-display-options.form-type-radios>label', context).addClass('element-invisible');
    $('.form-item-display-options.form-type-radios', context).show();
    // Make the radio set into a jQuery UI buttonset.
    $('#edit-display-options', context).buttonset();

    // Override the default show/hide animation for Form API states.
    $('#menu-block-settings', context).bind('state:visible', function(e) {
      if (e.trigger) {
        e.stopPropagation() /* Stop the handler further up the tree. Note: test this as there could be problems */
        $(e.target).closest('.form-item, .form-wrapper')[e.value ? 'slideDown' : 'slideUp']('fast');
      }
    });
  }
};

})(jQuery);
