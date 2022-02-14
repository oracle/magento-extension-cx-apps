define([
  'jquery'
], function($) {
  return function(data) {
    $.post(data.fiddleUrl, {
      currentUrl: location.href
    });
  };
});
