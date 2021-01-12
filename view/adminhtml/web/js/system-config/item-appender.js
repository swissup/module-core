define([
    'jquery'
], function ($) {
    'use strict';

    return function (options, element) {
        var $toggler;

        options = $.extend({
            content: '',
            appendedClass: 'appended-items-wrapper',
            hiddenClass: 'appended-hidden',
            togglerClass: 'appended-items-toggler'
        }, options);

        $('<div></div>')
            .appendTo(element)
            .addClass(options.appendedClass)
            .addClass(options.hiddenClass)
            .append($(options.content).clone());
        $toggler = $('<div></div>')
            .addClass(options.togglerClass)
            .appendTo(element);

        $(document).click(function (event) {
            var $appendedContent = $toggler.siblings('.' + options.appendedClass);

            if ($toggler.get(0) === event.target) {
                // clicked on toggler
                $appendedContent.toggleClass(options.hiddenClass);
            } else {
                // clicked somewhere else
                $appendedContent.addClass(options.hiddenClass);
            }
        });
    };
});
