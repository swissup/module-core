define([
    'jquery'
], function ($) {
    'use strict';

    return function (options, element) {
        var $destination;

        options = $.extend({
            content: '',
            appendedClass: 'appended-items-wrapper',
            hiddenClass: 'appended-hidden',
            togglerClass: 'appended-items-toggler'
        }, options);

        /**
         * Toggler click handler
         *
         * @param  {jQuery.event} event
         */
        function togglerClick(event) {
            event.stopPropagation();
            $(event.target)
                .siblings('.' + options.appendedClass)
                .toggleClass(options.hiddenClass);
        }

        $destination = $(element);
        $('<div></div>')
            .appendTo($destination)
            .addClass(options.appendedClass)
            .addClass(options.hiddenClass)
            .append($(options.content).clone());
        $('<div></div>')
            .addClass(options.togglerClass)
            .appendTo($destination)
            .click(togglerClick);
    };
});
