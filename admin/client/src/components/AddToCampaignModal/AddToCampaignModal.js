import React from 'react';
import { Modal } from 'react-bootstrap-4';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import FormBuilder from 'components/FormBuilder/FormBuilder';

class AddToCampaignModal extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.handleSubmit = this.handleSubmit.bind(this);

    this.state = {
      response: null,
      error: false,
    };
  }

  handleSubmit(event, fieldValues, submitFn) {
    let promise = null;
    if (typeof this.props.handleSubmit === 'function') {
      promise = this.props.handleSubmit(event, fieldValues, submitFn);
    } else {
      event.preventDefault();
      promise = submitFn();
    }

    if (promise) {
      promise
        .then((response) => {
          // show response
          if (typeof response === 'string' || response.response.ok) {
            this.setState({
              response,
              error: false,
            });
          } else {
            this.setState({
              response: `${response.name}: ${response.message}`,
              error: true,
            });
          }
          return response;
        });
    }
    return promise;
  }

  getBody() {
    // if no schema defined, then lets use existing children instead
    if (!this.props.schemaUrl) {
      return this.props.children;
    }
    return (
      <FormBuilder
        schemaUrl={this.props.schemaUrl}
        handleSubmit={this.handleSubmit}
        handleAction={this.props.handleAction}
      />
    );
  }

  getResponse() {
    if (!this.state.response) {
      return null;
    }

    let className = 'add-to-campaign__response';

    if (this.state.error) {
      className += ' add-to-campaign__response--error';
    } else {
      className += ' add-to-campaign__response--good';
    }

    return (
      <div className={className}>
        <span>{this.state.response}</span>
      </div>
    );
  }

  clearResponse() {
    // TODO to be used with "Try again" and other options later
    this.setState({
      response: null,
    });
  }

  render() {
    const body = this.getBody();
    const response = this.getResponse();

    return (
      <Modal
        show={this.props.show}
        onHide={this.props.handleHide}
      >
        {this.props.title !== false &&
          <Modal.Header closeButton><Modal.Title>{this.props.title}</Modal.Title></Modal.Header>
        }
        <Modal.Body>
          {body}
          {response}
        </Modal.Body>
      </Modal>
    );
  }
}

AddToCampaignModal.propTypes = {
  show: React.PropTypes.bool,
  title: React.PropTypes.string,
  handleHide: React.PropTypes.func,
  schemaUrl: React.PropTypes.string,
  handleSubmit: React.PropTypes.func,
};

AddToCampaignModal.defaultProps = {
  show: false,
  title: null,
};

export default AddToCampaignModal;
