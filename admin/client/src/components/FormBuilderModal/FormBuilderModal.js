import React from 'react';
import i18n from 'i18n';
import { Modal } from 'react-bootstrap-ss';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import FormBuilderLoader from 'containers/FormBuilderLoader/FormBuilderLoader';

class FormBuilderModal extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.handleSubmit = this.handleSubmit.bind(this);
    this.handleHide = this.handleHide.bind(this);
    this.clearResponse = this.clearResponse.bind(this);
  }

  /**
   * Defines the form part of the Modal
   *
   * @returns {Component}
   */
  getForm() {
    if (!this.props.schemaUrl) {
      return null;
    }
    return (
      <FormBuilderLoader
        schemaUrl={this.props.schemaUrl}
        handleSubmit={this.handleSubmit}
        handleAction={this.props.handleAction}
      />
    );
  }

  /**
   * Generates the response part of the Modal
   *
   * @returns {Component}
   */
  getResponse() {
    if (!this.state || !this.state.response) {
      return null;
    }

    let className = '';

    if (this.state.error) {
      className = this.props.responseClassBad || 'response error';
    } else {
      className = this.props.responseClassGood || 'response good';
    }

    return (
      <div className={className}>
        <span>{this.state.response}</span>
      </div>
    );
  }

  /**
   * Removes the response from the state
   */
  clearResponse() {
    this.setState({
      response: null,
    });
  }

  /**
   * Call the callback for hiding this Modal
   */
  handleHide() {
    this.clearResponse();
    if (typeof this.props.handleHide === 'function') {
      this.props.handleHide();
    }
  }

  /**
   * Handle submitting the form in the Modal
   *
   * @param {Object} data
   * @param {String} action
   * @param {Function} submitFn The original submit function
   * @returns {Promise}
   */
  handleSubmit(data, action, submitFn) {
    let promise = null;
    if (typeof this.props.handleSubmit === 'function') {
      promise = this.props.handleSubmit(data, action, submitFn);
    } else {
      promise = submitFn();
    }

    if (promise) {
      // do not want this as part of the main promise chain.
      promise
        .then((response) => {
          this.setState({
            response: response.message,
            error: false,
          });
          return response;
        })
        .catch((errorPromise) => {
          errorPromise.then((errorText) => {
            this.setState({
              response: errorText,
              error: true,
            });
          });
        });
    } else {
      throw new Error('Promise was not returned for submitting');
    }

    return promise;
  }

  renderHeader() {
    if (this.props.title !== false) {
      return (
        <Modal.Header closeButton><Modal.Title>{this.props.title}</Modal.Title></Modal.Header>
      );
    }

    if (typeof this.props.handleHide === 'function') {
      return (
        <button
          type="button"
          className="close form-builder-modal__close-button"
          onClick={this.handleHide}
          aria-label={i18n._t('FormBuilderModal.CLOSE', 'Close')}
        >
          <span aria-hidden="true">×</span>
        </button>
      );
    }

    return null;
  }

  render() {
    const form = this.getForm();
    const response = this.getResponse();

    return (
      <Modal
        show={this.props.show}
        onHide={this.handleHide}
        className={this.props.className}
        bsSize={this.props.bsSize}
      >
        {this.renderHeader()}
        <Modal.Body className={this.props.bodyClassName}>
          {response}
          {form}
          {this.props.children}
        </Modal.Body>
      </Modal>
    );
  }
}

FormBuilderModal.propTypes = {
  show: React.PropTypes.bool,
  title: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.bool]),
  className: React.PropTypes.string,
  bodyClassName: React.PropTypes.string,
  handleHide: React.PropTypes.func,
  schemaUrl: React.PropTypes.string,
  handleSubmit: React.PropTypes.func,
  handleAction: React.PropTypes.func,
  responseClassGood: React.PropTypes.string,
  responseClassBad: React.PropTypes.string,
};

FormBuilderModal.defaultProps = {
  show: false,
  title: null,
};

export default FormBuilderModal;
