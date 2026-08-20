define([
    'jquery',
    'mage/translate',
    'Swissup_Core/js/system-config/modules'
], function ($, $t, modules) {
    'use strict';

    // keep the wording in sync with Fieldset\Modules::getLastCheckLabel()
    function label(time) {
        var seconds = Math.max(0, Math.floor(Date.now() / 1000) - time),
            minutes = Math.floor(seconds / 60),
            ago;

        if (seconds < 20) {
            ago = $t('just now');
        } else if (minutes < 1) {
            ago = $t('less than a minute ago');
        } else if (minutes < 60) {
            ago = $t('%1 min ago').replace('%1', minutes);
        } else {
            ago = $t('%1 h ago').replace('%1', Math.floor(minutes / 60));
        }

        return $t('Last checked %1').replace('%1', ago);
    }

    function showLastCheck() {
        var $lastCheck = $('.swissup-modules-lastcheck'),
            time = parseInt($lastCheck.attr('data-time'), 10);

        $lastCheck.text(time ? label(time) : '');
    }

    setInterval(showLastCheck, 10000);

    // every load carries the time the server actually checked at - a failed
    // check keeps the old one, so the label never claims more than happened
    modules.onLoad(function (response) {
        $('.swissup-modules-lastcheck').attr('data-time', response.last_check || 0);
        showLastCheck();
    });

    return function (config, element) {
        $(element).on('click', function () {
            var $button = $(this);

            $button.prop('disabled', true);

            modules.getModules(config.url, true).always(function () {
                $button.prop('disabled', false);
            });
        });
    };
});
