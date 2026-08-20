define([
    'jquery',
    'moment',
    'mage/translate',
    'Swissup_Core/js/system-config/modules'
], function ($, moment, $t, modules) {
    'use strict';

    // the list is loaded by the outdated badge - this only re-renders the rows
    // of an already rendered table, be it the first load or a manual refresh
    return function (config, element) {
        var $table = $(element);

        modules.onLoad(function (response) {
            var $tbody = $table.find('tbody'),
                outdated = 0;

            response.items.forEach(function (item) {
                // the installed list itself cannot change without a reload -
                // only the latest version and the outdated state can
                var $row = $tbody.find('tr[data-code="' + item.code + '"]');

                $row.toggleClass('_outdated', !!item.is_outdated);
                $row.find('.latest .version')
                    .toggleClass('_outdated', !!item.is_outdated)
                    .attr('title', item.release_date
                        ? $t('Released on %1').replace('%1', moment(item.release_date).format('ll'))
                        : '')
                    .text(item.latest_version || $t('N/A'));

                if (item.is_outdated) {
                    outdated++;
                }
            });

            // keep the outdated first ordering of the rendered table - both
            // groups are sorted by name already, and prepend keeps that order
            $tbody.prepend($tbody.children('._outdated'));

            $('.swissup-modules-summary').html(
                [
                    $t('%1 installed').replace('%1', response.items.length),
                    outdated
                        ? '<span class="outdated">' +
                            $t('%1 updates available').replace('%1', outdated) +
                        '</span>'
                        : ''
                ].filter(Boolean).join(' &middot; ')
            );
        });
    };
});
