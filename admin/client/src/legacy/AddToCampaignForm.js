import jQuery from 'jQuery';
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import FormBuilderModal from 'components/FormBuilderModal/FormBuilderModal';

jQuery.entwine('ss', ($) => {
	/**
   * Kick off an "add to campaign" dialog from the CMS actions.
   */
  $(
    '.cms-content-actions .add-to-campaign-action,' +
    '#add-to-campaign__action'
  ).entwine({
    onclick() {
      let dialog = $('#add-to-campaign__dialog-wrapper');

      if (!dialog.length) {
        dialog = $('<div id="add-to-campaign__dialog-wrapper" />');
        $('body').append(dialog);
      }

      dialog.open();

      return false;
    },
  });

	/**
   * Uses React-Bootstrap in order to replicate the bootstrap styling and JavaScript behaviour.
   * The "add to campaign" dialog is used in a similar fashion in AssetAdmin.
   */
  $('#add-to-campaign__dialog-wrapper').entwine({

    onunmatch() {
      // solves errors given by ReactDOM "no matched root found" error.
      this._clearModal();
    },

    open() {
      this._renderModal(true);
    },

    close() {
      this._renderModal(false);
    },

    _renderModal(show) {
      const handleHide = () => this.close();
      const handleSubmit = (...args) => this._handleSubmitModal(...args);
      const id = $('form.cms-edit-form :input[name=ID]').val();
      const store = window.ss.store;
      const sectionKey = 'SilverStripe\\CMS\\Controllers\\CMSPageEditController';
      const sectionConfig = store.getState().config.sections[sectionKey];
      const modalSchemaUrl = `${sectionConfig.form.AddToCampaignForm.schemaUrl}/${id}`;

      ReactDOM.render(
        <Provider store={store}>
          <FormBuilderModal
            show={show}
            handleSubmit={handleSubmit}
            handleHide={handleHide}
            schemaUrl={modalSchemaUrl}
            bodyClassName="modal__dialog"
            responseClassBad="modal__response modal__response--error"
            responseClassGood="modal__response modal__response--good"
          />
        </Provider>,
        this[0]
      );
    },

    _clearModal() {
      ReactDOM.unmountComponentAtNode(this[0]);
      // this.empty();
    },

    _handleSubmitModal(data, action, submitFn) {
      return submitFn();
    },

  });
});
