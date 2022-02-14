define([
    'jquery'
], function ($) {
    'use strict';

    var ORACLE_STORAGE_KEY = 'oracle-storage';

    /** @type {{}} */
    var storage;

    /**
     * @returns {{}}
     */
    var getStorage = function () {
        if (!storage) {
            storage = JSON.parse(window.localStorage.getItem(ORACLE_STORAGE_KEY)) || {};
        }
        return storage;
    }

    /**
     * Persists the storage object into localStorage
     */
    var store = function () {
        window.localStorage.setItem(ORACLE_STORAGE_KEY, JSON.stringify(getStorage()));
    }

    return {
        /**
         * @param {string} name
         * @returns {*}
         */
        get: function (name) {
            return getStorage()[name];
        },

        /**
         * @param {string} name
         * @param {string} value
         */
        set: function (name, value) {
            getStorage()[name] = value;
            store();
        },

        /**
         * @param {string} name
         */
        remove: function (name) {
            delete getStorage()[name]
            store();
        }
    }
});