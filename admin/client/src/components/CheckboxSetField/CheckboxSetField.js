import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import OptionField from 'components/OptionsetField/OptionField';
import fieldHolder from 'components/FieldHolder/FieldHolder';

// a group of check boxes
class CheckboxSetField extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.getItemKey = this.getItemKey.bind(this);
    this.getOptionProps = this.getOptionProps.bind(this);
    this.handleChange = this.handleChange.bind(this);
    this.getValues = this.getValues.bind(this);
  }

  /**
   * Generates a unique key for an item
   *
   * @param {object} item
   * @param {int} index
   * @returns {string} key
   */
  getItemKey(item, index) {
    return `${this.props.id}-${item.value || `empty${index}`}`;
  }

  /**
   * Gets the array of values possible, converts to array if it is not.
   *
   * @returns {Array} values
   */
  getValues() {
    let values = this.props.value;

    if (!Array.isArray(values) &&
      (values || typeof values === 'string' || typeof values === 'number')) {
      values = [values];
    }

    if (values) {
      // casting all to string because of numeric strings being casted to numbers
      return values.map((value) => `${value}`);
    }
    return [];
  }

  /**
   * Handler for sorting what the value of the field will be, this flows on from the
   * OptionField (single checkbox) event handler and adding or removing the corresponding value the
   * single checkbox represented to suit the multiple checkbox group.
   *
   * @param {Event} event
   * @param {object} field
   */
  handleChange(event, field) {
    if (typeof this.props.onChange === 'function') {
      const oldValue = this.getValues();
      const newValue = this.props.source
        .filter((item, index) => {
          if (this.getItemKey(item, index) === field.id) {
            return field.value === 1;
          }
          return oldValue.indexOf(`${item.value}`) > -1;
        })
        .map((item) => `${item.value}`);

      this.props.onChange(newValue);
    }
  }

  /**
   * Fetches properties for an item
   *
   * @param {object} item
   * @param {int} index
   * @returns {object} properties
   */
  getOptionProps(item, index) {
    const oldValue = this.getValues();
    const key = this.getItemKey(item, index);

    return {
      key,
      id: key,
      name: this.props.name,
      className: this.props.itemClass,
      disabled: item.disabled || this.props.disabled,
      readOnly: this.props.readOnly,
      onChange: this.handleChange,
      value: oldValue.indexOf(`${item.value}`) > -1,
      title: item.title,
      type: 'checkbox',
    };
  }

  render() {
    if (!this.props.source) {
      return null;
    }
    return (
      <div>
        { this.props.source.map((item, index) => (
          <OptionField {...this.getOptionProps(item, index)} />
        )) }
      </div>
    );
  }
}

CheckboxSetField.propTypes = {
  className: React.PropTypes.string,
  extraClass: React.PropTypes.string,
  itemClass: React.PropTypes.string,
  id: React.PropTypes.string,
  name: React.PropTypes.string.isRequired,
  source: React.PropTypes.arrayOf(React.PropTypes.shape({
    value: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.number]),
    title: React.PropTypes.any,
    disabled: React.PropTypes.bool,
  })),
  onChange: React.PropTypes.func,
  value: React.PropTypes.any,
  readOnly: React.PropTypes.bool,
  disabled: React.PropTypes.bool,
};

CheckboxSetField.defaultProps = {
  // React considers "undefined" as an uncontrolled component.
  extraClass: '',
  className: '',
  value: [],
};

export { CheckboxSetField };

export default fieldHolder(CheckboxSetField);
