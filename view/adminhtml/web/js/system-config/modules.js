define(['jquery', 'mage/loader'], function ($) {
    'use strict';

    var promise,
        startCallbacks = $.Callbacks('memory'),
        callbacks = $.Callbacks('memory');

    return {
        /**
         * @param {Object} config - $.ajax settings of the action to load from
         */
        getModules: function (config) {
            var settings = $.extend({
                type: 'GET',
                dataType: 'json'
            }, config);

            // the read is shared by every consumer, while a writing action
            // is meant to bring a new list every time it is asked for
            if (settings.type !== 'GET' || !promise) {
                promise = $.ajax(settings).done(function (response) {
                    callbacks.fire(response);
                });
                startCallbacks.fire(promise);
            }

            return promise;
        },

        onStart: function (callback) {
            startCallbacks.add(callback);
            return this;
        },

        onLoad: function (callback) {
            callbacks.add(callback);
            return this;
        }
    };
});
