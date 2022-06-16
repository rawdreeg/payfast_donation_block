(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.payfast_donation_block = {
    attach(context, settings) {
      $.fn.processPayfastPaymentForm = function (data) {
        var form = '';
        $.each( data, function( key, value ) {
          if (key === 'action_url')
          value = value.split('"').join('\"')
          form += '<input type="hidden" name="'+key+'" value="'+value+'">';
        });
        $('<form action="' + data.action_url + '" method="POST">' + form + '</form>').appendTo($(document.body)).submit();
      }
    }
  }
})(jQuery, Drupal, drupalSettings);
