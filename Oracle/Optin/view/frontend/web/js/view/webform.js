define([
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data'
], function(Component, ko, customerData) {
        'use strict';

        return Component.extend({
            initialize: function() {
                this._super();
                this.webform = customerData.get('webform');
            }
        })
    }
);