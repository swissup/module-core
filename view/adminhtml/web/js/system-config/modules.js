define(['jquery', 'mage/loader'], function ($) {
    'use strict';

    var promise,
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
            }

            return promise;
        },

        onLoad: function (callback) {
            callbacks.add(callback);
            return this;
        }
    };
});
