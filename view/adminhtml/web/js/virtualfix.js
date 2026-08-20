define([
    'jquery',
    'Magento_Ui/js/modal/alert',
], function ($, alert) {
    'use strict';

    var self, url, cacheUrl,
        rowSelector = '#row_swissup_core_troubleshooting_virtualcheck';

    return {
        init: function (ajaxCallUrl, cacheUrlPath, assignBtn) {
            self = this;
            url = ajaxCallUrl;
            cacheUrl = cacheUrlPath;

            $(assignBtn).on('click', function () {
                self.virtualfix();
            });
        },
        virtualfix: function () {
            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                showLoader: true,
                data: {
                    form_key:   window.FORM_KEY
                }
            })
            .done(function (data) {
                var $message = $('<tr class="swissup-virtualfix-message"><td colspan="100"></td></tr>'),
                    $cell = $message.find('td');

                $('.swissup-virtualfix-message').remove();

                $cell.text(data.message);

                if (data.fixed) {
                    $(rowSelector + ' .themes-table table').hide();
                    $cell.append(' ', $('<a/>', {
                        href: cacheUrl,
                        target: '_blank',
                        text: $.mage.__('Clear the cache')
                    }));
                }

                if (!data.skipped) {
                    $('#fix-all-themes').hide();
                }

                $message.insertAfter(rowSelector);
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                alert({
                    title: $.mage.__('Error'),
                    content: $.mage.__('An error occured: ') + errorThrown
                });
            });
        }
    };
});
