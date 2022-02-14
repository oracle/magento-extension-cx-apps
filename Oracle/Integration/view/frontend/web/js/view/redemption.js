define([
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data'
], function(Component, ko, customerData) {
        'use strict';

        ko.bindingHandlers.bcmBinding = {
            init: function(element, valueAccessor, allBindings, viewModel, bindingContext) {
                var data = bindingContext.$rawData;
                if (data.redemption().coupon != undefined) {
                    __bcm.redeemCoupon(
                        data.redemption().siteId,
                        {
                            email: JSON.stringify(data.redemption().email),
                            coupon: JSON.stringify(data.redemption().coupon),
                            orderId: JSON.stringify(data.redemption().orderId),
                            orderSubtotal: JSON.stringify(data.redemption().orderSubtotal),
                            orderDiscount: JSON.stringify(data.redemption().orderDiscount)
                        }
                    );
                } else {
                    console.warn('Oracle Redemption data could not be found');
                }
            }
        }

        return Component.extend({
            initialize: function() {
                this._super();
                this.redemption = customerData.get('redemption');
            }
        })
    }
);