import React, { PropTypes } from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import { Alert } from 'react-bootstrap-ss';
import castStringToElement from 'lib/castStringToElement';

/**
 * A wrapper for Alert messages in react-bootstrap.
 * Displays a given message.
 */
class FormAlert extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.handleDismiss = this.handleDismiss.bind(this);

    this.state = {
      visible: true,
    };
  }

  /**
   * Handler for when the message box is dismissed and hidden
   */
  handleDismiss() {
    if (typeof this.props.onDismiss === 'function') {
      this.props.onDismiss();
    } else {
      this.setState({ visible: false });
    }
  }

  /**
   * Determines the style for the alert box to be shown based on messageType or other property
   * by default use "danger".
   *
   * @returns {string} can be the following values "success", "warning", "danger", "info"
   */
  getMessageStyle() {
    // See ValidationResult::TYPE_ constant definitions in PHP.
    switch (this.props.type) {
      case 'good':
      case 'success':
        return 'success';
      case 'info':
        return 'info';
      case 'warn':
      case 'warning':
        return 'warning';
      default:
        return 'danger';
    }
  }

  /**
   * Generate the properties for the FormAlert
   * @returns {object} properties
   */
  getMessageProps() {
    const type = this.props.type || 'no-type';

    return {
      className: [
        'message-box',
        `message-box--${type}`,
        this.props.className,
        this.props.extraClass,
      ].join(' '),
      bsStyle: this.props.bsStyle || this.getMessageStyle(),
      bsClass: this.props.bsClass,
      onDismiss: (this.props.closeLabel) ? this.handleDismiss : null,
      closeLabel: this.props.closeLabel,
    };
  }

  render() {
    // use this component's state
    if (typeof this.props.visible !== 'boolean' && this.state.visible || this.props.visible) {
      // needs to be inside a div because the `Alert` component does some magic with props.children
      const body = castStringToElement('div', this.props.value);
      if (body) {
        return (
          <Alert {...this.getMessageProps()}>
            {body}
          </Alert>
        );
      }
    }
    return null;
  }
}

FormAlert.propTypes = {
  extraClass: PropTypes.string,
  value: PropTypes.any,
  type: PropTypes.string,
  onDismiss: PropTypes.func,
  closeLabel: PropTypes.string,
  visible: PropTypes.bool,
};

FormAlert.defaultProps = {
  extraClass: '',
  className: '',
};

export default FormAlert;
