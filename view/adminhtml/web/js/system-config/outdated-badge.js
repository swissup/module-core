define([
    'jquery',
    'mage/translate',
    'Swissup_Core/js/system-config/modules'
], function ($, $t, modules) {
    'use strict';

    return function (config) {
        var render = function (count) {
            var $title = $('.swissup-tab > .admin__page-nav-title');

            $title.find('.swissup-config-badge').remove();

            if (!count) {
                // the stored counter is outdated itself - drop it with the styles
                return $('#swissup-outdated-badge-style').remove();
            }

            $('<a class="swissup-config-badge"/>')
                .attr('href', config.moduleListUrl)
                .attr('title', $t('Outdated modules: %1').replace('%1', count))
                .on('click', function (event) {
                    event.stopPropagation();
                })
                .text(count)
                .appendTo($title);
        };

        modules.onLoad(function (response) {
            render(response.items.filter(function (item) {
                return item.is_outdated;
            }).length);
        });

        if (config.checkRequired) {
            modules.getModules({ url: config.url });
        } else {
            // the stored data is still fresh - the server counter is the answer
            render(config.count);
        }
    };
});
