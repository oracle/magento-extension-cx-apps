define([
  'jquery'
], function($) {
  return function(data) {
    $.post(data.browseUrl, data.browseEvents);
  };
});
