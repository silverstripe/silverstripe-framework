import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import { FormControl } from 'react-bootstrap-ss';

class HiddenField extends SilverStripeComponent {
  /**
   * Fetches the properties for the field
   *
   * @returns {object} properties
   */
  getInputProps() {
    return {
      bsClass: this.props.bsClass,
      componentClass: 'input',
      className: `${this.props.className} ${this.props.extraClass}`,
      id: this.props.id,
      name: this.props.name,
      type: 'hidden',
      value: this.props.value,
    };
  }

  render() {
    return (
      <FormControl {...this.getInputProps()} />
    );
  }
}

HiddenField.propTypes = {
  id: React.PropTypes.string,
  extraClass: React.PropTypes.string,
  name: React.PropTypes.string.isRequired,
  value: React.PropTypes.any,
};

HiddenField.defaultProps = {
  className: '',
  extraClass: '',
  value: '',
};

export default HiddenField;
