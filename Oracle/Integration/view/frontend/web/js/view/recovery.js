define([
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data'
], function(Component, ko, customerData) {
        'use strict';

        ko.bindingHandlers.afterRender = {
            init: function(element, valueAccessor, allBindings, viewModel, bindingContext) {
                addEmbeddedScript();
            }
        };

        return Component.extend({
            initialize: function() {
                this._super();
                this.recovery = customerData.get('recovery');
            }
        })
    }
);