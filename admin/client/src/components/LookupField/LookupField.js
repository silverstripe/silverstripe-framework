import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import { FormControl } from 'react-bootstrap-ss';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import i18n from 'i18n';

class LookupField extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.getValueCSV = this.getValueCSV.bind(this);
  }

  /**
   * Gets the array of values possible, converts to CSV string.
   *
   * @returns {string} csv
   */
  getValueCSV() {
    const values = this.props.value;

    if (!Array.isArray(values) &&
      (values || typeof values === 'string' || typeof values === 'number')) {
      const item = this.props.source.find((next) => next.value === values);
      if (item) {
        return item.title;
      }
      return '';
    }

    if (!values || !values.length) {
      return '';
    }
    return values
      .map((value) => {
        const item = this.props.source.find((next) => next.value === value);
        return item && item.title;
      })
      .filter((value) => `${value}`.length)
      .join(', ');
  }

  /**
   * Fetches properties for an the field
   *
   * @returns {object} properties
   */
  getFieldProps() {
    return {
      id: this.props.id,
      name: this.props.name,
      className: `${this.props.className} ${this.props.extraClass}`,
    };
  }

  render() {
    if (!this.props.source) {
      return null;
    }
    const none = `('${i18n._t('FormField.NONE', 'None')}')`;

    return (
      <FormControl.Static {...this.getFieldProps()}>
        { this.getValueCSV() || none }
      </FormControl.Static>
    );
  }
}

LookupField.propTypes = {
  extraClass: React.PropTypes.string,
  id: React.PropTypes.string,
  name: React.PropTypes.string.isRequired,
  source: React.PropTypes.arrayOf(React.PropTypes.shape({
    value: React.PropTypes.oneOfType([React.PropTypes.string, React.PropTypes.number]),
    title: React.PropTypes.any,
    disabled: React.PropTypes.bool,
  })),
  value: React.PropTypes.any,
};

LookupField.defaultProps = {
  // React considers "undefined" as an uncontrolled component.
  extraClass: '',
  className: '',
  value: [],
};

export { LookupField };

export default fieldHolder(LookupField);

