define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data',
    'fiddleAbstract',
    'connectorClient'
], function($, Component, ko, customerData, FiddleAbstract, client) {

    var TOPIC = 'cart-fiddle';
    var FIDDLE_PERIOD = 3; // in minutes
    var CACHE_MISS_RETRY_PERIOD = 10 // in seconds

    /** @type {number} hearbeatInterval */
    var heartbeatInterval = FIDDLE_PERIOD * client.MINUTES;

    /** @type {number} retryInterval */
    var retryInterval = CACHE_MISS_RETRY_PERIOD * client.SECONDS;

    /**
     * @constructor
     * @returns {Fiddle}
     */
    function Fiddle() {
        FiddleAbstract.call(this);

        return (this);
    }

    /** @extends FiddleAbstract **/
    Fiddle.prototype = Object.create(FiddleAbstract.prototype);

    /**
     * Builds the necessary post data needed to fiddle
     *
     * @returns {{}}
     */
    var getFiddleData = function() {
        var postData = {};

        try {
            postData = {
                eik: Fiddle.prototype.extract('connector', 'eik', true),
                customerCartId: Fiddle.prototype.extract('cart-fiddle', 'customerCartId', true),
                url: Fiddle.prototype.extract('cart-fiddle', 'url')
            }
        } catch (err) {
            postData = {};
        } finally {
            return postData
        }
    }

    /**
     * Performs fiddle and sets timer to repeat
     */
    var fiddleAndRepeat = function() {
        // if (!Fiddle.prototype.initAppData() || Fiddle.prototype.cacheMissed(TOPIC)) {
        //     setTimeout(fiddleAndRepeat, retryInterval);
        // } else if (Fiddle.prototype.canFiddle(TOPIC)) {
        //     Fiddle.prototype.fiddle(TOPIC, getFiddleData());
        //     setTimeout(fiddleAndRepeat, heartbeatInterval);
        // }
    }

    /**
     * Knockout binding to listen for section data updates
     *
     * @type {{init: ko.bindingHandlers.cartFiddleBinding.init}}
     */
    ko.bindingHandlers.cartFiddleBinding = {
        init: function(element, valueAccessor, allBindings, viewModel, bindingContext) {
            fiddleAndRepeat();
        }
    }

    return Component.extend({
        initialize: function() {
            this._super();
            this.cartFiddle = customerData.get('cart-fiddle');
        }
    })
});
