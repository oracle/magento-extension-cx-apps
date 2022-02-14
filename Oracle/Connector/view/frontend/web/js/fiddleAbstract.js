define([
    'jquery',
    'connectorClient'
], function($, client) {

    /**
     * @type {{}} appData
     */
    var appData;

    /**
     * @type {{}} invalidatedAppData
     */
    var invalidatedAppData;

    /**
     * @type {string} serviceUrl
     */
    var serviceUrl;

    /**
     * @type {string} instanceType
     */
    var instanceType;

    /**
     * @constructor
     * @returns {FiddleAbstract}
     */
    function FiddleAbstract() {
        return (this);
    }

    /**
     * @abstract
     */
    FiddleAbstract.prototype = {

        /**
         * Sets the appData values from localstorage
         * @returns {boolean} True if app data was successfully initialized
         */
        initAppData: function() {
            appData = window.localStorage.getItem('mage-cache-storage')
            if (typeof appData !== 'string' || appData.length == 0) {
                return false;
            }
            appData = JSON.parse(appData);
            
            if (Object.keys(appData).length == 0) {
            	return false;
            }

            invalidatedAppData = window.localStorage.getItem('mage-cache-storage-section-invalidation')
            if (typeof invalidatedAppData !== 'string' || invalidatedAppData.length == 0) {
                return false;
            }
            invalidatedAppData = JSON.parse(invalidatedAppData);

            return true;
        },

        /**
         * Gets the cached value of the given model and key
         *
         * @param {string} model
         * @param {string} key
         * @param {boolean} [require]
         * @returns {*}
         * @throws Error When required data doesn't exist in the cache
         */
        extract: function(model, key, require) {
            var value = appData[model][key];
            if (require && !value) {
                throw new Error('Extract value for key ' + key + ' is NULL or does not exist');
            }
            return value;
        },
        
        /**
         * @returns {string}
         */
        getServiceUrl: function() {
            if (typeof serviceUrl === 'undefined') {
                serviceUrl = this.extract('connector', 'serviceUrl', true);
            }

            return serviceUrl;
        },

        /**
         * @returns {string}
         */
        getInstanceType: function() {
            if (typeof instanceType === 'undefined') {
                instanceType = this.extract('connector', 'instanceType', true);
            }

            return instanceType;
        },

        /**
         * We can fiddle if the topic has something in it (aside from id)
         * 
         * @param {string} topic
         * @returns {boolean}
         */
        canFiddle: function(topic) {
            var module = appData[topic];
            return typeof module !== 'undefined' && Object.keys(module).length > 1
        },

        /**
         * Checks if the cache for the topic has been invalidated
         *
         * @param {string} topic The type of fiddle event
         * @returns {boolean}
         */
        cacheMissed: function(topic) {
            return invalidatedAppData[topic] === true;
        },

        /**
         * Posts the data to the Connector
         *
         * Resets the timer for the next fiddle
         *
         * @param {string} topic The type of fiddle
         * @param {{}} fiddleData The post body
         */
        fiddle: function(topic, fiddleData) {
            var serviceUrl = this.getServiceUrl();
            var instanceType = this.getInstanceType();
            if (serviceUrl && instanceType && !$.isEmptyObject(fiddleData)) {
               // client.post(this.getServiceUrl(), this.getInstanceType(), topic, fiddleData);
            }
        }
    };

    return (FiddleAbstract);
});
