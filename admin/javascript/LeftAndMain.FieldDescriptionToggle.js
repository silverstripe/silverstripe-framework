/**
 * Enable toggling (show/hide) of the field's description.
 */

(function ($) {

    $.entwine('ss', function ($) {

        $('.cms-description-toggle').entwine({
            onadd: function () {
                var shown = false, // Current state of the description.
                    fieldId = this.prop('id').substr(0, this.prop('id').indexOf('_Holder')),
                    $trigger = this.find('.cms-description-trigger'), // Click target for toggling the description.
                    $description = this.find('.description');

                // Prevent multiple events being added.
                if (this.hasClass('description-toggle-enabled')) {
                    return;
                }

                // If a custom trigger han't been supplied use a sensible default.
                if ($trigger.length === 0) {
                    $trigger = this
                        .find('.middleColumn')
                        .first() // Get the first middleColumn so we don't add multiple triggers on composite field types.
                        .after('<label class="right" for="' + fieldId + '"><a class="cms-description-trigger" href="javascript:void(0)"><span class="btn-icon-information"></span></a></label>')
                        .next();
                }

                this.addClass('description-toggle-enabled');

                // Toggle next description when button is clicked.
                $trigger.on('click', function() {
                    $description[shown ? 'hide' : 'show']();
                    shown = !shown;
                });

                // Hide next description by default.
                $description.hide();
            }
        });

    });
})(jQuery);
