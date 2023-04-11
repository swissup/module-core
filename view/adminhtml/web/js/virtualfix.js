define([
    'jquery',
    'Magento_Ui/js/modal/alert',
], function ($, alert) {
    'use strict';

    var self, url, cacheUrl;

    return {
        init: function(ajaxCallUrl, cacheUrlPath, assignBtn) {
            self = this;
            url = ajaxCallUrl;
            cacheUrl = cacheUrlPath;

            $(assignBtn).on('click', function() {
                self.virtualfix();
            });
        },
        virtualfix: function() {
            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                showLoader: true,
                data: {
                    form_key:   window.FORM_KEY
                }
            })
            .done(function(data) {
                $('#row_swissup_core_troubleshooting_virtualcheck .themes-table table').hide();
                $('#fix-all-themes').hide();
                $('<tr><td>Success! Please, <a href="' + cacheUrl + '" target=_blank>clear the cache</a></td></tr>').insertAfter('#row_swissup_core_troubleshooting_virtualcheck');
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                alert({
                    title: $.mage.__('Error'),
                    content: $.mage.__('An error occured: ') + errorThrown
                });
            });  
        }
    }
});
