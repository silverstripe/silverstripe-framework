import React from 'react';
import { Modal } from 'react-bootstrap-4';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import FormBuilder from 'components/FormBuilder/FormBuilder';
import Config from 'lib/Config';

class AddToCampaignModal extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.handleSubmit = this.handleSubmit.bind(this);
  }

  handleSubmit(event, fieldValues, submitFn) {

    if (typeof this.props.handleSubmit === 'function') {
      this.props.handleSubmit(event, fieldValues, submitFn);
      return;
    }

    event.preventDefault();
    submitFn();
  }

  getBody() {
    const schemaUrl = `${this.props.schemaUrl}/${this.props.fileId}`;

    return <FormBuilder schemaUrl={schemaUrl} handleSubmit={this.handleSubmit} />;
  }

  render() {
    const body = this.getBody();

    return <Modal show={this.props.show} onHide={this.props.handleHide} container={document.getElementsByClassName('cms-container')[0]}>
      <Modal.Header closeButton>
        <Modal.Title>{this.props.title + ' - Test'}</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        {body}
      </Modal.Body>
    </Modal>;
  }
}

AddToCampaignModal.propTypes = {
  show:       React.PropTypes.bool.isRequired,
  title:      React.PropTypes.string,
  handleHide: React.PropTypes.func,
  schemaUrl:  React.PropTypes.string,
  handleSubmit: React.PropTypes.func,
};

export default AddToCampaignModal;
