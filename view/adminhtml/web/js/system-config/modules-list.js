define([
    'jquery',
    'moment',
    'mage/translate',
    'Swissup_Core/js/system-config/modules'
], function ($, moment, $t, modules) {
    'use strict';

    // keep the labels in sync with Field\Modules::getLinks()
    function links(item) {
        return [
            { url: item.docs_link, label: $t('Docs') },
            { url: item.changelog_link, label: $t('Changelog') }
        ].filter(function (link) {
            // the same schemes the php escapeUrl() lets through
            return /^https?:\/\//.test(link.url);
        }).map(function (link) {
            return $('<a target="_blank"/>').attr('href', link.url).text(link.label);
        });
    }

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

                $row.toggleClass('outdated', !!item.is_outdated);
                $row.find('.latest .version')
                    .toggleClass('outdated', !!item.is_outdated)
                    .attr('title', item.release_date
                        ? $t('Released on %1').replace('%1', moment(item.release_date).format('ll'))
                        : '')
                    .text(item.latest_version || $t('N/A'));

                // on a cold start the links are unknown until the list is
                // loaded - the server had nothing to render them from
                $row.find('.links').empty().append(links(item));

                if (item.is_outdated) {
                    outdated++;
                }
            });

            // keep the outdated first ordering of the rendered table - both
            // groups are sorted by name already, and prepend keeps that order
            $tbody.prepend($tbody.children('.outdated'));

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
