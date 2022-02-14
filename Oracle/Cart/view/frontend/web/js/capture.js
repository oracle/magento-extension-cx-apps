define([
  'jquery',
  'mage/validation'
], function($) {
  return function(data) {
    var emailCapture = function(emailAddress) {
      $.post(data.captureUrl, { emailAddress: emailAddress });
    };

    if (document.addEventListener) {
      document.addEventListener('oracle:popup-created', function(e) {
        $('.popup-dialog input[id*=inputs-email]').each(function(index, item) {
          $(item).on('change', function() {
            emailCapture(item.value);
          });
        });
      }, false);
    }

    $('body').on('change', data.selectors, function() {
      if ($(this).valid()) {
        emailCapture(this.value);
      }
    });
  };
});
