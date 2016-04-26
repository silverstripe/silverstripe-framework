(function (global, factory) {
    if (typeof define === "function" && define.amd) {
        define('ss.AddToCampaignForm', ['jQuery', 'i18n'], factory);
    } else if (typeof exports !== "undefined") {
        factory(require('jQuery'), require('i18n'));
    } else {
        var mod = {
            exports: {}
        };
        factory(global.jQuery, global.i18n);
        global.ssAddToCampaignForm = mod.exports;
    }
})(this, function (_jQuery, _i18n) {
    'use strict';

    var _jQuery2 = _interopRequireDefault(_jQuery);

    var _i18n2 = _interopRequireDefault(_i18n);

    function _interopRequireDefault(obj) {
        return obj && obj.__esModule ? obj : {
            default: obj
        };
    }

    _jQuery2.default.entwine('ss', function ($) {
        $('.add-to-campaign-action, #add-to-campaign__action').entwine({
            onclick: function onclick() {
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
                formData.push({ name: button.attr('name'), value: '1' });

                $.ajax({
                    url: form.attr('action'),
                    data: formData,
                    type: 'POST',
                    global: false,
                    complete: function complete() {
                        dialog.removeClass('loading');
                    },
                    success: function success(data, status, xhr) {
                        if (xhr.getResponseHeader('Content-Type').indexOf('text/plain') === 0) {
                            var container = $('<div class="add-to-campaign__response add-to-campaign__response--good"><span></span></div>');
                            container.find('span').text(data);
                            dialog.append(container);
                        } else {
                            dialog.html(data);
                        }
                    },
                    error: function error(xhr, status) {
                        var error = xhr.responseText || "Something went wrong. Please try again in a few minutes.";
                        var container = $('<div class="add-to-campaign__response add-to-campaign__response--error"><span></span></div>');
                        container.find('span').text(error);
                        dialog.append(container);
                    }
                });

                return false;
            }
        }), $('#add-to-campaign__dialog').entwine({
            onadd: function onadd() {
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

            open: function open() {
                this.ssdialog('open');
            },

            close: function close() {
                this.ssdialog('close');
            },

            'onssdialogclose': function onssdialogclose() {
                this.empty();
            },

            'onchosen:showing_dropdown': function onchosenShowing_dropdown() {
                this.css({ overflow: 'visible' });
            },

            'onchosen:hiding_dropdown': function onchosenHiding_dropdown() {
                this.css({ overflow: '' });
            }
        });
    });
});