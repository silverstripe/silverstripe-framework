import $ from 'jQuery';
import i18n from 'i18n';

$.entwine('ss', function($){
    $('.add-to-campaign-action, #add-to-campaign__action').entwine({
        onclick: function() {
            var dialog = $('#add-to-campaign__dialog');

            if (dialog.length) {
                dialog.open();
            } else {
                dialog = $('<div id="add-to-campaign__dialog" class="add-to-campaign__dialog" />');
                $('body').append(dialog);
            }

            if (dialog.children().length == 0) dialog.addClass('loading');

            var form = this.closest('form');
            var button = this;

            var formData = form.serializeArray();
            formData.push({name: button.attr('name'), value:'1'});

            $.ajax({
                url: form.attr('action'),
                data: formData,
                type: 'POST',
                global: false,
                complete: function() {
                    dialog.removeClass('loading');
                },
                success: function(data, status, xhr) {
                    if (xhr.getResponseHeader('Content-Type').indexOf('text/plain') === 0) {
                        var container = $('<div class="add-to-campaign__response add-to-campaign__response--good"><span></span></div>');
                        container.find('span').text(data);
                        dialog.append(container);
                    } else {
                        dialog.html(data);
                    }
                },
                error: function(xhr, status) {
                    var error = xhr.responseText || "Something went wrong. Please try again in a few minutes.";
                    var container = $('<div class="add-to-campaign__response add-to-campaign__response--error"><span></span></div>');
                    container.find('span').text(error);
                    dialog.append(container);
                }
            });

            return false;
        }
    }),

    $('#add-to-campaign__dialog').entwine({
        onadd: function() {
            // Create jQuery dialog
            if (!this.is('.ui-dialog-content')) {
                this.ssdialog({
                    autoOpen: true,
                    minHeight: 200,
                    maxHeight: 200,
                    minWidth: 200,
                    maxWidth: 500
                });
            }

            this._super();
        },

        open: function() {
            this.ssdialog('open');
        },

        close: function() {
            this.ssdialog('close');
        },

        'onssdialogclose': function() {
            this.empty();
        },

        'onchosen:showing_dropdown': function() {
            this.css({overflow: 'visible'});
        },

        'onchosen:hiding_dropdown': function() {
            this.css({overflow: ''});
        }
    });

})
