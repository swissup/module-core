define(['jquery', 'mage/loader'], function ($) {
    'use strict';

    var promise,
        callbacks = $.Callbacks('memory');

    return {
        getModules: function (url, refresh) {
            if (refresh || !promise) {
                promise = $.ajax({
                    url: url,
                    dataType: 'json',
                    showLoader: !!refresh,
                    data: {
                        form_key: window.FORM_KEY,
                        refresh: refresh ? 1 : 0
                    }
                }).done(function (items) {
                    callbacks.fire(items);
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
