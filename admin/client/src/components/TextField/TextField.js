import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import fieldHolder from 'components/FieldHolder/FieldHolder';

class TextField extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
  }

  render() {
    let field = null;

    if (this.props.readOnly) {
      field = <div><i>{this.props.value}</i></div>;
    } else {
      field = <input {...this.getInputProps()} />;
    }

    return field;
  }

  /**
   * Fetches the properties for the text field
   *
   * @returns Object properties
   */
  getInputProps() {
    // TODO Merge with 'attributes' from formfield schema
    return {
      // The extraClass property is defined on both the holder and element
      // for legacy reasons (same behaviour as PHP rendering)
      className: ['form-control', this.props.extraClass].join(' '),
      id: this.props.id,
      name: this.props.name,
      onChange: this.handleChange,
      type: 'text',
      value: this.props.value,
    };
  }

  /**
   * Handles changes to the text field's value.
   *
   * @param object event
   */
  handleChange(event) {
    if (typeof this.props.onChange === 'function') {
      this.props.onChange(event, { id: this.props.id, value: event.target.value });
    }
  }
}

TextField.propTypes = {
  extraClass: React.PropTypes.string,
  id: React.PropTypes.string,
  name: React.PropTypes.string.isRequired,
  onChange: React.PropTypes.func,
  value: React.PropTypes.string,
  readOnly: React.PropTypes.bool,
};

TextField.defaultProps = {
  // React considers "undefined" as an uncontrolled component.
  value: null,
};

export default fieldHolder(TextField);
