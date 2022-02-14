define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data',
    'fiddleAbstract',
    'connectorClient',
], function($, Component, ko, customerData, FiddleAbstract, client) {

    var TOPIC = 'browse-fiddle';
    var EVENT_TYPE_VIEW = 'VIEW';
    var EVENT_TYPE_SEARCH = 'SEARCH';

    var CACHE_MISS_RETRY_PERIOD = 10 // in seconds

    /** @type {number} retryInterval*/
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
     * @returns {string}
     */
    var getCurrentUrl = function() {
        return window.location.href;
    }

    /**
     * Gets the browse type
     *
     * @returns {string}
     */
    var getEventType = function() {
        type = $("[data-role=browse-fiddle] > #browse-type").text();
        return (type === EVENT_TYPE_SEARCH) ? EVENT_TYPE_SEARCH : EVENT_TYPE_VIEW;
    }
    
    /**
     * Gets the current product Id
     * 
     * @returns {string}
     */
    var getProductId = function() {
    	return $("[data-role=browse-fiddle] > #oracle-product-id").text();
    }

    /**
     * Builds the necessary post data needed to fiddle
     *
     * @returns {{}}
     */
    var getFiddleData = function() {
        var postData;

        try {
            postData = {
                eik: Fiddle.prototype.extract('connector', 'eik', true),
                customerId: Fiddle.prototype.extract('browse-fiddle', 'customerId', true),
                emailAddress: Fiddle.prototype.extract('browse-fiddle', 'emailAddress'),
                eventType: getEventType(),
                eventDate: new Date().toISOString(),
                url: getCurrentUrl(),
                browseSiteId: Fiddle.prototype.extract('browse-fiddle', 'browseSiteId', true),
                productId: getProductId()
            }
        } catch (err) {
            postData = {};
        } finally {
            return postData;
        }
    }

    /**
     * Performs fiddle
     */
    var fiddle = function() {
        if (!Fiddle.prototype.initAppData() || Fiddle.prototype.cacheMissed(TOPIC)) {
            setTimeout(fiddle, retryInterval);
        } else if (Fiddle.prototype.canFiddle(TOPIC)) {
            Fiddle.prototype.fiddle(TOPIC, getFiddleData());
        }
    }

    /**
     * Knockout binding to listen for section data updates
     * 
     * @type {{init: ko.bindingHandlers.browseFiddleBinding.init}}
     */
    ko.bindingHandlers.browseFiddleBinding = {
        init: function(element, valueAccessor, allBindings, viewModel, bindingContext) {
            fiddle();
        }
    }

    return Component.extend({
        initialize: function() {
            this._super();
            this.browseFiddle = customerData.get('browse-fiddle');
        }
    })
});
