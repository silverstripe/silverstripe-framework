(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define('ss.AddToCampaignForm', ['jQuery', 'i18n', 'react', 'react-dom', 'react-redux', 'components/FormBuilderModal/FormBuilderModal'], factory);
  } else if (typeof exports !== "undefined") {
    factory(require('jQuery'), require('i18n'), require('react'), require('react-dom'), require('react-redux'), require('components/FormBuilderModal/FormBuilderModal'));
  } else {
    var mod = {
      exports: {}
    };
    factory(global.jQuery, global.i18n, global.react, global.reactDom, global.reactRedux, global.FormBuilderModal);
    global.ssAddToCampaignForm = mod.exports;
  }
})(this, function (_jQuery, _i18n, _react, _reactDom, _reactRedux, _FormBuilderModal) {
  'use strict';

  var _jQuery2 = _interopRequireDefault(_jQuery);

  var _i18n2 = _interopRequireDefault(_i18n);

  var _react2 = _interopRequireDefault(_react);

  var _reactDom2 = _interopRequireDefault(_reactDom);

  var _FormBuilderModal2 = _interopRequireDefault(_FormBuilderModal);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  _jQuery2.default.entwine('ss', function ($) {
    $('.cms-content-actions .add-to-campaign-action,' + '#add-to-campaign__action').entwine({
      onclick: function onclick() {
        var dialog = $('#add-to-campaign__dialog-wrapper');

        if (!dialog.length) {
          dialog = $('<div id="add-to-campaign__dialog-wrapper" />');
          $('body').append(dialog);
        }

        dialog.open();

        return false;
      }
    });

    $('#add-to-campaign__dialog-wrapper').entwine({
      open: function open() {
        this._renderModal();
      },
      close: function close() {
        this._clearModal();
      },
      _renderModal: function _renderModal() {
        var _this = this;

        var handleHide = function handleHide() {
          return _this._clearModal();
        };
        var handleSubmit = function handleSubmit() {
          return _this._handleSubmitModal.apply(_this, arguments);
        };
        var id = $('form.cms-edit-form :input[name=ID]').val();
        var store = window.ss.store;
        var sectionConfig = store.getState().config.sections['SilverStripe\\CMS\\Controllers\\CMSPageEditController'];
        var modalSchemaUrl = sectionConfig.form.AddToCampaignForm.schemaUrl + '/' + id;

        _reactDom2.default.render(_react2.default.createElement(
          _reactRedux.Provider,
          { store: store },
          _react2.default.createElement(_FormBuilderModal2.default, {
            show: true,
            handleSubmit: handleSubmit,
            handleHide: handleHide,
            schemaUrl: modalSchemaUrl,
            bodyClassName: 'add-to-campaign__dialog',
            responseClassBad: 'add-to-campaign__response add-to-campaign__response--error',
            responseClassGood: 'add-to-campaign__response add-to-campaign__response--good'
          })
        ), this[0]);
      },
      _clearModal: function _clearModal() {
        _reactDom2.default.unmountComponentAtNode(this[0]);
      },
      _handleSubmitModal: function _handleSubmitModal(event, fieldValues, submitFn) {
        event.preventDefault();

        if (!fieldValues.Campaign) {
          alert(_i18n2.default._t('AddToCampaigns.ErrorCampaignNotSelected', 'There was no campaign selected to be added to'));
          return null;
        }
        return submitFn();
      }
    });
  });
});