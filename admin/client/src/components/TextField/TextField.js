import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import { FormControl } from 'react-bootstrap-ss';

class TextField extends SilverStripeComponent {

  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
  }

  render() {
    let field = null;

    if (this.props.readOnly) {
      field = <FormControl.Static {...this.getInputProps()}>{this.props.value}</FormControl.Static>;
    } else {
      field = <FormControl {...this.getInputProps()} />;
    }

    return field;
  }

  /**
   * Fetches the properties for the text field
   *
   * @returns {object} properties
   */
  getInputProps() {
    const props = {
      bsClass: this.props.bsClass,
      className: `${this.props.className} ${this.props.extraClass}`,
      id: this.props.id,
      name: this.props.name,
      disabled: this.props.disabled,
      readOnly: this.props.readOnly,
    };

    if (!this.props.readOnly) {
      Object.assign(props, {
        placeholder: this.props.placeholder,
        onChange: this.handleChange,
        value: this.props.value,
      });

      if (this.isMultiline()) {
        Object.assign(props, {
          componentClass: 'textarea',
          rows: this.props.data.rows,
          cols: this.props.data.columns,
        });
      } else {
        Object.assign(props, {
          componentClass: 'input',
          type: this.props.type.toLowerCase(),
        });
      }
    }

    return props;
  }

  /**
   * Determines whether this text field is a multi-line textarea or not
   *
   * @returns {boolean}
   */
  isMultiline() {
    return this.props.data && this.props.data.rows > 1;
  }

  /**
   * Handles changes to the text field's value.
   *
   * @param {Event} event
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
  value: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.number]),
  readOnly: React.PropTypes.bool,
  disabled: React.PropTypes.bool,
  placeholder: React.PropTypes.string,
  type: React.PropTypes.string,
};

TextField.defaultProps = {
  // React considers "undefined" as an uncontrolled component.
  value: '',
  extraClass: '',
  className: '',
  type: 'text',
};

export { TextField };

export default fieldHolder(TextField);
