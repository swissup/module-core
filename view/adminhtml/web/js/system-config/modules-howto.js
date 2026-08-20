define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    function copy(text) {
        var $area, copied;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        // the clipboard api is https only - fall back to a hidden textarea
        $area = $('<textarea/>')
            .val(text)
            .css({ position: 'fixed', top: 0, left: '-9999px' })
            .appendTo('body');
        $area[0].select();
        copied = document.execCommand('copy');
        $area.remove();

        return copied ? $.Deferred().resolve() : $.Deferred().reject();
    }

    return function (config, element) {
        var $trigger = $(element),
            $dropdown = $trigger.siblings('.swissup-modules-howto-dropdown'),
            timer;

        function close() {
            $dropdown.removeClass('active');
        }

        $trigger.on('click', function (event) {
            // the header toggles the fieldset on click - keep it out of it
            event.stopPropagation();
            $dropdown.toggleClass('active');
        });

        $dropdown.on('click', function (event) {
            event.stopPropagation();
        });

        $dropdown.find('[data-role=copy]').on('click', function () {
            var $label = $(this).find('span');

            $.when(copy($dropdown.find('pre').text())).done(function () {
                clearTimeout(timer);
                $label.text($t('Copied'));
                timer = setTimeout(function () {
                    $label.text($t('Copy'));
                }, 2000);
            });
        });

        $(document).on('click', close);
        $(document).on('keyup', function (event) {
            if (event.key === 'Escape') {
                close();
            }
        });
    };
});
