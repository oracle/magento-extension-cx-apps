define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data',
    'oracleStorage'
], function ($, Component, ko, customerData, oracleStorage) {

    'use strict';

    var TID_COOKIE_KEY_PREFIX = 'tid_';
    var TID_PARAM_KEY = '_bta_tid';
    var TID_STORAGE_KEY = 'tid';

    var EXPIRATION_OFFSET = 20*24*60*60*1000; // 20 days

    /**
     * Set the TID cookie from localStorage
     * Instantiate __bta object
     *
     * @type {{init: ko.bindingHandlers.btaBinding.init}}
     */
    ko.bindingHandlers.btaBinding = {
        init: function (element, valueAccessor, allBindings, viewModel, bindingContext) {
            var data = bindingContext.$rawData;
            var siteId = data.bta().siteId;
            var tid = oracleStorage.get(TID_STORAGE_KEY)

            var cookieExpiration = new Date();
            cookieExpiration.setTime(new Date().getTime() + EXPIRATION_OFFSET);

            if (siteId && typeof siteId != 'undefined' && tid) {
                document.cookie = TID_COOKIE_KEY_PREFIX + siteId + '=' + tid + ';expires=' + cookieExpiration.toUTCString() + ';path=/';
                oracleStorage.remove(TID_STORAGE_KEY);
            }
        }
    }

    /**
     * Gets the value of the given URL query parameter name
     *
     * @param {string} name
     * @returns {string}
     */
    var getUrlParam = function (name) {
        var paramStrings = decodeURIComponent(window.location.search.substring(1)).split('&');
        var paramValue = '';
        $.each(paramStrings, function (index, paramString) {
            var paramArray = paramString.split('=');
            if (name == paramArray[0]) {
                paramValue = paramArray[1];
                return false;
            }
        });
        return paramValue;
    }

    /**
     * Stores the tid value in localStorage
     */
    var storeTidValue = function () {
        var tid = getUrlParam(TID_PARAM_KEY);
        if (tid) {
            oracleStorage.set(TID_STORAGE_KEY, tid);
        }
    }

    return Component.extend({
        initialize: function () {
            this._super();
            this.bta = customerData.get('bta');
            storeTidValue();
        }
    })
});