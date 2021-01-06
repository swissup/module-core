define([
    'jquery'
], function ($) {
    'use strict';

    return function (options, element) {
        /**
         * [_updateTitle description]
         */
        function _updateTitle() {
            var currentItem;

            currentItem = $('._active', element).text();

            if (currentItem) {
                $('.admin__page-nav-title', element).html(
                    options.title.selected.replace('{{itemName}}', currentItem)
                );
            } else {
                $('.admin__page-nav-title', element).html(options.title.no);
            }
        }

        /**
         * Assign image to theme
         */
        function _assignImage() {
            var itemTitle, image, html;

            itemTitle = $(this).text().trim();
            image = options.images ? options.images[itemTitle] : false;

            if (image) {
                html = '<div class="img-wrapper"><img src="{{image}}" width="240" /></div>';
                $(this).prepend(html.replace('{{image}}', image));
            }
        }

        /**
         * [_activateItem description]
         */
        function _activateItem() {
            $(options.itemToActivateWhenSelected).closest('.config-nav-block').collapsible('activate');
            $(options.itemToActivateWhenSelected).addClass('_active');
        }

        /**
         * Update section collapsible status
         */
        function _updateCollapsibleStatus() {
            var currentItem;

            if (typeof $(element).collapsible === 'function') {
                currentItem = $('._active', element).text();

                $('.admin__page-nav-items', element).css({
                    display: 'flex'
                });

                if (currentItem) {
                    $(element).collapsible('deactivate');
                    $('.admin__page-nav-items', element).css({
                        display: 'none'
                    });
                    _activateItem();
                } else {
                    $(element).collapsible('activate');
                }
            } else {
                setTimeout(_updateCollapsibleStatus, 400);
            }
        }

        $(element).detach();
        $('a', element).each(_assignImage);
        _updateTitle();
        _updateCollapsibleStatus();
        $(element).appendTo(options.destination);
    };
});
