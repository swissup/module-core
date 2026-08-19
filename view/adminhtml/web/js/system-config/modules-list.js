define([
    'jquery',
    'moment',
    'mage/translate',
    'Swissup_Core/js/system-config/modules'
], function ($, moment, $t, modules) {
    'use strict';

    return function (config, element) {
        var $table = $(element);

        modules.onLoad(function (items) {
            var outdated = 0;

            items.forEach(function (item) {
                // the installed list itself cannot change without a reload -
                // only the latest version and the outdated state can
                $table.find('tr[data-code="' + item.code + '"] .latest .version')
                    .toggleClass('outdated', !!item.is_outdated)
                    .attr('title', item.release_date
                        ? $t('Released on %1').replace('%1', moment(item.release_date).format('ll'))
                        : '')
                    .text(item.latest_version || $t('N/A'));

                if (item.is_outdated) {
                    outdated++;
                }
            });

            $('.swissup-modules-summary').html(
                [
                    $t('%1 installed').replace('%1', items.length),
                    outdated
                        ? '<span class="outdated">' +
                            $t('%1 updates available').replace('%1', outdated) +
                        '</span>'
                        : ''
                ].filter(Boolean).join(' &middot; ')
            );
        });

        modules.getModules(config.url);
    };
});
