import jQuery from 'jQuery';

jQuery.entwine('ss', ($) => {
  $('.add-to-campaign-action, #add-to-campaign__action').entwine({
    onclick() {
      let dialog = $('#add-to-campaign__dialog');

      if (dialog.length) {
        dialog.open();
      } else {
        dialog = $('<div id="add-to-campaign__dialog" class="add-to-campaign__dialog" />');
        $('body').append(dialog);
      }

      if (dialog.children().length === 0) dialog.addClass('loading');

      const form = this.closest('form');
      const button = this;

      const formData = form.serializeArray();
      formData.push({
        name: button.attr('name'),
        value: '1',
      });

      $.ajax({
        url: form.attr('action'),
        data: formData,
        type: 'POST',
        global: false,
        complete() {
          dialog.removeClass('loading');
        },
        success(data, status, xhr) {
          if (xhr.getResponseHeader('Content-Type').indexOf('text/plain') === 0) {
            const container = $(
              '<div class="add-to-campaign__response add-to-campaign__response--good">' +
              '<span></span></div>'
            );
            container.find('span').text(data);
            dialog.append(container);
          } else {
            dialog.html(data);
          }
        },
        error(xhr) {
          const error = xhr.responseText
            || 'Something went wrong. Please try again in a few minutes.';
          const container = $(
            '<div class="add-to-campaign__response add-to-campaign__response--error">' +
            '<span></span></div>'
          );
          container.find('span').text(error);
          dialog.append(container);
        },
      });

      return false;
    },
  });

  $('#add-to-campaign__dialog').entwine({
    onadd() {
      // Create jQuery dialog
      if (!this.is('.ui-dialog-content')) {
        this.ssdialog({
          autoOpen: true,
          minHeight: 200,
          maxHeight: 200,
          minWidth: 200,
          maxWidth: 500,
        });
      }

      this._super();
    },

    open() {
      this.ssdialog('open');
    },

    close() {
      this.ssdialog('close');
    },

    onssdialogclose() {
      this.empty();
    },

    'onchosen:showing_dropdown': function () {  // eslint-disable-line
      this.css({
        overflow: 'visible',
      });
    },

    'onchosen:hiding_dropdown': function () {  // eslint-disable-line
      this.css({
        overflow: '',
      });
    },
  });
});
