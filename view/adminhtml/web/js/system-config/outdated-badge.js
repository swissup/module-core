define([
    'jquery',
    'mage/translate',
    'Swissup_Core/js/system-config/modules'
], function ($, $t, modules) {
    'use strict';

    return function (config) {
        modules.onLoad(function (items) {
            var $title = $('.swissup-tab > .admin__page-nav-title'),
                outdated = items.filter(function (item) {
                    return item.is_outdated;
                });

            $title.find('.swissup-config-badge').remove();

            if (!outdated.length) {
                // the stored counter is outdated itself - drop it with the styles
                return $('#swissup-outdated-badge-style').remove();
            }

            $('<a class="swissup-config-badge"/>')
                .attr('href', config.moduleListUrl)
                .attr('title', $t('Outdated modules: %1').replace('%1', outdated.length))
                .on('click', function (event) {
                    event.stopPropagation();
                })
                .text(outdated.length)
                .appendTo($title);
        });

        modules.getModules(config.url);
    };
});
