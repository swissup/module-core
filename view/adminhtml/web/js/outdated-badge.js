define([
    'jquery',
    'mage/translate',
    'Swissup_Core/js/system-config/modules'
], function ($, $t, modules) {
    'use strict';

    return function (config) {
        var style = document.documentElement.style,
            counts = { outdated: 0, security: 0 },
            setVar = function (name, count) {
                var property = '--swissup-' + name + '-count';

                if (count) {
                    style.setProperty(property, '\'' + count + '\'');
                } else {
                    style.removeProperty(property);
                }
            },
            setCount = function (name, count) {
                counts[name] = count;
                setVar(name, count);
                // the Swissup menu badge sums up all of the counters
                setVar('total', counts.outdated + counts.security);
            },
            render = function (count) {
                var $title = $('.swissup-tab > .admin__page-nav-title');

                $title.find('.swissup-config-badge').remove();
                setCount('outdated', count);

                if (!count) {
                    return;
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

        // the stored counters could be outdated themselves - the rendered ones
        // drive the badges from now on
        setCount('outdated', config.count);
        setCount('security', config.securityCount);
        $('#swissup-outdated-badge-style').remove();

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

        if (config.securityCheckRequired) {
            $.getJSON(config.securityUrl).done(function (response) {
                setCount('security', response.count);
            });
        }
    };
});
