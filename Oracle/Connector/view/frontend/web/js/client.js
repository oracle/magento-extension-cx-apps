define([
    'jquery'
], function($) {
    'use strict';

    var REQUEST_METHOD_POST = 'POST';
    var CONTENT_TYPE_JSON = 'application/json';
    var RETURN_TYPE_JSON = 'json'

    var SECONDS = 1000;
    var MINUTES = (60 * SECONDS);

    /** {{}} The request object */
    var req = {};

    /**
     * Creates and populates the request
     *
     * @param {string} instanceType The environment type
     * @param {string} serviceUrl
     * @param {string} topic The type of fiddle
     * @param {{}} payload Request body
     */
    var loadRequest = function(serviceUrl, instanceType, topic, payload) {
        req = {
            url: serviceUrl + '/public/' + instanceType + '/hook/' + topic + '?platform=m2',
            type: REQUEST_METHOD_POST,
            dataType: RETURN_TYPE_JSON,
            contentType: CONTENT_TYPE_JSON,
            data: JSON.stringify(payload)

        }
        JSON.stringify(payload)
    }

    return {
        SECONDS: SECONDS,
        MINUTES: MINUTES,

        /**
         * Change the URL protocol to match that which is used on the store
         *
         * @param {string} url
         * @returns {string}
         */
        adjustProtocol: function(url) {
            if ((typeof url !== 'string') && !(url instanceof String)) {
                return '';
            }
            url = url.replace(/^https?\:\/\//, '');

            return [window.location.protocol, url].join("//");
        },

        /**
         * Send POST request with given payload to the Connector
         *
         * @param {string} serviceUrl
         * @param {string} instanceType The environment type
         * @param {string} topic the type of fiddle
         * @param {{}} payload
         */
        post: function(serviceUrl, instanceType, topic, payload) {
            loadRequest(serviceUrl, instanceType, topic, payload);
            $.ajax(req);
        }
    }
});