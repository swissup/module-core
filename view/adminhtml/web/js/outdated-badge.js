define([
    'jquery',
    'mage/translate',
    'Swissup_Core/js/system-config/modules'
], function ($, $t, modules) {
    'use strict';

    return function (config) {
        var render = function (count) {
            var $title = $('.swissup-tab > .admin__page-nav-title'),
                style = document.documentElement.style;

            $title.find('.swissup-config-badge').remove();

            // the stored counter could be outdated itself - the rendered one
            // drives the menu badges and the config tab fallback from now on
            $('#swissup-outdated-badge-style').remove();

            if (!count) {
                return style.removeProperty('--swissup-outdated-count');
            }

            style.setProperty('--swissup-outdated-count', '\'' + count + '\'');

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
