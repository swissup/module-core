define([
    'underscore',
    'Magento_Ui/js/form/element/textarea'
], function (_, Textarea) {
    'use strict';

    return Textarea.extend({

        initialize: function () {
            this._super();
            this.updateStatus();
        },

        /**
         * Update field status according to the `identity_key_link` property
         */
        updateStatus: function () {
            var link = this.source.data.general.identity_key_link,
                isRequired = (link && link.length > 0);

            if (!isRequired) {
                this.error(false);
                this.validation = _.omit(this.validation, 'required-entry');
            } else {
                this.validation['required-entry'] = true;
            }

            this.setVisible(isRequired);
            this.required(isRequired);
        }
    });
});
