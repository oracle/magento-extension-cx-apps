define([
  'jquery',
  'Magento_Ui/js/form/element/boolean'
], function($, Checkbox) {
    'use strict';

    return Checkbox.extend({
        getInitialValue: function() {
            this.onUpdate();
            return this._super();
        },
        onUpdate: function() {
            $.post('/optin/checkout/', { subscribed: this.value() ? '1' : '0' });
            return this._super();
        }
    });
});
